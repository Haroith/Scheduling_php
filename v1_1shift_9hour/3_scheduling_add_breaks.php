<?
// Здесь нам надо будет перейти от массива с количеством фантомов с разбивкой по сменам
// к массиву с каждым отдельным фантомом
// Теперь нам понадобится большая фактическая матрица, где каждый большой элемент - 1 смена с добавленными перерывами

include_once('2_scheduling_balance_shifts.php');
print_r('<br><br>Третий шаг<br>');
print_r('Добавляем перерывы<br>');

$countAllPhantomShifts = 0;
$phantomsShifts = [];
for($i = 0; $i < $dimension; $i++){
    $countPhantomShifts = $phantoms[$i];
    for($k = 0; $k < $countPhantomShifts; $k++){
        $phantomsShifts[] = $shifts[$i];
        $countAllPhantomShifts++;
    }
}
print_r('phantomsShifts:<br>');
// Вывод для проверки
for ($i = 0; $i < $countAllPhantomShifts; $i++){
    for ($j = 0; $j < $dayQuarters; $j++) {
        print_r($phantomsShifts[$i][$j]);
        print_r('  ');
    }
    print_r('<br>');
}
print_r('<br>');
print_r('<br>');

// Ещё раз напоминание
// Если в $difference значение > 0, то это недостаффинг, FTE не хватает
// Если в $difference значение < 0, то это перестаффинг, FTE больше нужного
print_r('difference:<br>');
for ($j = 0; $j < $dayQuarters; $j++) {
    if(strlen($difference[$j]) == 1){
        print_r($difference[$j]);
        print_r('  <br>');
        //print_r('  ');
    }
    if(strlen($difference[$j]) == 2){
        print_r($difference[$j]);
        print_r(' <br>');
        //print_r(' ');
    }
}
print_r('<br>');
print_r('<br>');

// Возьмём из БД и преобразуем в удобный для нас вид ограничения перерывов
// Для 4 часов перерывов нет, для 6 часов 1 перерыв, для 9 часов 3 перерыва
// Рассмотрим случай 3 перерывов, но заложим гибкую структуру
// В отличие от смен перерывы могут быть разной длительности, что тоже надо заложить

// Расписание составляется с дискретным шагом по 15 минут
// Длительность 15 минут = 1, 30 минут = 2, 1 час = 4 раза по 15 минут = 4

// В БД начало перерывов записано "от начала смены",
// значит позже надо будет пересчитывать через массив $startFillingPoints

// Начальные точки перерывов взяты из настроек смены "448 Орел 1,0",
// т.к. у смены "448_1.0" периоды перерывов пересекаются, а первый перерыв прописан "в любое время"
// Нужно будет в редакторе привязки перерыв к сменам сделать проверяльщик на пересечения
// Либо ставить ограничения пользователю, либо делать гораздо более сложный алгоритм подбора, который бы "догадывался" сам

// Первый уровень - порядковый номер перерыва
// Второй уровень - длительность перерыва в 15ти минутках
// Третий уровень - моменты начала
// Нужно манипулировать с моментами начала, они должны быть значениями, а не ключами

// Порядковый номер перерыва в прототипе будет не равен 0 и не равен 1, чтобы отличать от no-activity и от основной активности
// Все перерывы уникальны, нельзя сказать "такой тип перерыва 2 раза за смену"
// Нужно создать формально 2 разных перерыва
$startBreakPoints = [
    2 => [
        1 => [
            //1:30 = 1*4+2 = 4+2 = 6
            6,
            7,
            8,
            9,
            //2:30 = 2*4+2 = 8+2 = 10
            10,
        ],
    ],
    3 => [
        2 => [
            //3:15 = 3*4+1 = 12+1 = 13
            13,
            14,
            15,
            16,
            17,
            18,
            19,
            20,
            //5:15 = 5*4+1 = 20+1 = 21
            21,
        ],
    ],
    4 => [
        1 => [
            //6:30 = 6*4+2 = 24+2 = 26
            26,
            27,
            28,
            29,
            //7:15 = 7*4+2 = 28+2 = 30
            30,
        ],
    ],
];

// Перерывы надо раздать в любом случае всем фантомам,
// поэтому большим циклом пойдём по $phantomsShifts, внутри будем сверяться с $difference
for ($i = 0; $i < $countAllPhantomShifts; $i++){
//$i=0;
    $startPhantomShiftPoint = 0;
    for ($j = 0; $j < $dayQuarters; $j++) {
        // Начальную точку конкретной смены нельзя взять из массива $startFillingPoints,
        // т.к. есть несколько изначально одинаковых одинаковых фантомов
        // Будем ориентироваться на позицию первой единицы
        if($phantomsShifts[$i][$j] == 1){
            $startPhantomShiftPoint = $j;
            break;
        }
    }

    // Теперь можно из этой точки $startPhantomShiftPoint и массива относительных начал $startBreakPoints получить
    // начала перерывов относительно дня и начать сверяться с $difference
    $dayStartBreakPoints = $startBreakPoints;
    // Изначально неизвестно, сколько перерывов и сколько внутри точек старта, массив не одномерный
    // поэтому придётся искусственно переопределять значения через foreach
    // Передача по ссылке тоже корректно не работает =(
    foreach ($dayStartBreakPoints AS $breakNumber => $break){
        foreach ($break AS $breakLength => $breakStarts){
            foreach ($breakStarts AS $breakStartsKeys => $startPoints){
                $dayStartBreakPoints[$breakNumber][$breakLength][$breakStartsKeys] += $startPhantomShiftPoint;
            }
        }
    }

    // Распределим перерывы так же, как распределяли смены - наслоением
    // но при этом сверяемся с difference
    // Идти надо по перерывам, т.к. каждый надо распределить по 1 разу
    // Придётся применять чудовищную конструкцию выше
    foreach ($dayStartBreakPoints AS $breakNumber => $break){
        foreach ($break AS $breakLength => $breakStarts){
            // $breakLength - это длительность перерыва
            // $breakStarts - это массив точек начала

            $maxOverStaffing = $difference[$breakStarts[0]];
            $maxOverStaffingPoint = $breakStarts[0];
            foreach ($breakStarts AS $breakStartsKeys => $startPoint){
                // При помощи массива $breakStarts нужно пройти по $difference
                // и выбрать период размера $breakLength с самым большим перестаффингом
                // Сделаем проще - будем проверять только точку начала

                // Снова напоминание, что < 0 - это лишние FTE
                if ($difference[$startPoint] < $maxOverStaffing){
                    $maxOverStaffing = $difference[$startPoint];
                    $maxOverStaffingPoint = $startPoint;
                }

            }
            // Получили точку с оверстаффингом
            // Теперь надо сделать столько пометок, какова длительность перерыва
            // Вместе с этим действием пересчитать difference, чтобы было с чем сравнивать
            for($k=0; $k<$breakLength; $k++){
                $phantomsShifts[$i][$maxOverStaffingPoint+$k] = $breakNumber;
                $difference[$maxOverStaffingPoint+$k] += 1;
            }
        }
    }
}

//Проверим, что у нас получилось
for ($i = 0; $i < $countAllPhantomShifts; $i++){
    for ($j = 0; $j < $dayQuarters; $j++) {
        print_r($phantomsShifts[$i][$j]);
        print_r('  ');
    }
    print_r('<br>');
}
print_r('<br>');
print_r('<br>');

print_r('difference:<br>');
for ($j = 0; $j < $dayQuarters; $j++) {
    if(strlen($difference[$j]) == 1){
        print_r($difference[$j]);
        print_r('  <br>');
        //print_r('  ');
    }
    if(strlen($difference[$j]) == 2){
        print_r($difference[$j]);
        print_r(' <br>');
        //print_r(' ');
    }
}

// Вычислим получившийся SL
// Если SL в норме, то ничего не делаем
// Если SL не в норме, то нужно ещё раз балансировать или дополнять алгоритм выше

// Вычисляем средневзвешенный по количеству прогнозируемых звонков SL
// Чистый SL на каждом промежутке
$shiftsSL = [];
for ($j = 0; $j < $dayQuarters; $j++) {
    $shiftsSL[$j] = ErlangSL($forecast[$j], ($agentsNeeded[$j]-$difference[$j]), $ahtSeconds[$j]);
}
// "взвешиваем" SL по прогнозу количества звонков
$sumSLWeighted = 0;
$sumForecast = 0;
for ($j = 0; $j < $dayQuarters; $j++) {
    $sumSLWeighted = $sumSLWeighted + $forecast[$j] * $shiftsSL[$j];
    $sumForecast = $sumForecast + $forecast[$j];
}
$averageWeightedSL = $sumSLWeighted / $sumForecast;
// Приводим к отображению в виде процентов
$averageWeightedSL = round($averageWeightedSL * 100,2,PHP_ROUND_HALF_UP) . '%';

print_r('<br>');
print_r('<br>Получившийся SL после третьего шага = ');
print_r($averageWeightedSL);
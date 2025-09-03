<?
// Здесь нам надо будет перейти от массива с количеством фантомов с разбивкой по сменам
// Here we need to transform the array with the number of phantoms by shift
// к массиву с каждым отдельным фантомом
// into an array with each individual phantom
// Теперь нам понадобится большая фактическая матрица, где каждый большой элемент - 1 смена с добавленными перерывами
// Now we need a large actual matrix, where each major element = 1 shift with added breaks

include_once('2_scheduling_balance_shifts.php');
//print_r('<br><br>Третий шаг<br>');
print_r('<br><br>Step three<br>');
//print_r('Добавляем перерывы<br>');
print_r('Add breaks<br>');

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
// Output for checking
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
// Reminder again
// Если в $difference значение > 0, то это недостаффинг, FTE не хватает
// If $difference > 0, it means understaffing, FTE not enough
// Если в $difference значение < 0, то это перестаффинг, FTE больше нужного
// If $difference < 0, it means overstaffing, FTE more than required
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
// Take break constraints from the DB and convert into a convenient format
// Для 4 часов перерывов нет, для 6 часов 1 перерыв, для 9 часов 3 перерыва
// For 4 hours – no breaks, for 6 hours – 1 break, for 9 hours – 3 breaks
// Рассмотрим случай 3 перерывов, но заложим гибкую структуру
// Consider the case of 3 breaks, but allow a flexible structure
// В отличие от смен перерывы могут быть разной длительности, что тоже надо заложить
// Unlike shifts, breaks can have different durations, which must also be accounted for

// Расписание составляется с дискретным шагом по 15 минут
// The schedule is built with 15-minute intervals
// Длительность 15 минут = 1, 30 минут = 2, 1 час = 4 раза по 15 минут = 4
// Duration: 15 minutes = 1, 30 minutes = 2, 1 hour = 4×15 minutes = 4

// В БД начало перерывов записано "от начала смены",
// In the DB, break starts are recorded “from the beginning of the shift”,
// значит позже надо будет пересчитывать через массив $startFillingPoints
// so later we need to recalculate them using the $startFillingPoints array

// Начальные точки перерывов взяты из настроек смены,
// Initial break points are taken from shift settings
// т.к. у смены "448_1.0" периоды перерывов пересекаются, а первый перерыв прописан "в любое время"
// because in the shift the break periods overlap, and the first break is set as “anytime”
// Нужно будет в редакторе привязки перерыв к сменам сделать проверяльщик на пересечения
// In the break-to-shift binding editor we need a validator for overlaps
// Либо ставить ограничения пользователю, либо делать гораздо более сложный алгоритм подбора, который бы "догадывался" сам
// Either put limits for the user, or make a much more complex algorithm that “figures it out” automatically

// Первый уровень - порядковый номер перерыва
// First level – break sequence number
// Второй уровень - длительность перерыва в 15ти минутках
// Second level – break duration in 15-minute intervals
// Третий уровень - моменты начала
// Third level – start times
// Нужно манипулировать с моментами начала, они должны быть значениями, а не ключами
// We need to manipulate the start times, they must be values, not keys

// Порядковый номер перерыва в прототипе будет не равен 0 и не равен 1, чтобы отличать от no-activity и от основной активности
// In the prototype, the break sequence number will not be 0 and not 1,
// to distinguish from no-activity and main activity
// Все перерывы уникальны, нельзя сказать "такой тип перерыва 2 раза за смену"
// All breaks are unique, you cannot say “this type of break 2 times per shift”
// Нужно создать формально 2 разных перерыва
// We need to formally create 2 separate breaks
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
// Breaks must be assigned to all phantoms anyway,
// поэтому большим циклом пойдём по $phantomsShifts, внутри будем сверяться с $difference
// so we loop through $phantomsShifts, checking against $difference inside
for ($i = 0; $i < $countAllPhantomShifts; $i++){
//$i=0;
    $startPhantomShiftPoint = 0;
    for ($j = 0; $j < $dayQuarters; $j++) {
        // Начальную точку конкретной смены нельзя взять из массива $startFillingPoints,
        // The starting point of a specific shift cannot be taken from $startFillingPoints,
        // т.к. есть несколько изначально одинаковых одинаковых фантомов
        // because there may be several initially identical phantoms
        // Будем ориентироваться на позицию первой единицы
        // We will use the position of the first “1” as a reference
        if($phantomsShifts[$i][$j] == 1){
            $startPhantomShiftPoint = $j;
            break;
        }
    }

    // Теперь можно из этой точки $startPhantomShiftPoint и массива относительных начал $startBreakPoints получить
    // Now, from this $startPhantomShiftPoint and the relative start array $startBreakPoints
    // начала перерывов относительно дня и начать сверяться с $difference
    // we get the absolute break starts relative to the day and start checking against $difference
    $dayStartBreakPoints = $startBreakPoints;
    // Изначально неизвестно, сколько перерывов и сколько внутри точек старта, массив не одномерный
    // Initially, the number of breaks and the number of start points is unknown, array is not one-dimensional
    // поэтому придётся искусственно переопределять значения через foreach
    // so we must artificially reassign values via foreach
    // Передача по ссылке тоже корректно не работает =(
    // Passing by reference does not work correctly either =(
    foreach ($dayStartBreakPoints AS $breakNumber => $break){
        foreach ($break AS $breakLength => $breakStarts){
            foreach ($breakStarts AS $breakStartsKeys => $startPoints){
                $dayStartBreakPoints[$breakNumber][$breakLength][$breakStartsKeys] += $startPhantomShiftPoint;
            }
        }
    }

    // Распределим перерывы так же, как распределяли смены - наслоением
    // We will distribute breaks the same way as we distributed shifts – layering
    // но при этом сверяемся с difference
    // but at the same time check against $difference
    // Идти надо по перерывам, т.к. каждый надо распределить по 1 разу
    // We must go through the breaks, since each break must be assigned exactly once
    // Придётся применять чудовищную конструкцию выше
    // That’s why we need the heavy construction above
    foreach ($dayStartBreakPoints AS $breakNumber => $break){
        foreach ($break AS $breakLength => $breakStarts){
            // $breakLength - это длительность перерыва
            // $breakStarts - это массив точек начала

            $maxOverStaffing = $difference[$breakStarts[0]];
            $maxOverStaffingPoint = $breakStarts[0];
            foreach ($breakStarts AS $breakStartsKeys => $startPoint){
                // При помощи массива $breakStarts нужно пройти по $difference
                // Using the $breakStarts array we go through $difference
                // и выбрать период размера $breakLength с самым большим перестаффингом
                // and select a period of size $breakLength with the biggest overstaffing
                // Сделаем проще - будем проверять только точку начала
                // For simplicity – check only the starting point

                // Снова напоминание, что < 0 - это лишние FTE
                // Reminder again: < 0 means extra FTE
                if ($difference[$startPoint] < $maxOverStaffing){
                    $maxOverStaffing = $difference[$startPoint];
                    $maxOverStaffingPoint = $startPoint;
                }

            }
            // Получили точку с оверстаффингом
            // Got the point with overstaffing
            // Теперь надо сделать столько пометок, какова длительность перерыва
            // Now we must mark it for as many intervals as the break length
            // Вместе с этим действием пересчитать difference, чтобы было с чем сравнивать
            // At the same time recalculate $difference to compare further
            for($k=0; $k<$breakLength; $k++){
                $phantomsShifts[$i][$maxOverStaffingPoint+$k] = $breakNumber;
                $difference[$maxOverStaffingPoint+$k] += 1;
            }
        }
    }
}

//Проверим, что у нас получилось
// Check what we got
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
// Calculate the resulting SL
// Если SL в норме, то ничего не делаем
// If SL is within norm, do nothing
// Если SL не в норме, то нужно ещё раз балансировать или дополнять алгоритм выше
// If SL is not within norm, we need to balance again or extend the algorithm above

// Вычисляем средневзвешенный по количеству прогнозируемых звонков SL
// Calculate the weighted average SL by the number of forecasted calls
// Чистый SL на каждом промежутке
// The pure SL at each interval
$shiftsSL = [];
for ($j = 0; $j < $dayQuarters; $j++) {
    $shiftsSL[$j] = ErlangSL($forecast[$j], ($agentsNeeded[$j]-$difference[$j]), $ahtSeconds[$j]);
}
// "взвешиваем" SL по прогнозу количества звонков
// “Weight” SL by call forecast
$sumSLWeighted = 0;
$sumForecast = 0;
for ($j = 0; $j < $dayQuarters; $j++) {
    $sumSLWeighted = $sumSLWeighted + $forecast[$j] * $shiftsSL[$j];
    $sumForecast = $sumForecast + $forecast[$j];
}
$averageWeightedSL = $sumSLWeighted / $sumForecast;
// Приводим к отображению в виде процентов
// Convert to percentage for display
$averageWeightedSL = round($averageWeightedSL * 100,2,PHP_ROUND_HALF_UP) . '%';

print_r('<br>');
//print_r('<br>Получившийся SL после третьего шага = ');
print_r('<br>Resulting SL after step three = ');
print_r($averageWeightedSL);
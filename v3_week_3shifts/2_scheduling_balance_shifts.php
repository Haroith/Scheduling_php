<?
// Let's try to balance the initial schedule relative to the middle of the day
// We assume that for 80-90% of cases, the call and FTE forecast forms a single-peak shape
// When layering shifts, it results in:
// - under-staffing in the first half of the day
// - over-staffing in the second half of the day

// We'll look for points (15-minute intervals) where
// the difference between forecasted FTE (required FTE) and the current schedule is maximal
// At these points, we'll add/increase shifts depending on under/over-staffing
// We'll add/increase shifts not until zero difference, but until +/-1
// We'll also need to leave space for breaks in step three

print_r('First step<br>');
print_r('Selecting the initial schedule point');
include_once('1_scheduling_generate_begin_point.php');
print_r('<br><br>Second step<br>');
print_r('Balancing under-staffing and over-staffing');
/*
// Ищем точку в difference с наибольшим модулем
$maxAbsoluteDifferenceKey = 0;
$maxAbsoluteDifference = 0;
for ($j=0; $j<$dayQuarters; $j++){
    if(abs($difference[$j]) >= $maxAbsoluteDifference){
        $maxAbsoluteDifference = abs($difference[$j]);
        $maxAbsoluteDifferenceKey = $j;
    }
}
// Если вдруг расписание идеальное, то maxAbsoluteDifference будет равно нулю
// Случай по факту недостижимый
// Если расписание не идеальное, то мы получим координату самого большого различия
// Возьмём эту разницу в отдельную переменную вместе со знаком +/-

// ВАЖНО! Если значение в $difference > 0, то это недостаффинг - здесь нужно добавлять смены
// ВАЖНО! Если значение в $difference < 0, то это перестаффинг - здесь нужно уменьшать смены
$maxDifference = $difference[$maxAbsoluteDifferenceKey];

print_r('<br>');
print_r($maxAbsoluteDifferenceKey);
print_r('<br>');
print_r($maxDifference);
print_r('<br>');
print_r('<br>');

// Найдём один раз середины смен
// Понадобится чуть ниже в цикле
$halfPointShifts = [];
for($i = 0; $i < $dimension; $i++){
    $halfPointShifts[$i] = round(($startFillingPoints[$i] + $shiftQuarters) / 2, 0, PHP_ROUND_HALF_UP);
    //на всякий заложим округление
    print_r($halfPointShifts[$i].'<br>');
}
print_r('<br>');

// Будем искать "идеал" не более N раз
// Будем искать не совсем идеал, чтобы был запас для перерывов
// TODO по идее, нужно оставлять перестаффинг, но не недостаффинг, но пока обойдёмся так
$iterationCount = 0;
while($maxAbsoluteDifference > 1 && $iterationCount < 50){
    // Найдём смену, для которой найденная точка перестаффинга/недостаффинга - середина
    // Если не середина, то наиболее близко к середине
    // И одновременно смена должна быть релевентной перестаффинга/недостаффингу
    // Если найден перестаффинг, то надо брать ближайшую смену, которых больше нуля

    // Эту смену будем прибавлять/убавлять
    // Это действие не должно усугубить ситуацию с перестаффингом/недостаффингом!
    // Для этого будем уменьшать/увеличивать на 1, затем снова искать максимальную разницу и т.д.

    $minDistant = $dayQuarters;
    $numberOfVariablingShift = 0;
    for($i = 0; $i < $dimension; $i++){
        if ($maxDifference < 0){ // если перестаффинг
            // тогда надо искать смены, которых больше 0
            if (
                abs($halfPointShifts[$i]-$maxAbsoluteDifferenceKey) < $minDistant
                &&
                $phantoms[$i] > 0
            ) {
                $minDistant = abs($halfPointShifts[$i]-$maxAbsoluteDifferenceKey);
                $numberOfVariablingShift = $i;
            }
        } elseif ($maxDifference > 0) { // если недостаффинг
            // тогда подойдут любые смены
            if (
                abs($halfPointShifts[$i]-$maxAbsoluteDifferenceKey) < $minDistant
            ) {
                $minDistant = abs($halfPointShifts[$i]-$maxAbsoluteDifferenceKey);
                $numberOfVariablingShift = $i;
            }
        } else {
            break;
        }
    }

    // Нашли ту смену, которую надо сдвигать
    // Теперь сдвинем на 1
    // Нельзя сдвигать количество смен в минус!

    // Если перестаффинг
    if($maxDifference < 0 && $phantoms[$numberOfVariablingShift] > 0){
        $phantoms[$numberOfVariablingShift] -= 1;
        // Пересчитаем массив с разницами прогноз/расписание $difference
        for ($j=$startFillingPoints[$numberOfVariablingShift]; $j<($startFillingPoints[$numberOfVariablingShift]+$shiftQuarters); $j++){
            $difference[$j] += 1;
        }
    // Если недостаффинг
    } elseif ($maxDifference > 0) {
        $phantoms[$numberOfVariablingShift] += 1;
        // Пересчитаем массив с разницами прогноз/расписание $difference
        for ($j=$startFillingPoints[$numberOfVariablingShift]; $j<($startFillingPoints[$numberOfVariablingShift]+$shiftQuarters); $j++){
            $difference[$j] -= 1;
        }
    } else {
        break;
    }

    // Вновь ищем новую разницу перед новой итерацией!
    $maxAbsoluteDifferenceKey = 0;
    $maxAbsoluteDifference = 0;
    for ($j=0; $j<$dayQuarters; $j++){
        if(abs($difference[$j]) > $maxAbsoluteDifference){
            $maxAbsoluteDifference = abs($difference[$j]);
            $maxAbsoluteDifferenceKey = $j;
        }
    }
    $maxDifference = $difference[$maxAbsoluteDifferenceKey];

    $iterationCount++;
}

// Посмотрим, что в итоге получилось
for ($j=0; $j<$dayQuarters; $j++){
    print_r($difference[$j].' ');
}
print_r('<br>phantoms после балансировки:');
print_r('<br>');
var_dump($phantoms);


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
print_r('<br>Получившийся SL после второго шага = ');
print_r($averageWeightedSL);
*/
<?
// Попытаемся сбалансировать начальное состояние расписания относительно середины дня
// Let’s try to balance the initial state of the schedule relative to the middle of the day
// Считаем, что для 80-90% случаев прогноз звонков и FTE - это горка с одной условной вершиной
// We assume that in 80–90% of cases, the call forecast and FTE form a “hill” with a single conditional peak
// При подборе смен наслоением получается:
// When layering shifts, we usually get:
// - в первой половине дня недостаффинг
// - understaffing in the first half of the day
// - во второй половине дня перестаффинг
// - overstaffing in the second half of the day

// Будем искать точки (15ти минутные), где
// We will look for points (15-minute intervals) where
// разница между прогнозом FTE (требуемыми FTE) и составленным расписанием максимальна
// the difference between forecasted FTE (required FTE) and the generated schedule is maximal
// В этих точках будем добавлять/прибавлять смены в зависимости от недо/пере стаффинга
// At these points we will add/remove shifts depending on under/overstaffing
// Добавлять и прибавлять смены будем не до нулевой разницы, а до +/- 1 (один)
// We won’t adjust shifts to reach exactly zero difference, but only to within +/- 1
// Нам ещё нужно будет на третьем шаге разместить перерывы, для них надо оставить место
// We also need to leave space for breaks, which we will assign in step three

//print_r('Первый шаг<br>');
print_r('Step one<br>');
//print_r('Подбираем изначальную точку расписания');
print_r('Searching for the initial starting point for the schedule');
include_once('1_scheduling_generate_begin_point.php');
//print_r('<br><br>Второй шаг<br>');
print_r('<br><br>Step two<br>');
//print_r('Балансируем недостаффинг и перестаффинг');
print_r('Balance understaffing and overstaffing');

// Ищем точку в difference с наибольшим модулем
// Find the point in the difference array with the largest absolute value
$maxAbsoluteDifferenceKey = 0;
$maxAbsoluteDifference = 0;
for ($j=0; $j<$dayQuarters; $j++){
    if(abs($difference[$j]) >= $maxAbsoluteDifference){
        $maxAbsoluteDifference = abs($difference[$j]);
        $maxAbsoluteDifferenceKey = $j;
    }
}
// Если вдруг расписание идеальное, то maxAbsoluteDifference будет равно нулю
// If the schedule is suddenly perfect, then maxAbsoluteDifference will be zero
// Случай по факту недостижимый
// In practice, this case is impossible
// Если расписание не идеальное, то мы получим координату самого большого различия
// If the schedule is not perfect, we will get the coordinate of the largest difference
// Возьмём эту разницу в отдельную переменную вместе со знаком +/-
// We will take this difference into a separate variable along with its sign (+/-)

// ВАЖНО! Если значение в $difference > 0, то это недостаффинг - здесь нужно добавлять смены
// IMPORTANT! If $difference > 0, then it’s understaffing – we need to add shifts here
// ВАЖНО! Если значение в $difference < 0, то это перестаффинг - здесь нужно уменьшать смены
// IMPORTANT! If $difference < 0, then it’s overstaffing – we need to reduce shifts
$maxDifference = $difference[$maxAbsoluteDifferenceKey];

print_r('<br>');
print_r($maxAbsoluteDifferenceKey);
print_r('<br>');
print_r($maxDifference);
print_r('<br>');
print_r('<br>');

// Найдём один раз середины смен
// Find the midpoints of the shifts once
// Понадобится чуть ниже в цикле
// Will be needed a bit later in the loop
$halfPointShifts = [];
for($i = 0; $i < $dimension; $i++){
    $halfPointShifts[$i] = round(($startFillingPoints[$i] + $shiftQuarters) / 2, 0, PHP_ROUND_HALF_UP);
    //на всякий заложим округление
    // Apply rounding just in case
    print_r($halfPointShifts[$i].'<br>');
}
print_r('<br>');

// Будем искать "идеал" не более N раз
// We will search for the “ideal” no more than N times
// Будем искать не совсем идеал, чтобы был запас для перерывов
// And not a complete ideal, to leave a buffer for breaks
// TODO по идее, нужно оставлять перестаффинг, но не недостаффинг, но пока обойдёмся так
// TODO Ideally, we should leave overstaffing but not understaffing – but for now, we’ll keep it this way
$iterationCount = 0;
while($maxAbsoluteDifference > 1 && $iterationCount < 50){
    // Найдём смену, для которой найденная точка перестаффинга/недостаффинга - середина
    // Find the shift whose midpoint is the detected point of under/overstaffing
    // Если не середина, то наиболее близко к середине
    // If not exactly the midpoint, then the closest to it
    // И одновременно смена должна быть релевентной перестаффинга/недостаффингу
    // At the same time, the shift must be relevant to the under/overstaffing
    // Если найден перестаффинг, то надо брать ближайшую смену, которых больше нуля
    // If it’s overstaffing, we must pick the closest shift with a count greater than zero

    // Эту смену будем прибавлять/убавлять
    // We will then add/remove this shift
    // Это действие не должно усугубить ситуацию с перестаффингом/недостаффингом!
    // This action must not worsen the situation with under/overstaffing!
    // Для этого будем уменьшать/увеличивать на 1, затем снова искать максимальную разницу и т.д.
    // Therefore, we will adjust by 1, then re-check the maximum difference, and so on

    $minDistant = $dayQuarters;
    $numberOfVariablingShift = 0;
    for($i = 0; $i < $dimension; $i++){
        if ($maxDifference < 0){ // если перестаффинг
            // If overstaffing
            // тогда надо искать смены, которых больше 0
            // then we need to search shifts that are more than 0
            if (
                abs($halfPointShifts[$i]-$maxAbsoluteDifferenceKey) < $minDistant
                &&
                $phantoms[$i] > 0
            ) {
                $minDistant = abs($halfPointShifts[$i]-$maxAbsoluteDifferenceKey);
                $numberOfVariablingShift = $i;
            }
        } elseif ($maxDifference > 0) { // если недостаффинг
            // If understaffing
            // тогда подойдут любые смены
            // Then any shifts will be good
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
    // Found the shift we need to adjust
    // Теперь сдвинем на 1
    // Now shift it by 1
    // Нельзя сдвигать количество смен в минус!
    // The number of shifts cannot go below zero!

    // Если перестаффинг
    // If overstaffing
    if($maxDifference < 0 && $phantoms[$numberOfVariablingShift] > 0){
        $phantoms[$numberOfVariablingShift] -= 1;
        // Пересчитаем массив с разницами прогноз/расписание $difference
        // Recalculate the difference array forecast vs schedule $difference
        for ($j=$startFillingPoints[$numberOfVariablingShift]; $j<($startFillingPoints[$numberOfVariablingShift]+$shiftQuarters); $j++){
            $difference[$j] += 1;
        }
        // Если недостаффинг
        // If understaffing
    } elseif ($maxDifference > 0) {
        $phantoms[$numberOfVariablingShift] += 1;
        // Пересчитаем массив с разницами прогноз/расписание $difference
        // Recalculate the difference array forecast vs schedule $difference
        for ($j=$startFillingPoints[$numberOfVariablingShift]; $j<($startFillingPoints[$numberOfVariablingShift]+$shiftQuarters); $j++){
            $difference[$j] -= 1;
        }
    } else {
        break;
    }

    // Вновь ищем новую разницу перед новой итерацией!
    // Again, find the new maximum difference before the next iteration!
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
// Check what we ended up with
for ($j=0; $j<$dayQuarters; $j++){
    print_r($difference[$j].' ');
}
//print_r('<br>phantoms после балансировки:');
print_r('<br>phantoms after balancing:');
print_r('<br>');
var_dump($phantoms);


// Вычисляем средневзвешенный по количеству прогнозируемых звонков SL
// Calculate the weighted average SL by the number of forecasted calls
// Чистый SL на каждом промежутке
// The pure SL at each interval
$shiftsSL = [];
for ($j = 0; $j < $dayQuarters; $j++) {
    $shiftsSL[$j] = ErlangSL($forecast[$j], ($agentsNeeded[$j]-$difference[$j]), $ahtSeconds[$j]);
}
// "взвешиваем" SL по прогнозу количества звонков
// “Weight” the SL by the call forecast
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
//print_r('<br>Получившийся SL после второго шага = ');
print_r('<br>Resulting SL after step two = ');
print_r($averageWeightedSL);
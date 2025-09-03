<?
// Let’s try to balance the initial state of the schedule relative to the middle of the day
// We assume that in 80–90% of cases, the call forecast and FTE form a “hill” with a single conditional peak
// When layering shifts, we usually get:
// - understaffing in the first half of the day
// - overstaffing in the second half of the day

// We will look for points (15-minute intervals) where
// the difference between forecasted FTE (required FTE) and the generated schedule is maximal
// At these points we will add/remove shifts depending on under/overstaffing
// We won’t adjust shifts to reach exactly zero difference, but only to within +/- 1
// We also need to leave space for breaks, which we will assign in step three

print_r('Step one<br>');
print_r('Searching for the initial starting point for the schedule');
include_once('1_scheduling_generate_begin_point.php');
print_r('<br><br>Step two<br>');
print_r('Balance understaffing and overstaffing');

// Find the point in the difference array with the largest absolute value
$maxAbsoluteDifferenceKey = 0;
$maxAbsoluteDifference = 0;
for ($j=0; $j<$dayQuarters; $j++){
    if(abs($difference[$j]) >= $maxAbsoluteDifference){
        $maxAbsoluteDifference = abs($difference[$j]);
        $maxAbsoluteDifferenceKey = $j;
    }
}
// If the schedule is suddenly perfect, then maxAbsoluteDifference will be zero
// In practice, this case is impossible
// If the schedule is not perfect, we will get the coordinate of the largest difference
// We will take this difference into a separate variable along with its sign (+/-)

// IMPORTANT! If $difference > 0, then it’s understaffing – we need to add shifts here
// IMPORTANT! If $difference < 0, then it’s overstaffing – we need to reduce shifts
$maxDifference = $difference[$maxAbsoluteDifferenceKey];

print_r('<br>');
print_r($maxAbsoluteDifferenceKey);
print_r('<br>');
print_r($maxDifference);
print_r('<br>');
print_r('<br>');

// Find the midpoints of the shifts once
// Will be needed a bit later in the loop
$halfPointShifts = [];
for($i = 0; $i < $dimension; $i++){
    $halfPointShifts[$i] = round(($startFillingPoints[$i] + $shiftQuarters) / 2, 0, PHP_ROUND_HALF_UP);
    // Apply rounding just in case
    print_r($halfPointShifts[$i].'<br>');
}
print_r('<br>');

// We will search for the “ideal” no more than N times
// And not a complete ideal, to leave a buffer for breaks
// TODO Ideally, we should leave overstaffing but not understaffing – but for now, we’ll keep it this way
$iterationCount = 0;
while($maxAbsoluteDifference > 1 && $iterationCount < 50){
    // Find the shift whose midpoint is the detected point of under/overstaffing
    // If not exactly the midpoint, then the closest to it
    // At the same time, the shift must be relevant to the under/overstaffing
    // If it’s overstaffing, we must pick the closest shift with a count greater than zero

    // We will then add/remove this shift
    // This action must not worsen the situation with under/overstaffing!
    // Therefore, we will adjust by 1, then re-check the maximum difference, and so on

    $minDistant = $dayQuarters;
    $numberOfVariablingShift = 0;
    for($i = 0; $i < $dimension; $i++){
        if ($maxDifference < 0){
            // If overstaffing
            // then we need to search shifts that are more than 0
            if (
                abs($halfPointShifts[$i]-$maxAbsoluteDifferenceKey) < $minDistant
                &&
                $phantoms[$i] > 0
            ) {
                $minDistant = abs($halfPointShifts[$i]-$maxAbsoluteDifferenceKey);
                $numberOfVariablingShift = $i;
            }
        } elseif ($maxDifference > 0) {
            // If understaffing
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

    // Found the shift we need to adjust
    // Now shift it by 1
    // The number of shifts cannot go below zero!

    // If overstaffing
    if($maxDifference < 0 && $phantoms[$numberOfVariablingShift] > 0){
        $phantoms[$numberOfVariablingShift] -= 1;
        // Recalculate the difference array forecast vs schedule $difference
        for ($j=$startFillingPoints[$numberOfVariablingShift]; $j<($startFillingPoints[$numberOfVariablingShift]+$shiftQuarters); $j++){
            $difference[$j] += 1;
        }
        // If understaffing
    } elseif ($maxDifference > 0) {
        $phantoms[$numberOfVariablingShift] += 1;
        // Recalculate the difference array forecast vs schedule $difference
        for ($j=$startFillingPoints[$numberOfVariablingShift]; $j<($startFillingPoints[$numberOfVariablingShift]+$shiftQuarters); $j++){
            $difference[$j] -= 1;
        }
    } else {
        break;
    }

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

// Check what we ended up with
for ($j=0; $j<$dayQuarters; $j++){
    print_r($difference[$j].' ');
}
print_r('<br>phantoms after balancing:');
print_r('<br>');
var_dump($phantoms);


// Calculate the weighted average SL by the number of forecasted calls
// The pure SL at each interval
$shiftsSL = [];
for ($j = 0; $j < $dayQuarters; $j++) {
    $shiftsSL[$j] = ErlangSL($forecast[$j], ($agentsNeeded[$j]-$difference[$j]), $ahtSeconds[$j]);
}
// “Weight” the SL by the call forecast
$sumSLWeighted = 0;
$sumForecast = 0;
for ($j = 0; $j < $dayQuarters; $j++) {
    $sumSLWeighted = $sumSLWeighted + $forecast[$j] * $shiftsSL[$j];
    $sumForecast = $sumForecast + $forecast[$j];
}
$averageWeightedSL = $sumSLWeighted / $sumForecast;
// Convert to percentage for display
$averageWeightedSL = round($averageWeightedSL * 100,2,PHP_ROUND_HALF_UP) . '%';

print_r('<br>');
print_r('<br>Resulting SL after step two = ');
print_r($averageWeightedSL);
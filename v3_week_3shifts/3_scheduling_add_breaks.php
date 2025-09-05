<?
// Here we will need to move from an array with the number of phantoms broken down by shifts
// to an array with each individual phantom
// Now we will need a large actual matrix, where each large element is 1 shift with added breaks

include_once('2_scheduling_balance_shifts.php');
print_r('<br><br>Third step<br>');
print_r('Adding breaks<br>');

$countAllPhantomShifts = [];
for($k=0; $k<$shiftKinds; $k++){
    $countAllPhantomShifts[] = 0;
}
$phantomsShifts = [];
for($j=0; $j<$shiftKinds; $j++) {
    for ($i = 0; $i < $dimension[$j]; $i++) {
        $countPhantomShifts = $phantoms[$j][$i];
        for ($k = 0; $k < $countPhantomShifts; $k++) {
            $phantomsShifts[$j][] = $shifts[$j][$i];
            $countAllPhantomShifts[$j]++;
        }
    }
}
print_r('phantomsShifts:<br>');
// Output for verification
for($k=0; $k<$shiftKinds; $k++) {
    for ($i = 0; $i < $countAllPhantomShifts[$k]; $i++) {
        for ($j = 0; $j < $dayQuarters; $j++) {
            print_r($phantomsShifts[$k][$i][$j]);
            print_r('  ');
        }
        print_r('<br>');
    }
}
print_r('<br>');
print_r('<br>');

// Reminder again
// If the value in $difference > 0, it means under-staffing, FTE is insufficient
// If the value in $difference < 0, it means over-staffing, FTE exceeds the required amount
print_r('difference:<br>');
for ($j = 0; $j < $dayQuarters; $j++) {
    if(strlen($difference[$j]) == 1){
        print_r($difference[$j]);
        //print_r('  <br>');
        print_r('  ');
    }
    if(strlen($difference[$j]) == 2){
        print_r($difference[$j]);
        //print_r(' <br>');
        print_r(' ');
    }
}
print_r('<br>');
print_r('<br>');

// Take break constraints from the database and convert them into a convenient format
// For 4.25-hour shift: 1 break of 15 minutes
// For 6.5-hour shift: 2 breaks of 15 minutes
// For 9-hour shift: 3 breaks
// We are extending the algorithm; now we need to add breaks for three types of shifts
// Unlike shifts, breaks can have different durations, which also needs to be accounted for

// Scheduling is done in discrete steps of 15 minutes
// Duration 15 minutes = 1, 30 minutes = 2, 1 hour = 4 (15-minute units)

// In the database, break start is recorded "from the start of the shift"
// Later we will need to recalculate using the $startFillingPoints array

// Initial break points are taken from shift settings "448 Orël 1.0", "448 Orël 0.75", "448 Orël 0.5"
// Since in shifts "448_1.0", "448_0.75", "448_0.5" break periods overlap or are set to "any time"
// A check for overlap must be added in the break-to-shift editor
// Either set restrictions for the user or implement a much more complex selection algorithm that would "guess" automatically

// "Zero" level – which shift type the breaks belong to
// First level – ordinal number of the break
// Second level – break duration in 15-minute units
// Third level – start moments
// Need to manipulate start moments; they should be values, not keys

// In the prototype, the break ordinal number will not be 0 or 1 to distinguish from no-activity and main activity
// All breaks are unique; you cannot say "this type of break occurs twice in a shift"
// Formally, we need to create 2 different breaks

// Breaks for 9-hour shift
$startBreakPoints[0] = [
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

// Breaks for 6.5-hour shift
$startBreakPoints[1] = [
    5 => [
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
    6 => [
        1 => [
            //3:45 = 3*4+3 = 12+3 = 15
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
];

// Breaks for 4.25-hour shift
$startBreakPoints[2] = [
    7 => [
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
];

// Breaks must be distributed in any case to all phantoms,
// so we will iterate over $phantomsShifts and compare with $difference

for ($w = 0; $w < $shiftKinds; $w++){
    for ($i = 0; $i < $countAllPhantomShifts[$w]; $i++){
        //$i=0;
        $startPhantomShiftPoint = 0;
        for ($j = 0; $j < $dayQuarters; $j++) {
            // The initial point of a specific shift cannot be taken from the $startFillingPoints array,
            // because there are several initially identical phantoms
            // We will base it on the position of the first 1
            if($phantomsShifts[$w][$i][$j] == 1){
                $startPhantomShiftPoint = $j;
                break;
            }
        }

        // Now, from this point $startPhantomShiftPoint and the array of relative start points $startBreakPoints,
        // we can get the break start moments relative to the day and start comparing with $difference
        $dayStartBreakPoints = $startBreakPoints;
        // Initially, it is unknown how many breaks and how many start points inside; the array is not one-dimensional
        // Therefore, we will artificially redefine values using foreach
        // Passing by reference also does not work correctly =(
        foreach ($dayStartBreakPoints[$w] AS $breakNumber => $break){
            foreach ($break AS $breakLength => $breakStarts){
                foreach ($breakStarts AS $breakStartsKeys => $startPoints){
                    $dayStartBreakPoints[$w][$breakNumber][$breakLength][$breakStartsKeys] += $startPhantomShiftPoint;
                }
            }
        }

        // We will distribute breaks the same way we distributed shifts – by layering
        // But we will compare with difference
        // We need to iterate over breaks, as each must be distributed once
        // We will have to use the monstrous construction above
        foreach ($dayStartBreakPoints[$w] AS $breakNumber => $break){
            foreach ($break AS $breakLength => $breakStarts){
                // $breakLength – this is the duration of the break
                // $breakStarts – this is an array of start points

                $maxOverStaffing = $difference[$breakStarts[0]];
                $maxOverStaffingPoint = $breakStarts[0];
                foreach ($breakStarts AS $breakStartsKeys => $startPoint){
                    // Using the $breakStarts array, we need to iterate over $difference
                    // and select a period of size $breakLength with the highest over-staffing
                    // To simplify – we will check only the start point

                    // Reminder: < 0 means excess FTE
                    if ($difference[$startPoint] < $maxOverStaffing){
                        $maxOverStaffing = $difference[$startPoint];
                        $maxOverStaffingPoint = $startPoint;
                    }

                }
                // We get the point with the highest over-staffing
                // Now we need to make as many marks as the duration of the break
                // At the same time, recalculate difference so we have something to compare against
                for($k=0; $k<$breakLength; $k++){
                    $phantomsShifts[$w][$i][$maxOverStaffingPoint+$k] = $breakNumber;
                    $difference[$maxOverStaffingPoint+$k] += 1;
                }
            }
        }
    }
}


// Verify the result
for($k=0; $k<$shiftKinds; $k++){
    for ($i = 0; $i < $countAllPhantomShifts[$k]; $i++){
        for ($j = 0; $j < $dayQuarters; $j++) {
            print_r($phantomsShifts[$k][$i][$j]);
            print_r('  ');
        }
        print_r('<br>');
    }
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

// Compute resulting SL
// If SL is within acceptable limits, do nothing
// If SL is not acceptable, we may need to rebalance or extend the algorithm above

// Compute weighted SL according to the number of forecasted calls
// Pure SL for each interval
$shiftsSL = [];
for ($j = 0; $j < $dayQuarters; $j++) {
    $shiftsSL[$j] = ErlangSL($forecast[$j], ($agentsNeeded[$j]-$difference[$j]), $ahtSeconds[$j]);
}
// Weight SL by forecasted call volume
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
print_r('<br>Resulting SL after the third step = ');
print_r($averageWeightedSL);
<?
// Here we need to transition from the array of phantom counts broken down by shift types
// to an array with each individual phantom
// Now we will need a large actual matrix where each large element represents one shift with added breaks

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
// If the value in $difference > 0, this is understaffing, FTE are insufficient
// If the value in $difference < 0, this is overstaffing, FTE exceed the requirement
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

// Take break restrictions from the database and convert them into a format convenient for us
// For a 4.25-hour shift: 1 break of 15 minutes, for a 6.5-hour shift: 2 breaks of 15 minutes, for a 9-hour shift: 3 breaks
// Extend the algorithm: now breaks need to be added for all three types of shifts
// Unlike shifts, breaks can vary in duration, which must also be accounted for

// Scheduling is done with a discrete step of 15 minutes
// Duration: 15 minutes = 1, 30 minutes = 2, 1 hour = 4 intervals of 15 minutes = 4

// In the database, the start of breaks is recorded "from the start of the shift"
// Later, it will need to be recalculated using the $startFillingPoints array

// Initial break points are taken from shift settings "448 Орел 1,0", "448 Орел 0,75", "448 Орел 0,5"
// Since breaks for shifts "448_1.0", "448_0.75", "448_0.5" overlap or are marked "anytime"
// The break-to-shift binding editor will need a check for overlaps
// Either impose restrictions for the user, or implement a more complex algorithm that can "guess" automatically

// "Zero" level – which type of shift the breaks belong to
// First level – sequential break number
// Second level – break duration in 15-minute intervals
// Third level – start moments
// We need to manipulate start moments; they should be values, not keys

// In the prototype, the sequential break number will not equal 0 or 1, to distinguish it from no-activity or main activity
// All breaks are unique; you cannot say "this break type occurs twice per shift"
// Formally, two separate breaks must be created

// Breaks for the 9-hour shift
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

// Breaks for the 6.5-hour shift
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

// Breaks for the 4.25-hour shift
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

// Breaks must be assigned to all phantoms in any case
// So we iterate through $phantomsShifts and check against $difference

for ($w = 0; $w < $shiftKinds; $w++){
    for ($i = 0; $i < $countAllPhantomShifts[$w]; $i++){
        //$i=0;
        $startPhantomShiftPoint = 0;
        for ($j = 0; $j < $dayQuarters; $j++) {
            // The start point of a specific shift cannot be taken from $startFillingPoints
            // Because there are several initially identical phantoms
            // We'll use the position of the first "1" as reference
            if($phantomsShifts[$w][$i][$j] == 1){
                $startPhantomShiftPoint = $j;
                break;
            }
        }

        // From $startPhantomShiftPoint and the relative starts array $startBreakPoints
        // we can get break starts relative to the day and compare with $difference
        $dayStartBreakPoints = $startBreakPoints;
        // Initially unknown how many breaks and how many start points inside; the array is not 1D
        // So we need to artificially redefine values using foreach
        // Passing by reference does not work properly =(
        foreach ($dayStartBreakPoints[$w] AS $breakNumber => $break){
            foreach ($break AS $breakLength => $breakStarts){
                foreach ($breakStarts AS $breakStartsKeys => $startPoints){
                    $dayStartBreakPoints[$w][$breakNumber][$breakLength][$breakStartsKeys] += $startPhantomShiftPoint;
                }
            }
        }

        // Distribute breaks the same way as shifts – layered
        // But check against difference
        // Iterate through breaks, as each needs to be assigned once
        // We'll have to use the monstrous construction above
        foreach ($dayStartBreakPoints[$w] AS $breakNumber => $break){
            foreach ($break AS $breakLength => $breakStarts){
                // $breakLength – break duration
                // $breakStarts – array of start points

                $maxOverStaffing = $difference[$breakStarts[0]];
                $maxOverStaffingPoint = $breakStarts[0];
                foreach ($breakStarts AS $breakStartsKeys => $startPoint){
                    // Using $breakStarts array, check $difference
                    // Choose the period of size $breakLength with the greatest overstaffing
                    // Simplify: check only the start point

                    // Reminder: < 0 means extra FTE
                    if ($difference[$startPoint] < $maxOverStaffing){
                        $maxOverStaffing = $difference[$startPoint];
                        $maxOverStaffingPoint = $startPoint;
                    }

                }
                // We obtained the point with overstaffing
                // Now we need to mark as many intervals as the break duration
                // At the same time, update $difference so it can be used for comparison
                for($k=0; $k<$breakLength; $k++){
                    $phantomsShifts[$w][$i][$maxOverStaffingPoint+$k] = $breakNumber;
                    $difference[$maxOverStaffingPoint+$k] += 1;
                }
            }
        }
    }
}


// Check the resulting matrix
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

// Calculate resulting SL
// If SL is acceptable, do nothing
// If SL is not acceptable, need to rebalance or extend the algorithm above

// Calculate weighted SL by the forecasted number of calls
// Pure SL at each interval
$shiftsSL = [];
for ($j = 0; $j < $dayQuarters; $j++) {
    $shiftsSL[$j] = ErlangSL($forecast[$j], ($agentsNeeded[$j]-$difference[$j]), $ahtSeconds[$j]);
}
// "Weight" SL by call forecast
$sumSLWeighted = 0;
$sumForecast = 0;
for ($j = 0; $j < $dayQuarters; $j++) {
    $sumSLWeighted = $sumSLWeighted + $forecast[$j] * $shiftsSL[$j];
    $sumForecast = $sumForecast + $forecast[$j];
}
$averageWeightedSL = $sumSLWeighted / $sumForecast;
// Convert to display as a percentage
$averageWeightedSL = round($averageWeightedSL * 100,2,PHP_ROUND_HALF_UP) . '%';

print_r('<br>');
print_r('<br>Resulting SL after the third step = ');
print_r($averageWeightedSL);
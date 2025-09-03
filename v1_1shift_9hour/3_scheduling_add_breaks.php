<?
// Here we need to transform the array with the number of phantoms by shift
// into an array with each individual phantom
// Now we need a large actual matrix, where each major element = 1 shift with added breaks

include_once('2_scheduling_balance_shifts.php');
print_r('<br><br>Step three<br>');
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

// Reminder again
// If $difference > 0, it means understaffing, FTE not enough
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

// Take break constraints from the DB and convert into a convenient format
// For 4 hours – no breaks, for 6 hours – 1 break, for 9 hours – 3 breaks
// Consider the case of 3 breaks, but allow a flexible structure
// Unlike shifts, breaks can have different durations, which must also be accounted for

// The schedule is built with 15-minute intervals
// Duration: 15 minutes = 1, 30 minutes = 2, 1 hour = 4×15 minutes = 4

// In the DB, break starts are recorded “from the beginning of the shift”,
// so later we need to recalculate them using the $startFillingPoints array

// Initial break points are taken from shift settings
// because in the shift the break periods overlap, and the first break is set as “anytime”
// In the break-to-shift binding editor we need a validator for overlaps
// Either put limits for the user, or make a much more complex algorithm that “figures it out” automatically

// First level – break sequence number
// Second level – break duration in 15-minute intervals
// Third level – start times
// We need to manipulate the start times, they must be values, not keys

// In the prototype, the break sequence number will not be 0 and not 1,
// to distinguish from no-activity and main activity
// All breaks are unique, you cannot say “this type of break 2 times per shift”
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

// Breaks must be assigned to all phantoms anyway,
// so we loop through $phantomsShifts, checking against $difference inside
for ($i = 0; $i < $countAllPhantomShifts; $i++){
//$i=0;
    $startPhantomShiftPoint = 0;
    for ($j = 0; $j < $dayQuarters; $j++) {
        // The starting point of a specific shift cannot be taken from $startFillingPoints,
        // because there may be several initially identical phantoms
        // We will use the position of the first “1” as a reference
        if($phantomsShifts[$i][$j] == 1){
            $startPhantomShiftPoint = $j;
            break;
        }
    }

    // Now, from this $startPhantomShiftPoint and the relative start array $startBreakPoints
    // we get the absolute break starts relative to the day and start checking against $difference
    $dayStartBreakPoints = $startBreakPoints;
    // Initially, the number of breaks and the number of start points is unknown, array is not one-dimensional
    // so we must artificially reassign values via foreach
    // Passing by reference does not work correctly either
    foreach ($dayStartBreakPoints AS $breakNumber => $break){
        foreach ($break AS $breakLength => $breakStarts){
            foreach ($breakStarts AS $breakStartsKeys => $startPoints){
                $dayStartBreakPoints[$breakNumber][$breakLength][$breakStartsKeys] += $startPhantomShiftPoint;
            }
        }
    }

    // We will distribute breaks the same way as we distributed shifts – layering
    // but at the same time check against $difference
    // We must go through the breaks, since each break must be assigned exactly once
    // That’s why we need the heavy construction above
    foreach ($dayStartBreakPoints AS $breakNumber => $break){
        foreach ($break AS $breakLength => $breakStarts){
            // $breakLength - it' the length of a break
            // $breakStarts - it's the array of start points

            $maxOverStaffing = $difference[$breakStarts[0]];
            $maxOverStaffingPoint = $breakStarts[0];
            foreach ($breakStarts AS $breakStartsKeys => $startPoint){
                // Using the $breakStarts array we go through $difference
                // and select a period of size $breakLength with the biggest overstaffing
                // For simplicity – check only the starting point

                // Reminder again: < 0 means extra FTE
                if ($difference[$startPoint] < $maxOverStaffing){
                    $maxOverStaffing = $difference[$startPoint];
                    $maxOverStaffingPoint = $startPoint;
                }

            }
            // Got the point with overstaffing
            // Now we must mark it for as many intervals as the break length
            // At the same time recalculate $difference to compare further
            for($k=0; $k<$breakLength; $k++){
                $phantomsShifts[$i][$maxOverStaffingPoint+$k] = $breakNumber;
                $difference[$maxOverStaffingPoint+$k] += 1;
            }
        }
    }
}

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

// Calculate the resulting SL
// If SL is within norm, do nothing
// If SL is not within norm, we need to balance again or extend the algorithm above

// Calculate the weighted average SL by the number of forecasted calls
// The pure SL at each interval
$shiftsSL = [];
for ($j = 0; $j < $dayQuarters; $j++) {
    $shiftsSL[$j] = ErlangSL($forecast[$j], ($agentsNeeded[$j]-$difference[$j]), $ahtSeconds[$j]);
}
// “Weight” SL by call forecast
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
print_r('<br>Resulting SL after step three = ');
print_r($averageWeightedSL);
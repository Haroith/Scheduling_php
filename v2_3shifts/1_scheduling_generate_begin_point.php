<?
// Implementation of the algorithm based on project 0448
// Shift lengths: 9 hours, 6.5 hours, 4.25 hours
// Base week: 20.06.2022 to 26.06.2022
// Simplification: only the first day from 20.06.2022 5:00 to 20.06.2022 1:00
// Such a period is chosen because of the restriction that shifts can only start between 5:00 and 16:00
// Simplification: assume no shortage of operators
// For the future: if there is a shortage of operators, reduce the forecast evenly
// and try to fit shifts to the reduced forecast
// Trying to solve the problem with three types of shifts

$dayHours = 20; // Number of hours in the day we are scheduling for
// Even on 24/7 lines the scheduling problem can be split by days
// TODO develop an algorithm to split the provided time range into days
$dayQuarters = $dayHours * 4; // Number of 15-minute intervals in the day

// Forecasted calls
$forecast = [
    3,
    1,
    3,
    2,
    6,
    4,
    7,
    3,
    5,
    6,
    5,
    7,
    8,
    8,
    10,
    9,
    13,
    14,
    17,
    14,
    17,
    22,
    19,
    20,
    20,
    22,
    21,
    22,
    20,
    24,
    21,
    20,
    19,
    21,
    21,
    20,
    26,
    22,
    24,
    21,
    22,
    23,
    21,
    19,
    24,
    22,
    18,
    16,
    20,
    19,
    22,
    18,
    19,
    20,
    16,
    16,
    12,
    13,
    13,
    11,
    10,
    8,
    8,
    9,
    10,
    7,
    6,
    4,
    4,
    3,
    3,
    2,
    5,
    1,
    2,
    2,
    1,
    1,
    3,
    1,
];

// Forecasted FTE
$agentsNeeded = [
    3,
    2,
    3,
    2,
    4,
    3,
    3,
    3,
    4,
    4,
    4,
    4,
    5,
    5,
    6,
    6,
    7,
    7,
    9,
    8,
    9,
    11,
    10,
    11,
    10,
    12,
    11,
    11,
    10,
    12,
    11,
    10,
    10,
    10,
    10,
    10,
    13,
    11,
    12,
    11,
    11,
    11,
    11,
    10,
    12,
    11,
    10,
    9,
    10,
    10,
    11,
    10,
    10,
    11,
    9,
    9,
    7,
    7,
    7,
    6,
    6,
    5,
    5,
    6,
    6,
    5,
    5,
    3,
    4,
    3,
    3,
    3,
    5,
    2,
    2,
    3,
    2,
    1,
    3,
    2,
];

// AHT values per 15-minute interval
// Needed later when checking SL
$ahtSeconds = [
    266,
    306,
    294,
    282,
    230,
    253,
    178,
    248,
    269,
    278,
    279,
    261,
    301,
    316,
    292,
    315,
    287,
    292,
    294,
    302,
    311,
    316,
    312,
    326,
    316,
    327,
    320,
    313,
    313,
    316,
    315,
    313,
    302,
    295,
    298,
    306,
    307,
    297,
    307,
    310,
    306,
    303,
    310,
    298,
    312,
    322,
    331,
    322,
    317,
    326,
    324,
    337,
    325,
    332,
    310,
    322,
    311,
    305,
    281,
    295,
    299,
    295,
    304,
    298,
    297,
    315,
    339,
    306,
    371,
    309,
    392,
    362,
    415,
    401,
    314,
    443,
    324,
    83,
    279,
    306,
];

// Available shifts
// 9-hour shifts without breaks
// 6.5-hour shifts without breaks
// 4.25-hour shifts without breaks
$shiftKinds = 3; // Number of shift types
for($k=0; $k<$shiftKinds; $k++){
    $shifts[$k] = [];
}
// k - shift type (by shift length in hours)
// i - number of different shifts
// j - whether an FTE is present at a given time

// Shifts can only start at certain times, rarely at "any time"
// 9-hour shift starting points
$startFillingPoints[0] = [ // 15-min slot index to start filling arrays
    0, // 5:00
    //2, // 5:30
    4, // 6:00
    //6, // 6:30
    8, // 7:00
    //10,// 7:30
    12,// 8:00
    //14,// 8:30
    //16,// 9:00
    //18,// 9:30
    20,// 10:00
    22,// 10:30
    24,// 11:00
    //26,// 11:30
    28,// 12:00
    //30,// 12:30
    32,// 13:00
    //34,// 13:30
    36,// 14:00
    //38,// 14:30
    40,// 15:00
    //42,// 15:30
    44,// 16:00
    //46,// 16:30
    //48,// 17:00
    //50,// 17:30
    //52,// 18:00
    //54,// 18:30
    //56,// 19:00
    //58,// 19:30
    //60,// 20:00
    //62,// 20:30
    //64,// 21:00
];

// 6.5-hour shift starting points
$startFillingPoints[1] = [ // 15-min slot index to start filling arrays
    0, // 5:00
    //2, // 5:30
    4, // 6:00
    //6, // 6:30
    8, // 7:00
    10,// 7:30
    12,// 8:00
    14,// 8:30
    16,// 9:00
    18,// 9:30
    20,// 10:00
    //22,// 10:30
    24,// 11:00
    //26,// 11:30
    28,// 12:00
    //30,// 12:30
    32,// 13:00
    34,// 13:30
    36,// 14:00
    38,// 14:30
    //40,// 15:00
    42,// 15:30
    //44,// 16:00
    46,// 16:30
    //48,// 17:00
    50,// 17:30
    //52,// 18:00
    54,// 18:30
    //56,// 19:00
    //58,// 19:30
    //60,// 20:00
    //62,// 20:30
    //64,// 21:00
];

// 4.25-hour shift starting points
$startFillingPoints[2] = [ // 15-min slot index to start filling arrays
    //0, // 5:00
    //2, // 5:30
    //4, // 6:00
    //6, // 6:30
    //8, // 7:00
    //10,// 7:30
    12,// 8:00
    //14,// 8:30
    16,// 9:00
    //18,// 9:30
    20,// 10:00
    //22,// 10:30
    //24,// 11:00
    //26,// 11:30
    //28,// 12:00
    //30,// 12:30
    32,// 13:00
    //34,// 13:30
    36,// 14:00
    //38,// 14:30
    40,// 15:00
    //42,// 15:30
    43,// 15:45
    44,// 16:00
    //46,// 16:30
    47,//16:45
    //48,// 17:00
    //50,// 17:30
    51,// 17:45
    //52,// 18:00
    //54,// 18:30
    //56,// 19:00
    //58,// 19:30
    //60,// 20:00
    //62,// 20:30
    //64,// 21:00
];
print_r('<pre>');

// Shift space dimensionality = number of possible shift starting points
$dimension = []; // Dimensionality for each shift type
$summaryDimension = 0; // Total dimensionality
for($k=0; $k<$shiftKinds; $k++){
    $dimension[$k] = count($startFillingPoints[$k]);
    $summaryDimension += $dimension[$k];
}

$shiftHours[0] = 9; // Shift length in hours – user can change, shifts may be 4, 6.5, 9, or 12 hours
$shiftHours[1] = 6.5;
$shiftHours[2] = 4.25;
for($k=0; $k<$shiftKinds; $k++){
    $shiftQuarters[$k] = $shiftHours[$k] * 4; // The number of  15 minutes intervals in a shift
}

// Filling N-hour working periods without breaks
for($k=0; $k<$shiftKinds; $k++) {
    for ($i = 0; $i < $dimension[$k]; $i++) {
        for ($j = 0; $j < $dayQuarters; $j++) {
            if ($j >= $startFillingPoints[$k][$i] && $j < $startFillingPoints[$k][$i] + $shiftQuarters[$k]) {
                $shifts[$k][$i][$j] = 1;
            } else {
                $shifts[$k][$i][$j] = 0;
            }
        }
    }
}
print_r('<pre>');

print_r('shifts:<br>');
// Output for verification
for($k=0; $k<$shiftKinds; $k++) {
    for ($i = 0; $i < $dimension[$k]; $i++) {
        for ($j = 0; $j < $dayQuarters; $j++) {
            print_r($shifts[$k][$i][$j]);
            print_r(' ');
        }
        print_r('<br>');
    }
}
print_r('<br>');

print_r('agentsNeeded:<br>');
// Output of the array with forecasted FTE
for ($j = 0; $j < $dayQuarters; $j++) {
    print_r($agentsNeeded[$j]);
    if(strlen($agentsNeeded[$j]) == 1){
        print_r(' ');
    } elseif (strlen($agentsNeeded[$j]) == 2){
        print_r('');
    }
}
print_r('<br>');
print_r('<br>');

// Obtained an array of shifts to build the schedule from
// In real tasks, shift boundaries and start times must be taken from a DB
// Shift boundaries must be converted into arrays of 0s and 1s with 15-min intervals
// These shifts will be “taken” by phantoms, of which we have an infinite number
// Phantoms are not restricted by “between shifts” rules
// Those rules will be checked later when assigning real agents instead of phantoms
// At the first stage, part of the hardest checking is shifted onto users

// Phantom array contains arrays of phantoms
// Its dimensions equal the number of shift types – i.e. the number of shifts used in the schedule
$phantoms = [];
// Filling by default values
for($k=0; $k<$shiftKinds; $k++){
    for ($i = 0; $i < $dimension[$k]; $i++) {
        $phantoms[$k][] = 0;
    }
}

print_r('<br>');

// Create a copy of the forecasted FTE array and reset it to zero
// to track how much FTE is covered by the schedule
// This new array will be used to check alignment with the forecast
$phantomsScheduled = $agentsNeeded;
for ($j = 0; $j < $dayQuarters; $j++) {
    $phantomsScheduled[$j] = 0;
}

// Difference array = FTE “needed minus scheduled”
// “Scheduled” is initialised with zeros, so just copy
$difference = $phantomsScheduled;

for($j = 0; $j < $dayQuarters; $j++) {
    $difference[$j] = $agentsNeeded[$j] - $phantomsScheduled[$j];
}

// Here comes the main processing!

// For now, we add 1 shift at a time in sequence
// Not guaranteed to be optimal
// Because of the constraints, problems may occur


print_r('difference:<br>');
for($k = 0; $k < $dayQuarters; $k++) {
    print_r($difference[$k]);
    print_r(' ');
}
print_r('<br><br>');


for($j = 0; $j < $dayQuarters; $j++){ // By 15 intervals of a day
    $count=0; // The flag to avoid the infinite loop
    while ($difference[$j] > 0 && $count<100){ // Until the difference is filled, but only in points where it's possible
        for($t=0; $t<$shiftKinds; $t++){ // By shift types // TODO In this place we can choose the shift type randomly, not straightly
            for ($i=0; $i<$dimension[$t]; $i++){ // By every starting point of every shift type
                if ($difference[$j] > 0 && $j == $startFillingPoints[$t][$i]){

                    $phantoms[$t][$i] = $phantoms[$t][$i] + 1; // Adding one shift of one type during one iteration
                    // recalculate $phantomsScheduled and $difference
                    for($k = $startFillingPoints[$t][$i]; $k < $startFillingPoints[$t][$i] + $shiftQuarters[$t]; $k++){
                        $phantomsScheduled[$k] = $phantomsScheduled[$k] + 1;
                    }
                    for($r = 0; $r < $dayQuarters; $r++) {
                        $difference[$r] = $agentsNeeded[$r] - $phantomsScheduled[$r];
                    }

                    print_r($j);
                    print_r('<br>');
                }
            }
            // ?? $difference <= 0? If yes, then break.
            // This is needed for situation when we need 3 agents, but only 2 or 4 available shift types
            // We do not need to pass 2 by 2 or all 4, we need to stop earlier
            if($difference[$j] <= 0){
                break;
            }
        }
        $count++;
    }
}

print_r('difference:<br>');
for($k = 0; $k < $dayQuarters; $k++) {
    print_r($difference[$k]);
    print_r(' ');
}
print_r('<br><br>');

/*

for($i = 0; $i < $dimension; $i++){
    for($j = 0; $j < $dayQuarters; $j++){
        // Important condition!
        // $difference > 0 ? If yes, add as many shifts as the difference requires
        // Shifts can only be added at certain points, so we only check the difference at those points
        if($difference[$j] > 0 && $j == $startFillingPoints[$i]) {
            $phantoms[$i] = $phantoms[$i] + $difference[$j];
            // пересчитать $phantomsScheduled и $difference
            for($k = $startFillingPoints[$i]; $k < $startFillingPoints[$i] + $shiftQuarters; $k++){
                $phantomsScheduled[$k] = $phantomsScheduled[$k] + $phantoms[$i];
            }
            for($j = 0; $j < $dayQuarters; $j++) {
                $difference[$j] = $agentsNeeded[$j] - $phantomsScheduled[$j];
            }
            break;
        }
    }
}
*/

print_r('Total phantoms needed<br>');
var_dump($phantoms);

print_r('<br>');
// Check number of phantom FTE
print_r('phantomsScheduled:<br>');
for ($j = 0; $j < $dayQuarters; $j++) {
    print_r($phantomsScheduled[$j]);
    if(strlen($phantomsScheduled[$j]) == 1){
        print_r(' ');
    } elseif (strlen($phantomsScheduled[$j]) == 2){
        print_r('');
    }
}
print_r('<br>');
print_r('<br>');
// Check difference between forecast and scheduled coverage
print_r('difference:<br>');
for($k = 0; $k < $dayQuarters; $k++) {
    //$difference[$k] = $agentsNeeded[$k] - $phantomsScheduled[$k];
    print_r($difference[$k]);
    print_r(' ');
    //print_r('<br>');
}
// Check SL for the day
// Output final SL

// Helper functions for SL calculation
// $fc – forecasted calls for a 15-min interval
// $agents – actual number of agents scheduled, not forecasted
// $aht – average handling time in seconds
function ErlangSL($fc,$agents,$aht) {
    if($aht == 0){
        $SL = 0; // Since the weighted-average SL is multiplied by the number of calls, then
    } else {
        $fc = $fc/15; // Forecasted calls per 1 minute
        $beta = $aht/60; // Convert AHT in seconds to beta in minutes
        $a = $beta * $fc; // number of working minutes required to handle the incoming calls
        $tta = 20/60; // We assume the target SL = 80% in 20 seconds; convert 20 seconds to minutes
        $tempExp = -($agents/$beta - $fc)*$tta;
        $SL = 1 - C($agents, $a) * exp($tempExp);
        if ($SL<0) {
            $SL = 0;
        }
    }
    return $SL;
}

// Helper function for SL calculation
function C($s,$a) {
    $denominator = Factorial($s-1)*($s-$a); // Denominator
    if($denominator <> 0){ // Sometimes the denominator turns out to be zero; in that case, SL must be reduced to zero
        $firstStep = pow($a,$s)/$denominator;
        $secondStep = 0;
        for ($j=0; $j<=$s-1; $j++){
            $secondStep = $secondStep + (pow($a,$j) / Factorial($j));
        }
        $secondStep = $secondStep + $firstStep;
        $c = $firstStep / $secondStep;
    } else { // With such a value in the function above, SL will equal zero
        $c = 1;
    }
    return $c;
}

// Function for factorial calculation
function Factorial($x) {
    $y = 1;
    for ($i = 1; $i < $x; $i++) {
        $y = $y*($i+1);
    }
    return $y;
}

// Weighted SL calculation by forecasted call volume
// Raw SL per interval
$shiftsSL = [];
for ($j = 0; $j < $dayQuarters; $j++) {
    $shiftsSL[$j] = ErlangSL($forecast[$j], $phantomsScheduled[$j], $ahtSeconds[$j]);
}
// Weighted SL by forecasted calls
$sumSLWeighted = 0;
$sumForecast = 0;
for ($j = 0; $j < $dayQuarters; $j++) {
    $sumSLWeighted = $sumSLWeighted + $forecast[$j] * $shiftsSL[$j];
    $sumForecast = $sumForecast + $forecast[$j];
}
$averageWeightedSL = $sumSLWeighted / $sumForecast;
// Convert to display as percentages
$averageWeightedSL = round($averageWeightedSL * 100,2,PHP_ROUND_HALF_UP) . '%';

print_r('<br>');
print_r('<br><br>Resulting SL after the first step = ');
print_r($averageWeightedSL);
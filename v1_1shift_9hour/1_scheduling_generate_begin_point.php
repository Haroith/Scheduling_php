<?php
// Реализация алгоритма на основе реальной статистики и настроек одного из проектов
// The algorithm is based on the statistics of the real contact centre
// Берём за основу одну неделю в июне 2022 года
// We took the statistic of one week in June 2022
// Основная часть работ покрывается сменами по 9 часов, из которых суммарно 1 час - перерывы
// The main part of the schedule consists of 9 hours shifts: 8 hours work and 1 hour breaks
// Упрощаем задачу: рассматриваем 1 первые сутки с 5:00 по 1:00
// We made the task simpler: we generated the schedule for one day from 5:00 am to 1:00 am
// Такой период из-за ограничения на начало смен с 5:00 по 16:00
// We worked with such a period because of constrainments: shifts can start from 5:00 am to 4:00 pm
// Упрощаем задачу: считаем, что недостатка в количестве операторов у нас нет
// We made the task even simpler: we assume that the number of agents is infinite
// На будущее - если будет недостаток операторов, то прогноз равномерно уменьшаем
// For future versions - if we have insufficient operators, than we'll lower the forecast
// и пытаемся подогнать смены под уменьшенный прогноз
// We'll try to cover the lowered forecast by shifts
// Упрощаем задачу: покрываем весь период только одним типом смен
// We made the task simpler: we use only one type of shifts

$dayHours = 20; // Количество часов в дне, для которого ищем расписание
// The number of hours in the working day that should be covered by schedule
// Даже на круглосуточных линиях можно разбить задачу поиска расписания по дням
// Even for 24-hours inbound line we can divide thee task by days
// TODO придумать алгоритм разделения предоставленного временного промежутка на дни
// TODO develop the algorithm for dividing the scheduling period by days
$dayQuarters = $dayHours * 4; // Кол-во 15тиминутных промежутков в дне
// The number of 15 minutes periods in a day

// Прогноз звонков
// Calls forecast for every 15 minutes
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

// Прогноз FTE
// FTE forecast for every 15 minutes
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

// Значения AHT в каждый 15ти минутный промежуток
// AHT for every 15 minutes
// Понадобится позже при проверке SL
// It is required in the end to check SL
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

// Имеющиеся смены
// Array for shifts
// 9-часовые смены без перерывов
// First - 9 hour shifts without breaks
$shifts = [];
// i - кол-во разных смен
// i - the number of different shifts
// j - на месте ли FTE
// j - is FTE working during this 15 minutes interval

// Смены могут начинаться только в определённое время, редко указывается "any time"
// Shifts start only in particular moments. "Any time" is used very rarely.
$startFillingPoints = [ // С какой 15минутки начать заполнять массивы
    // From what 15 minutes interval we need to fill arrays
    0, // 5:00 am
    //2, // 5:30
    4, // 6:00 am
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
    32,// 13:00 // 1:00 pm
    //34,// 13:30
    36,// 14:00 // 2:00 pm
    //38,// 14:30
    40,// 15:00 // 3:00 pm
    //42,// 15:30
    44,// 16:00 // 4:00 pm
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
// Размерность пространства смен - количество возможных точек начала смен
// Space dimension of shifts - the number of possible shift starting points
$dimension = count($startFillingPoints);

$shiftHours = 9; // Кол-во часов в смене - пользователь может менять, могут быть смены по 4, 6.5, 9 или 12 часов
// The number of hours  in a shift - a user can change that setting, shifts can last 4, 6.5, 9 or 12 hours
$shiftQuarters = $shiftHours * 4; // Кол-во 15тиминутных промежутков в смене
// The number of 15 minutes intervals in a shift

// Заполнение 9ти часовыми промежутками работы без перерывов
// Here we fill thee timeline by 9 hour working intervals without breaks
for ($i = 0; $i < $dimension; $i++){
    for ($j = 0; $j < $dayQuarters; $j++) {
        if ($j >= $startFillingPoints[$i] && $j < $startFillingPoints[$i] + $shiftQuarters) {
            $shifts[$i][$j] = 1;
        } else {
            $shifts[$i][$j] = 0;
        }
    }
}
print_r('<pre>');

print_r('shifts:<br>');
// Вывод для проверки
// Output on the screen for checking
for ($i = 0; $i < $dimension; $i++){
    for ($j = 0; $j < $dayQuarters; $j++) {
        print_r($shifts[$i][$j]);
        print_r(' ');
    }
    print_r('<br>');
}
print_r('<br>');

print_r('agentsNeeded:<br>');
// Вывод рядом массива с прогнозом FTE
// Output the array with the forecasted FTE
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

// Получили массив смен, из которых будем составлять расписание
// We have got the array with shifts, we create the schedule with them
// Разумеется, в реальных задачах нужно будет вытаскивать границы смен и время начала из БД
// Of course, during the real schedule generation we need to extract possible start times from a database
// Границы смен надо будет преобразовывать в массивы нулей и единиц с разбивкой по 15 минут
// Shift boundaries need to be converted into arrays of 0s and 1s
// Эти смены будут разбирать фантомы, которых у нас бесконечное количество
// Phantoms will later take those shifts, the number of phantoms is infinite
// Также фантомы условно не ограничены правилами "между сменами"
// We assume that there is no constraints about non-working time between shifts
// Правила "между сменами" будут проверяться при назначении живых людей на место фантомов
// The constraints "between shifts" will be checked when agents are given phantom shifts
// Отдаём на первом этапе проверку самого сложного на частичный откуп пользователям
// In this first draft of the algorithm we delegate the most complicated constraint to users

// Массив фантомов размерности равный количеству видов смен - это кол-во взятых для расписания смен
// Phantoms array is equal the number of shifts - this is the number of taken shifts for this schedule
$phantoms = [];
// Заполнение значениями по умолчанию
// Filling with default values
for ($i = 0; $i < $dimension; $i++) {
    $phantoms[] = 0;
}

print_r('<br>');

// Создаём копию массива прогнозируемых FTE и обнуляем, чтобы понимать, сколько мы по расписанию FTE набрали
// Make a copy array of forecasted FTE and fill with 0s to understand, how many FTE we have taken by scheduling
// По новому массиву будем проверять соответствие прогнозу
// We check adherence between the forecast and the schedule with the help of this array
$phantomsScheduled = $agentsNeeded;
for ($j = 0; $j < $dayQuarters; $j++) {
    $phantomsScheduled[$j] = 0;
}

// Массив разниц в FTE "необходимые минус расписание"
// Array of differences
// "По расписанию" уже задан с нулями, так что просто копируем
// This array is already filled with 0s "as scheduled", so we need only to copy it
$difference = $phantomsScheduled;

for($j = 0; $j < $dayQuarters; $j++) {
    $difference[$j] = $agentsNeeded[$j] - $phantomsScheduled[$j];
    // $difference>0 ? Если да, то добавлять смен столько, какая разница
    // $difference>0 ? If yes, then to add so many shifts as the difference
}
// Идём по большому циклу фантомов (видов смен) - сколько их нужно набрать
// Go by larger loop of phantoms - how many we should  take them
// Проверяем соответствие прогнозу по фантомным FTE
// We should check the difference between forecast and schedule of phantom FTEs
for($i = 0; $i < $dimension; $i++){
    for($j = 0; $j < $dayQuarters; $j++){
        // Важное условие!
        // Important condition!
        // $difference>0 ? Если да, то добавлять смен столько, какая разница
        // difference>0 ? If yes, then to add so many shifts as the difference
        // Добавлять смены можно только в некоторых местах, поэтому проверяем разницу только в этих местах
        // We are permitted to add shifts only in particular moments, so we need to check difference only in those moments
        if($difference[$j] > 0 && $j == $startFillingPoints[$i]) {
            $phantoms[$i] = $phantoms[$i] + $difference[$j];
            // пересчитать $phantomsScheduled и $difference
            // recalculate $phantomsScheduled and $difference
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

// print_r('Сколько в итоге понадобилось phantoms<br>');
print_r('How many phantoms are needed<br>');
var_dump($phantoms);

print_r('<br>');
// Проверка кол-ва фантомных FTE
// Checking the number of phantom  FTE
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
// Проверка массива разниц прогноза и составленного расписания
// Checking the array of difference between the forecast and the schedule
print_r('difference:<br>');
for($k = 0; $k < $dayQuarters; $k++) {
    $difference[$k] = $agentsNeeded[$k] - $phantomsScheduled[$k];
    print_r($difference[$k]);
    print_r(' ');
}
// Проверить SL за день.
// To check daily SL
// Вывести итоговый SL
// To show the final SL

// Подготовительные вспомогательные функции для вычисления SL
// $fc - прогноз звонков за 15 минут
// $agents - количество агентов по факту, а не по прогнозу
// $aht - средняя продолжительность обслуживания в секундах
function ErlangSL($fc,$agents,$aht) {
    if($aht == 0){
        $SL = 0; // Т.к. при вычислении средневзвешенного SL умножается на количество звонков, то
    } else {
        $fc = $fc/15; // Прогноз звонков за 1 минуту
        $beta = $aht/60; // Переводим из AHT в секундах в бэта в минутах
        $a = $beta * $fc; // сколько рабочих минут потребуется для обслуживания поступивших звонков
        $tta = 20/60; // Считаем целевой SL = 80% на 20 секунд, приводим 20 секунд к минутам
        $tempExp = -($agents/$beta - $fc)*$tta;
        $SL = 1 - C($agents, $a) * exp($tempExp);
        if ($SL<0) {
            $SL = 0;
        }
    }
    return $SL;
}

// Вспомогательная функция для вычисления SL
function C($s,$a) {
    $denominator = Factorial($s-1)*($s-$a); // Знаменатель
    if($denominator <> 0){ // Иногда знаменатель получается равным нулю, в этом случае нужно SL свести в ноль
        $firstStep = pow($a,$s)/$denominator;
        $secondStep = 0;
        for ($j=0; $j<=$s-1; $j++){
            $secondStep = $secondStep + (pow($a,$j) / Factorial($j));
        }
        $secondStep = $secondStep + $firstStep;
        $c = $firstStep / $secondStep;
    } else { // При таком значении в функции выше SL получится равным нулю
        $c = 1;
    }
    return $c;
}

// Функция вычисления факториала
function Factorial($x) {
    $y = 1;
    for ($i = 1; $i < $x; $i++) {
        $y = $y*($i+1);
    }
    return $y;
}

// Вычисляем средневзвешенный по количеству прогнозируемых звонков SL
// Чистый SL на каждом промежутке
$shiftsSL = [];
for ($j = 0; $j < $dayQuarters; $j++) {
    $shiftsSL[$j] = ErlangSL($forecast[$j], $phantomsScheduled[$j], $ahtSeconds[$j]);
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
print_r('<br>Получившийся SL после первого шага = ');
print_r($averageWeightedSL);
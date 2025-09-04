<?
// Implementation of the algorithm based on project 0448
// Shift durations – 9 hours, 6.5 hours, 4.25 hours
// Using the week from 20.06.2022 to 26.06.2022 as a basis
// Considering 7 days from 20.06.2022 00:00 to 26.06.2022 23:59
// Simplification: assume there is no shortage of operators
// For the future – if operators are insufficient, then the forecast is reduced proportionally
// and shifts are adjusted to match the reduced forecast
// Trying to solve the task with three types of shifts

$days = 7; // Number of days in the period for which we are creating the schedule
$dayHours = $days * 24; // Number of hours in the days
$dayQuarters = $dayHours * 4; // Number of 15-minute intervals in the days
// dayQuarters – this will be the size of the arrays for call forecast, AHT forecast, FTE requirement
// basically, it is the number of 15-minute intervals in the week

// Call forecast
$forecast = [
    1,
    1,
    1,
    1,
    0,
    2,
    1,
    1,
    1,
    0,
    1,
    1,
    2,
    1,
    1,
    0,
    1,
    1,
    1,
    2,
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
    0,
    1,
    0,
    1,
    2,
    0,
    1,
    0,
    0,
    2,
    2,
    0,
    1,
    1,
    1,
    1,
    0,
    3,
    1,
    1,
    3,
    5,
    5,
    4,
    3,
    6,
    6,
    7,
    6,
    8,
    9,
    13,
    11,
    10,
    11,
    13,
    14,
    16,
    17,
    18,
    21,
    23,
    20,
    22,
    18,
    19,
    19,
    23,
    21,
    21,
    23,
    23,
    20,
    20,
    21,
    19,
    21,
    21,
    22,
    18,
    19,
    20,
    20,
    17,
    18,
    20,
    18,
    21,
    20,
    17,
    14,
    17,
    18,
    13,
    16,
    14,
    7,
    11,
    12,
    10,
    10,
    8,
    7,
    7,
    7,
    3,
    6,
    4,
    4,
    3,
    3,
    1,
    2,
    1,
    3,
    1,
    2,
    1,
    2,
    0,
    0,
    2,
    1,
    1,
    0,
    2,
    2,
    0,
    0,
    1,
    0,
    3,
    1,
    3,
    1,
    4,
    2,
    4,
    4,
    4,
    5,
    5,
    4,
    5,
    7,
    10,
    10,
    13,
    13,
    14,
    12,
    19,
    15,
    19,
    21,
    23,
    21,
    24,
    23,
    23,
    23,
    23,
    25,
    23,
    24,
    20,
    22,
    24,
    22,
    23,
    22,
    22,
    20,
    20,
    23,
    22,
    22,
    20,
    24,
    24,
    21,
    21,
    20,
    20,
    17,
    20,
    17,
    21,
    20,
    19,
    14,
    16,
    13,
    7,
    11,
    10,
    11,
    5,
    8,
    5,
    8,
    5,
    4,
    4,
    3,
    4,
    1,
    3,
    1,
    0,
    2,
    2,
    1,
    0,
    3,
    0,
    1,
    1,
    0,
    0,
    1,
    3,
    1,
    2,
    1,
    0,
    2,
    2,
    1,
    2,
    2,
    3,
    2,
    6,
    3,
    5,
    4,
    7,
    5,
    6,
    8,
    10,
    8,
    12,
    15,
    14,
    16,
    17,
    14,
    18,
    20,
    20,
    20,
    24,
    24,
    23,
    20,
    20,
    25,
    21,
    21,
    21,
    22,
    20,
    22,
    20,
    23,
    24,
    23,
    27,
    24,
    20,
    23,
    23,
    21,
    18,
    19,
    21,
    20,
    18,
    23,
    18,
    21,
    21,
    16,
    17,
    17,
    11,
    11,
    12,
    11,
    10,
    11,
    9,
    8,
    7,
    6,
    5,
    4,
    2,
    4,
    3,
    2,
    1,
    3,
    0,
    3,
    1,
    2,
    3,
    1,
    1,
    0,
    2,
    1,
    0,
    1,
    5,
    0,
    1,
    2,
    2,
    3,
    2,
    1,
    3,
    1,
    2,
    3,
    4,
    5,
    6,
    5,
    5,
    6,
    6,
    6,
    10,
    9,
    13,
    15,
    12,
    19,
    14,
    17,
    18,
    19,
    22,
    22,
    23,
    25,
    24,
    21,
    22,
    23,
    26,
    28,
    23,
    25,
    21,
    23,
    23,
    21,
    26,
    23,
    25,
    21,
    21,
    20,
    18,
    23,
    20,
    18,
    23,
    19,
    20,
    16,
    21,
    19,
    17,
    19,
    17,
    17,
    13,
    11,
    7,
    9,
    8,
    10,
    9,
    7,
    8,
    5,
    3,
    5,
    2,
    3,
    3,
    4,
    3,
    1,
    1,
    2,
    3,
    1,
    1,
    2,
    0,
    1,
    3,
    2,
    2,
    0,
    4,
    2,
    1,
    1,
    2,
    2,
    2,
    2,
    2,
    2,
    3,
    2,
    2,
    5,
    3,
    6,
    5,
    4,
    6,
    5,
    7,
    6,
    10,
    12,
    11,
    11,
    15,
    19,
    16,
    18,
    21,
    20,
    22,
    24,
    24,
    22,
    22,
    28,
    21,
    24,
    23,
    20,
    23,
    22,
    28,
    24,
    21,
    19,
    26,
    20,
    19,
    20,
    19,
    21,
    16,
    18,
    15,
    17,
    17,
    16,
    19,
    19,
    15,
    13,
    12,
    13,
    16,
    10,
    9,
    10,
    10,
    8,
    9,
    6,
    8,
    5,
    3,
    5,
    3,
    1,
    3,
    1,
    2,
    1,
    1,
    1,
    4,
    2,
    0,
    4,
    1,
    1,
    1,
    0,
    2,
    0,
    3,
    0,
    2,
    2,
    3,
    1,
    2,
    1,
    1,
    2,
    3,
    1,
    4,
    4,
    4,
    3,
    5,
    5,
    4,
    4,
    5,
    8,
    7,
    6,
    7,
    12,
    13,
    11,
    15,
    16,
    16,
    18,
    19,
    19,
    22,
    22,
    21,
    20,
    21,
    21,
    21,
    23,
    19,
    19,
    26,
    24,
    23,
    21,
    19,
    16,
    17,
    20,
    18,
    18,
    19,
    19,
    13,
    17,
    15,
    13,
    20,
    14,
    14,
    14,
    13,
    12,
    10,
    10,
    9,
    10,
    8,
    8,
    7,
    10,
    6,
    5,
    3,
    4,
    3,
    2,
    3,
    1,
    1,
    2,
];

// FTE forecast
$agentsNeeded = [
    1,
    1,
    1,
    1,
    0,
    2,
    1,
    1,
    1,
    0,
    1,
    1,
    2,
    1,
    1,
    0,
    1,
    1,
    1,
    2,
    2,
    1,
    2,
    2,
    4,
    3,
    3,
    2,
    3,
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
    10,
    9,
    7,
    8,
    6,
    7,
    6,
    6,
    5,
    6,
    6,
    4,
    5,
    4,
    4,
    3,
    2,
    2,
    4,
    2,
    1,
    2,
    2,
    1,
    2,
    0,
    0,
    1,
    1,
    0,
    0,
    1,
    0,
    1,
    1,
    1,
    2,
    0,
    1,
    1,
    2,
    0,
    2,
    2,
    3,
    1,
    2,
    3,
    2,
    2,
    4,
    5,
    4,
    4,
    5,
    6,
    7,
    7,
    7,
    7,
    7,
    8,
    9,
    8,
    9,
    10,
    10,
    11,
    10,
    10,
    9,
    10,
    11,
    10,
    10,
    11,
    10,
    10,
    10,
    11,
    9,
    11,
    11,
    10,
    10,
    10,
    10,
    10,
    10,
    9,
    10,
    9,
    12,
    10,
    9,
    8,
    9,
    9,
    7,
    9,
    8,
    5,
    6,
    7,
    6,
    6,
    5,
    6,
    5,
    4,
    4,
    4,
    3,
    4,
    4,
    3,
    2,
    2,
    2,
    2,
    2,
    1,
    2,
    1,
    1,
    1,
    0,
    0,
    2,
    2,
    1,
    1,
    1,
    0,
    0,
    1,
    2,
    1,
    2,
    1,
    2,
    2,
    2,
    2,
    4,
    4,
    3,
    3,
    4,
    4,
    7,
    6,
    7,
    8,
    7,
    7,
    10,
    8,
    11,
    10,
    12,
    11,
    13,
    11,
    12,
    12,
    12,
    11,
    12,
    12,
    11,
    12,
    12,
    11,
    11,
    12,
    11,
    10,
    10,
    11,
    11,
    11,
    10,
    12,
    13,
    11,
    11,
    10,
    10,
    10,
    11,
    11,
    11,
    11,
    10,
    9,
    9,
    7,
    6,
    7,
    6,
    5,
    5,
    5,
    5,
    5,
    4,
    4,
    2,
    4,
    3,
    2,
    2,
    0,
    2,
    1,
    1,
    1,
    2,
    2,
    0,
    0,
    1,
    0,
    1,
    1,
    1,
    1,
    1,
    2,
    0,
    1,
    2,
    1,
    2,
    2,
    1,
    2,
    4,
    4,
    4,
    3,
    4,
    4,
    3,
    5,
    7,
    6,
    7,
    8,
    8,
    9,
    9,
    8,
    9,
    10,
    10,
    10,
    12,
    11,
    12,
    10,
    11,
    13,
    11,
    11,
    10,
    11,
    11,
    10,
    11,
    11,
    12,
    11,
    13,
    12,
    11,
    12,
    12,
    12,
    10,
    10,
    11,
    10,
    10,
    12,
    10,
    11,
    10,
    9,
    9,
    9,
    7,
    6,
    8,
    6,
    6,
    6,
    6,
    5,
    5,
    4,
    3,
    3,
    3,
    3,
    3,
    3,
    1,
    1,
    2,
    2,
    2,
    2,
    1,
    1,
    2,
    0,
    1,
    1,
    1,
    1,
    2,
    0,
    2,
    1,
    2,
    2,
    2,
    1,
    2,
    3,
    2,
    2,
    3,
    4,
    4,
    3,
    4,
    4,
    3,
    5,
    6,
    5,
    7,
    8,
    7,
    9,
    7,
    9,
    9,
    10,
    10,
    10,
    11,
    11,
    11,
    11,
    11,
    11,
    13,
    13,
    11,
    12,
    12,
    11,
    11,
    11,
    12,
    11,
    11,
    11,
    11,
    10,
    10,
    10,
    11,
    10,
    11,
    10,
    11,
    9,
    11,
    10,
    10,
    9,
    9,
    8,
    7,
    7,
    5,
    6,
    5,
    6,
    5,
    5,
    5,
    4,
    3,
    4,
    4,
    3,
    3,
    4,
    2,
    1,
    2,
    2,
    2,
    2,
    1,
    1,
    2,
    1,
    1,
    1,
    2,
    1,
    2,
    1,
    1,
    1,
    2,
    4,
    3,
    3,
    2,
    2,
    2,
    2,
    2,
    3,
    2,
    4,
    4,
    3,
    5,
    5,
    4,
    5,
    5,
    6,
    7,
    7,
    8,
    9,
    9,
    9,
    10,
    10,
    11,
    11,
    11,
    11,
    11,
    12,
    10,
    12,
    11,
    9,
    12,
    10,
    12,
    12,
    10,
    8,
    11,
    9,
    9,
    10,
    10,
    9,
    8,
    9,
    9,
    9,
    9,
    9,
    10,
    9,
    9,
    7,
    8,
    7,
    8,
    6,
    6,
    6,
    6,
    5,
    6,
    6,
    5,
    4,
    3,
    4,
    4,
    3,
    2,
    2,
    1,
    2,
    1,
    0,
    1,
    2,
    2,
    1,
    2,
    0,
    1,
    0,
    2,
    0,
    1,
    1,
    2,
    2,
    1,
    2,
    2,
    1,
    1,
    2,
    1,
    3,
    3,
    2,
    3,
    2,
    4,
    3,
    4,
    3,
    5,
    4,
    5,
    4,
    6,
    6,
    7,
    7,
    9,
    9,
    9,
    9,
    10,
    9,
    11,
    11,
    10,
    10,
    11,
    10,
    10,
    10,
    10,
    9,
    13,
    12,
    10,
    10,
    9,
    8,
    8,
    9,
    9,
    9,
    8,
    9,
    7,
    10,
    9,
    9,
    10,
    8,
    9,
    7,
    7,
    7,
    6,
    7,
    5,
    6,
    5,
    6,
    4,
    5,
    4,
    5,
    2,
    3,
    2,
    2,
    2,
    1,
    3,
    0,
];

// AHT values for each 15-minute interval
// Will be used later when checking SL
$ahtSeconds = [
    427,
    398,
    302,
    371,
    0,
    316,
    402,
    404,
    218,
    0,
    390,
    40,
    336,
    28,
    254,
    0,
    59,
    331,
    365,
    281,
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
    0,
    335,
    0,
    70,
    361,
    0,
    274,
    0,
    0,
    275,
    69,
    0,
    229,
    363,
    234,
    188,
    0,
    260,
    299,
    269,
    211,
    162,
    218,
    218,
    282,
    279,
    274,
    279,
    260,
    292,
    299,
    327,
    318,
    343,
    319,
    328,
    296,
    292,
    274,
    278,
    307,
    286,
    309,
    298,
    321,
    298,
    305,
    301,
    288,
    295,
    298,
    291,
    302,
    291,
    321,
    308,
    314,
    302,
    314,
    313,
    308,
    312,
    313,
    287,
    308,
    319,
    311,
    309,
    318,
    306,
    298,
    308,
    293,
    305,
    302,
    308,
    310,
    304,
    316,
    311,
    317,
    325,
    351,
    374,
    361,
    383,
    349,
    395,
    382,
    455,
    400,
    514,
    386,
    504,
    410,
    383,
    332,
    11,
    211,
    0,
    0,
    181,
    387,
    502,
    0,
    186,
    177,
    0,
    0,
    397,
    0,
    300,
    180,
    236,
    178,
    263,
    266,
    196,
    291,
    335,
    257,
    248,
    265,
    341,
    320,
    334,
    329,
    339,
    325,
    292,
    295,
    305,
    303,
    317,
    317,
    333,
    325,
    329,
    338,
    318,
    311,
    318,
    304,
    307,
    320,
    320,
    322,
    313,
    303,
    311,
    320,
    317,
    326,
    323,
    317,
    326,
    313,
    319,
    327,
    329,
    335,
    315,
    329,
    335,
    347,
    342,
    367,
    343,
    353,
    340,
    319,
    325,
    295,
    324,
    310,
    334,
    328,
    327,
    319,
    322,
    373,
    309,
    371,
    435,
    453,
    547,
    414,
    309,
    14,
    0,
    273,
    247,
    311,
    0,
    230,
    0,
    362,
    272,
    0,
    0,
    151,
    130,
    65,
    173,
    156,
    0,
    285,
    337,
    233,
    262,
    301,
    279,
    267,
    246,
    246,
    251,
    260,
    260,
    242,
    305,
    319,
    324,
    304,
    307,
    281,
    301,
    285,
    299,
    293,
    307,
    311,
    285,
    319,
    288,
    305,
    305,
    314,
    323,
    317,
    310,
    314,
    285,
    296,
    298,
    299,
    319,
    306,
    308,
    306,
    316,
    321,
    322,
    330,
    331,
    340,
    338,
    334,
    319,
    300,
    309,
    316,
    320,
    328,
    327,
    322,
    319,
    329,
    328,
    339,
    331,
    323,
    307,
    310,
    334,
    312,
    360,
    398,
    319,
    344,
    439,
    354,
    334,
    322,
    382,
    263,
    0,
    252,
    453,
    368,
    268,
    15,
    414,
    0,
    248,
    248,
    0,
    292,
    188,
    0,
    385,
    279,
    123,
    199,
    238,
    276,
    235,
    229,
    327,
    255,
    246,
    274,
    277,
    255,
    293,
    263,
    340,
    291,
    310,
    281,
    297,
    299,
    281,
    288,
    271,
    307,
    296,
    296,
    294,
    296,
    282,
    296,
    293,
    295,
    309,
    295,
    305,
    297,
    318,
    304,
    305,
    290,
    304,
    291,
    312,
    311,
    302,
    312,
    317,
    318,
    338,
    301,
    329,
    304,
    315,
    300,
    344,
    321,
    317,
    322,
    293,
    323,
    302,
    289,
    286,
    303,
    305,
    300,
    307,
    296,
    306,
    311,
    326,
    305,
    303,
    362,
    379,
    412,
    393,
    412,
    446,
    352,
    411,
    250,
    256,
    325,
    118,
    256,
    0,
    450,
    449,
    178,
    309,
    0,
    165,
    235,
    118,
    331,
    405,
    285,
    555,
    460,
    322,
    315,
    266,
    267,
    311,
    250,
    257,
    316,
    281,
    306,
    340,
    282,
    307,
    326,
    292,
    304,
    299,
    322,
    306,
    308,
    284,
    296,
    294,
    303,
    303,
    298,
    289,
    300,
    293,
    284,
    278,
    287,
    297,
    303,
    289,
    296,
    291,
    293,
    283,
    285,
    280,
    276,
    283,
    287,
    298,
    289,
    292,
    305,
    307,
    297,
    300,
    302,
    311,
    307,
    307,
    295,
    308,
    293,
    309,
    279,
    297,
    303,
    320,
    346,
    318,
    384,
    323,
    372,
    324,
    425,
    383,
    297,
    380,
    413,
    319,
    414,
    367,
    350,
    109,
    242,
    0,
    272,
    399,
    155,
    303,
    0,
    338,
    0,
    99,
    0,
    334,
    269,
    198,
    200,
    206,
    198,
    202,
    249,
    326,
    171,
    268,
    279,
    254,
    283,
    309,
    328,
    335,
    327,
    317,
    335,
    323,
    330,
    317,
    303,
    312,
    330,
    317,
    321,
    302,
    312,
    326,
    309,
    333,
    304,
    298,
    292,
    308,
    305,
    304,
    294,
    300,
    298,
    312,
    311,
    296,
    298,
    270,
    282,
    282,
    300,
    289,
    290,
    293,
    293,
    294,
    318,
    336,
    364,
    336,
    325,
    318,
    313,
    293,
    301,
    305,
    330,
    313,
    306,
    291,
    295,
    313,
    312,
    326,
    339,
    345,
    321,
    282,
    334,
    343,
    344,
    323,
    355,
];

// Available shifts
// 9-hour shifts without breaks
// 6.5-hour shifts without breaks
// 4.25-hour shifts without breaks
$shiftKinds = 3; // Number of shift types
for($k=0; $k<$shiftKinds; $k++){
    $shifts[$k] = [];
}
// k - types of shifts by the number of hours per shift
// i - number of different shifts
// j - whether FTE is present

// Shifts can only start at specific times, “any time” is rarely allowed
// 9-hour shift start times in a day
$startFillingPointsDay[0] = [ // From which 15-minute interval of the day to start filling the arrays
    // 0,  // 00:00
    // 1,  // 00:15
    // 2,  // 00:30
    // 3,  // 00:45

    // 4,  // 01:00
    // 5,  // 01:15
    // 6,  // 01:30
    // 7,  // 01:45

    // 8,  // 02:00
    // 9,  // 02:15
    // 10, // 02:30
    // 11, // 02:45

    // 12, // 03:00
    // 13, // 03:15
    // 14, // 03:30
    // 15, // 03:45

    // 16, // 04:00
    // 17, // 04:15
    // 18, // 04:30
    // 19, // 04:45

    20, // 05:00
    // 21, // 05:15
    // 22, // 05:30
    // 23, // 05:45

    24, // 06:00
    // 25, // 06:15
    // 26, // 06:30
    // 27, // 06:45

    28, // 07:00
    // 29, // 07:15
    // 30, // 07:30
    // 31, // 07:45

    32, // 08:00
    // 33, // 08:15
    // 34, // 08:30
    // 35, // 08:45

    // 36, // 09:00
    // 37, // 09:15
    // 38, // 09:30
    // 39, // 09:45

    40, // 10:00
    // 41, // 10:15
    42, // 10:30
    // 43, // 10:45

    44, // 11:00
    // 45, // 11:15
    // 46, // 11:30
    // 47, // 11:45

    48, // 12:00
    // 49, // 12:15
    // 50, // 12:30
    // 51, // 12:45

    52, // 13:00
    // 53, // 13:15
    // 54, // 13:30
    // 55, // 13:45

    56, // 14:00
    // 57, // 14:15
    // 58, // 14:30
    // 59, // 14:45

    60, // 15:00
    // 61, // 15:15
    // 62, // 15:30
    // 63, // 15:45

    64, // 16:00
    // 65, // 16:15
    // 66, // 16:30
    // 67, // 16:45

    // 68, // 17:00
    // 69, // 17:15
    // 70, // 17:30
    // 71, // 17:45

    // 72, // 18:00
    // 73, // 18:15
    // 74, // 18:30
    // 75, // 18:45

    // 76, // 19:00
    // 77, // 19:15
    // 78, // 19:30
    // 79, // 19:45

    // 80, // 20:00
    // 81, // 20:15
    // 82, // 20:30
    // 83, // 20:45

    // 68, // 17:00
    // 69, // 17:15
    // 70, // 17:30
    // 71, // 17:45

    // 72, // 18:00
    // 73, // 18:15
    // 74, // 18:30
    // 75, // 18:45

    // 76, // 19:00
    // 77, // 19:15
    // 78, // 19:30
    // 79, // 19:45

    // 80, // 20:00
    // 81, // 20:15
    // 82, // 20:30
    // 83, // 20:45

    // 84, // 21:00
    // 85, // 21:15
    // 86, // 21:30
    // 87, // 21:45

    // 88, // 22:00
    // 89, // 22:15
    // 90, // 22:30
    // 91, // 22:45

    // 92, // 23:00
    // 93, // 23:15
    // 94, // 23:30
    // 95, // 23:45
];

// 6.5-hour shift start times in a day
$startFillingPointsDay[1] = [ // From which 15-minute interval of the day to start filling the arrays
    // 0,  // 00:00
    // 1,  // 00:15
    // 2,  // 00:30
    // 3,  // 00:45

    // 4,  // 01:00
    // 5,  // 01:15
    // 6,  // 01:30
    // 7,  // 01:45

    // 8,  // 02:00
    // 9,  // 02:15
    // 10, // 02:30
    // 11, // 02:45

    // 12, // 03:00
    // 13, // 03:15
    // 14, // 03:30
    // 15, // 03:45

    // 16, // 04:00
    // 17, // 04:15
    // 18, // 04:30
    // 19, // 04:45

    20, // 05:00
    // 21, // 05:15
    // 22, // 05:30
    // 23, // 05:45

    24, // 06:00
    // 25, // 06:15
    // 26, // 06:30
    // 27, // 06:45

    28, // 07:00
    // 29, // 07:15
    30, // 07:30
    // 31, // 07:45

    32, // 08:00
    // 33, // 08:15
    34, // 08:30
    // 35, // 08:45

    36, // 09:00
    // 37, // 09:15
    38, // 09:30
    // 39, // 09:45

    40, // 10:00
    // 41, // 10:15
    // 42, // 10:30
    // 43, // 10:45

    44, // 11:00
    // 45, // 11:15
    // 46, // 11:30
    // 47, // 11:45

    48, // 12:00
    // 49, // 12:15
    // 50, // 12:30
    // 51, // 12:45

    52, // 13:00
    // 53, // 13:15
    54, // 13:30
    // 55, // 13:45

    56, // 14:00
    // 57, // 14:15
    58, // 14:30
    // 59, // 14:45

    // 60, // 15:00
    // 61, // 15:15
    62, // 15:30
    // 63, // 15:45

    // 64, // 16:00
    // 65, // 16:15
    66, // 16:30
    // 67, // 16:45

    // 68, // 17:00
    // 69, // 17:15
    70, // 17:30
    // 71, // 17:45

    // 72, // 18:00
    // 73, // 18:15
    74, // 18:30
    // 75, // 18:45

    // 76, // 19:00
    // 77, // 19:15
    // 78, // 19:30
    // 79, // 19:45

    // 80, // 20:00
    // 81, // 20:15
    // 82, // 20:30
    // 83, // 20:45

    // 84, // 21:00
    // 85, // 21:15
    // 86, // 21:30
    // 87, // 21:45

    // 88, // 22:00
    // 89, // 22:15
    // 90, // 22:30
    // 91, // 22:45

    // 92, // 23:00
    // 93, // 23:15
    // 94, // 23:30
    // 95, // 23:45
];

// 4.25-hour shift start times in a day
$startFillingPointsDay[2] = [ // From which 15-minute interval of the day to start filling the arrays
    // 0,  // 00:00
    // 1,  // 00:15
    // 2,  // 00:30
    // 3,  // 00:45

    // 4,  // 01:00
    // 5,  // 01:15
    // 6,  // 01:30
    // 7,  // 01:45

    // 8,  // 02:00
    // 9,  // 02:15
    // 10, // 02:30
    // 11, // 02:45

    // 12, // 03:00
    // 13, // 03:15
    // 14, // 03:30
    // 15, // 03:45

    // 16, // 04:00
    // 17, // 04:15
    // 18, // 04:30
    // 19, // 04:45

    // 20, // 05:00
    // 21, // 05:15
    // 22, // 05:30
    // 23, // 05:45

    // 24, // 06:00
    // 25, // 06:15
    // 26, // 06:30
    // 27, // 06:45

    // 28, // 07:00
    // 29, // 07:15
    // 30, // 07:30
    // 31, // 07:45

    32, // 08:00
    // 33, // 08:15
    // 34, // 08:30
    // 35, // 08:45

    36, // 09:00
    // 37, // 09:15
    // 38, // 09:30
    // 39, // 09:45

    40, // 10:00
    // 41, // 10:15
    // 42, // 10:30
    // 43, // 10:45

    // 44, // 11:00
    // 45, // 11:15
    // 46, // 11:30
    // 47, // 11:45

    // 48, // 12:00
    // 49, // 12:15
    // 50, // 12:30
    // 51, // 12:45

    52, // 13:00
    // 53, // 13:15
    // 54, // 13:30
    // 55, // 13:45

    56, // 14:00
    // 57, // 14:15
    // 58, // 14:30
    // 59, // 14:45

    60, // 15:00
    // 61, // 15:15
    // 62, // 15:30
    63, // 15:45

    64, // 16:00
    // 65, // 16:15
    // 66, // 16:30
    67, // 16:45

    // 68, // 17:00
    // 69, // 17:15
    // 70, // 17:30
    71, // 17:45

    // 72, // 18:00
    // 73, // 18:15
    // 74, // 18:30
    // 75, // 18:45

    // 76, // 19:00
    // 77, // 19:15
    // 78, // 19:30
    // 79, // 19:45

    // 80, // 20:00
    // 81, // 20:15
    // 82, // 20:30
    // 83, // 20:45

    // 84, // 21:00
    // 85, // 21:15
    // 86, // 21:30
    // 87, // 21:45

    // 88, // 22:00
    // 89, // 22:15
    // 90, // 22:30
    // 91, // 22:45

    // 92, // 23:00
    // 93, // 23:15
    // 94, // 23:30
    // 95, // 23:45
];

// Arrays of shift start times need to be extended from one day to the entire week
// A day has 24 hours, i.e. 96 periods of 15 minutes
// 0:15 on the first day = 1
// 0:15 on the second day = 97, i.e. 1+96
// 0:15 on the third day = 1+96+96, i.e. 193
// Therefore, we need to add 96 to each element of the array 6 times (number of days minus one)
// and append it to the end
// Since the array size is unknown, we go through it with foreach
for($k=0; $k<$shiftKinds; $k++){
    $startFillingPoints[$k] = [];
    for($n=0; $n<$days; $n++) {
        foreach ($startFillingPointsDay[$k] AS $key=>$point){
            $startFillingPoints[$k][] = $startFillingPointsDay[$k][$key] + $n*96;
        }
    }
}
print_r('<pre>');

// Dimension of the shift space — number of possible shift start points
$dimension = []; // Dimension for each shift type separately
$summaryDimension = 0; // Total dimension overall
for($k=0; $k<$shiftKinds; $k++){
    $dimension[$k] = count($startFillingPoints[$k]);
    $summaryDimension += $dimension[$k];
}

$shiftHours[0] = 9; // Number of hours in a shift — user can change this; shifts could be 4, 6.5, 9 or 12 hours
$shiftHours[1] = 6.5;
$shiftHours[2] = 4.25;
for($k=0; $k<$shiftKinds; $k++){
    $shiftQuarters[$k] = $shiftHours[$k] * 4; // Number of 15-minute intervals in a shift
}

// Fill N-hour work intervals with no breaks
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
// Output for checking
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
// Output the FTE forecast side-by-side
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

// We obtained an array of shifts from which we will build the schedule
// Of course, in real tasks you would pull shift boundaries and start times from the DB
// Shift boundaries must be converted into arrays of zeros and ones with 15-minute resolution
// These shifts will be consumed by phantoms, of which we have an infinite supply
// Phantoms are not constrained by “between-shift” rules
// "Between-shifts" rules will be checked when assigning real people to phantom positions
// At the first stage we hand off the hardest checks to the users partially

// The phantoms array contains inner arrays of phantoms
// with dimensions equal to the number of shift types — that's the number of shifts taken for the schedule
$phantoms = [];
// Fill with default values
for($k=0; $k<$shiftKinds; $k++){
    for ($i = 0; $i < $dimension[$k]; $i++) {
        $phantoms[$k][] = 0;
    }
}

print_r('<br>');

// Create a copy of the forecasted FTE array and zero it to see how many FTEs we scheduled
// We will use the new array to check conformity with the forecast
$phantomsScheduled = $agentsNeeded;
for ($j = 0; $j < $dayQuarters; $j++) {
    $phantomsScheduled[$j] = 0;
}

// Array of differences in FTE: "required minus scheduled"
// "Scheduled" is already zeroed, so just copy
$difference = $phantomsScheduled;

for($j = 0; $j < $dayQuarters; $j++) {
    $difference[$j] = $agentsNeeded[$j] - $phantomsScheduled[$j];
}

// Here is the main processing!

// Suppose we add one phantom at a time in turns
// Not guaranteed to be an optimal algorithm
// Also, because of quantity constraints, problems may arise here

print_r('difference:<br>');
for($k = 0; $k < $dayQuarters; $k++) {
    print_r($difference[$k]);
    if(strlen($difference[$k]) == 1){
        print_r(' ');
    } elseif (strlen($difference[$k]) == 2){
        print_r('');
    }
}

print_r('<br><br>');

for($j = 0; $j < $dayQuarters; $j++){ // Iterate by 15-minute intervals of the day
    $count=0; // Counter to prevent an infinite loop
    while ($difference[$j] > 0 && $count<100){ // Until the gap is filled, but only at the points where it is possible!
        for($t=0; $t<$shiftKinds; $t++){ // Iterate by shift types // TODO: here we could choose shift type randomly rather than sequentially
            for ($i=0; $i<$dimension[$t]; $i++){ // Iterate over all start times of each shift type
                if ($difference[$j] > 0 && $j == $startFillingPoints[$t][$i]){

                    $phantoms[$t][$i] = $phantoms[$t][$i] + 1; // Add one shift of this type on this iteration
                    // Recalculate $phantomsScheduled and $difference
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
            // This is needed in a situation when, for example, 3 operators are required, but available shift lengths are 2 or 4
            // No need to go 2 by 2 or all 4 — should break earlier
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
    if(strlen($difference[$k]) == 1){
        print_r(' ');
    } elseif (strlen($difference[$k]) == 2){
        print_r('');
    }
}
print_r('<br><br>');

/*

for($i = 0; $i < $dimension; $i++){
    for($j = 0; $j < $dayQuarters; $j++){
        // Important condition!
        // $difference > 0 ? If yes, then add as many shifts as the difference
        // Shifts can only be added at certain points, so we check the difference only at those points
        if($difference[$j] > 0 && $j == $startFillingPoints[$i]) {
            $phantoms[$i] = $phantoms[$i] + $difference[$j];
            // Recalculate $phantomsScheduled and $difference
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

print_r('How many phantoms were needed in the end<br>');
var_dump($phantoms);

print_r('<br>');
// Check the number of phantom FTEs
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
// Check array of differences between forecast and assembled schedule
print_r('difference:<br>');
for($k = 0; $k < $dayQuarters; $k++) {
    //$difference[$k] = $agentsNeeded[$k] - $phantomsScheduled[$k];
    print_r($difference[$k]);
    if(strlen($difference[$k]) == 1){
        print_r(' ');
    } elseif (strlen($difference[$k]) == 2){
        print_r('');
    }
    //print_r('<br>');
}
// Verify SL for the day.
// Output the final SL

// Preparatory helper functions for SL calculation
// $fc - call forecast for 15 minutes
// $agents - actual number of agents, not forecasted
// $aht - average handling time in seconds
function ErlangSL($fc,$agents,$aht) {
    if($aht == 0){
        $SL = 0; // Since weighted SL calculation multiplies by the number of calls
    } else {
        $fc = $fc / 15; // Forecast of calls per 1 minute
        $beta = $aht / 60; // Convert AHT from seconds to minutes
        $a = $beta * $fc; // Number of agent-minutes required to handle incoming calls
        $tta = 20 / 60; // Target SL = 80% within 20 seconds, convert 20 seconds to minutes
        $tempExp = -($agents/$beta - $fc)*$tta;
        $SL = 1 - C($agents, $a) * exp($tempExp);
        if ($SL<0) {
            $SL = 0;
        }
    }
    return $SL;
}

// Helper function to compute SL
function C($s,$a) {
    $denominator = Factorial($s-1)*($s-$a); // Denominator
    if($denominator <> 0){ /// Sometimes denominator becomes zero; in that case SL should be reduced to zero
        $firstStep = pow($a,$s)/$denominator;
        $secondStep = 0;
        for ($j=0; $j<=$s-1; $j++){
            $secondStep = $secondStep + (pow($a,$j) / Factorial($j));
        }
        $secondStep = $secondStep + $firstStep;
        $c = $firstStep / $secondStep;
    } else { // With such a value, the SL computed above will be zero
        $c = 1;
    }
    return $c;
}

// Factorial function
function Factorial($x) {
    $y = 1;
    for ($i = 1; $i < $x; $i++) {
        $y = $y*($i+1);
    }
    return $y;
}

// Calculate the weighted average SL by forecasted call volumes
// Raw SL on each interval
$shiftsSL = [];
for ($j = 0; $j < $dayQuarters; $j++) {
    $shiftsSL[$j] = ErlangSL($forecast[$j], $phantomsScheduled[$j], $ahtSeconds[$j]);
}
// We "weight" SL by the forecasted number of calls
$sumSLWeighted = 0;
$sumForecast = 0;
for ($j = 0; $j < $dayQuarters; $j++) {
    $sumSLWeighted = $sumSLWeighted + $forecast[$j] * $shiftsSL[$j];
    $sumForecast = $sumForecast + $forecast[$j];
}
$averageWeightedSL = $sumSLWeighted / $sumForecast;
// Convert to percentage representation
$averageWeightedSL = round($averageWeightedSL * 100,2,PHP_ROUND_HALF_UP) . '%';

print_r('<br>');
print_r('<br>Resulting SL after the first step = ');
print_r($averageWeightedSL);
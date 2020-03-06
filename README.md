# LSurveyParser

LSurveyParser is a high-performance parser written in pure PHP, capable of parsing the surveys created and managed 
with LimeSurvey (https://www.limesurvey.org).

## Table of Contents

- [About The Project](#about-the-project)
- [Getting Started](#getting-started)
- [Prerequisites](#prerequisites)
    - [Installation](#installation)
    - [Usage](#usage)
- [Documentation](#documentation)
- [Contributing](#contributing)
- [Authors](#authors)
- [Disclaimer](#disclaimer)
- [License](#license)

## About The Project

Among other functionalities, LimeSurvey exposes JSON-RPC APIs, including the `export_responses` which exports the 
survey responses. However, this API is particularly slow and consumes a lot of memory.

LSurveyParser parses the surveys in a really shorter time and with much less memory consumption when compared with the 
official API `export_responses`. The following table makes a comparison between the time and memory consumption of 
LSurveyParser and `export_responses`:

- <details>
    <summary>Spoiler</summary>

    | RESPONSE COUNT 	| PARSER TIME (ms) 	| export_responses TIME (ms) 	    | PARSER MEMORY (bytes) 	| export_responses MEMORY (bytes) 	|
    |---------------:	|-----------------:	|-------------------------------:	|----------------------:	|--------------------------------:	|
    |              0 	|               83 	|                            196 	|               760.032 	|                         424.888 	|
    |              0 	|              105 	|                            348 	|               844.072 	|                         426.392 	|
    |              0 	|              105 	|                            185 	|               844.560 	|                         426.768 	|
    |              0 	|               38 	|                            180 	|               630.432 	|                         427.144 	|
    |              0 	|               82 	|                            187 	|               762.400 	|                         427.520 	|
    |              0 	|               80 	|                            187 	|               764.200 	|                         428.272 	|
    |              0 	|               39 	|                            143 	|               632.632 	|                         429.344 	|
    |              0 	|              106 	|                            144 	|               846.672 	|                         429.720 	|
    |              0 	|               73 	|                            194 	|               950.992 	|                         435.624 	|
    |              0 	|              147 	|                            145 	|             3.239.144 	|                         448.560 	|
    |              0 	|              119 	|                            181 	|               897.880 	|                         449.688 	|
    |              0 	|              121 	|                            195 	|               902.568 	|                         451.192 	|
    |              1 	|               85 	|                            448 	|               826.656 	|                         465.904 	|
    |              2 	|               40 	|                            332 	|               664.184 	|                         465.192 	|
    |              2 	|              123 	|                            519 	|               946.784 	|                         511.000 	|
    |              3 	|               96 	|                            655 	|               838.288 	|                         483.184 	|
    |              3 	|               80 	|                            493 	|             1.457.416 	|                         489.912 	|
    |              6 	|               41 	|                            289 	|               652.592 	|                         489.496 	|
    |              6 	|              247 	|                          1.353 	|             1.505.064 	|                       1.671.072 	|
    |              7 	|               76 	|                            474 	|             1.470.904 	|                         574.888 	|
    |             13 	|              107 	|                            396 	|               927.320 	|                         864.720 	|
    |             14 	|               55 	|                            360 	|               716.160 	|                         577.664 	|
    |             20 	|               75 	|                            452 	|             1.013.448 	|                         753.040 	|
    |             21 	|               68 	|                            478 	|               726.640 	|                         682.392 	|
    |             21 	|               21 	|                            276 	|               598.872 	|                         491.008 	|
    |             21 	|              108 	|                            632 	|             1.077.944 	|                       1.553.376 	|
    |             21 	|               40 	|                            348 	|               693.776 	|                         664.248 	|
    |             23 	|               66 	|                            325 	|               749.000 	|                         747.024 	|
    |             23 	|               75 	|                            449 	|               822.704 	|                         777.016 	|
    |             27 	|               77 	|                            484 	|             1.453.648 	|                         839.160 	|
    |             33 	|              134 	|                            687 	|             1.429.504 	|                         484.008 	|
    |             33 	|              145 	|                            782 	|             1.049.928 	|                       2.658.192 	|
    |             34 	|               69 	|                            476 	|               756.104 	|                       1.688.728 	|
    |             41 	|               40 	|                            354 	|               698.432 	|                         802.608 	|
    |             45 	|               43 	|                            353 	|               704.544 	|                         891.552 	|
    |             46 	|               26 	|                            260 	|               657.808 	|                         707.048 	|
    |             50 	|               75 	|                            471 	|               830.352 	|                       1.072.864 	|
    |             53 	|                3 	|                            230 	|               557.152 	|                         506.256 	|
    |             53 	|                3 	|                            245 	|               566.416 	|                         518.064 	|
    |             54 	|               84 	|                            434 	|             1.025.320 	|                       1.211.520 	|
    |             58 	|               79 	|                            433 	|               785.992 	|                       1.114.344 	|
    |             59 	|              123 	|                            610 	|               979.136 	|                       2.426.280 	|
    |             61 	|                3 	|                            243 	|               594.088 	|                         548.808 	|
    |             69 	|               87 	|                            496 	|               841.896 	|                       1.541.688 	|
    |             71 	|               65 	|                            456 	|               791.448 	|                       1.269.288 	|
    |             73 	|               79 	|                            377 	|               841.800 	|                       1.779.400 	|
    |             73 	|               79 	|                            490 	|               794.192 	|                       1.413.960 	|
    |             77 	|               96 	|                            498 	|               914.848 	|                       1.651.304 	|
    |             78 	|               87 	|                            594 	|               789.008 	|                       2.296.624 	|
    |             84 	|               98 	|                            507 	|               923.680 	|                       1.814.096 	|
    |             91 	|              122 	|                            756 	|               996.808 	|                       3.509.488 	|
    |             91 	|               43 	|                            276 	|               713.296 	|                       1.385.328 	|
    |             94 	|               24 	|                            354 	|               680.896 	|                       1.160.536 	|
    |             94 	|               25 	|                            262 	|               685.752 	|                       1.160.576 	|
    |             99 	|               81 	|                            662 	|               786.024 	|                       2.753.752 	|
    |            100 	|               27 	|                            334 	|               649.648 	|                         995.872 	|
    |            101 	|               82 	|                            506 	|               839.272 	|                       2.025.248 	|
    |            131 	|               72 	|                            515 	|               760.280 	|                       2.122.032 	|
    |            143 	|               96 	|                            507 	|               924.512 	|                       2.828.048 	|
    |            145 	|               65 	|                            511 	|               848.664 	|                       3.373.528 	|
    |            156 	|              107 	|                            452 	|             1.184.960 	|                       3.071.216 	|
    |            160 	|               44 	|                            429 	|               749.840 	|                       2.154.248 	|
    |            178 	|               46 	|                            593 	|               716.816 	|                       2.231.656 	|
    |            195 	|              130 	|                            855 	|             1.043.152 	|                       7.003.752 	|
    |            200 	|               70 	|                            582 	|             1.111.144 	|                       3.745.888 	|
    |            240 	|              106 	|                            653 	|               932.224 	|                       4.414.624 	|
    |            271 	|               89 	|                            773 	|               944.888 	|                       5.721.288 	|
    |            326 	|              100 	|                            794 	|             1.213.872 	|                       5.724.816 	|
    |            357 	|               32 	|                            467 	|             1.010.432 	|                       2.097.392 	|
    |            390 	|               49 	|                            569 	|               817.504 	|                       4.128.344 	|
    |            392 	|               86 	|                            831 	|             1.172.440 	|                       6.135.248 	|
    |            496 	|               38 	|                            281 	|               900.672 	|                         943.112 	|
    |            514 	|              100 	|                            966 	|             1.388.240 	|                       9.045.736 	|
    |            544 	|               94 	|                            745 	|               934.240 	|                       7.347.288 	|
    |            552 	|               59 	|                            605 	|               885.928 	|                       6.092.704 	|
    |            632 	|               94 	|                          1.091 	|             1.280.216 	|                      10.160.216 	|
    |            712 	|               62 	|                            994 	|               948.640 	|                       9.929.800 	|
    |            765 	|               48 	|                            696 	|             1.352.104 	|                       4.554.704 	|
    |            867 	|               85 	|                          1.318 	|             1.328.744 	|                      11.184.264 	|
    |            945 	|               12 	|                            441 	|               675.328 	|                       2.294.080 	|
    |          1.123 	|               52 	|                            746 	|             2.063.456 	|                       4.760.184 	|
    |          1.143 	|              147 	|                          1.439 	|             1.128.720 	|                      19.970.408 	|
    |          1.450 	|               46 	|                            764 	|               879.816 	|                       8.837.400 	|
    |          1.454 	|               68 	|                          1.376 	|               927.656 	|                      28.367.656 	|
    |          1.819 	|              111 	|                          1.723 	|             1.915.984 	|                      26.132.592 	|
    |          2.312 	|              160 	|                          2.069 	|             1.315.192 	|                      34.081.568 	|
    |          2.992 	|               75 	|                          1.232 	|             2.845.704 	|                      11.875.384 	|
    |          3.025 	|            2.554 	|                          4.346 	|             3.382.568 	|                      75.857.088 	|
    |          3.172 	|              137 	|                          2.630 	|             2.240.584 	|                      40.756.584 	|
    |          5.234 	|              368 	|                          8.279 	|             2.175.632 	|                     173.548.800 	|
    |          6.941 	|              142 	|                          3.712 	|             2.994.312 	|                      57.093.688 	|
    |          7.201 	|              253 	|                          6.696 	|             3.959.760 	|                     158.925.104 	|
    |          8.081 	|              204 	|                          5.609 	|             2.298.552 	|                      83.790.320 	|
    |          9.051 	|              201 	|                          4.601 	|             4.343.944 	|                      74.257.064 	|
    |         14.169 	|              440 	|                         15.770 	|             6.271.992 	|                     334.219.248 	|
    |         21.957 	|              496 	|                          9.484 	|             9.692.344 	|                     180.580.880 	|
    |         23.612 	|              651 	|                         15.922 	|             4.398.496 	|                     365.702.008 	|
    |                	|                  	|                                	|                       	|                                 	|
    |      **TOTAL** 	|       **12.189**  |                    **125.260** 	|       **127.059.880** 	|               **1.852.752.800** 	|
</details>

As you can appreciate from the table, LSurveyParser takes __in total__ approximately 12 seconds to parse 98 
different questionnaires, compared to the 125 seconds ca. of the `export_responses` API. In addition, LSurveyParser 
takes __in total__ approximately 127 MB of memory to parse 98 different questionnaires against the about 1.852 MB used 
by the `export_responses` API.

The server used to perform the previous benchmarks was configured as follows:

- Debian stable 10.3
- LimeSurvey 3.22.7
- PHP 7.3
- MariaDB 10.3.22
- nginx 1.14.2

It has to be noted LSurveyParser returns the responses in an aggregated form, while `export_responses` API gives the 
responses back as they are organized in the database. At the same time, `export_responses` API does not return any 
information on the survey structure and the characteristics of the questions and subquestions, information available on 
LSurveyParser.

- <details>
    <summary>LSurveyParser output example</summary>
    
    ```json
    {
      "Group 1": {
        "234479X1478X17397": {
          "code": "q23",
          "text": "Et harum quidem rerum facilis Q23?",
          "type": "L",
          "mandatory": false,
          "numonly": false,
          "hidden": false,
          "answers": {
            "A1": {
              "text": "At",
              "N": 0,
              "%": 0
            },
            "A2": {
              "text": "vero",
              "N": 0,
              "%": 0
            },
            "A3": {
              "text": "eos",
              "N": 0,
              "%": 0
            },
            "A4": {
              "text": "et",
              "N": 0,
              "%": 0
            },
            "A5": {
              "text": "accusamus",
              "N": 0,
              "%": 0
            },
            "A6": {
              "text": "et",
              "N": 1,
              "%": 33.33
            },
            "A7": {
              "text": "iusto",
              "N": 0,
              "%": 0
            },
            "A8": {
              "text": "odio",
              "N": 1,
              "%": 33.33
            },
            "A9": {
              "text": "dignissimos",
              "N": 0,
              "%": 0
            },
            "A10": {
              "text": "ducimus",
              "N": 0,
              "%": 0
            },
            "A11": {
              "text": "qui",
              "N": 0,
              "%": 0
            },
            "A12": {
              "text": "blanditiis",
              "N": 0,
              "%": 0
            },
            "-oth-": {
              "text": "Other",
              "N": 1,
              "%": 33.33
            },
            "_X_": {
              "text": "Total",
              "N": 3,
              "%": 100
            }
          }
        },
        "234479X1478X17397other": {
          "code": "q23[other]",
          "text": "Et harum quidem rerum facilis Q23? [Other]",
          "type": "L",
          "mandatory": false,
          "numonly": false,
          "hidden": false,
          "answers": [
            "mjhkhj"
          ]
        }
      },
      "Group 2": {
        "234479X1480X17455": {
          "code": "q31",
          "text": "Sed ut perspiciatis unde omnis Q31?",
          "type": "S",
          "mandatory": false,
          "numonly": false,
          "hidden": false,
          "answers": [
            "mnbvcxz",
            "tyuio",
            "plmnhtfcxawdv"
          ]
        }
      }
    }
    ```
</details>

To see more examples of LSurveyParser outputs, please refer to the `.json` files available 
in the `examples/output` folder.

## Getting Started

Thanks to these instructions, you can get a copy of the project up and run on your local machine for 
development and testing purposes.

### Prerequisites

- LimeSurvey 3.x (https://github.com/LimeSurvey/LimeSurvey)
- PHP **7**.x

It should also work on LimeSurvey 2.x and 4.x, however it was not possible to test it.

### Installation

To include this parser in your PHP project, please follow the instructions below:

```bash
cd <YOUR_PROJECT_DIRECTORY>
git clone https://github.com/MrTiz9/LSurveyParser.git
```

Now you have to setup the `.php` configuration file in the `config` folder:

```php
/** DBMS hostname (i.e 'localhost') */
const DB_HOSTNAME   = 'localhost';

/** DBMS port */
const DB_PORT       = 3306;

/** Username to be used */
const DB_USERNAME   = '';

/** User password */
const DB_PASSWORD   = '';

/** PDO driver (https://www.php.net/manual/en/pdo.drivers.php) */
const DB_DRIVER     = 'mysql';

/** LimeSurvey database name */
const DB_NAME       = 'limesurvey';

/** LimeSurvey table prefix */
const TABLE_PREFIX  = 'lime_';

/** Charset
 *
 * NOTE: LimeSurvey uses the charset 'utf8mb4' by default,
 *       for this reason you should not change this value.
 */
const DB_CHARSET    = 'utf8mb4';

/** PDO attributes (optional but recommended) */
const DB_ATTRIBUTES = [
    /**
     * For security reasons and if you are using MySQL,
     * you should not remove this attribute.
     */
    PDO::MYSQL_ATTR_MULTI_STATEMENTS => false,

    /**
     * This is very useful, however you would probably like
     * to disable this setting when in production.
     */
    PDO::ERRMODE_EXCEPTION,

    /**
     * If the PDO::ATTR_EMULATE_PREPARES is enabled you can get a better performance,
     * but make sure the used driver supports the native prepared statements.
     */
    PDO::ATTR_EMULATE_PREPARES => true
];
```

## Usage

```php
<?php

require '../LSurveyParser/src/ILSurveyParser.php';

$sid    = 234479;
$tokens = ['K2rxGkU589E2Wx5', 'RW6nuxj9nm7J8N3'];
$parsed = [];

try {
    $parser = new LSurveyParser\Parser();
    $parsed = $parser->parseQuestionsByTokens($sid, $tokens);
}
catch (Exception $e) {
    die($e->getMessage());
}

print_r($parsed);
```

You can parse the surveys using the following public functions offered by LSurveyParser:

```php
/* Parse the questions only, excluding the answers */
getOnlyQuestions($sid, $lang = null, $qList = [], $qToExcl = []);

/* Parse the survey responses using the IDs */
parseQuestionsByIDs($sid, $IDs, $lang = null, $qList = [], $qToExcl = []);

/* Parse the survey responses using the tokens */
parseQuestionsByTokens($sid, $tokens, $lang = null, $qList = [], $qToExcl = []);

/* Parse the survey responses using the dates */
parseQuestionsByDates($sid, $from, $to, $lang = null, $qList = [], $qToExcl = []);
```

For further usage examples, please refer to the `.php` script in the `examples` directory.

## Documentation

The parser documentation is available on the `doc` folder, in particular we recommend the file `doc/html/index.html`.

## Contributing

Contributions are what make the open source community such a good place to learn, inspire, and create. 
Any contributions you can provide are **greatly appreciated**.

1. Fork the Project
2. Create your Feature Branch (`git checkout -b feature/AmazingFeature`)
3. Commit your Changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the Branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

## Authors

- **[Tiziano Marra](https://github.com/MrTiz9)**

## Disclaimer
[LimeSurvey](https://www.limesurvey.org/) is not associated with, nor it does not endorse nor sponsor this parser. 
I'm not affiliated with the authors of LimeSurvey or with the community of LimeSurvey. 
This is just an attempt to have a parser that parses the surveys in more efficient way.

## License
[![License: GPL v3](https://img.shields.io/badge/License-GPLv3-blue.svg)](https://www.gnu.org/licenses/gpl-3.0)

This project is licensed under the GNU General Public License v3.0 - see the 
[LICENSE](https://github.com/MrTiz9/LSurveyParser/blob/master/LICENSE) file for details.

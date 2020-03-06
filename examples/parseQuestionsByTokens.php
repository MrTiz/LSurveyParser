<?php
/**
 * @Copyright (c) 2020 Tiziano Marra <https://github.com/MrTiz9>.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. if not, see <https://www.gnu.org/licenses/>.
 *
 * @author  Tiziano Marra <https://github.com/MrTiz9>
 * @version 1.0
 */

/** Uncomment the line below to execute the following code */
//define('LSPARSER_TEST', true);

/** Prevent external direct access */
if (!defined('LSPARSER_TEST')) {
    header($_SERVER['SERVER_PROTOCOL'] . ' 404 Not Found', true, 404);
    exit();
}

defined('PATH')
    or define('PATH', realpath(dirname(__DIR__)));

require PATH . '/src/ILSurveyParser.php';

$sid    = 234479;
$tokens = ['K2rxGkU589E2Wx5', 'RW6nuxj9nm7J8N3'];

$parsed = [];

try {
    $parser = new LSurveyParser\Parser();
    $parsed = $parser->parseQuestionsByTokens($sid, $tokens);
} catch (Exception $e) {
    header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error', true, 500);
    $parsed = $e->getMessage();
}

header('Content-Type: application/json');
echo json_encode($parsed, JSON_PRETTY_PRINT);

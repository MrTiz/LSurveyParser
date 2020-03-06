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

namespace LSurveyParser;

/**
 * Absolute path to the current working directory
 */
defined('PATH')
    or define('PATH', realpath(dirname(__DIR__)));

require PATH . '/config/config.php';
require PATH . '/src/Parser/Parser.php';
require PATH . '/src/Exceptions/LSurveyParserException.php';
require PATH . '/src/Exceptions/SurveyException.php';
require PATH . '/src/Database/Database.php';

/**
 * Interface ILSurveyParser
 * @package LSurveyParser
 *
 * @author  Tiziano Marra <https://github.com/MrTiz9>
 * @since   1.0
 */

interface ILSurveyParser
{
    /**
     * Parse the questions only, excluding the answers.
     *
     * @author  Tiziano Marra <https://github.com/MrTiz9>
     * @since   1.0
     *
     * @param int    $sid       Survey ID
     * @param string $lang      (Optional) Survey language
     * @param array  $qList     (Optional) List of questions to be considered
     * @param array  $qToExcl   (Optional) List of questions to be excluded
     *
     * @return array
     * @throws SurveyException
     */

    public function getOnlyQuestions($sid, $lang = null, $qList = [], $qToExcl = []);

    /* **************************************** */

    /**
     * Parse the survey responses using the IDs.
     *
     * @author  Tiziano Marra <https://github.com/MrTiz9>
     * @since   1.0
     *
     * @param int    $sid       Survey ID
     * @param array  $IDs       List of IDs
     * @param string $lang      (Optional) Survey language
     * @param array  $qList     (Optional) List of questions to be considered
     * @param array  $qToExcl   (Optional) List of questions to be excluded
     *
     * @return array
     * @throws SurveyException
     */

    public function parseQuestionsByIDs($sid, $IDs, $lang = null, $qList = [], $qToExcl = []);

    /* **************************************** */

    /**
     * Parse the survey responses using the tokens.
     *
     * @author  Tiziano Marra <https://github.com/MrTiz9>
     * @since   1.0
     *
     * @param int    $sid       Survey ID
     * @param array  $tokens    List of tokens
     * @param string $lang      (Optional) Survey language
     * @param array  $qList     (Optional) List of questions to be considered
     * @param array  $qToExcl   (Optional) List of questions to be excluded
     *
     * @return array
     * @throws SurveyException
     */

    public function parseQuestionsByTokens($sid, $tokens, $lang = null, $qList = [], $qToExcl = []);

    /* **************************************** */

    /**
     * Parse the survey responses using the dates.
     *
     * @author  Tiziano Marra <https://github.com/MrTiz9>
     * @since   1.0
     *
     * @param int    $sid       Survey ID
     * @param string $from      Start date
     * @param string $to        End date
     * @param string $lang      (Optional) Survey language
     * @param array  $qList     (Optional) List of questions to be considered
     * @param array  $qToExcl   (Optional) List of questions to be excluded
     *
     * @return array
     * @throws SurveyException
     */

    public function parseQuestionsByDates($sid, $from, $to, $lang = null, $qList = [], $qToExcl = []);

    /* **************************************** */

    /**
     * Set the cutoff.
     *
     * @author  Tiziano Marra <https://github.com/MrTiz9>
     * @since   1.0
     *
     * @param int $cutoff
     */

    public function setCutoff($cutoff = 0);

    /* **************************************** */

    /**
     * Set column name that should be used to get response IDs using dates.
     *
     * @author  Tiziano Marra <https://github.com/MrTiz9>
     * @since   1.0
     *
     * @param string $dateCol   Column name
     */

    public function setDateCol($dateCol = 'submitdate');

    /* **************************************** */

    /**
     * Strip the HTML tags from the questions and answers texts.
     *
     * @author  Tiziano Marra <https://github.com/MrTiz9>
     * @since   1.0
     *
     * @param bool $stripTags
     */

    public function setStripTags($stripTags = true);
}

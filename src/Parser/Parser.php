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

use DateTime;
use Exception;
use PDO;
use PDOException;

/**
 * Class Parser
 * @package Parser
 *
 * @author  Tiziano Marra <https://github.com/MrTiz9>
 * @since   1.0
 */

class Parser implements ILSurveyParser
{
    /** @var int|null Survey ID */
    private $sid         = null;
    /** @var int      Cutoff */
    private $cutoff      = 0;
    /** @var string   Survey language */
    private $lang        = 'en';
    /** @var array    Questions to be excluded */
    private $qToExcl     = [];
    /** @var string   Column name that should be used to get response IDs using dates */
    private $dateCol     = 'submitdate';
    /** @var bool     Strip the HTML tags from the questions and answers texts */
    private $stripTags   = true;

    /** @var Database|null LSurveyParser\Database instance */
    private $db            = null;
    /** @var string        LimeSurvey database name */
    private $dbName        = DB_NAME;
    /** @var string        LimeSurvey table prefix */
    private $tablePrefix   = TABLE_PREFIX;
    /** @var array         Response IDs */
    private $IDs           = [];
    /** @var string        'Imploded' response IDs */
    private $implodedIDs   = '';
    /** @var array         Questions to be considered */
    private $qList         = [];
    /** @var bool          Parse only questions? */
    private $questionsOnly = false;

    /** @var array    The output returned by the 'parseQuestions()' function */
    private $output = [];

    /** @var array    Auxiliary array */
    private $total = [
        'text' => 'Total',
        'N'    => 0,
        '%'    => 100
    ];

    /* **************************************** */

    /**
     * Parser constructor.
     *
     * @author  Tiziano Marra <https://github.com/MrTiz9>
     * @since   1.0
     *
     * @throws PDOException|SurveyException
     */

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /* **************************************** */

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

    public function getOnlyQuestions($sid, $lang = null, $qList = [], $qToExcl = [])
    {
        if (!$this->checkValidSid($sid)) {
            throw new SurveyException('Survey not found or inactive');
        }

        if ($lang !== null && !$this->checkValidLanguage($sid, $lang)) {
            throw new SurveyException('Invalid language for this survey');
        }

        if (!$this->checkValidQlist($sid, $qList)) {
            throw new SurveyException('Invalid list of questions to be considered for this survey');
        }

        if (!$this->checkValidQlist($sid, $qToExcl)) {
            throw new SurveyException('Invalid list of questions to be excluded for this survey');
        }

        $this->sid           = $sid;
        $this->lang          = $lang    ?? $this->getDefaultLanguage();
        $this->qList         = $qList   ?? [];
        $this->qToExcl       = $qToExcl ?? [];
        $this->questionsOnly = true;

        return $this->parseQuestions();
    }

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

    public function parseQuestionsByIDs($sid, $IDs, $lang = null, $qList = [], $qToExcl = [])
    {
        if (!$this->checkValidSid($sid)) {
            throw new SurveyException('Survey not found or inactive');
        }

        if (!$this->checkValidIDs($IDs)) {
            throw new SurveyException('Invalid IDs list');
        }

        if ($lang !== null && !$this->checkValidLanguage($sid, $lang)) {
            throw new SurveyException('Invalid language for this survey');
        }

        if (!$this->checkValidQlist($sid, $qList)) {
            throw new SurveyException('Invalid list of questions to be considered for this survey');
        }

        if (!$this->checkValidQlist($sid, $qToExcl)) {
            throw new SurveyException('Invalid list of questions to be excluded for this survey');
        }

        $this->sid     = $sid;
        $this->IDs     = $IDs;
        $this->lang    = $lang    ?? $this->getDefaultLanguage();
        $this->qList   = $qList   ?? [];
        $this->qToExcl = $qToExcl ?? [];

        return $this->parseQuestions();
    }

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

    public function parseQuestionsByTokens($sid, $tokens, $lang = null, $qList = [], $qToExcl = [])
    {
        if (!$this->checkValidSid($sid)) {
            throw new SurveyException('Survey not found or inactive');
        }

        if (!$this->checkValidTokens($tokens)) {
            throw new SurveyException('Invalid tokens list');
        }

        if ($this->checkAnonymousSurvey($sid)) {
            throw new SurveyException('You can\'t using tokens to parse a anonymous survey');
        }

        if ($lang !== null && !$this->checkValidLanguage($sid, $lang)) {
            throw new SurveyException('Invalid language for this survey');
        }

        if (!$this->checkValidQlist($sid, $qList)) {
            throw new SurveyException('Invalid list of questions to be considered for this survey');
        }

        if (!$this->checkValidQlist($sid, $qToExcl)) {
            throw new SurveyException('Invalid list of questions to be excluded for this survey');
        }

        $this->sid     = $sid;
        $this->IDs     = $this->getIDsByTokens($tokens);
        $this->lang    = $lang    ?? $this->getDefaultLanguage();
        $this->qList   = $qList   ?? [];
        $this->qToExcl = $qToExcl ?? [];

        return $this->parseQuestions();
    }

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

    public function parseQuestionsByDates($sid, $from, $to, $lang = null, $qList = [], $qToExcl = [])
    {
        if (!$this->checkValidSid($sid)) {
            throw new SurveyException('Survey not found or inactive');
        }

        if (!$this->checkDatestampSurvey($sid)) {
            throw new SurveyException('Survey without datestamp');
        }

        $dStart = $this->checkValidDate($from);
        $dEnd   = $this->checkValidDate($to);

        if (empty($dStart) || empty($dEnd)) {
            throw new SurveyException('Invalid dates');
        }

        if ($lang !== null && !$this->checkValidLanguage($sid, $lang)) {
            throw new SurveyException('Invalid language for this survey');
        }

        if (!$this->checkValidQlist($sid, $qList)) {
            throw new SurveyException('Invalid list of questions to be considered for this survey');
        }

        if (!$this->checkValidQlist($sid, $qToExcl)) {
            throw new SurveyException('Invalid list of questions to be excluded for this survey');
        }

        $this->sid     = $sid;
        $this->IDs     = $this->getIDsByDate($dStart, $dEnd);
        $this->lang    = $lang    ?? $this->getDefaultLanguage();
        $this->qList   = $qList   ?? [];
        $this->qToExcl = $qToExcl ?? [];

        return $this->parseQuestions();
    }

    /* **************************************** */

    /**
     * Set the cutoff.
     *
     * @author  Tiziano Marra <https://github.com/MrTiz9>
     * @since   1.0
     *
     * @param int $cutoff
     */

    public function setCutoff($cutoff = 0)
    {
        $this->cutoff = (int)$cutoff;
    }

    /* **************************************** */

    /**
     * Set column name that should be used to get response IDs using dates.
     *
     * @author  Tiziano Marra <https://github.com/MrTiz9>
     * @since   1.0
     *
     * @param string $dateCol   Column name
     */

    public function setDateCol($dateCol = 'submitdate')
    {
        $this->dateCol = $dateCol === 'submitdate' ||
                         $dateCol === 'startdate'  ||
                         $dateCol === 'datestamp'   ? $dateCol : 'submitdate';
    }

    /* **************************************** */

    /**
     * Strip the HTML tags from the questions and answers texts.
     *
     * @author  Tiziano Marra <https://github.com/MrTiz9>
     * @since   1.0
     *
     * @param bool $stripTags
     */

    public function setStripTags($stripTags = true)
    {
        $this->stripTags = (bool)$stripTags;
    }

    /* **************************************** */

    /**
     * Parse all types of known questions.
     *
     * @author  Tiziano Marra <https://github.com/MrTiz9>
     * @since   1.0
     *
     * @internal
     *
     * @return array    Parsed survey
     */

    private function parseQuestions()
    {
        $this->output = [];

        $this->implodedIDs = empty($this->IDs) ? 0 : implode(', ', $this->IDs);
        $questions = $this->getInfoQuestions();

        if (!empty($questions)) {
            $qids     = array_column($questions, 'qid');
            $qAttribs = $this->getQuestionsAttributes($qids);

            foreach ($questions as $i => $qInfo) {
                $sgq   = $this->sid . 'X' . $qInfo['gid'] . 'X' . $qInfo['qid'];
                $gn    = $qInfo['group_name'];
                $qAttr = $qAttribs[$qInfo['qid']];

                switch ($qInfo['type']) {
                    /* **************************************** */
                    /* ARRAYS */

                    case 'F':   // Array
                        $this->parseFQuestion($sgq, $qInfo, $gn, $qAttr);
                        break;

                    case 'A':   // Array (5 point choice)
                        $this->parseAQuestion($sgq, $qInfo, $gn, $qAttr);
                        break;

                    case 'B':   // Array (10 point choice)
                        $this->parseBQuestion($sgq, $qInfo, $gn, $qAttr);
                        break;

                    case 'C':   // Array (Yes/No/Uncertain)
                        $this->parseCQuestion($sgq, $qInfo, $gn, $qAttr);
                        break;

                    case 'E':   // Array (Increase/Same/Decrease)
                        $this->parseEQuestion($sgq, $qInfo, $gn, $qAttr);
                        break;

                    case 'H':   // Array by column
                        $this->parseHQuestion($sgq, $qInfo, $gn, $qAttr);
                        break;

                    case '1':   // Array dual scale
                        $this->parse1Question($sgq, $qInfo, $gn, $qAttr);
                        break;

                    case ':':   // Array (Numbers)
                        $this->parseColonQuestion($sgq, $qInfo, $gn, $qAttr);
                        break;

                    case ';':   // Array (Texts)
                        $this->parseSemicolonQuestion($sgq, $qInfo, $gn, $qAttr);
                        break;

                    /* **************************************** */
                    /* MASK QUESTIONS */

                    case 'D':   // Date/Time
                        $this->parseDQuestion($sgq, $qInfo, $gn, $qAttr);
                        break;

                    case '|':   // File upload
                        $this->parsePipeQuestion($sgq, $qInfo, $gn, $qAttr);
                        break;

                    case 'G':   // Gender
                        $this->parseGQuestion($sgq, $qInfo, $gn, $qAttr);
                        break;

                    case 'I':   // Language switch
                        $this->parseIQuestion($sgq, $qInfo, $gn, $qAttr);
                        break;

                    case 'N':   // Numerical input
                        $this->parseNQuestion($sgq, $qInfo, $gn, $qAttr);
                        break;

                    case 'K':   // Multiple numerical input
                        $this->parseKQuestion($sgq, $qInfo, $gn, $qAttr);
                        break;

                    case 'R':   // Ranking
                        $this->parseRQuestion($sgq, $qInfo, $gn, $qAttr);
                        break;

                    case 'X':   // Text display
                        $this->parseXQuestion($sgq, $qInfo, $gn, $qAttr);
                        break;

                    case 'Y':   // Yes/No
                        $this->parseYQuestion($sgq, $qInfo, $gn, $qAttr);
                        break;

                    case '*':   // Equation
                        $this->parseStarQuestion($sgq, $qInfo, $gn, $qAttr);
                        break;

                    /* **************************************** */
                    /* MULTIPLE CHOICE QUESTIONS */

                    case 'M':   // Multiple choice
                        $this->parseMQuestion($sgq, $qInfo, $gn, $qAttr);
                        break;

                    case 'P':   // Multiple choice with comments
                        $this->parsePQuestion($sgq, $qInfo, $gn, $qAttr);
                        break;

                    /* **************************************** */
                    /* SINGLE CHOICE QUESTIONS */

                    case '5':   // 5 point choice
                        $this->parse5Question($sgq, $qInfo, $gn, $qAttr);
                        break;

                    case '!':   // List (dropdown)
                        $this->parseDropdownQuestion($sgq, $qInfo, $gn, $qAttr);
                        break;

                    case 'L':   // List (radio)
                        $this->parseLQuestion($sgq, $qInfo, $gn, $qAttr);
                        break;

                    case 'O':   // List with comment
                        $this->parseOQuestion($sgq, $qInfo, $gn, $qAttr);
                        break;

                    /* **************************************** */
                    /* TEXT QUESTIONS */

                    case 'S':   // Short free text
                        $this->parseSQuestion($sgq, $qInfo, $gn, $qAttr);
                        break;

                    case 'T':   // Long free text
                        $this->parseTQuestion($sgq, $qInfo, $gn, $qAttr);
                        break;

                    case 'U':   // Huge free text
                        $this->parseUQuestion($sgq, $qInfo, $gn, $qAttr);
                        break;

                    case 'Q':   // Multiple short text
                        $this->parseQQuestion($sgq, $qInfo, $gn, $qAttr);
                        break;

                    /* **************************************** */

                    default:
                        $this->parseUnknownQuestion($sgq, $qInfo, $gn, $qAttr);
                        break;
                }
            }
        }

        return $this->output;
    }

    /* **************************************** */

    /**
     * The following functions manipulate the array 'output' adding information such as:
     * question code, question text, question type, question mandatory, type of answers (only numbers or not),
     * visibility of the question and all available responses.
     */

    /* **************************************** */

    /**
     * ARRAYS (from https://manual.limesurvey.org/Question_types#Arrays)
     *
     * The Array question type further extends the List question type.
     * Using this question type, a matrix can be displayed where the left column is represented by a subquestion,
     * while each row is represented by the same set of answer options.
     * The text of the question can be either a specific question or a description.
     *
     * In terms of output there is no difference in how responses are stored compared to List (Radio) question type.
     * The given answer is stored in a separate column in the result table for both question types.
     *
     * The most flexible array types are Array, Array (Text) and Array (Numbers)'.
     * However, LimeSurvey also supports a number of array types which have predefined answer options
     * (e.g., Array 5 point choice).
     */

    /* **************************************** */

    /**
     * This function parses the 'F' type questions aka 'Array'.
     *
     * (from https://manual.limesurvey.org/Question_types#Array)
     * An array allows you to create a set of subquestions. Each of them uses the same set of answer options.
     *
     * To see an output example for this type of questions you can see
     * 'F.json' file inside 'examples/questions/Array' folder.
     *
     * @author  Tiziano Marra <https://github.com/MrTiz9>
     * @since   1.0
     *
     * @internal
     *
     * @param string $sgq       SGQ identifier
     * @param array  $qInfo     All data about question, getting from 'questions' table
     * @param string $gn        Group name
     * @param array  $qAttr     Some attributes about question, getting from 'question_attributes' table
     */

    private function parseFQuestion($sgq, $qInfo, $gn, $qAttr)
    {
        $subQuestions = $this->getSubQuestions($qInfo['qid']);

        if (!empty($subQuestions)) {
            $qText = $this->stripTags ? strip_tags($qInfo['question']) : $qInfo['question'];

            $mandatory = $qInfo['mandatory'] === 'Y';

            foreach ($subQuestions as $i => $sqInfo) {
                $sgqTtl = $sgq . $sqInfo['title'];

                if ((!empty($this->qList) && !in_array($sgqTtl, $this->qList)) || in_array($sgqTtl, $this->qToExcl)) {
                    continue;
                }

                $aText = $this->stripTags ? strip_tags($sqInfo['question']) : $sqInfo['question'];

                $this->output[$gn][$sgqTtl]['code']      = $qInfo['title'] . '[' . $sqInfo['title'] . ']';
                $this->output[$gn][$sgqTtl]['text']      = $qText . ' [' . $aText . ']';
                $this->output[$gn][$sgqTtl]['type']      = 'F';
                $this->output[$gn][$sgqTtl]['mandatory'] = $mandatory;
                $this->output[$gn][$sgqTtl]['numonly']   = false;
                $this->output[$gn][$sgqTtl]['hidden']    = $qAttr['hidden'];

                $codesAnswers = $this->getAnswersCodes($sqInfo['parent_qid']);

                if ($this->questionsOnly) {
                    continue;
                }

                if (!empty($codesAnswers)) {
                    foreach ($codesAnswers as $code => $answer) {
                        $this->output[$gn][$sgqTtl]['answers'][$code] = [
                            'text' => $this->stripTags ? strip_tags($answer) : $answer,
                            'N'    => 0,
                            '%'    => 0
                        ];
                    }

                    $this->output[$gn][$sgqTtl]['answers']['_X_'] = $this->total;
                }

                $countAnswers = $this->getCountAnswers($sgqTtl);

                if (!empty($countAnswers)) {
                    foreach ($countAnswers as $k => $answer) {
                        $this->output[$gn][$sgqTtl]['answers'][$answer['value']]['N'] = (int)$answer['N'];
                        $this->output[$gn][$sgqTtl]['answers']['_X_']['N']           += (int)$answer['N'];
                    }

                    $this->setPercentage($sgqTtl, $gn);
                }

                $this->dropByCutoff($sgqTtl, $gn);
            }
        }
    }

    /* **************************************** */

    /**
     * This function parses the 'A' type questions aka 'Array (5 point choice)'.
     *
     * (from https://manual.limesurvey.org/Question_types#Array_.285_point_choice.29)
     * It is an array type that is prefilled with answer choices. An 1 to 5 scale is used.
     *
     * To see an output example for this type of questions you can see
     * 'A.json' file inside 'examples/questions/Array' folder.
     *
     * @author  Tiziano Marra <https://github.com/MrTiz9>
     * @since   1.0
     *
     * @internal
     *
     * @param string $sgq       SGQ identifier
     * @param array  $qInfo     All data about question, getting from 'questions' table
     * @param string $gn        Group name
     * @param array  $qAttr     Some attributes about question, getting from 'question_attributes' table
     */

    private function parseAQuestion($sgq, $qInfo, $gn, $qAttr)
    {
        $subQuestions = $this->getSubQuestions($qInfo['qid']);

        if (!empty($subQuestions)) {
            $qText = $this->stripTags ? strip_tags($qInfo['question']) : $qInfo['question'];

            $mandatory = $qInfo['mandatory'] === 'Y';

            foreach ($subQuestions as $i => $sqInfo) {
                $sgqTtl = $sgq . $sqInfo['title'];

                if ((!empty($this->qList) && !in_array($sgqTtl, $this->qList)) || in_array($sgqTtl, $this->qToExcl)) {
                    continue;
                }

                $aText = $this->stripTags ? strip_tags($sqInfo['question']) : $sqInfo['question'];

                $this->output[$gn][$sgqTtl]['code']      = $qInfo['title'] . '[' . $sqInfo['title'] . ']';
                $this->output[$gn][$sgqTtl]['text']      = $qText . ' [' . $aText . ']';
                $this->output[$gn][$sgqTtl]['type']      = 'A';
                $this->output[$gn][$sgqTtl]['mandatory'] = $mandatory;
                $this->output[$gn][$sgqTtl]['numonly']   = true;
                $this->output[$gn][$sgqTtl]['hidden']    = $qAttr['hidden'];

                if ($this->questionsOnly) {
                    continue;
                }

                for ($j = 1; $j <= 5; $j++) {
                    $this->output[$gn][$sgqTtl]['answers'][$j] = [
                        'text' => (string)$j,
                        'N'    => 0,
                        '%'    => 0
                    ];
                }

                $this->output[$gn][$sgqTtl]['answers']['_X_'] = $this->total;

                $countAnswers = $this->getCountAnswers($sgqTtl);

                if (!empty($countAnswers)) {
                    foreach ($countAnswers as $k => $answer) {
                        $this->output[$gn][$sgqTtl]['answers'][$answer['value']]['N'] = (int)$answer['N'];
                        $this->output[$gn][$sgqTtl]['answers']['_X_']['N']           += (int)$answer['N'];
                    }

                    $this->setPercentage($sgqTtl, $gn);
                }

                $this->dropByCutoff($sgqTtl, $gn);
            }
        }
    }

    /* **************************************** */

    /**
     * This function parses the 'B' type questions aka 'Array (10 point choice)'.
     *
     * (from https://manual.limesurvey.org/Question_types#Array_.2810_point_choice.29)
     * It is an array question type that is prefilled with answer choices on a 1 to 10 scale.
     *
     * To see an output example for this type of questions you can see
     * 'B.json' file inside 'examples/questions/Array' folder.
     *
     * @author  Tiziano Marra <https://github.com/MrTiz9>
     * @since   1.0
     *
     * @internal
     *
     * @param string $sgq       SGQ identifier
     * @param array  $qInfo     All data about question, getting from 'questions' table
     * @param string $gn        Group name
     * @param array  $qAttr     Some attributes about question, getting from 'question_attributes' table
     */

    private function parseBQuestion($sgq, $qInfo, $gn, $qAttr)
    {
        $subQuestions = $this->getSubQuestions($qInfo['qid']);

        if (!empty($subQuestions)) {
            $qText = $this->stripTags ? strip_tags($qInfo['question']) : $qInfo['question'];

            $mandatory = $qInfo['mandatory'] === 'Y';

            foreach ($subQuestions as $i => $sqInfo) {
                $sgqTtl = $sgq . $sqInfo['title'];

                if ((!empty($this->qList) && !in_array($sgqTtl, $this->qList)) || in_array($sgqTtl, $this->qToExcl)) {
                    continue;
                }

                $aText = $this->stripTags ? strip_tags($sqInfo['question']) : $sqInfo['question'];

                $this->output[$gn][$sgqTtl]['code']      = $qInfo['title'] . '[' . $sqInfo['title'] . ']';
                $this->output[$gn][$sgqTtl]['text']      = $qText . ' [' . $aText . ']';
                $this->output[$gn][$sgqTtl]['type']      = 'B';
                $this->output[$gn][$sgqTtl]['mandatory'] = $mandatory;
                $this->output[$gn][$sgqTtl]['numonly']   = true;
                $this->output[$gn][$sgqTtl]['hidden']    = $qAttr['hidden'];

                if ($this->questionsOnly) {
                    continue;
                }

                for ($j = 1; $j <= 10; $j++) {
                    $this->output[$gn][$sgqTtl]['answers'][$j] = [
                        'text' => (string)$j,
                        'N'    => 0,
                        '%'    => 0
                    ];
                }

                $this->output[$gn][$sgqTtl]['answers']['_X_'] = $this->total;

                $countAnswers = $this->getCountAnswers($sgqTtl);

                if (!empty($countAnswers)) {
                    foreach ($countAnswers as $k => $answer) {
                        $this->output[$gn][$sgqTtl]['answers'][$answer['value']]['N'] = (int)$answer['N'];
                        $this->output[$gn][$sgqTtl]['answers']['_X_']['N']           += (int)$answer['N'];
                    }

                    $this->setPercentage($sgqTtl, $gn);
                }

                $this->dropByCutoff($sgqTtl, $gn);
            }
        }
    }

    /* **************************************** */

    /**
     * This function parses the 'C' type questions aka 'Array (Yes/No/Uncertain)'.
     *
     * (from https://manual.limesurvey.org/Question_types#Array_.28Yes.2FNo.2FUncertain.29)
     * It is an array question type that is prefilled with the following answer choices: 'Yes', 'No', and 'Uncertain'.
     *
     * To see an output example for this type of questions you can see
     * 'C.json' file inside 'examples/questions/Array' folder.
     *
     * @author  Tiziano Marra <https://github.com/MrTiz9>
     * @since   1.0
     *
     * @internal
     *
     * @param string $sgq       SGQ identifier
     * @param array  $qInfo     All data about question, getting from 'questions' table
     * @param string $gn        Group name
     * @param array  $qAttr     Some attributes about question, getting from 'question_attributes' table
     */

    private function parseCQuestion($sgq, $qInfo, $gn, $qAttr)
    {
        $subQuestions = $this->getSubQuestions($qInfo['qid']);

        if (!empty($subQuestions)) {
            $qText = $this->stripTags ? strip_tags($qInfo['question']) : $qInfo['question'];

            $mandatory = $qInfo['mandatory'] === 'Y';

            foreach ($subQuestions as $i => $sqInfo) {
                $sgqTtl = $sgq . $sqInfo['title'];

                if ((!empty($this->qList) && !in_array($sgqTtl, $this->qList)) || in_array($sgqTtl, $this->qToExcl)) {
                    continue;
                }

                $aText = $this->stripTags ? strip_tags($sqInfo['question']) : $sqInfo['question'];

                $this->output[$gn][$sgqTtl]['code']      = $qInfo['title'] . '[' . $sqInfo['title'] . ']';
                $this->output[$gn][$sgqTtl]['text']      = $qText . ' [' . $aText . ']';
                $this->output[$gn][$sgqTtl]['type']      = 'C';
                $this->output[$gn][$sgqTtl]['mandatory'] = $mandatory;
                $this->output[$gn][$sgqTtl]['numonly']   = false;
                $this->output[$gn][$sgqTtl]['hidden']    = $qAttr['hidden'];

                if ($this->questionsOnly) {
                    continue;
                }

                $this->output[$gn][$sgqTtl]['answers']['Y'] = [
                    'text' => 'Yes',
                    'N'    => 0,
                    '%'    => 0
                ];

                $this->output[$gn][$sgqTtl]['answers']['U'] = [
                    'text' => 'I don\'t know',
                    'N'    => 0,
                    '%'    => 0
                ];

                $this->output[$gn][$sgqTtl]['answers']['N'] = [
                    'text' => 'No',
                    'N'    => 0,
                    '%'    => 0
                ];

                $this->output[$gn][$sgqTtl]['answers']['_X_'] = $this->total;

                $countAnswers = $this->getCountAnswers($sgqTtl);

                if (!empty($countAnswers)) {
                    foreach ($countAnswers as $k => $answer) {
                        $this->output[$gn][$sgqTtl]['answers'][$answer['value']]['N'] = (int)$answer['N'];
                        $this->output[$gn][$sgqTtl]['answers']['_X_']['N']           += (int)$answer['N'];
                    }

                    $this->setPercentage($sgqTtl, $gn);
                }

                $this->dropByCutoff($sgqTtl, $gn);
            }
        }
    }

    /* **************************************** */

    /**
     * This function parses the 'E' type questions aka 'Array (Increase/Same/Decrease)'.
     *
     * (from https://manual.limesurvey.org/Question_types#Array_.28Increase.2FSame.2FDecrease.29)
     * It is an array type that is prefilled with the following answer choices: 'Increase', 'Same', and 'Decrease'.
     *
     * To see an output example for this type of questions you can see
     * 'E.json' file inside 'examples/questions/Array' folder.
     *
     * @author  Tiziano Marra <https://github.com/MrTiz9>
     * @since   1.0
     *
     * @internal
     *
     * @param string $sgq       SGQ identifier
     * @param array  $qInfo     All data about question, getting from 'questions' table
     * @param string $gn        Group name
     * @param array  $qAttr     Some attributes about question, getting from 'question_attributes' table
     */

    private function parseEQuestion($sgq, $qInfo, $gn, $qAttr)
    {
        $subQuestions = $this->getSubQuestions($qInfo['qid']);

        if (!empty($subQuestions)) {
            $qText = $this->stripTags ? strip_tags($qInfo['question']) : $qInfo['question'];

            $mandatory = $qInfo['mandatory'] === 'Y';

            foreach ($subQuestions as $i => $sqInfo) {
                $sgqTtl = $sgq . $sqInfo['title'];

                if ((!empty($this->qList) && !in_array($sgqTtl, $this->qList)) || in_array($sgqTtl, $this->qToExcl)) {
                    continue;
                }

                $aText = $this->stripTags ? strip_tags($sqInfo['question']) : $sqInfo['question'];

                $this->output[$gn][$sgqTtl]['code']      = $qInfo['title'] . '[' . $sqInfo['title'] . ']';
                $this->output[$gn][$sgqTtl]['text']      = $qText . ' [' . $aText . ']';
                $this->output[$gn][$sgqTtl]['type']      = 'E';
                $this->output[$gn][$sgqTtl]['mandatory'] = $mandatory;
                $this->output[$gn][$sgqTtl]['numonly']   = false;
                $this->output[$gn][$sgqTtl]['hidden']    = $qAttr['hidden'];

                if ($this->questionsOnly) {
                    continue;
                }

                $this->output[$gn][$sgqTtl]['answers']['I'] = [
                    'text' => 'Increase',
                    'N'    => 0,
                    '%'    => 0
                ];

                $this->output[$gn][$sgqTtl]['answers']['S'] = [
                    'text' => 'Same',
                    'N'    => 0,
                    '%'    => 0
                ];

                $this->output[$gn][$sgqTtl]['answers']['D'] = [
                    'text' => 'Decrease',
                    'N'    => 0,
                    '%'    => 0
                ];

                $this->output[$gn][$sgqTtl]['answers']['_X_'] = $this->total;

                $countAnswers = $this->getCountAnswers($sgqTtl);

                if (!empty($countAnswers)) {
                    foreach ($countAnswers as $k => $answer) {
                        $this->output[$gn][$sgqTtl]['answers'][$answer['value']]['N'] = (int)$answer['N'];
                        $this->output[$gn][$sgqTtl]['answers']['_X_']['N']           += (int)$answer['N'];
                    }

                    $this->setPercentage($sgqTtl, $gn);
                }

                $this->dropByCutoff($sgqTtl, $gn);
            }
        }
    }

    /* **************************************** */

    /**
     * This function parses the 'H' type questions aka 'Array by column'.
     *
     * (from https://manual.limesurvey.org/Question_types#Array_by_column)
     * This question type is the same as an array, except the subquestion and answer axes are swapped.
     *
     * To see an output example for this type of questions you can see
     * 'H.json' file inside 'examples/questions/Array' folder.
     *
     * @author  Tiziano Marra <https://github.com/MrTiz9>
     * @since   1.0
     *
     * @internal
     *
     * @param string $sgq       SGQ identifier
     * @param array  $qInfo     All data about question, getting from 'questions' table
     * @param string $gn        Group name
     * @param array  $qAttr     Some attributes about question, getting from 'question_attributes' table
     */

    private function parseHQuestion($sgq, $qInfo, $gn, $qAttr)
    {
        $subQuestions = $this->getSubQuestions($qInfo['qid']);

        if (!empty($subQuestions)) {
            $qText = $this->stripTags ? strip_tags($qInfo['question']) : $qInfo['question'];

            $mandatory = $qInfo['mandatory'] === 'Y';

            foreach ($subQuestions as $i => $sqInfo) {
                $sgqTtl = $sgq . $sqInfo['title'];

                if ((!empty($this->qList) && !in_array($sgqTtl, $this->qList)) || in_array($sgqTtl, $this->qToExcl)) {
                    continue;
                }

                $aText = $this->stripTags ? strip_tags($sqInfo['question']) : $sqInfo['question'];

                $this->output[$gn][$sgqTtl]['code']      = $qInfo['title'] . '[' . $sqInfo['title'] . ']';
                $this->output[$gn][$sgqTtl]['text']      = $qText . ' [' . $aText . ']';
                $this->output[$gn][$sgqTtl]['type']      = 'H';
                $this->output[$gn][$sgqTtl]['mandatory'] = $mandatory;
                $this->output[$gn][$sgqTtl]['numonly']   = false;
                $this->output[$gn][$sgqTtl]['hidden']    = $qAttr['hidden'];

                if ($this->questionsOnly) {
                    continue;
                }

                $codesAnswers = $this->getAnswersCodes($sqInfo['parent_qid']);

                if (!empty($codesAnswers)) {
                    foreach ($codesAnswers as $code => $answer) {
                        $this->output[$gn][$sgqTtl]['answers'][$code] = [
                            'text' => $this->stripTags ? strip_tags($answer) : $answer,
                            'N'    => 0,
                            '%'    => 0
                        ];
                    }

                    $this->output[$gn][$sgqTtl]['answers']['_X_'] = $this->total;
                }

                $countAnswers = $this->getCountAnswers($sgqTtl);

                if (!empty($countAnswers)) {
                    foreach ($countAnswers as $k => $answer) {
                        $this->output[$gn][$sgqTtl]['answers'][$answer['value']]['N'] = (int)$answer['N'];
                        $this->output[$gn][$sgqTtl]['answers']['_X_']['N']           += (int)$answer['N'];
                    }

                    $this->setPercentage($sgqTtl, $gn);
                }

                $this->dropByCutoff($sgqTtl, $gn);
            }
        }
    }

    /* **************************************** */

    /**
     * This function parses the '1' type questions aka 'Array dual scale'.
     *
     * (from https://manual.limesurvey.org/Question_types#Array_dual_scale)
     * This question type provides two scales of answer options for each subquestion.
     *
     * To see an output example for this type of questions you can see
     * '1.json' file inside 'examples/questions/Array' folder.
     *
     * @author  Tiziano Marra <https://github.com/MrTiz9>
     * @since   1.0
     *
     * @internal
     *
     * @param string $sgq       SGQ identifier
     * @param array  $qInfo     All data about question, getting from 'questions' table
     * @param string $gn        Group name
     * @param array  $qAttr     Some attributes about question, getting from 'question_attributes' table
     */

    private function parse1Question($sgq, $qInfo, $gn, $qAttr)
    {
        $subQuestions = $this->getSubQuestions($qInfo['qid']);

        if (!empty($subQuestions)) {
            $aux = [];

            foreach ($subQuestions as $i => $sqInfo) {
                $sqInfo['sharp'] = $sqInfo['title'];
                $temp = $sqInfo;

                $sqInfo['sharp']    .= '#0';
                $sqInfo['title']    .= '][0]';
                $sqInfo['question'] .= '][Scale 1]';

                $temp['sharp']    .= '#1';
                $temp['title']    .= '][1]';
                $temp['question'] .= '][Scale 2]';

                $aux[] = $sqInfo;
                $aux[] = $temp;
            }

            $subQuestions = $aux;
            unset($aux);

            $qText = $this->stripTags ? strip_tags($qInfo['question']) : $qInfo['question'];

            $mandatory = $qInfo['mandatory'] === 'Y';

            foreach ($subQuestions as $i => $sqInfo) {
                $sgqSh = $sgq . $sqInfo['sharp'];

                if ((!empty($this->qList) && !in_array($sgqSh, $this->qList)) || in_array($sgqSh, $this->qToExcl)) {
                    continue;
                }

                $aText = $this->stripTags ? strip_tags($sqInfo['question']) : $sqInfo['question'];

                $this->output[$gn][$sgqSh]['code']      = $qInfo['title'] . '[' . $sqInfo['title'];
                $this->output[$gn][$sgqSh]['text']      = $qText . ' [' . $aText;
                $this->output[$gn][$sgqSh]['type']      = '1';
                $this->output[$gn][$sgqSh]['mandatory'] = $mandatory;
                $this->output[$gn][$sgqSh]['numonly']   = false;
                $this->output[$gn][$sgqSh]['hidden']    = $qAttr['hidden'];

                if ($this->questionsOnly) {
                    continue;
                }

                $scaleId = substr($sqInfo['sharp'], -2) === '#0' ? 0 : 1;
                $codesAnswers = $this->getAnswersCodes($sqInfo['parent_qid'], $scaleId);

                if (!empty($codesAnswers)) {
                    foreach ($codesAnswers as $code => $answer) {
                        $this->output[$gn][$sgqSh]['answers'][$code] = [
                            'text' => $this->stripTags ? strip_tags($answer) : $answer,
                            'N'    => 0,
                            '%'    => 0
                        ];
                    }

                    $this->output[$gn][$sgqSh]['answers']['_X_'] = $this->total;
                }

                $countAnswers = $this->getCountAnswers($sgqSh);

                if (!empty($countAnswers)) {
                    foreach ($countAnswers as $k => $answer) {
                        $this->output[$gn][$sgqSh]['answers'][$answer['value']]['N'] = (int)$answer['N'];
                        $this->output[$gn][$sgqSh]['answers']['_X_']['N']           += (int)$answer['N'];
                    }

                    $this->setPercentage($sgqSh, $gn);
                }

                $this->dropByCutoff($sgqSh, $gn);
            }
        }
    }

    /* **************************************** */

    /**
     * This function parses the ':' type questions aka 'Array (Numbers)'.
     *
     * To see an output example for this type of questions you can see
     * 'Colon.json' file inside 'examples/questions/Array' folder.
     *
     * (from https://manual.limesurvey.org/Question_types#Array_.28Numbers.29)
     * This question types allows survey administrators to a create large arrays with numbered dropdown boxes
     * with a set of subquestions on the 'y-axis', and another set of subquestions on the 'x-axis'.
     *
     * @author  Tiziano Marra <https://github.com/MrTiz9>
     * @since   1.0
     *
     * @internal
     *
     * @param string $sgq       SGQ identifier
     * @param array  $qInfo     All data about question, getting from 'questions' table
     * @param string $gn        Group name
     * @param array  $qAttr     Some attributes about question, getting from 'question_attributes' table
     */

    private function parseColonQuestion($sgq, $qInfo, $gn, $qAttr)
    {
        $subQuestions = $this->getSubQuestions($qInfo['qid']);

        if (!empty($subQuestions)) {
            $scaleX = $this->getInfoByScaleId($qInfo['qid']);

            if (!empty($scaleX)) {
                $aux = [];

                foreach ($subQuestions as $i => $sqInfo) {
                    foreach ($scaleX as $title => $question) {
                        $temp = $sqInfo;
                        $temp['title']    .= '_' . $title;
                        $temp['question'] .= empty($question) ? '' : '][' . $question;

                        $aux[] = $temp;
                    }
                }

                $subQuestions = $aux;
            }

            $qText = $this->stripTags ? strip_tags($qInfo['question']) : $qInfo['question'];

            $mandatory = $qInfo['mandatory'] === 'Y';

            foreach ($subQuestions as $i => $sqInfo) {
                $sgqTtl = $sgq . $sqInfo['title'];

                if ((!empty($this->qList) && !in_array($sgqTtl, $this->qList)) || in_array($sgqTtl, $this->qToExcl)) {
                    continue;
                }

                $aText = $this->stripTags ? strip_tags($sqInfo['question']) : $sqInfo['question'];

                $this->output[$gn][$sgqTtl]['code']      = $qInfo['title'] . '[' . $sqInfo['title'] . ']';
                $this->output[$gn][$sgqTtl]['text']      = $qText . ' [' . $aText . ']';
                $this->output[$gn][$sgqTtl]['type']      = ':';
                $this->output[$gn][$sgqTtl]['mandatory'] = $mandatory;
                $this->output[$gn][$sgqTtl]['numonly']   = true;
                $this->output[$gn][$sgqTtl]['hidden']    = $qAttr['hidden'];

                if ($this->questionsOnly) {
                    continue;
                }

                $countAnswers = $this->getCountAnswers($sgqTtl);

                if (!empty($countAnswers)) {
                    $this->output[$gn][$sgqTtl]['answers']['_X_'] = $this->total;

                    foreach ($countAnswers as $k => $answer) {
                        $this->output[$gn][$sgqTtl]['answers'][$answer['value']] = [
                            'text' => $answer['value'],
                            'N'    => (int)$answer['N'],
                            '%'    => 0
                        ];

                        $this->output[$gn][$sgqTtl]['answers']['_X_']['N'] += (int)$answer['N'];
                    }

                    $this->setPercentage($sgqTtl, $gn);
                    $this->moveTotalToEnd($sgqTtl, $gn);
                }

                $this->dropByCutoff($sgqTtl, $gn);
            }
        }
    }

    /* **************************************** */

    /**
     * This function parses the ';' type questions aka 'Array (Texts)'.
     *
     * (from https://manual.limesurvey.org/Question_types#Array_.28Texts.29)
     * The Array (Text) question type allows for an array of text boxes with a set of subquestions as the 'y-axis',
     * and another set of subquestions as the 'x-axis'.
     *
     * To see an output example for this type of questions you can see
     * 'Semicolon.json' file inside 'examples/questions/Array' folder.
     *
     * @author  Tiziano Marra <https://github.com/MrTiz9>
     * @since   1.0
     *
     * @internal
     *
     * @param string $sgq       SGQ identifier
     * @param array  $qInfo     All data about question, getting from 'questions' table
     * @param string $gn        Group name
     * @param array  $qAttr     Some attributes about question, getting from 'question_attributes' table
     */

    private function parseSemicolonQuestion($sgq, $qInfo, $gn, $qAttr)
    {
        $subQuestions = $this->getSubQuestions($qInfo['qid']);

        if (!empty($subQuestions)) {
            $scaleX = $this->getInfoByScaleId($qInfo['qid']);

            if (!empty($scaleX)) {
                $aux = [];

                foreach ($subQuestions as $i => $sqInfo) {
                    foreach ($scaleX as $title => $question) {
                        $temp = $sqInfo;
                        $temp['title']    .= '_' . $title;
                        $temp['question'] .= empty($question) ? '' : '][' . $question;

                        $aux[] = $temp;
                    }
                }

                $subQuestions = $aux;
            }

            $qText = $this->stripTags ? strip_tags($qInfo['question']) : $qInfo['question'];

            $mandatory = $qInfo['mandatory'] === 'Y';

            foreach ($subQuestions as $i => $sqInfo) {
                $sgqTtl = $sgq . $sqInfo['title'];

                if ((!empty($this->qList) && !in_array($sgqTtl, $this->qList)) || in_array($sgqTtl, $this->qToExcl)) {
                    continue;
                }

                $aText = $this->stripTags ? strip_tags($sqInfo['question']) : $sqInfo['question'];

                $this->output[$gn][$sgqTtl]['code']      = $qInfo['title'] . '[' . $sqInfo['title'] . ']';
                $this->output[$gn][$sgqTtl]['text']      = $qText . ' [' . $aText . ']';
                $this->output[$gn][$sgqTtl]['type']      = ';';
                $this->output[$gn][$sgqTtl]['mandatory'] = $mandatory;
                $this->output[$gn][$sgqTtl]['numonly']   = $qAttr['numbers_only'];
                $this->output[$gn][$sgqTtl]['hidden']    = $qAttr['hidden'];

                if ($this->questionsOnly) {
                    continue;
                }

                $this->output[$gn][$sgqTtl]['answers'] = $this->getFreeTextAnswers($sgqTtl);
                $this->dropByCutoff($sgqTtl, $gn);
            }
        }
    }

    /* **************************************** */

    /**
     * MASK QUESTIONS (from https://manual.limesurvey.org/Question_types#Mask_questions)
     *
     * Due to a lack of better word we define all questions where
     * the input of answers is predefined as 'mask questions'.
     */

    /* **************************************** */

    /**
     * This function parses the 'D' type questions aka 'Date/Time'.
     *
     * (from https://manual.limesurvey.org/Question_types#Date)
     * This question type can be used to ask for a certain date, time or a combination of both date and time.
     * The values can be selected by the participants either from a popup calendar or from dropdown boxes.
     *
     * To see an output example for this type of questions you can see
     * 'D.json' file inside 'examples/questions/Mask' folder.
     *
     * @author  Tiziano Marra <https://github.com/MrTiz9>
     * @since   1.0
     *
     * @internal
     *
     * @param string $sgq       SGQ identifier
     * @param array  $qInfo     All data about question, getting from 'questions' table
     * @param string $gn        Group name
     * @param array  $qAttr     Some attributes about question, getting from 'question_attributes' table
     */

    private function parseDQuestion($sgq, $qInfo, $gn, $qAttr)
    {
        if ((!empty($this->qList) && !in_array($sgq, $this->qList)) || in_array($sgq, $this->qToExcl)) {
            return;
        }

        $qText = $this->stripTags ? strip_tags($qInfo['question']) : $qInfo['question'];

        $this->output[$gn][$sgq]['code']      = $qInfo['title'];
        $this->output[$gn][$sgq]['text']      = $qText;
        $this->output[$gn][$sgq]['type']      = 'D';
        $this->output[$gn][$sgq]['mandatory'] = $qInfo['mandatory'] === 'Y';
        $this->output[$gn][$sgq]['numonly']   = false;
        $this->output[$gn][$sgq]['hidden']    = $qAttr['hidden'];

        if ($this->questionsOnly) {
            return;
        }

        $this->output[$gn][$sgq]['answers'] = $this->getFreeTextAnswers($sgq);
        $this->dropByCutoff($sgq, $gn);
    }

    /* **************************************** */

    /**
     * This function parses the '|' type questions aka 'File upload'.
     *
     * (from https://manual.limesurvey.org/Question_types#File_upload)
     * The file upload question type can be used to collect file(s) from a user in response to a question.
     *
     * NOTE: The 'SGQ_filecount' column is used as a counter for the uploaded files.
     *
     * To see an output example for this type of questions you can see
     * 'Pipe.json' file inside 'examples/questions/Mask' folder.
     *
     * @author  Tiziano Marra <https://github.com/MrTiz9>
     * @since   1.0
     *
     * @internal
     *
     * @param string $sgq       SGQ identifier
     * @param array  $qInfo     All data about question, getting from 'questions' table
     * @param string $gn        Group name
     * @param array  $qAttr     Some attributes about question, getting from 'question_attributes' table
     */

    private function parsePipeQuestion($sgq, $qInfo, $gn, $qAttr)
    {
        if ((!empty($this->qList) && !in_array($sgq, $this->qList)) || in_array($sgq, $this->qToExcl)) {
            return;
        }

        $qText = $this->stripTags ? strip_tags($qInfo['question']) : $qInfo['question'];

        $this->output[$gn][$sgq]['code']      = $qInfo['title'];
        $this->output[$gn][$sgq]['text']      = $qText;
        $this->output[$gn][$sgq]['type']      = '|';
        $this->output[$gn][$sgq]['mandatory'] = $qInfo['mandatory'] === 'Y';
        $this->output[$gn][$sgq]['numonly']   = false;
        $this->output[$gn][$sgq]['hidden']    = $qAttr['hidden'];

        if ($this->questionsOnly) {
            return;
        }

        $uploaded = $this->getUploadedFiles($sgq);

        if (!empty($uploaded)) {
            $this->output[$gn][$sgq]['answers']['_X_'] = $this->total;

            foreach ($uploaded as $i => $data) {
                $this->output[$gn][$sgq]['answers'][] = [
                    'text' => $data['info'],
                    'N'    => (int)$data['N'],
                    '%'    => 0
                ];

                $this->output[$gn][$sgq]['answers']['_X_']['N'] += (int)$data['N'];
            }

            $this->setPercentage($sgq, $gn);
            $this->moveTotalToEnd($sgq, $gn);
        }

        $this->dropByCutoff($sgq, $gn);
    }

    /* **************************************** */

    /**
     * This function parses the 'G' type questions aka 'Gender'.
     *
     * (from https://manual.limesurvey.org/Question_types#Gender)
     * This question type collects gender information ('Male' or 'Female') from the respondents.
     *
     * To see an output example for this type of questions you can see
     * 'G.json' file inside 'examples/questions/Mask' folder.
     *
     * @author  Tiziano Marra <https://github.com/MrTiz9>
     * @since   1.0
     *
     * @internal
     *
     * @param string $sgq       SGQ identifier
     * @param array  $qInfo     All data about question, getting from 'questions' table
     * @param string $gn        Group name
     * @param array  $qAttr     Some attributes about question, getting from 'question_attributes' table
     */

    private function parseGQuestion($sgq, $qInfo, $gn, $qAttr)
    {
        if ((!empty($this->qList) && !in_array($sgq, $this->qList)) || in_array($sgq, $this->qToExcl)) {
            return;
        }

        $qText = $this->stripTags ? strip_tags($qInfo['question']) : $qInfo['question'];

        $this->output[$gn][$sgq]['code']      = $qInfo['title'];
        $this->output[$gn][$sgq]['text']      = $qText;
        $this->output[$gn][$sgq]['type']      = 'G';
        $this->output[$gn][$sgq]['mandatory'] = $qInfo['mandatory'] === 'Y';
        $this->output[$gn][$sgq]['numonly']   = false;
        $this->output[$gn][$sgq]['hidden']    = $qAttr['hidden'];

        if ($this->questionsOnly) {
            return;
        }

        $this->output[$gn][$sgq]['answers']['M'] = [
            'text' => 'Male',
            'N'    => 0,
            '%'    => 0
        ];

        $this->output[$gn][$sgq]['answers']['F'] = [
            'text' => 'Female',
            'N'    => 0,
            '%'    => 0
        ];

        $this->output[$gn][$sgq]['answers']['_X_'] = $this->total;

        $countAnswers = $this->getCountAnswers($sgq);

        if (!empty($countAnswers)) {
            foreach ($countAnswers as $i => $answer) {
                $this->output[$gn][$sgq]['answers'][$answer['value']]['N'] = (int)$answer['N'];
                $this->output[$gn][$sgq]['answers']['_X_']['N']           += (int)$answer['N'];
            }

            $this->setPercentage($sgq, $gn);
        }

        $this->dropByCutoff($sgq, $gn);
    }

    /* **************************************** */

    /**
     * This function parses the 'I' type questions aka 'Language switch'.
     *
     * (from https://manual.limesurvey.org/Question_types#Language_switch)
     * This question type allows the user to change the language of the survey.
     * Users can choose from a dropdown list, the language in which they wish to have the survey questions displayed.
     * The dropdown list includes the base language (which is selected when the survey is created for the first time)
     * and the additional ones.
     *
     * To see an output example for this type of questions you can see
     * 'I.json' file inside 'examples/questions/Mask' folder.
     *
     * @author  Tiziano Marra <https://github.com/MrTiz9>
     * @since   1.0
     *
     * @internal
     *
     * @param string $sgq       SGQ identifier
     * @param array  $qInfo     All data about question, getting from 'questions' table
     * @param string $gn        Group name
     * @param array  $qAttr     Some attributes about question, getting from 'question_attributes' table
     */

    private function parseIQuestion($sgq, $qInfo, $gn, $qAttr)
    {
        if ((!empty($this->qList) && !in_array($sgq, $this->qList)) || in_array($sgq, $this->qToExcl)) {
            return;
        }

        $qText = $this->stripTags ? strip_tags($qInfo['question']) : $qInfo['question'];

        $this->output[$gn][$sgq]['code']      = $qInfo['title'];
        $this->output[$gn][$sgq]['text']      = $qText;
        $this->output[$gn][$sgq]['type']      = 'I';
        $this->output[$gn][$sgq]['mandatory'] = $qInfo['mandatory'] === 'Y';
        $this->output[$gn][$sgq]['numonly']   = false;
        $this->output[$gn][$sgq]['hidden']    = $qAttr['hidden'];

        if ($this->questionsOnly) {
            return;
        }

        $languages = $this->getAvailableLanguages();

        if (!empty($languages)) {
            foreach ($languages as $lang) {
                $this->output[$gn][$sgq]['answers'][$lang] = [
                    'text' => $lang,
                    'N'    => 0,
                    '%'    => 0
                ];
            }

            $this->output[$gn][$sgq]['answers']['_X_'] = $this->total;

            $countAnswers = $this->getCountAnswers($sgq);

            if (!empty($countAnswers)) {
                foreach ($countAnswers as $i => $answer) {
                    $this->output[$gn][$sgq]['answers'][$answer['value']] = [
                        'text' => $answer['value'],
                        'N'    => (int)$answer['N'],
                        '%'    => 0
                    ];

                    $this->output[$gn][$sgq]['answers']['_X_']['N'] += (int)$answer['N'];
                }

                $this->setPercentage($sgq, $gn);
            }
        }

        $this->dropByCutoff($sgq, $gn);
    }

    /* **************************************** */

    /**
     * This function parses the 'N' type questions aka 'Numerical input'.
     *
     * (from https://manual.limesurvey.org/Question_types#Numerical_input)
     * This question types asks the survey participant to enter a single number.
     *
     * To see an output example for this type of questions you can see
     * 'N.json' file inside 'examples/questions/Mask' folder.
     *
     * @author  Tiziano Marra <https://github.com/MrTiz9>
     * @since   1.0
     *
     * @internal
     *
     * @param string $sgq       SGQ identifier
     * @param array  $qInfo     All data about question, getting from 'questions' table
     * @param string $gn        Group name
     * @param array  $qAttr     Some attributes about question, getting from 'question_attributes' table
     */

    private function parseNQuestion($sgq, $qInfo, $gn, $qAttr)
    {
        if ((!empty($this->qList) && !in_array($sgq, $this->qList)) || in_array($sgq, $this->qToExcl)) {
            return;
        }

        $qText = $this->stripTags ? strip_tags($qInfo['question']) : $qInfo['question'];

        $this->output[$gn][$sgq]['code']      = $qInfo['title'];
        $this->output[$gn][$sgq]['text']      = $qText;
        $this->output[$gn][$sgq]['type']      = 'N';
        $this->output[$gn][$sgq]['mandatory'] = $qInfo['mandatory'] === 'Y';
        $this->output[$gn][$sgq]['numonly']   = true;
        $this->output[$gn][$sgq]['hidden']    = $qAttr['hidden'];

        if ($this->questionsOnly) {
            return;
        }

        $this->output[$gn][$sgq]['answers'] = $this->getFreeTextAnswers($sgq);
        $this->dropByCutoff($sgq, $gn);
    }

    /* **************************************** */

    /**
     * This function parses the 'K' type questions aka 'Multiple numerical input'.
     *
     * (from https://manual.limesurvey.org/Question_types#Multiple_numerical_input)
     * This question type is a variation of the 'Numerical input' question type.
     * It allows multiple text boxes to be created, each of them allowing the survey respondents to enter only numbers.
     * Each text box corresponds to an subquestion, the subquestion text being the label for the input.
     *
     * To see an output example for this type of questions you can see
     * 'K.json' file inside 'examples/questions/Mask' folder.
     *
     * @author  Tiziano Marra <https://github.com/MrTiz9>
     * @since   1.0
     *
     * @internal
     *
     * @param string $sgq       SGQ identifier
     * @param array  $qInfo     All data about question, getting from 'questions' table
     * @param string $gn        Group name
     * @param array  $qAttr     Some attributes about question, getting from 'question_attributes' table
     */

    private function parseKQuestion($sgq, $qInfo, $gn, $qAttr)
    {
        $subQuestions = $this->getSubQuestions($qInfo['qid']);

        if (!empty($subQuestions)) {
            $qText = $this->stripTags ? strip_tags($qInfo['question']) : $qInfo['question'];

            $mandatory = $qInfo['mandatory'] === 'Y';

            foreach ($subQuestions as $i => $sqInfo) {
                $sgqTtl = $sgq . $sqInfo['title'];

                if ((!empty($this->qList) && !in_array($sgqTtl, $this->qList)) || in_array($sgqTtl, $this->qToExcl)) {
                    continue;
                }

                $aText = $this->stripTags ? strip_tags($sqInfo['question']) : $sqInfo['question'];

                $this->output[$gn][$sgqTtl]['code']      = $qInfo['title'] . '[' . $sqInfo['title'] . ']';
                $this->output[$gn][$sgqTtl]['text']      = $qText . ' [' . $aText . ']';
                $this->output[$gn][$sgqTtl]['type']      = 'K';
                $this->output[$gn][$sgqTtl]['mandatory'] = $mandatory;
                $this->output[$gn][$sgqTtl]['numonly']   = true;
                $this->output[$gn][$sgqTtl]['hidden']    = $qAttr['hidden'];

                if ($this->questionsOnly) {
                    continue;
                }

                $this->output[$gn][$sgqTtl]['answers'] = $this->getFreeTextAnswers($sgqTtl);
                $this->dropByCutoff($sgqTtl, $gn);
            }
        }
    }

    /* **************************************** */

    /**
     * This function parses the 'R' type questions aka 'Ranking'.
     *
     * (from https://manual.limesurvey.org/Question_types#Ranking)
     * This question type allows you to present your survey participants a list of possible answers/options,
     * which they may then rank according to their preferences.
     *
     * To see an output example for this type of questions you can see
     * 'R.json' file inside 'examples/questions/Mask' folder.
     *
     * @author  Tiziano Marra <https://github.com/MrTiz9>
     * @since   1.0
     *
     * @internal
     *
     * @param string $sgq       SGQ identifier
     * @param array  $qInfo     All data about question, getting from 'questions' table
     * @param string $gn        Group name
     * @param array  $qAttr     Some attributes about question, getting from 'question_attributes' table
     */

    private function parseRQuestion($sgq, $qInfo, $gn, $qAttr)
    {
        $codesAnswers = $this->getAnswersCodes($qInfo['qid']);

        if (!empty($codesAnswers)) {
            $qText = $this->stripTags ? strip_tags($qInfo['question']) : $qInfo['question'];

            $mandatory = $qInfo['mandatory'] === 'Y';

            $n = count($codesAnswers);

            for ($i = 1; $i <= $n; $i++) {
                $sgqI = $sgq . $i;

                if ((!empty($this->qList) && !in_array($sgqI, $this->qList)) || in_array($sgqI, $this->qToExcl)) {
                    continue;
                }

                $this->output[$gn][$sgqI]['code']      = $qInfo['title'] . '[' . $i . ']';
                $this->output[$gn][$sgqI]['text']      = $qText . ' [Ranking ' . $i . ']';
                $this->output[$gn][$sgqI]['type']      = 'R';
                $this->output[$gn][$sgqI]['mandatory'] = $mandatory;
                $this->output[$gn][$sgqI]['numonly']   = false;
                $this->output[$gn][$sgqI]['hidden']    = $qAttr['hidden'];

                if ($this->questionsOnly) {
                    continue;
                }

                foreach ($codesAnswers as $code => $answer) {
                    $this->output[$gn][$sgqI]['answers'][$code] = [
                        'text' => $this->stripTags ? strip_tags($answer) : $answer,
                        'N'    => 0,
                        '%'    => 0
                    ];
                }

                $this->output[$gn][$sgqI]['answers']['_X_'] = $this->total;

                $countAnswers = $this->getCountAnswers($sgqI);

                if (!empty($countAnswers)) {
                    foreach ($countAnswers as $k => $answer) {
                        $this->output[$gn][$sgqI]['answers'][$answer['value']]['N'] = (int)$answer['N'];
                        $this->output[$gn][$sgqI]['answers']['_X_']['N']           += (int)$answer['N'];
                    }

                    $this->setPercentage($sgqI, $gn);
                }

                $this->dropByCutoff($sgqI, $gn);
            }
        }
    }

    /* **************************************** */

    /**
     * This function parses the 'X' type questions aka 'Text display'.
     *
     * (from https://manual.limesurvey.org/Question_types#Text_display)
     * This question type does not collect any input from the respondent. It just simply displays text.
     * It can be used to provide further instructions or a design break in the survey.
     *
     * To see an output example for this type of questions you can see
     * 'X.json' file inside 'examples/questions/Mask' folder.
     *
     * @author  Tiziano Marra <https://github.com/MrTiz9>
     * @since   1.0
     *
     * @internal
     *
     * @param string $sgq       SGQ identifier
     * @param array  $qInfo     All data about question, getting from 'questions' table
     * @param string $gn        Group name
     * @param array  $qAttr     Some attributes about question, getting from 'question_attributes' table
     */

    private function parseXQuestion($sgq, $qInfo, $gn, $qAttr)
    {
        if ((!empty($this->qList) && !in_array($sgq, $this->qList)) || in_array($sgq, $this->qToExcl)) {
            return;
        }

        $qText = $this->stripTags ? strip_tags($qInfo['question']) : $qInfo['question'];

        $this->output[$gn][$sgq]['code']      = $qInfo['title'];
        $this->output[$gn][$sgq]['text']      = $qText;
        $this->output[$gn][$sgq]['type']      = 'X';
        $this->output[$gn][$sgq]['mandatory'] = $qInfo['mandatory'] === 'Y';
        $this->output[$gn][$sgq]['numonly']   = false;
        $this->output[$gn][$sgq]['hidden']    = $qAttr['hidden'];
    }

    /* **************************************** */

    /**
     * This function parses the 'Y' type questions aka 'Yes/No'.
     *
     * (from https://manual.limesurvey.org/Question_types#Yes.2FNo)
     * Survey administrators can collect 'Yes' or 'No' responses
     * from the respondents with the help of this question type.
     *
     * To see an output example for this type of questions you can see
     * 'Y.json' file inside 'examples/questions/Mask' folder.
     *
     * @author  Tiziano Marra <https://github.com/MrTiz9>
     * @since   1.0
     *
     * @internal
     *
     * @param string $sgq       SGQ identifier
     * @param array  $qInfo     All data about question, getting from 'questions' table
     * @param string $gn        Group name
     * @param array  $qAttr     Some attributes about question, getting from 'question_attributes' table
     */

    private function parseYQuestion($sgq, $qInfo, $gn, $qAttr)
    {
        if ((!empty($this->qList) && !in_array($sgq, $this->qList)) || in_array($sgq, $this->qToExcl)) {
            return;
        }

        $qText = $this->stripTags ? strip_tags($qInfo['question']) : $qInfo['question'];

        $this->output[$gn][$sgq]['code']      = $qInfo['title'];
        $this->output[$gn][$sgq]['text']      = $qText;
        $this->output[$gn][$sgq]['type']      = 'Y';
        $this->output[$gn][$sgq]['mandatory'] = $qInfo['mandatory'] === 'Y';
        $this->output[$gn][$sgq]['numonly']   = false;
        $this->output[$gn][$sgq]['hidden']    = $qAttr['hidden'];

        if ($this->questionsOnly) {
            return;
        }

        $this->output[$gn][$sgq]['answers']['N'] = [
            'text' => 'No',
            'N'    => 0,
            '%'    => 0
        ];

        $this->output[$gn][$sgq]['answers']['Y'] = [
            'text' => 'Yes',
            'N'    => 0,
            '%'    => 0
        ];

        $this->output[$gn][$sgq]['answers']['_X_'] = $this->total;

        $values = $this->getCountAnswers($sgq);

        if (!empty($values)) {
            foreach ($values as $i => $answer) {
                $this->output[$gn][$sgq]['answers'][$answer['value']]['N'] = (int)$answer['N'];
                $this->output[$gn][$sgq]['answers']['_X_']['N']           += (int)$answer['N'];
            }

            $this->setPercentage($sgq, $gn);
        }

        $this->dropByCutoff($sgq, $gn);
    }

    /* **************************************** */

    /**
     * This function parses the '*' type questions aka 'Equation'.
     *
     * (from https://manual.limesurvey.org/Question_types#Equation)
     * This question type lets the author create an equation (e.g., a calculation or tailored report)
     * and save it in a database variable.
     * Equations can use any syntax supported by Expression Manager (https://manual.limesurvey.org/Expression_Manager).
     *
     * To see an output example for this type of questions you can see
     * 'Star.json' file inside 'examples/questions/Mask' folder.
     *
     * @author  Tiziano Marra <https://github.com/MrTiz9>
     * @since   1.0
     *
     * @internal
     *
     * @param string $sgq       SGQ identifier
     * @param array  $qInfo     All data about question, getting from 'questions' table
     * @param string $gn        Group name
     * @param array  $qAttr     Some attributes about question, getting from 'question_attributes' table
     */

    private function parseStarQuestion($sgq, $qInfo, $gn, $qAttr)
    {
        if ((!empty($this->qList) && !in_array($sgq, $this->qList)) || in_array($sgq, $this->qToExcl)) {
            return;
        }

        $qText = $this->stripTags ? strip_tags($qInfo['question']) : $qInfo['question'];

        $this->output[$gn][$sgq]['code']      = $qInfo['title'];
        $this->output[$gn][$sgq]['text']      = $qText;
        $this->output[$gn][$sgq]['type']      = '*';
        $this->output[$gn][$sgq]['mandatory'] = $qInfo['mandatory'] === 'Y';
        $this->output[$gn][$sgq]['numonly']   = $qAttr['numbers_only'];
        $this->output[$gn][$sgq]['hidden']    = $qAttr['hidden'];

        if ($this->questionsOnly) {
            return;
        }

        $this->output[$gn][$sgq]['answers'] = $this->getFreeTextAnswers($sgq);
        $this->dropByCutoff($sgq, $gn);
    }

    /* **************************************** */

    /**
     * MULTIPLE CHOICE QUESTIONS (from https://manual.limesurvey.org/Question_types#Multiple_choice_questions)
     *
     * Sometimes you want the participan to mark more than one answer option in the same question;
     * this is achieved using checkboxes.
     */

    /* **************************************** */

    /**
     * This function parses the 'M' type questions aka 'Multiple choice'.
     *
     * (from https://manual.limesurvey.org/Question_types#Multiple_choice)
     * This question type can collect input of multiple selections through checkboxes.
     *
     * NOTE: LimeSurvey by default creates as many columns as there are sub-questions.
     *       This function 'compresses' all sub-questions into one question.
     *
     * To see an output example for this type of questions you can see
     * 'M.json' file inside 'examples/questions/Multiple choice' folder.
     *
     * @author  Tiziano Marra <https://github.com/MrTiz9>
     * @since   1.0
     *
     * @internal
     *
     * @param string $sgq       SGQ identifier
     * @param array  $qInfo     All data about question, getting from 'questions' table
     * @param string $gn        Group name
     * @param array  $qAttr     Some attributes about question, getting from 'question_attributes' table
     */

    private function parseMQuestion($sgq, $qInfo, $gn, $qAttr)
    {
        if ((!empty($this->qList) && !in_array($sgq, $this->qList)) || in_array($sgq, $this->qToExcl)) {
            return;
        }

        $qText = $this->stripTags ? strip_tags($qInfo['question']) : $qInfo['question'];

        $this->output[$gn][$sgq]['code']      = $qInfo['title'];
        $this->output[$gn][$sgq]['text']      = $qText;
        $this->output[$gn][$sgq]['type']      = 'M';
        $this->output[$gn][$sgq]['mandatory'] = $qInfo['mandatory'] === 'Y';
        $this->output[$gn][$sgq]['numonly']   = false;
        $this->output[$gn][$sgq]['hidden']    = $qAttr['hidden'];

        if ($qInfo['other'] === 'Y') {
            $sgqOth = $sgq . 'other';

            if ((empty($this->qList) || in_array($sgqOth, $this->qList)) && !in_array($sgqOth, $this->qToExcl)) {
                $this->output[$gn][$sgqOth]['code']      = $qInfo['title'] . '[other]';
                $this->output[$gn][$sgqOth]['text']      = $qText . ' [Other]';
                $this->output[$gn][$sgqOth]['type']      = 'M';
                $this->output[$gn][$sgqOth]['mandatory'] = $qAttr['other_comment_mandatory'];
                $this->output[$gn][$sgqOth]['numonly']   = $qAttr['other_numbers_only'];
                $this->output[$gn][$sgqOth]['hidden']    = $qAttr['hidden'];

                if ($this->questionsOnly) {
                    return;
                }

                $this->output[$gn][$sgqOth]['answers'] = $this->getFreeTextAnswers($sgqOth);
                $this->dropByCutoff($sgqOth, $gn);
            }
        }

        if ($this->questionsOnly) {
            return;
        }

        $subQuestions = $this->getSubQuestions($qInfo['qid']);

        if (!empty($subQuestions)) {
            $this->output[$gn][$sgq]['answers']['_X_'] = $this->total;

            foreach ($subQuestions as $i => $sqInfo) {
                $sgqaCount = $this->getCountSGQA($sgq . $sqInfo['title']);
                $n = !empty($sgqaCount) ? (int)$sgqaCount : 0;

                $this->output[$gn][$sgq]['answers'][$sqInfo['title']] = [
                    'text' => $this->stripTags ? strip_tags($sqInfo['question']) : $sqInfo['question'],
                    'N'    => $n,
                    '%'    => 0
                ];

                $this->output[$gn][$sgq]['answers']['_X_']['N'] += $n;
            }

            if ($qInfo['other'] === 'Y') {
                $sgqOth = $sgq . 'other';

                $this->output[$gn][$sgq]['answers']['other'] = [
                    'text' => 'Other',
                    'N'    => count($this->output[$gn][$sgqOth]['answers']),
                    '%'    => 0
                ];
            }

            $this->setPercentage($sgq, $gn);
            $this->moveTotalToEnd($sgq, $gn);
        }

        $this->dropByCutoff($sgq, $gn);
    }

    /* **************************************** */

    /**
     * This function parses the 'P' type questions aka 'Multiple choice with comments'.
     *
     * (from https://manual.limesurvey.org/Question_types#Multiple_choice_with_comments)
     * This question type can collect input of multiple selections through checkboxes,
     * while allowing the user to provide additional comments.
     *
     * NOTE: LimeSurvey by default creates as many columns as there are sub-questions.
     *       This function 'compresses' all sub-questions into one question,
     *       for columns that don't contain comments ('{SGQA}comment').
     *
     * To see an output example for this type of questions you can see
     * 'P.json' file inside 'examples/questions/Multiple choice' folder.
     *
     * @author  Tiziano Marra <https://github.com/MrTiz9>
     * @since   1.0
     *
     * @internal
     *
     * @param string $sgq       SGQ identifier
     * @param array  $qInfo     All data about question, getting from 'questions' table
     * @param string $gn        Group name
     * @param array  $qAttr     Some attributes about question, getting from 'question_attributes' table
     */

    private function parsePQuestion($sgq, $qInfo, $gn, $qAttr)
    {
        if ((!empty($this->qList) && !in_array($sgq, $this->qList)) || in_array($sgq, $this->qToExcl)) {
            return;
        }

        $qText = $this->stripTags ? strip_tags($qInfo['question']) : $qInfo['question'];

        $mandatory = $qInfo['mandatory'] === 'Y';

        $this->output[$gn][$sgq]['code']      = $qInfo['title'];
        $this->output[$gn][$sgq]['text']      = $qText;
        $this->output[$gn][$sgq]['type']      = 'P';
        $this->output[$gn][$sgq]['mandatory'] = $mandatory;
        $this->output[$gn][$sgq]['numonly']   = false;
        $this->output[$gn][$sgq]['hidden']    = $qAttr['hidden'];

        $subQuestions = $this->getSubQuestions($qInfo['qid']);

        if (!empty($subQuestions)) {
            foreach ($subQuestions as $i => $sqInfo) {
                $sgqTtlComment = $sgq . $sqInfo['title'] . 'comment';

                if ((!empty($this->qList) && !in_array($sgqTtlComment, $this->qList)) ||
                    in_array($sgqTtlComment, $this->qToExcl)) {
                    continue;
                }

                $this->output[$gn][$sgqTtlComment]['code']      = $qInfo['title'] . '[' . $sqInfo['title'] . 'comment]';
                $this->output[$gn][$sgqTtlComment]['text']      = $qText . ' [' . $sqInfo['question'] . '] [Comment]';
                $this->output[$gn][$sgqTtlComment]['type']      = 'P';
                $this->output[$gn][$sgqTtlComment]['mandatory'] = $mandatory;
                $this->output[$gn][$sgqTtlComment]['numonly']   = false;
                $this->output[$gn][$sgqTtlComment]['hidden']    = $qAttr['hidden'];

                if ($this->questionsOnly) {
                    continue;
                }

                $this->output[$gn][$sgqTtlComment]['answers'] = $this->getFreeTextAnswers($sgqTtlComment);
                $this->dropByCutoff($sgqTtlComment, $gn);
            }

            if ($qInfo['other'] === 'Y') {
                $sgqOth = $sgq . 'other';

                if ((empty($this->qList) || in_array($sgqOth, $this->qList)) && !in_array($sgqOth, $this->qToExcl)) {
                    $this->output[$gn][$sgqOth]['code']      = $qInfo['title'] . '[other]';
                    $this->output[$gn][$sgqOth]['text']      = $qText . ' [Other]';
                    $this->output[$gn][$sgqOth]['type']      = 'P';
                    $this->output[$gn][$sgqOth]['mandatory'] = $qAttr['other_comment_mandatory'];
                    $this->output[$gn][$sgqOth]['numonly']   = $qAttr['other_numbers_only'];
                    $this->output[$gn][$sgqOth]['hidden']    = $qAttr['hidden'];

                    if ($this->questionsOnly) {
                        return;
                    }

                    $this->output[$gn][$sgqOth]['answers'] = $this->getFreeTextAnswers($sgqOth);
                    $this->dropByCutoff($sgqOth, $gn);
                }
            }

            if ($this->questionsOnly) {
                return;
            }

            $this->output[$gn][$sgq]['answers']['_X_'] = $this->total;

            foreach ($subQuestions as $i => $sqInfo) {
                $sgqaCount = $this->getCountSGQA($sgq . $sqInfo['title']);
                $n = !empty($sgqaCount) ? (int)$sgqaCount : 0;

                $this->output[$gn][$sgq]['answers'][$sqInfo['title']] = [
                    'text' => $this->stripTags ? strip_tags($sqInfo['question']) : $sqInfo['question'],
                    'N'    => $n,
                    '%'    => 0
                ];

                $this->output[$gn][$sgq]['answers']['_X_']['N'] += $n;
            }

            if ($qInfo['other'] === 'Y') {
                $sgqOth = $sgq . 'other';

                $this->output[$gn][$sgq]['answers']['other'] = [
                    'text' => 'Other',
                    'N'    => count($this->output[$gn][$sgqOth]['answers']),
                    '%'    => 0
                ];
            }

            $this->setPercentage($sgq, $gn);
            $this->moveTotalToEnd($sgq, $gn);
            $this->dropByCutoff($sgq, $gn);
        }
    }

    /* **************************************** */

    /**
     * SINGLE CHOICE QUESTIONS (from https://manual.limesurvey.org/Question_types#Single_choice_questions)
     *
     * Single choice questions are those where the participant can only pick a single predefined answer option.
     */

    /* **************************************** */

    /**
     * This function parses the '5' type questions aka '5 point choice'.
     *
     * (from https://manual.limesurvey.org/Question_types#5_point_choice)
     * This question shows a horizontal 1 to 5 scale from where
     * the survey participants can select a single answer option.
     *
     * To see an output example for this type of questions you can see
     * '5.json' file inside 'examples/questions/Single choice' folder.
     *
     * @author  Tiziano Marra <https://github.com/MrTiz9>
     * @since   1.0
     *
     * @internal
     *
     * @param string $sgq       SGQ identifier
     * @param array  $qInfo     All data about question, getting from 'questions' table
     * @param string $gn        Group name
     * @param array  $qAttr     Some attributes about question, getting from 'question_attributes' table
     */

    private function parse5Question($sgq, $qInfo, $gn, $qAttr)
    {
        if ((!empty($this->qList) && !in_array($sgq, $this->qList)) || in_array($sgq, $this->qToExcl)) {
            return;
        }

        $qText = $this->stripTags ? strip_tags($qInfo['question']) : $qInfo['question'];

        $this->output[$gn][$sgq]['code']      = $qInfo['title'];
        $this->output[$gn][$sgq]['text']      = $qText;
        $this->output[$gn][$sgq]['type']      = '5';
        $this->output[$gn][$sgq]['mandatory'] = $qInfo['mandatory'] === 'Y';
        $this->output[$gn][$sgq]['numonly']   = false;
        $this->output[$gn][$sgq]['hidden']    = $qAttr['hidden'];

        if ($this->questionsOnly) {
            return;
        }

        for ($i = 1; $i <= 5; $i++) {
            $this->output[$gn][$sgq]['answers'][$i] = [
                'text' => (string)$i,
                'N'    => 0,
                '%'    => 0
            ];
        }

        $this->output[$gn][$sgq]['answers']['_X_'] = $this->total;

        $countAnswers = $this->getCountAnswers($sgq);

        if (!empty($countAnswers)) {
            foreach ($countAnswers as $k => $answer) {
                $this->output[$gn][$sgq]['answers'][$answer['value']]['N'] = (int)$answer['N'];
                $this->output[$gn][$sgq]['answers']['_X_']['N']           += (int)$answer['N'];
            }

            $this->setPercentage($sgq, $gn);
        }

        $this->dropByCutoff($sgq, $gn);
    }

    /* **************************************** */

    /**
     * This function parses the '!' type questions aka 'List (dropdown)'.
     *
     * (from https://manual.limesurvey.org/Question_types#List_.28Dropdown.29)
     * This question type collects input from a dropdown list menu.
     *
     * To see an output example for this type of questions you can see
     * 'Dropdown.json' file inside 'examples/questions/Single choice' folder.
     *
     * @author  Tiziano Marra <https://github.com/MrTiz9>
     * @since   1.0
     *
     * @internal
     *
     * @param string $sgq       SGQ identifier
     * @param array  $qInfo     All data about question, getting from 'questions' table
     * @param string $gn        Group name
     * @param array  $qAttr     Some attributes about question, getting from 'question_attributes' table
     */

    private function parseDropdownQuestion($sgq, $qInfo, $gn, $qAttr)
    {
        if ((!empty($this->qList) && !in_array($sgq, $this->qList)) || in_array($sgq, $this->qToExcl)) {
            return;
        }

        $qText = $this->stripTags ? strip_tags($qInfo['question']) : $qInfo['question'];

        $this->output[$gn][$sgq]['code']      = $qInfo['title'];
        $this->output[$gn][$sgq]['text']      = $qText;
        $this->output[$gn][$sgq]['type']      = '!';
        $this->output[$gn][$sgq]['mandatory'] = $qInfo['mandatory'] === 'Y';
        $this->output[$gn][$sgq]['numonly']   = false;
        $this->output[$gn][$sgq]['hidden']    = $qAttr['hidden'];

        if ($qInfo['other'] === 'Y') {
            $sgqOth = $sgq . 'other';

            if ((empty($this->qList) || in_array($sgqOth, $this->qList)) && !in_array($sgqOth, $this->qToExcl)) {
                $this->output[$gn][$sgqOth]['code']      = $qInfo['title'] . '[other]';
                $this->output[$gn][$sgqOth]['text']      = $qText . ' [Other]';
                $this->output[$gn][$sgqOth]['type']      = '!';
                $this->output[$gn][$sgqOth]['mandatory'] = $qAttr['other_comment_mandatory'];
                $this->output[$gn][$sgqOth]['numonly']   = $qAttr['other_numbers_only'];
                $this->output[$gn][$sgqOth]['hidden']    = $qAttr['hidden'];

                if ($this->questionsOnly) {
                    return;
                }

                $this->output[$gn][$sgqOth]['answers'] = $this->getFreeTextAnswers($sgqOth);
                $this->dropByCutoff($sgqOth, $gn);
            }
        }

        if ($this->questionsOnly) {
            return;
        }

        $codesAnswers = $this->getAnswersCodes($qInfo['qid']);

        if (!empty($codesAnswers)) {
            foreach ($codesAnswers as $code => $answer) {
                $this->output[$gn][$sgq]['answers'][$code] = [
                    'text' => $this->stripTags ? strip_tags($answer) : $answer,
                    'N'    => 0,
                    '%'    => 0
                ];
            }

            if ($qInfo['other'] === 'Y') {
                $this->output[$gn][$sgq]['answers']['-oth-'] = [
                    'text' => 'Other',
                    'N'    => 0,
                    '%'    => 0
                ];
            }

            $this->output[$gn][$sgq]['answers']['_X_'] = $this->total;

            $countAnswers = $this->getCountAnswers($sgq);

            if (!empty($countAnswers)) {
                foreach ($countAnswers as $k => $answer) {
                    $this->output[$gn][$sgq]['answers'][$answer['value']]['N'] = (int)$answer['N'];
                    $this->output[$gn][$sgq]['answers']['_X_']['N']           += (int)$answer['N'];
                }

                $this->setPercentage($sgq, $gn);
            }
        }

        $this->dropByCutoff($sgq, $gn);
    }

    /* **************************************** */

    /**
     * This function parses the 'L' type questions aka 'List (radio)'.
     *
     * (from https://manual.limesurvey.org/Question_types#List_.28Radio.29)
     * This question type collects input from a list of radio buttons.
     *
     * To see an output example for this type of questions you can see
     * 'L.json' file inside 'examples/questions/Single choice' folder.
     *
     * @author  Tiziano Marra <https://github.com/MrTiz9>
     * @since   1.0
     *
     * @internal
     *
     * @param string $sgq       SGQ identifier
     * @param array  $qInfo     All data about question, getting from 'questions' table
     * @param string $gn        Group name
     * @param array  $qAttr     Some attributes about question, getting from 'question_attributes' table
     */

    private function parseLQuestion($sgq, $qInfo, $gn, $qAttr)
    {
        if ((!empty($this->qList) && !in_array($sgq, $this->qList)) || in_array($sgq, $this->qToExcl)) {
            return;
        }

        $qText = $this->stripTags ? strip_tags($qInfo['question']) : $qInfo['question'];

        $this->output[$gn][$sgq]['code']      = $qInfo['title'];
        $this->output[$gn][$sgq]['text']      = $qText;
        $this->output[$gn][$sgq]['type']      = 'L';
        $this->output[$gn][$sgq]['mandatory'] = $qInfo['mandatory'] === 'Y';
        $this->output[$gn][$sgq]['numonly']   = false;
        $this->output[$gn][$sgq]['hidden']    = $qAttr['hidden'];

        if ($qInfo['other'] === 'Y') {
            $sgqOth = $sgq . 'other';

            if ((empty($this->qList) || in_array($sgqOth, $this->qList)) && !in_array($sgqOth, $this->qToExcl)) {
                $this->output[$gn][$sgqOth]['code']      = $qInfo['title'] . '[other]';
                $this->output[$gn][$sgqOth]['text']      = $qText . ' [Other]';
                $this->output[$gn][$sgqOth]['type']      = 'L';
                $this->output[$gn][$sgqOth]['mandatory'] = $qAttr['other_comment_mandatory'];
                $this->output[$gn][$sgqOth]['numonly']   = $qAttr['other_numbers_only'];
                $this->output[$gn][$sgqOth]['hidden']    = $qAttr['hidden'];

                if ($this->questionsOnly) {
                    return;
                }

                $this->output[$gn][$sgqOth]['answers'] = $this->getFreeTextAnswers($sgqOth);
                $this->dropByCutoff($sgqOth, $gn);
            }
        }

        if ($this->questionsOnly) {
            return;
        }

        $codesAnswers = $this->getAnswersCodes($qInfo['qid']);

        if (!empty($codesAnswers)) {
            foreach ($codesAnswers as $code => $answer) {
                $this->output[$gn][$sgq]['answers'][$code] = [
                    'text' => $this->stripTags ? strip_tags($answer) : $answer,
                    'N'    => 0,
                    '%'    => 0
                ];
            }

            if ($qInfo['other'] === 'Y') {
                $this->output[$gn][$sgq]['answers']['-oth-'] = [
                    'text' => 'Other',
                    'N'    => 0,
                    '%'    => 0
                ];
            }

            $this->output[$gn][$sgq]['answers']['_X_'] = $this->total;

            $countAnswers = $this->getCountAnswers($sgq);

            if (!empty($countAnswers)) {
                foreach ($countAnswers as $k => $answer) {
                    $this->output[$gn][$sgq]['answers'][$answer['value']]['N'] = (int)$answer['N'];
                    $this->output[$gn][$sgq]['answers']['_X_']['N']           += (int)$answer['N'];
                }

                $this->setPercentage($sgq, $gn);
            }
        }

        $this->dropByCutoff($sgq, $gn);
    }

    /* **************************************** */

    /**
     * This function parses the 'O' type questions aka 'List with comment'.
     *
     * (from https://manual.limesurvey.org/Question_types#List_with_comment)
     * This question type displays a list of radio buttons, while allowing the participants to provide a additional
     * comment with their submission.
     *
     * To see an output example for this type of questions you can see
     * 'O.json' file inside 'examples/questions/Single choice' folder.
     *
     * @author  Tiziano Marra <https://github.com/MrTiz9>
     * @since   1.0
     *
     * @internal
     *
     * @param string $sgq       SGQ identifier
     * @param array  $qInfo     All data about question, getting from 'questions' table
     * @param string $gn        Group name
     * @param array  $qAttr     Some attributes about question, getting from 'question_attributes' table
     */

    private function parseOQuestion($sgq, $qInfo, $gn, $qAttr)
    {
        if ((!empty($this->qList) && !in_array($sgq, $this->qList)) || in_array($sgq, $this->qToExcl)) {
            return;
        }

        $qText = $this->stripTags ? strip_tags($qInfo['question']) : $qInfo['question'];

        $mandatory  = $qInfo['mandatory'] === 'Y';

        $this->output[$gn][$sgq]['code']      = $qInfo['title'];
        $this->output[$gn][$sgq]['text']      = $qText;
        $this->output[$gn][$sgq]['type']      = 'O';
        $this->output[$gn][$sgq]['mandatory'] = $mandatory;
        $this->output[$gn][$sgq]['numonly']   = false;
        $this->output[$gn][$sgq]['hidden']    = $qAttr['hidden'];

        $sgqComment = $sgq . 'comment';

        if ((empty($this->qList) || in_array($sgqComment, $this->qList)) && !in_array($sgqComment, $this->qToExcl)) {
            $this->output[$gn][$sgqComment]['code']      = $qInfo['title'] . '[comment]';
            $this->output[$gn][$sgqComment]['text']      = $qText . ' [Comment]';
            $this->output[$gn][$sgqComment]['type']      = 'O';
            $this->output[$gn][$sgqComment]['mandatory'] = $mandatory;
            $this->output[$gn][$sgqComment]['numonly']   = false;
            $this->output[$gn][$sgqComment]['hidden']    = $qAttr['hidden'];

            if ($this->questionsOnly) {
                return;
            }

            $this->output[$gn][$sgqComment]['answers'] = $this->getFreeTextAnswers($sgqComment);
            $this->dropByCutoff($sgqComment, $gn);
        }

        if ($this->questionsOnly) {
            return;
        }

        $codesAnswers = $this->getAnswersCodes($qInfo['qid']);

        if (!empty($codesAnswers)) {
            foreach ($codesAnswers as $code => $answer) {
                $this->output[$gn][$sgq]['answers'][$code] = [
                    'text' => $this->stripTags ? strip_tags($answer) : $answer,
                    'N'    => 0,
                    '%'    => 0
                ];
            }

            $this->output[$gn][$sgq]['answers']['_X_'] = $this->total;

            $countAnswers = $this->getCountAnswers($sgq);

            if (!empty($countAnswers)) {
                foreach ($countAnswers as $k => $answer) {
                    $this->output[$gn][$sgq]['answers'][$answer['value']]['N'] = (int)$answer['N'];
                    $this->output[$gn][$sgq]['answers']['_X_']['N']           += (int)$answer['N'];
                }

                $this->setPercentage($sgq, $gn);
            }
        }

        $this->dropByCutoff($sgq, $gn);
    }

    /* **************************************** */

    /**
     * TEXT QUESTIONS
     */

    /* **************************************** */

    /**
     * This function parses the 'S' type questions aka 'Short free text'.
     *
     * (from https://manual.limesurvey.org/Question_types#Short_free_text)
     * This question type collects a single line of text input.
     *
     * To see an output example for this type of questions you can see
     * 'S.json' file inside 'examples/questions/Text' folder.
     *
     * @author  Tiziano Marra <https://github.com/MrTiz9>
     * @since   1.0
     *
     * @internal
     *
     * @param string $sgq       SGQ identifier
     * @param array  $qInfo     All data about question, getting from 'questions' table
     * @param string $gn        Group name
     * @param array  $qAttr     Some attributes about question, getting from 'question_attributes' table
     */

    private function parseSQuestion($sgq, $qInfo, $gn, $qAttr)
    {
        if ((!empty($this->qList) && !in_array($sgq, $this->qList)) || in_array($sgq, $this->qToExcl)) {
            return;
        }

        $qText = $this->stripTags ? strip_tags($qInfo['question']) : $qInfo['question'];

        $this->output[$gn][$sgq]['code']      = $qInfo['title'];
        $this->output[$gn][$sgq]['text']      = $qText;
        $this->output[$gn][$sgq]['type']      = 'S';
        $this->output[$gn][$sgq]['mandatory'] = $qInfo['mandatory'] === 'Y';
        $this->output[$gn][$sgq]['numonly']   = $qAttr['numbers_only'];
        $this->output[$gn][$sgq]['hidden']    = $qAttr['hidden'];

        if ($this->questionsOnly) {
            return;
        }

        $this->output[$gn][$sgq]['answers'] = $this->getFreeTextAnswers($sgq);
        $this->dropByCutoff($sgq, $gn);
    }

    /* **************************************** */

    /**
     * This function parses the 'T' type questions aka 'Long free text'.
     *
     * (from https://manual.limesurvey.org/Question_types#Long_free_text)
     * This question type collects multiple lines of text input.
     *
     * To see an output example for this type of questions you can see
     * 'T.json' file inside 'examples/questions/Text' folder.
     *
     * @author  Tiziano Marra <https://github.com/MrTiz9>
     * @since   1.0
     *
     * @internal
     *
     * @param string $sgq       SGQ identifier
     * @param array  $qInfo     All data about question, getting from 'questions' table
     * @param string $gn        Group name
     * @param array  $qAttr     Some attributes about question, getting from 'question_attributes' table
     */

    private function parseTQuestion($sgq, $qInfo, $gn, $qAttr)
    {
        if ((!empty($this->qList) && !in_array($sgq, $this->qList)) || in_array($sgq, $this->qToExcl)) {
            return;
        }

        $qText = $this->stripTags ? strip_tags($qInfo['question']) : $qInfo['question'];

        $this->output[$gn][$sgq]['code']      = $qInfo['title'];
        $this->output[$gn][$sgq]['text']      = $qText;
        $this->output[$gn][$sgq]['type']      = 'T';
        $this->output[$gn][$sgq]['mandatory'] = $qInfo['mandatory'] === 'Y';
        $this->output[$gn][$sgq]['numonly']   = false;
        $this->output[$gn][$sgq]['hidden']    = $qAttr['hidden'];

        if ($this->questionsOnly) {
            return;
        }

        $this->output[$gn][$sgq]['answers'] = $this->getFreeTextAnswers($sgq);
        $this->dropByCutoff($sgq, $gn);
    }

    /* **************************************** */

    /**
     * This function parses the 'U' type questions aka 'Huge free text'.
     *
     * (from https://manual.limesurvey.org/Question_types#Huge_free_text)
     * This question type collect multiple lines of text input, allowing more text to be typed in.
     *
     * To see an output example for this type of questions you can see
     * 'U.json' file inside 'examples/questions/Text' folder.
     *
     * @author  Tiziano Marra <https://github.com/MrTiz9>
     * @since   1.0
     *
     * @internal
     *
     * @param string $sgq       SGQ identifier
     * @param array  $qInfo     All data about question, getting from 'questions' table
     * @param string $gn        Group name
     * @param array  $qAttr     Some attributes about question, getting from 'question_attributes' table
     */

    private function parseUQuestion($sgq, $qInfo, $gn, $qAttr)
    {
        if ((!empty($this->qList) && !in_array($sgq, $this->qList)) || in_array($sgq, $this->qToExcl)) {
            return;
        }

        $qText = $this->stripTags ? strip_tags($qInfo['question']) : $qInfo['question'];

        $this->output[$gn][$sgq]['code']      = $qInfo['title'];
        $this->output[$gn][$sgq]['text']      = $qText;
        $this->output[$gn][$sgq]['type']      = 'U';
        $this->output[$gn][$sgq]['mandatory'] = $qInfo['mandatory'] === 'Y';
        $this->output[$gn][$sgq]['numonly']   = false;
        $this->output[$gn][$sgq]['hidden']    = $qAttr['hidden'];

        if ($this->questionsOnly) {
            return;
        }

        $this->output[$gn][$sgq]['answers'] = $this->getFreeTextAnswers($sgq);
        $this->dropByCutoff($sgq, $gn);
    }

    /* **************************************** */

    /**
     * This function parses the 'Q' type questions aka 'Multiple short text'.
     *
     * (from https://manual.limesurvey.org/Question_types#Multiple_short_text)
     * This question type is a variation on the 'Short Text' question type which allows
     * more than one text entry per question.
     * The user first defines the question and can then add additional text boxes by adding 'Answers'.
     * Each answer becomes the label of the new text box.
     *
     * NOTE: For this type of question, an additional object containing the count of the answers
     *       to the sub-questions will be added.
     *
     * To see an output example for this type of questions you can see
     * 'Q.json' file inside 'examples/questions/Text' folder.
     *
     * @author  Tiziano Marra <https://github.com/MrTiz9>
     * @since   1.0
     *
     * @internal
     *
     * @param string $sgq       SGQ identifier
     * @param array  $qInfo     All data about question, getting from 'questions' table
     * @param string $gn        Group name
     * @param array  $qAttr     Some attributes about question, getting from 'question_attributes' table
     */

    private function parseQQuestion($sgq, $qInfo, $gn, $qAttr)
    {
        if ((!empty($this->qList) && !in_array($sgq, $this->qList)) || in_array($sgq, $this->qToExcl)) {
            return;
        }

        $qText = $this->stripTags ? strip_tags($qInfo['question']) : $qInfo['question'];

        $mandatory = $qInfo['mandatory'] === 'Y';

        $this->output[$gn][$sgq]['code']      = $qInfo['title'];
        $this->output[$gn][$sgq]['text']      = $qText;
        $this->output[$gn][$sgq]['type']      = 'Q';
        $this->output[$gn][$sgq]['mandatory'] = $mandatory;
        $this->output[$gn][$sgq]['numonly']   = false;
        $this->output[$gn][$sgq]['hidden']    = $qAttr['hidden'];

        $subQuestions = $this->getSubQuestions($qInfo['qid']);

        if (!empty($subQuestions)) {
            $this->output[$gn][$sgq]['answers']['_X_'] = $this->total;

            foreach ($subQuestions as $i => $sqInfo) {
                $sgqTtl = $sgq . $sqInfo['title'];

                if ((!empty($this->qList) && !in_array($sgqTtl, $this->qList)) || in_array($sgqTtl, $this->qToExcl)) {
                    continue;
                }

                $aText = $this->stripTags ? strip_tags($sqInfo['question']) : $sqInfo['question'];

                $this->output[$gn][$sgqTtl]['code']      = $qInfo['title'] . '[' . $sqInfo['title'] . ']';
                $this->output[$gn][$sgqTtl]['text']      = $qText . ' [' . $aText . ']';
                $this->output[$gn][$sgqTtl]['type']      = 'Q';
                $this->output[$gn][$sgqTtl]['mandatory'] = $mandatory;
                $this->output[$gn][$sgqTtl]['numonly']   = $qAttr['numbers_only'];
                $this->output[$gn][$sgqTtl]['hidden']    = $qAttr['hidden'];

                if ($this->questionsOnly) {
                    unset($this->output[$gn][$sgq]['answers']);
                    continue;
                }

                $this->output[$gn][$sgqTtl]['answers'] = $this->getFreeTextAnswers($sgqTtl);
                $this->dropByCutoff($sgqTtl, $gn);

                $n = isset($this->output[$gn][$sgqTtl]['answers']) ? count($this->output[$gn][$sgqTtl]['answers']) : 0;

                $this->output[$gn][$sgq]['answers'][$sqInfo['title']] = [
                    'text' => $aText,
                    'N'    => $n,
                    '%'    => 0
                ];

                $this->output[$gn][$sgq]['answers']['_X_']['N'] += $n;
            }

            if ($this->questionsOnly) {
                return;
            }

            $this->setPercentage($sgq, $gn);
            $this->moveTotalToEnd($sgq, $gn);
        }

        $this->dropByCutoff($sgq, $gn);
    }

    /* **************************************** */

    /**
     * This function parses the unknown type questions.
     *
     * @author  Tiziano Marra <https://github.com/MrTiz9>
     * @since   1.0
     *
     * @internal
     *
     * @param string $sgq       SGQ identifier
     * @param array  $qInfo     All data about question, getting from 'questions' table
     * @param string $gn        Group name
     * @param array  $qAttr     Some attributes about question, getting from 'question_attributes' table
     */

    private function parseUnknownQuestion($sgq, $qInfo, $gn, $qAttr)
    {
        if ((!empty($this->qList) && !in_array($sgq, $this->qList)) || in_array($sgq, $this->qToExcl)) {
            return;
        }

        $qText = $this->stripTags ? strip_tags($qInfo['question']) : $qInfo['question'];

        $this->output[$gn][$sgq]['code']      = $qInfo['title'];
        $this->output[$gn][$sgq]['text']      = $qText;
        $this->output[$gn][$sgq]['type']      = $qInfo['type'];
        $this->output[$gn][$sgq]['mandatory'] = $qInfo['mandatory'] === 'Y';
        $this->output[$gn][$sgq]['numonly']   = $qAttr['numbers_only'];
        $this->output[$gn][$sgq]['hidden']    = $qAttr['hidden'];

        if ($this->questionsOnly) {
            return;
        }

        $this->output[$gn][$sgq]['error'] = 'ERROR: unknown question type';
    }

    /* **************************************** */

    /**
     * This function moves to the end the object identified by the key '_X_'.
     * The identified object is located inside the 'answers' object.
     *
     * @author  Tiziano Marra <https://github.com/MrTiz9>
     * @since   1.0
     *
     * @internal
     *
     * @param string $sgq   SGQ identifier
     * @param string $gn    Group name
     */

    private function moveTotalToEnd($sgq, $gn)
    {
        $aux = $this->output[$gn][$sgq]['answers']['_X_'];
        unset($this->output[$gn][$sgq]['answers']['_X_']);
        $this->output[$gn][$sgq]['answers']['_X_'] = $aux;
    }

    /* **************************************** */

    /**
     * Set the response rate for each answer.
     *
     * @author  Tiziano Marra <https://github.com/MrTiz9>
     * @since   1.0
     *
     * @internal
     *
     * @param string $sgq   SGQ identifier
     * @param string $gn    Group name
     */

    private function setPercentage($sgq, $gn)
    {
        $denominator = $this->output[$gn][$sgq]['answers']['_X_']['N'];

        foreach ($this->output[$gn][$sgq]['answers'] as $k => &$values) {
            if ($k !== '_X_') {
                $percentage  = $denominator === 0 ? 0 : ($values['N'] / $denominator) * 100;
                $values['%'] = round($percentage, 2);
            }
        }
    }

    /* **************************************** */

    /**
     * Discard all the answers whose cardinality is less than the cutoff.
     *
     * @author  Tiziano Marra <https://github.com/MrTiz9>
     * @since   1.0
     *
     * @internal
     *
     * @param string $sgq   SGQ identifier
     * @param string $gn    Group name
     */

    private function dropByCutoff($sgq, $gn)
    {
        $errorMsg = 'The number of responses is insufficient to display the data';

        if (!array_key_exists('answers', $this->output[$gn][$sgq]) && $this->cutoff > 0) {
            $this->output[$gn][$sgq]['error'] = $errorMsg;
        } elseif (!array_key_exists('answers', $this->output[$gn][$sgq]) && $this->cutoff <= 0) {
            $this->output[$gn][$sgq]['answers'] = [];
        } elseif (array_key_exists('_X_', $this->output[$gn][$sgq]['answers'])) {
            if ($this->output[$gn][$sgq]['answers']['_X_']['N'] < $this->cutoff) {
                unset($this->output[$gn][$sgq]['answers']);
                $this->output[$gn][$sgq]['error'] = $errorMsg;
            }
        } else {
            if (count($this->output[$gn][$sgq]['answers']) < $this->cutoff) {
                unset($this->output[$gn][$sgq]['answers']);
                $this->output[$gn][$sgq]['error'] = $errorMsg;
            }
        }
    }

    /* **************************************** */

    /**
     * Check whether a valid array of IDs has been passed.
     *
     * @author  Tiziano Marra <https://github.com/MrTiz9>
     * @since   1.0
     *
     * @internal
     *
     * @param  array $IDs    Array of IDs
     *
     * @return bool
     */

    private function checkValidIDs($IDs)
    {
        if (!is_array($IDs)) {
            return false;
        }

        foreach ($IDs as $id) {
            if (!is_numeric($id)) {
                return false;
            }
        }

        return true;
    }

    /* **************************************** */

    /**
     * Check whether a valid array of tokens has been passed.
     * All the tokens must contains only [0-9a-zA-z_~] characters which are all transparent in raw URL encoding,
     * according to LimeSurvey 'Token.php' class.
     * (https://github.com/LimeSurvey/LimeSurvey/blob/master/application/models/Token.php#L257)
     *
     * @author  Tiziano Marra <https://github.com/MrTiz9>
     * @since   1.0
     *
     * @internal
     *
     * @param  array $tokens    Array of tokens
     *
     * @return bool
     */

    private function checkValidTokens($tokens)
    {
        if (!is_array($tokens)) {
            return false;
        }

        foreach ($tokens as $token) {
            if (!is_string($token) || preg_match('/^[0-9a-zA-Z_~]+$/', $token) !== 1) {
                return false;
            }
        }

        return true;
    }

    /* **************************************** */

    /**
     * Check whether a valid date has been passed and parse it.
     *
     * @author  Tiziano Marra <https://github.com/MrTiz9>
     * @since   1.0
     *
     * @internal
     *
     * @param  string $date
     *
     * @return string|bool
     */

    private function checkValidDate($date)
    {
        try {
            return (new DateTime($date))->format('Y-m-d h:i:s');
        } catch (Exception $e) {
            return false;
        }
    }

    /* **************************************** */

    /**
     * Get all data about all questions.
     *
     * @author  Tiziano Marra <https://github.com/MrTiz9>
     * @since   1.0
     *
     * @internal
     *
     * @return array|mixed
     */

    private function getInfoQuestions()
    {
        $sql = "SELECT q.qid, q.gid, q.type, q.title, q.question, q.other, q.mandatory, g.group_name
                  FROM `$this->dbName`.`{$this->tablePrefix}questions` AS q
                    JOIN `$this->dbName`.`{$this->tablePrefix}groups` AS g
                      ON (q.gid = g.gid
                      AND q.language = g.language)
                WHERE q.sid = $this->sid
                  AND q.language = '$this->lang'
                  AND q.parent_qid = 0
                ORDER BY g.group_order, q.question_order";

        return $this->db->query($sql);
    }

    /* **************************************** */

    /**
     * Get all sub-questions about a specific question.
     *
     * @author  Tiziano Marra <https://github.com/MrTiz9>
     * @since   1.0
     *
     * @internal
     *
     * @param  int $qid     Question ID
     *
     * @return array|mixed
     */

    private function getSubQuestions($qid)
    {
        $sql = "SELECT parent_qid, title, question
                FROM `$this->dbName`.`{$this->tablePrefix}questions`
                WHERE parent_qid = $qid
                  AND `language` = '$this->lang'
                  AND scale_id = 0
                ORDER BY question_order";

        return $this->db->query($sql);
    }

    /* **************************************** */

    /**
     * Get all responses in a specific survey column; this function is useful to parse free text questions.
     *
     * @author  Tiziano Marra <https://github.com/MrTiz9>
     * @since   1.0
     *
     * @internal
     *
     * @param  string $sgq      SGQ identifier
     *
     * @return array|mixed
     */

    private function getFreeTextAnswers($sgq)
    {
        $sql = "SELECT `$sgq` AS 'text'
                FROM `$this->dbName`.`{$this->tablePrefix}survey_$this->sid`
                WHERE CHAR_LENGTH(`$sgq`) > 0
                  AND id IN ($this->implodedIDs)";

        return $this->db->query($sql, true, PDO::FETCH_COLUMN);
    }

    /* **************************************** */

    /**
     * Get the count of all responses in a specific survey column
     *
     * @author  Tiziano Marra <https://github.com/MrTiz9>
     * @since   1.0
     *
     * @internal
     *
     * @param  string $sgq      SGQ identifier
     *
     * @return array|mixed
     */

    private function getCountAnswers($sgq)
    {
        $sql = "SELECT `$sgq` AS 'value',
                       COUNT(`$sgq`) AS 'N'
                FROM `$this->dbName`.`{$this->tablePrefix}survey_$this->sid`
                WHERE CHAR_LENGTH(`$sgq`) > 0
                  AND id IN ($this->implodedIDs)
                GROUP BY `$sgq`";

        return $this->db->query($sql);
    }

    /* **************************************** */

    /**
     * Get some attributes about all question; the considered attributes are:
     *  - other_comment_mandatory,
     *  - other_numbers_only,
     *  - numbers_only,
     *  - hidden
     *
     * @author  Tiziano Marra <https://github.com/MrTiz9>
     * @since   1.0
     *
     * @internal
     *
     * @param  array $qids     Question IDs
     *
     * @return array
     */

    private function getQuestionsAttributes($qids)
    {
        $attribs = [
            'other_comment_mandatory' => false,
            'other_numbers_only'      => false,
            'numbers_only'            => false,
            'hidden'                  => false
        ];

        $_qids = implode(', ', $qids);
        $attrs = implode('\', \'', array_keys($attribs));

        $sql = "SELECT qid, attribute, `value`
                FROM `$this->dbName`.`{$this->tablePrefix}question_attributes`
                WHERE qid IN ($_qids)
                  AND attribute IN ('$attrs')";

        $fetched = $this->db->query($sql, true, PDO::FETCH_GROUP | PDO::FETCH_ASSOC);

        foreach ($fetched as $qid => &$attributes) {
            foreach ($attributes as $i => $attribute) {
                $fetched[$qid][$attribute['attribute']] = (bool)$attribute['value'];
                unset($attributes[$i]);
            }

            $fetched[$qid] += $attribs;
        }

        foreach ($qids as $qid) {
            if (!isset($fetched[$qid])) {
                $fetched[$qid] = $attribs;
            }
        }

        return $fetched;
    }

    /* **************************************** */

    /**
     * Get the codes and the questions texts for a specific sub-question.
     * This function is useful to parse the ':' and the ';' questions types.
     *
     * @author  Tiziano Marra <https://github.com/MrTiz9>
     * @since   1.0
     *
     * @internal
     *
     * @param  int $qid     Question ID
     *
     * @return array|mixed
     */

    private function getInfoByScaleId($qid)
    {
        $sql = "SELECT title, question
                FROM `$this->dbName`.`{$this->tablePrefix}questions`
                WHERE parent_qid = $qid
                  AND scale_id = 1
                  AND `language` = '$this->lang'
                ORDER BY question_order";

        return $this->db->query($sql, true, PDO::FETCH_KEY_PAIR);
    }

    /* **************************************** */

    /**
     * Get the response count in survey column {SGQA}.
     * This function is useful to parse multiple choice questions.
     *
     * @author  Tiziano Marra <https://github.com/MrTiz9>
     * @since   1.0
     *
     * @internal
     *
     * @param  string $sgqa     SGQA identifier
     *
     * @return array|mixed
     */

    private function getCountSGQA($sgqa)
    {
        $sql = "SELECT COUNT(`$sgqa`)
                FROM `$this->dbName`.`{$this->tablePrefix}survey_$this->sid`
                WHERE `$sgqa` = 'Y'
                  AND id IN ($this->implodedIDs)";

        return $this->db->query($sql, false, PDO::FETCH_COLUMN);
    }

    /* **************************************** */

    /**
     * Get the codes and the answers texts for a specific sub-question.
     *
     * @author  Tiziano Marra <https://github.com/MrTiz9>
     * @since   1.0
     *
     * @internal
     *
     * @param  int $parentQid   The parent question ID
     * @param  int $scaleId     (Optional) Scale ID
     *
     * @return array|mixed
     */

    private function getAnswersCodes($parentQid, $scaleId = 0)
    {
        $sql = "SELECT `code`, answer
                FROM `$this->dbName`.`{$this->tablePrefix}answers`
                WHERE qid = $parentQid
                  AND `language` = '$this->lang'
                  AND scale_id = $scaleId
                ORDER BY sortorder";

        return $this->db->query($sql, true, PDO::FETCH_KEY_PAIR);
    }

    /* **************************************** */

    /**
     * Get data about uploaded files.
     * This function is useful to parse the '|' questions type.
     *
     * @author  Tiziano Marra <https://github.com/MrTiz9>
     * @since   1.0
     *
     * @internal
     *
     * @param  string $sgq    SGQ identifier
     *
     * @return array|mixed
     */

    private function getUploadedFiles($sgq)
    {
        $sql = "SELECT `$sgq` AS 'info',
                       `{$sgq}_filecount` AS 'N'
                FROM `$this->dbName`.`{$this->tablePrefix}survey_$this->sid`
                WHERE `{$sgq}_filecount` > 0
                  AND id IN ($this->implodedIDs)";

        return $this->db->query($sql);
    }

    /* **************************************** */

    /**
     * Get all the active languages for the current survey.
     *
     * @author  Tiziano Marra <https://github.com/MrTiz9>
     * @since   1.0
     *
     * @internal
     *
     * @return array|mixed
     */

    private function getAvailableLanguages()
    {
        $sql = "SELECT surveyls_language
                FROM `$this->dbName`.`{$this->tablePrefix}surveys_languagesettings`
                WHERE surveyls_survey_id = $this->sid";

        return $this->db->query($sql, true, PDO::FETCH_COLUMN);
    }

    /* **************************************** */

    /**
     * Get default language for the current survey.
     *
     * @author  Tiziano Marra <https://github.com/MrTiz9>
     * @since   1.0
     *
     * @internal
     *
     * @return string|mixed
     */

    private function getDefaultLanguage()
    {
        $sql = "SELECT `language`
                FROM `$this->dbName`.`{$this->tablePrefix}surveys`
                WHERE sid = $this->sid";

        return $this->db->query($sql, false, PDO::FETCH_COLUMN);
    }

    /* **************************************** */

    /**
     * Check whether the current survey stores the response tokens.
     *
     * @author  Tiziano Marra <https://github.com/MrTiz9>
     * @since   1.0
     *
     * @internal
     *
     * @param  int  $sid    Survey ID
     *
     * @return bool
     */

    private function checkAnonymousSurvey($sid)
    {
        $sql = "SELECT anonymized
                FROM `$this->dbName`.`{$this->tablePrefix}surveys`
                WHERE sid = $sid
                  AND active = 'Y'";

        return $this->db->query($sql, false, PDO::FETCH_COLUMN) === 'Y' ? true : false;
    }

    /* **************************************** */

    /**
     * Check whether the current survey stores the response datestamps.
     *
     * @author  Tiziano Marra <https://github.com/MrTiz9>
     * @since   1.0
     *
     * @internal
     *
     * @param  int  $sid    Survey ID
     *
     * @return bool
     */

    private function checkDatestampSurvey($sid)
    {
        $sql = "SELECT datestamp
                FROM `$this->dbName`.`{$this->tablePrefix}surveys`
                WHERE sid = $sid
                  AND active = 'Y'";

        return $this->db->query($sql, false, PDO::FETCH_COLUMN) === 'Y' ? true : false;
    }

    /* **************************************** */

    /**
     * Get the response IDs using tokens.
     *
     * @author  Tiziano Marra <https://github.com/MrTiz9>
     * @since   1.0
     *
     * @internal
     *
     * @param  array $tokens   Tokens list
     *
     * @return array|mixed
     */

    private function getIDsByTokens($tokens)
    {
        $_tokens = implode('\', \'', $tokens);

        $sql = "SELECT id
                FROM `$this->dbName`.`{$this->tablePrefix}survey_$this->sid`
                WHERE token IN ('$_tokens')";

        return $this->db->query($sql, true, PDO::FETCH_COLUMN);
    }

    /* **************************************** */

    /**
     * Get the response IDs using dates.
     *
     * @author  Tiziano Marra <https://github.com/MrTiz9>
     * @since   1.0
     *
     * @internal
     *
     * @param  string $from     Start date
     * @param  string $to       End date
     *
     * @return array|mixed
     */

    private function getIDsByDate($from, $to)
    {
        $sql = "SELECT id
                FROM `$this->dbName`.`{$this->tablePrefix}survey_$this->sid`
                WHERE DATE(`$this->dateCol`) BETWEEN DATE('$from') AND DATE('$to')";

        return $this->db->query($sql, true, PDO::FETCH_COLUMN);
    }

    /* **************************************** */

    /**
     * Check whether a valid survey id has been passed and whether the survey is active or not.
     *
     * @author  Tiziano Marra <https://github.com/MrTiz9>
     * @since   1.0
     *
     * @internal
     *
     * @param  int  $sid    Survey ID
     *
     * @return bool
     */

    private function checkValidSid($sid)
    {
        if (!is_int($sid)) {
            return false;
        }

        $sql = "SELECT 1
                FROM `$this->dbName`.`{$this->tablePrefix}surveys`
                WHERE sid = $sid
                  AND active = 'Y'";

        return (int)$this->db->query($sql, false, PDO::FETCH_COLUMN) === 1 ? true : false;
    }

    /* **************************************** */

    /**
     * Check whether a valid language for current survey has been passed.
     *
     * @author  Tiziano Marra <https://github.com/MrTiz9>
     * @since   1.0
     *
     * @internal
     *
     * @param  int    $sid      Survey ID
     * @param  string $lang     Survey language
     *
     * @return bool
     */

    private function checkValidLanguage($sid, $lang)
    {
        if (preg_match('/^[a-zA-Z-]+$/', $lang) !== 1) {
            return false;
        }

        $sql = "SELECT 1
                FROM `$this->dbName`.`{$this->tablePrefix}surveys_languagesettings`
                WHERE surveyls_survey_id = $sid
                  AND surveyls_language = '$lang'";

        return (int)$this->db->query($sql, false, PDO::FETCH_COLUMN) === 1 ? true : false;
    }

    /* **************************************** */

    /**
     * Check whether a valid questions list has been passed.
     * All the questions codes must comply the SGQA syntax,
     * that is '^\d{5,6}X\d+[A-Za-z0-9]{0,20}[#_]?[A-Za-z0-9]*$'
     *
     * @author  Tiziano Marra <https://github.com/MrTiz9>
     * @since   1.0
     *
     * @internal
     *
     * @param  int   $sid       Survey ID
     * @param  array $qList     Questions codes
     *
     * @return bool
     */

    private function checkValidQlist($sid, $qList)
    {
        if (empty($qList)) {
            return true;
        }

        if (!is_array($qList)) {
            return false;
        }

        foreach ($qList as $sgq) {
            if (!is_string($sgq)) {
                return false;
            }

            if (preg_match('/^' . $sid . 'X\d{1,6}X\d+[A-Za-z0-9]{0,20}[#_]?[A-Za-z0-9]*$/', $sgq) !== 1) {
                return false;
            }
        }

        $qListAsString = implode('`, `', $qList);

        $sql = "SELECT `$qListAsString`
                FROM `$this->dbName`.`{$this->tablePrefix}survey_$sid`
                LIMIT 0";

        try {
            return is_array($this->db->query($sql));
        } catch (PDOException $e) {
            return false;
        }
    }
}

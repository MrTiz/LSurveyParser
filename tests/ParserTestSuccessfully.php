<?php
/**
 * @Copyright (c) 2020 Tiziano Marra <https://github.com/MrTiz>.
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
 * @author  Tiziano Marra <https://github.com/MrTiz>
 * @version 1.0
 */

namespace LSurveyParser;

use PHPUnit\Framework\TestCase;

defined('PATH')
    or define('PATH', realpath(dirname(__DIR__)));

require PATH . '/src/ILSurveyParser.php';

/**
 * Class ParserTest
 * @package LSurveyParser
 *
 * @author  Tiziano Marra <https://github.com/MrTiz>
 * @since   1.0
 */

class ParserTestSuccessfully extends TestCase
{
    /** @var int        Enter here a correct survey ID */
    private $sid    = 234479;

    /** @var string     Enter here a correct survey language */
    private $lang   = 'en';

    /** @var array      Enter here a correct array of IDs */
    private $IDs    = [1, 2, 3];

    /** @var array      Enter here a correct array of tokens */
    private $tokens = ['K2rxGkU589E2Wx5', 'RW6nuxj9nm7J8N3'];

    /** @var string     Enter here a correct date */
    private $from   = '1980-01-01 00:00:00';

    /** @var string     Enter here a correct date */
    private $to     = '2020-12-31 23:59:59';

    /* **************************************** */

    /**
     * Test 'getOnlyQuestions' function.
     * The test is supposed to be successful.
     *
     * @author  Tiziano Marra <https://github.com/MrTiz>
     * @since   1.0
     *
     * @throws SurveyException
     */

    public function testGetOnlyQuestions()
    {
        $parser = new Parser();
        $this->assertIsArray($parser->getOnlyQuestions($this->sid, $this->lang));
    }

    /* **************************************** */

    /**
     * Test 'parseQuestionsByIDs' function.
     * The test is supposed to be successful.
     *
     * @author  Tiziano Marra <https://github.com/MrTiz>
     * @since   1.0
     *
     * @throws SurveyException
     */

    public function testParseQuestionsByIDs()
    {
        $parser = new Parser();
        $this->assertIsArray($parser->parseQuestionsByIDs($this->sid, $this->IDs));
    }

    /* **************************************** */

    /**
     * Test 'parseQuestionsByTokens' function.
     * The test is supposed to be successful.
     *
     * @author  Tiziano Marra <https://github.com/MrTiz>
     * @since   1.0
     *
     * @throws SurveyException
     */

    public function testParseQuestionsByTokens()
    {
        $parser = new Parser();
        $this->assertIsArray($parser->parseQuestionsByTokens($this->sid, $this->tokens));
    }

    /* **************************************** */

    /**
     * Test 'parseQuestionsByDates' function.
     * The test is supposed to be successful.
     *
     * @author  Tiziano Marra <https://github.com/MrTiz>
     * @since   1.0
     *
     * @throws SurveyException
     */

    public function testParseQuestionsByDates()
    {
        $parser = new Parser();
        $this->assertIsArray($parser->parseQuestionsByDates($this->sid, $this->from, $this->to));
    }
}

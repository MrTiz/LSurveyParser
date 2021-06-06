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

class ParserTestFailure extends TestCase
{
    /** @var int        Enter here an incorrect survey ID */
    private $sid    = -1;

    /** @var string     Enter here an incorrect survey language */
    private $lang   = 'chinese';

    /** @var array      Enter here an incorrect array of IDs */
    private $IDs    = ['wrong', 'test'];

    /** @var array      Enter here an incorrect array of IDs */
    private $tokens = ['$test', '@wrong#'];

    /** @var string     Enter here an incorrect date */
    private $from   = 'yesterday';

    /** @var string     Enter here an incorrect date */
    private $to     = 'today';

    /* **************************************** */

    /**
     * Test 'getOnlyQuestions' function.
     * The test is supposed to fail.
     *
     * @author  Tiziano Marra <https://github.com/MrTiz>
     * @since   1.0
     *
     * @throws SurveyException
     */

    public function testGetOnlyQuestions()
    {
        $this->expectException(SurveyException::class);

        $parser = new Parser();
        $parser->getOnlyQuestions($this->sid, $this->lang);
    }

    /* **************************************** */

    /**
     * Test 'parseQuestionsByIDs' function.
     * The test is supposed to fail.
     *
     * @author  Tiziano Marra <https://github.com/MrTiz>
     * @since   1.0
     *
     * @throws SurveyException
     */

    public function testParseQuestionsByIDs()
    {
        $this->expectException(SurveyException::class);

        $parser = new Parser();
        $parser->parseQuestionsByIDs($this->sid, $this->IDs);
    }

    /* **************************************** */

    /**
     * Test 'parseQuestionsByTokens' function.
     * The test is supposed to fail.
     *
     * @author  Tiziano Marra <https://github.com/MrTiz>
     * @since   1.0
     *
     * @throws SurveyException
     */

    public function testParseQuestionsByTokensFailure()
    {
        $this->expectException(SurveyException::class);

        $parser = new Parser();
        $parser->parseQuestionsByTokens($this->sid, $this->tokens);
    }

    /* **************************************** */

    /**
     * Test 'parseQuestionsByDates' function.
     * The test is supposed to fail.
     *
     * @author  Tiziano Marra <https://github.com/MrTiz>
     * @since   1.0
     *
     * @throws SurveyException
     */

    public function testParseQuestionsByDatesFailure()
    {
        $this->expectException(SurveyException::class);

        $parser = new Parser();
        $parser->parseQuestionsByDates($this->sid, $this->from, $this->to);
    }
}

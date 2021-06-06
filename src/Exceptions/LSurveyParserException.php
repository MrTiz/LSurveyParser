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

use Exception;

/**
 * Class LSurveyParserException
 * @package LSurveyParser
 *
 * @author  Tiziano Marra <https://github.com/MrTiz>
 * @since   1.0
 */

abstract class LSurveyParserException extends Exception
{
    /** @var string     Default error message */
    protected $message = 'Unknown error';
    /** @var int        Default error code */
    protected $code    = 0;

    /**
     * LSurveyParserException constructor.
     *
     * @author  Tiziano Marra <https://github.com/MrTiz>
     * @since   1.0
     *
     * @param null|string $message  Error message
     * @param null|int    $code     Error code
     */

    public function __construct($message = null, $code = null)
    {
        $msg = $message ?? $this->message;
        $cd  = $code    ?? $this->code;

        parent::__construct($msg, $cd);
    }
}

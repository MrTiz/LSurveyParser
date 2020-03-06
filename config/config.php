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

use PDO;

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

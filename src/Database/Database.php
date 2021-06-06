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

use PDO;
use PDOException;

/**
 * Class Database
 * @package LSurveyParser
 *
 * @author  Tiziano Marra <https://github.com/MrTiz>
 * @since   1.0
 */

class Database
{
    /** @var null|self  self instance */
    private static $instance = null;

    /** @var PDO|null   PDO instance */
    private $connection = null;

    /**
     * Database constructor.
     *
     * @author  Tiziano Marra <https://github.com/MrTiz>
     * @since   1.0
     *
     * @internal
     *
     * @throws SurveyException
     */

    private function __construct()
    {
        $host = DB_HOSTNAME;
        $port = DB_PORT;
        $drvr = DB_DRIVER;
        $dbnm = DB_NAME;
        $chrs = DB_CHARSET;

        $dsn = "$drvr:host=$host;port=$port;dbname=$dbnm;charset=$chrs";

        $this->connection = new PDO($dsn, DB_USERNAME, DB_PASSWORD, DB_ATTRIBUTES);

        if (!$this->checkTablePrefix($dbnm, TABLE_PREFIX)) {
            throw new SurveyException('Invalid table prefix');
        }
    }

    /* **************************************** */

    /**
     * Singleton
     *
     * @author  Tiziano Marra <https://github.com/MrTiz>
     * @since   1.0
     *
     * @return Database|null
     * @throws SurveyException
     */

    public static function getInstance()
    {
        if (empty(self::$instance)) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /* **************************************** */

    /**
     * Check whether table prefix is correct for the selected database
     *
     * @author  Tiziano Marra <https://github.com/MrTiz>
     * @since   1.0
     *
     * @internal
     *
     * @param string $dbName        LimeSurvey database name
     * @param string $tablePrefix   LimeSurvey table prefix
     *
     * @return bool
     */

    private function checkTablePrefix($dbName, $tablePrefix)
    {
        $sql = "SELECT 1
                FROM `$dbName`.`{$tablePrefix}surveys`";

        try {
            return (int)$this->query($sql, false, PDO::FETCH_COLUMN) === 1 ? true : false;
        } catch (PDOException $e) {
            return false;
        }
    }

    /* **************************************** */

    /**
     * Executes an SQL statement and fetch the result set.
     *
     * @author  Tiziano Marra <https://github.com/MrTiz>
     * @since   1.0
     *
     * @param string $sql          SQL query to execute
     * @param bool   $fetchAll     Fetch all the results or only the first ones?
     * @param int    $fetchStyle   How the rows will be returned
     *
     * @return array|mixed
     * @throws PDOException
     */

    public function query($sql, $fetchAll = true, $fetchStyle = PDO::FETCH_ASSOC)
    {
        if (preg_match('/^(\s+)?SELECT/', $sql) !== 1) {
            throw new PDOException('Write access denied');
        }

        if (empty($this->connection) || ($stmt = $this->connection->query($sql)) === false) {
            throw new PDOException('PDO query failed');
        }

        return $fetchAll ? $stmt->fetchAll($fetchStyle) : $stmt->fetch($fetchStyle);
    }
}

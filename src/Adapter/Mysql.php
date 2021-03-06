<?php
/**
 * Copyright (c) 2013-2014 Thomas Müller
 *
 * Permission is hereby granted, free of charge, to any person obtaining a
 * copy of this software and associated documentation files (the "Software"),
 * to deal in the Software without restriction, including without limitation
 * the rights to use, copy, modify, merge, publish, distribute, sublicense,
 * and/or sell copies of the Software, and to permit persons to whom the
 * Software is furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included
 * in all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS
 * OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 *
 * @category   WurflCache
 * @package    Adapter
 * @copyright  2013-2014 Thomas Müller
 * @license    http://www.opensource.org/licenses/MIT MIT License
 * @link       https://github.com/mimmi20/WurflCache/
 */

namespace WurflCache\Adapter;

/**
 * Adapter to use a MySQL database for caching
 *
 * @category   WurflCache
 * @package    Adapter
 * @author     Thomas Müller <t_mueller_stolzenhain@yahoo.de>
 * @copyright  2013-2014 Thomas Müller
 * @license    http://www.opensource.org/licenses/MIT MIT License
 * @link       https://github.com/mimmi20/WurflCache/
 * @todo       : rewrite to use PDO or mysqli
 */
class Mysql extends AbstractAdapter
{
    private $defaultParams = array(
        'host'        => 'localhost',
        'port'        => 3306,
        'db'          => 'wurfl_persistence_db',
        'user'        => '',
        'pass'        => '',
        'table'       => 'wurfl_object_cache',
        'keycolumn'   => 'key',
        'valuecolumn' => 'value'
    );

    private $link;
    private $host;
    private $db;
    private $user;
    private $pass;
    private $port;
    private $table;
    private $cacheIdcolumn;
    private $valuecolumn;

    public function __construct($params)
    {
        $currentParams = $this->defaultParams;

        if (is_array($params) && !empty($params)) {
            $currentParams = array_merge($this->defaultParams, $params);
        }

        foreach ($currentParams as $cacheId => $value) {
            $this->$cacheId = $value;
        }
        $this->initialize();
    }

    /**
     * Get an item.
     *
     * @param  string $cacheId
     * @param  bool   $success
     *
     * @return mixed Data on success, null on failure
     */
    public function getItem($cacheId, & $success = null)
    {
        $result = $this->hasItem($cacheId);

        if (!$result) {
            $success = false;
            return null;
        }

        $objectId = $this->encode('', $cacheId);
        $objectId = mysql_real_escape_string($objectId);

        $sql = 'select `' . $this->valuecolumn . '` from `' . $this->db . '`.`' . $this->table . '` where `'
            . $this->keycolumn . '`=\'' . $objectId . '\'';

        $result = mysql_query($sql, $this->link);
        $row    = mysql_fetch_assoc($result);
        $return = null;

        if (is_array($row)) {
            $return = @unserialize($row['value']);

            if ($return === false) {
                $success = false;
                $return  = null;
            }
        }

        if (is_resource($result)) {
            mysql_free_result($result);
        }

        return $return;
    }

    /**
     * Test if an item exists.
     *
     * @param  string $cacheId
     *
     * @return bool
     */
    public function hasItem($cacheId)
    {
        $objectId = $this->encode('', $cacheId);
        $objectId = mysql_real_escape_string($objectId);

        $sql    = 'select `' . $this->valuecolumn . '` from `' . $this->db . '`.`' . $this->table . '` where `'
            . $this->keycolumn . '`=\'' . $objectId . '\'';
        $result = mysql_query($sql, $this->link);

        if (!is_resource($result)) {
            return false;
        }

        return true;
    }

    /**
     * Store an item.
     *
     * @param  string $cacheId
     * @param  mixed  $value
     *
     * @return bool
     */
    public function setItem($cacheId, $value)
    {
        $object   = mysql_real_escape_string(serialize($value));
        $objectId = $this->encode('', $cacheId);
        $objectId = mysql_real_escape_string($objectId);

        $success = $this->removeItem($cacheId);

        if (!$success) {
            return false;
        }

        $sql = 'insert into `' . $this->db . '`.`' . $this->table . '` (`' . $this->keycolumn . '`,`'
            . $this->valuecolumn . '`) VALUES (\'' . $objectId . '\',\'' . $object . '\')';

        return (boolean)mysql_query($sql, $this->link);
    }

    /**
     * Remove an item.
     *
     * @param  string $cacheId
     *
     * @return bool
     */
    public function removeItem($cacheId)
    {
        $objectId = $this->encode('', $cacheId);
        $objectId = mysql_real_escape_string($objectId);

        $sql = 'delete from `' . $this->db . '`.`' . $this->table . '` where `' . $this->keycolumn . '`=\''
            . $objectId . '\'';

        return (boolean)mysql_query($sql, $this->link);
    }

    /**
     * Flush the whole storage
     *
     * @throws Exception
     * @return bool
     */
    public function flush()
    {
        $sql     = 'truncate table `' . $this->db . '`.`' . $this->table . '`';
        $success = mysql_query($sql, $this->link);

        if (mysql_error($this->link)) {
            throw new Exception(
                'MySql error ' . mysql_error($this->link) . ' clearing ' . $this->db . '.' . $this->table
            );
        }

        return $success;
    }

    /**
     * @throws Exception
     */
    private function initialize()
    {
        $this->ensureModuleExistance();

        /* Initializes link to MySql */
        $this->link = mysql_connect($this->host . ':' . $this->port, $this->user, $this->pass);
        if (mysql_error($this->link)) {
            throw new Exception('Couldn\'t link to `' . $this->host . '` (' . mysql_error($this->link) . ')');
        }

        /* Initializes link to database */
        $success = mysql_select_db($this->db, $this->link);
        if (!$success) {
            throw new Exception('Couldn\'t change to database `' . $this->db . '` (' . mysql_error($this->link) . ')');
        }

        /* Is Table there? */
        $test = mysql_query('SHOW TABLES FROM ' . $this->db . ' LIKE \'' . $this->table . '\'', $this->link);
        if (!is_resource($test)) {
            throw new Exception(
                'Couldn\'t show tables from database `' . $this->db . '` (' . mysql_error($this->link) . ')'
            );
        }

        // create table if it's not there.
        if (mysql_num_rows($test) == 0) {
            $sql
                     = 'CREATE TABLE `' . $this->db . '`.`' . $this->table . '` (
                      `' . $this->keycolumn . '` varchar(255) collate latin1_general_ci NOT NULL,
                      `' . $this->valuecolumn . '` mediumblob NOT NULL,
                      `ts` timestamp NOT NULL default CURRENT_TIMESTAMP,
                      PRIMARY KEY  (`' . $this->keycolumn . '`)
                    ) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci';
            $success = mysql_query($sql, $this->link);
            if (!$success) {
                throw new Exception(
                    'Table ' . $this->table . ' missing in ' . $this->db . ' (' . mysql_error($this->link) . ')'
                );
            }
        }

        if (is_resource($test)) {
            mysql_free_result($test);
        }
    }

    /**
     * Ensures the existance of the the PHP Extension mysql
     *
     * @throws Exception required extension is unavailable
     */
    private function ensureModuleExistance()
    {
        if (!extension_loaded('mysql')) {
            throw new Exception('The PHP extension mysql must be installed and loaded in order to use the mysql.');
        }
    }

    /**
     * Encode the Object Id using the Persistence Identifier
     *
     * @param string $namespace
     * @param string $input
     *
     * @return string $input with the given $namespace as a prefix
     */
    private function encode($namespace, $input)
    {
        return implode(':', array('Wurfl', $namespace, $input));
    }

    /**
     * set the expiration time
     *
     * @param integer $expiration
     *
     * @return AdapterInterface
     */
    public function setExpiration($expiration = 86400)
    {
        $this->cacheExpiration = $expiration;

        return $this;
    }

    /**
     * set the cache namespace
     *
     * @param string $namespace
     *
     * @return AdapterInterface
     */
    public function setNamespace($namespace)
    {
        $this->namespace = $namespace;

        return $this;
    }
}

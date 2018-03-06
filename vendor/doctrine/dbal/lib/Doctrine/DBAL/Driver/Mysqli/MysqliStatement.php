<?php
/*
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the MIT license. For more information, see
 * <http://www.doctrine-project.org>.
 */

namespace Doctrine\DBAL\Driver\Mysqli;

use Doctrine\DBAL\Driver\Statement;
use PDO;

/**
 * @author Kim Hemsø Rasmussen <kimhemsoe@gmail.com>
 */
class MysqliStatement implements \IteratorAggregate, Statement
{
    /**
     * @var array
     */
    protected static $_paramTypeMap = [
        PDO::PARAM_STR => 's',
        PDO::PARAM_BOOL => 'i',
        PDO::PARAM_NULL => 's',
        PDO::PARAM_INT => 'i',
        PDO::PARAM_LOB => 's' // TODO Support LOB bigger then max package size.
    ];

    /**
     * @var \mysqli
     */
    protected $_conn;

    /**
     * @var \mysqli_stmt
     */
    protected $_stmt;

    /**
     * @var null|boolean|array
     */
    protected $_columnNames;

    /**
     * @var null|array
     */
    protected $_rowBindedValues;

    /**
     * @var array
     */
    protected $_bindedValues;

    /**
     * @var string
     */
    protected $types;

    /**
     * Contains ref values for bindValue().
     *
     * @var array
     */
    protected $_values = [];

    /**
     * @var integer
     */
    protected $_defaultFetchMode = PDO::FETCH_BOTH;

    /**
     * Indicates whether the statement is in the state when fetching results is possible
     *
     * @var bool
     */
    private $result = false;

    /**
     * @param \mysqli $conn
     * @param string  $prepareString
     *
     * @throws \Doctrine\DBAL\Driver\Mysqli\MysqliException
     */
    public function __construct(\mysqli $conn, $prepareString)
    {
        $this->_conn = $conn;
        $this->_stmt = $conn->prepare($prepareString);
        if (false === $this->_stmt) {
            throw new MysqliException($this->_conn->error, $this->_conn->sqlstate, $this->_conn->errno);
        }

        $paramCount = $this->_stmt->param_count;
        if (0 < $paramCount) {
            $this->types = str_repeat('s', $paramCount);
            $this->_bindedValues = array_fill(1, $paramCount, null);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function bindParam($column, &$variable, $type = null, $length = null)
    {
        if (null === $type) {
            $type = 's';
        } else {
            if (isset(self::$_paramTypeMap[$type])) {
                $type = self::$_paramTypeMap[$type];
            } else {
                throw new MysqliException("Unknown type: '{$type}'");
            }
        }

        $this->_bindedValues[$column] =& $variable;
        $this->types[$column - 1] = $type;

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function bindValue($param, $value, $type = null)
    {
        if (null === $type) {
            $type = 's';
        } else {
            if (isset(self::$_paramTypeMap[$type])) {
                $type = self::$_paramTypeMap[$type];
            } else {
                throw new MysqliException("Unknown type: '{$type}'");
            }
        }

        $this->_values[$param] = $value;
        $this->_bindedValues[$param] =& $this->_values[$param];
        $this->types[$param - 1] = $type;

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function execute($params = null)
    {
        if (null !== $this->_bindedValues) {
            if (null !== $params) {
                if ( ! $this->_bindValues($params)) {
                    throw new MysqliException($this->_stmt->error, $this->_stmt->errno);
                }
            } else {
                if (!call_user_func_array([$this->_stmt, 'bind_param'], [$this->types] + $this->_bindedValues)) {
                    throw new MysqliException($this->_stmt->error, $this->_stmt->sqlstate, $this->_stmt->errno);
                }
            }
        }

        if ( ! $this->_stmt->execute()) {
            throw new MysqliException($this->_stmt->error, $this->_stmt->sqlstate, $this->_stmt->errno);
        }

        if (null === $this->_columnNames) {
            $meta = $this->_stmt->result_metadata();
            if (false !== $meta) {
                $columnNames = [];
                foreach ($meta->fetch_fields() as $col) {
                    $columnNames[] = $col->name;
                }
                $meta->free();

                $this->_columnNames = $columnNames;
            } else {
                $this->_columnNames = false;
            }
        }

        if (false !== $this->_columnNames) {
            // Store result of every execution which has it. Otherwise it will be impossible
            // to execute a new statement in case if the previous one has non-fetched rows
            // @link http://dev.mysql.com/doc/refman/5.7/en/commands-out-of-sync.html
            $this->_stmt->store_result();

            // Bind row values _after_ storing the result. Otherwise, if mysqli is compiled with libmysql,
            // it will have to allocate as much memory as it may be needed for the given column type
            // (e.g. for a LONGBLOB field it's 4 gigabytes)
            // @link https://bugs.php.net/bug.php?id=51386#1270673122
            //
            // Make sure that the values are bound after each execution. Otherwise, if closeCursor() has been
            // previously called on the statement, the values are unbound making the statement unusable.
            //
            // It's also important that row values are bound after _each_ call to store_result(). Otherwise,
            // if mysqli is compiled with libmysql, subsequently fetched string values will get truncated
            // to the length of the ones fetched during the previous execution.
            $this->_rowBindedValues = array_fill(0, count($this->_columnNames), null);

            $refs = [];
            foreach ($this->_rowBindedValues as $key => &$value) {
                $refs[$key] =& $value;
            }

            if (!call_user_func_array([$this->_stmt, 'bind_result'], $refs)) {
                throw new MysqliException($this->_stmt->error, $this->_stmt->sqlstate, $this->_stmt->errno);
            }
        }

        $this->result = true;

        return true;
    }

    /**
     * Binds a array of values to bound parameters.
     *
     * @param array $values
     *
     * @return boolean
     */
    private function _bindValues($values)
    {
        $params = [];
        $types = str_repeat('s', count($values));
        $params[0] = $types;

        foreach ($values as &$v) {
            $params[] =& $v;
        }

        return call_user_func_array([$this->_stmt, 'bind_param'], $params);
    }

    /**
     * @return boolean|array
     */
    private function _fetch()
    {
        $ret = $this->_stmt->fetch();

        if (true === $ret) {
            $values = [];
            foreach ($this->_rowBindedValues as $v) {
                $values[] = $v;
            }

            return $values;
        }

        return $ret;
    }

    /**
     * {@inheritdoc}
     */
    public function fetch($fetchMode = null, $cursorOrientation = \PDO::FETCH_ORI_NEXT, $cursorOffset = 0)
    {
        // do not try fetching from the statement if it's not expected to contain result
        // in order to prevent exceptional situation
        if (!$this->result) {
            return false;
        }

        $values = $this->_fetch();
        if (null === $values) {
            return false;
        }

        if (false === $values) {
            throw new MysqliException($this->_stmt->error, $this->_stmt->sqlstate, $this->_stmt->errno);
        }

        $fetchMode = $fetchMode ?: $this->_defaultFetchMode;

        switch ($fetchMode) {
            case PDO::FETCH_NUM:
                return $values;

            case PDO::FETCH_ASSOC:
                return array_combine($this->_columnNames, $values);

            case PDO::FETCH_BOTH:
                $ret = array_combine($this->_columnNames, $values);
                $ret += $values;

                return $ret;

            case PDO::FETCH_OBJ:
                $assoc = array_combine($this->_columnNames, $values);
                $ret = new \stdClass();

                foreach ($assoc as $column => $value) {
                    $ret->$column = $value;
                }

                return $ret;

            default:
                throw new MysqliException("Unknown fetch type '{$fetchMode}'");
        }
    }

    /**
     * {@inheritdoc}
     */
    public function fetchAll($fetchMode = null, $fetchArgument = null, $ctorArgs = null)
    {
        $fetchMode = $fetchMode ?: $this->_defaultFetchMode;

        $rows = [];
        if (PDO::FETCH_COLUMN == $fetchMode) {
            while (($row = $this->fetchColumn()) !== false) {
                $rows[] = $row;
            }
        } else {
            while (($row = $this->fetch($fetchMode)) !== false) {
                $rows[] = $row;
            }
        }

        return $rows;
    }

    /**
     * {@inheritdoc}
     */
    public function fetchColumn($columnIndex = 0)
    {
        $row = $this->fetch(PDO::FETCH_NUM);
        if (false === $row) {
            return false;
        }

        return isset($row[$columnIndex]) ? $row[$columnIndex] : null;
    }

    /**
     * {@inheritdoc}
     */
    public function errorCode()
    {
        return $this->_stmt->errno;
    }

    /**
     * {@inheritdoc}
     */
    public function errorInfo()
    {
        return $this->_stmt->error;
    }

    /**
     * {@inheritdoc}
     */
    public function closeCursor()
    {
        $this->_stmt->free_result();
        $this->result = false;

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function rowCount()
    {
        if (false === $this->_columnNames) {
            return $this->_stmt->affected_rows;
        }

        return $this->_stmt->num_rows;
    }

    /**
     * {@inheritdoc}
     */
    public function columnCount()
    {
        return $this->_stmt->field_count;
    }

    /**
     * {@inheritdoc}
     */
    public function setFetchMode($fetchMode, $arg2 = null, $arg3 = null)
    {
        $this->_defaultFetchMode = $fetchMode;

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator()
    {
        $data = $this->fetchAll();

        return new \ArrayIterator($data);
    }
}

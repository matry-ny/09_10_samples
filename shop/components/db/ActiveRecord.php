<?php

namespace components\db;

use components\console\Application;
use exceptions\DbException;
use helpers\ArrayHelper;
use PDO;

/**
 * Class ActiveRecord
 * @package components\db
 */
abstract class ActiveRecord extends Model
{
    /**
     * @var string
     */
    private $primaryKey;

    /**
     * @var array
     */
    private $schema = [];

    /**
     * @var bool
     */
    private $isSelected = false;

    /**
     * @var array
     */
    private $attributes = [];

    public function __construct()
    {
        $this->primaryKey = $this->primaryKey();
        $this->schema = $this->schema();
    }

    /**
     * @param string $name
     * @return mixed
     */
    public function __get($name)
    {
        return ArrayHelper::getValue($this->attributes, $name);
    }

    /**
     * @param array $data
     */
    public function load(array $data)
    {
        foreach ($data as $key => $value) {
            if (in_array($key, $this->schema)) {
                $this->attributes[$key] = $value;
            }
        }
    }

    /**
     * @param array $conditions
     * @return static[]
     */
    public static function findAll(array $conditions)
    {
        $query = self::find()->where($conditions);

        $models = [];
        foreach ($query->all() as $row) {
            $model = new static();
            $model->load($row);

            $models[] = $model;
        }

        return $models;
    }

    /**
     * @return events\Select
     */
    public static function find()
    {
        $table = (new static())->tableName();

        /** @var \components\db\events\Select $query */
        $query = (new Query())->getBuilder(Query::SELECT);
        $query->select(['*'])->from($table);

        return $query;
    }

    /**
     * @param int|array $condition
     * @return static|null
     */
    public static function findOne($condition)
    {
        $model = new static();

        if (is_array($condition)) {
            $data = self::find()->where($condition)->one();
        } else {
            $data = $model->getRow($condition);
        }

        if ($data) {
            $model->load($data);
            $model->isSelected = true;
            return $model;
        }

        return null;
    }

    /**
     * @param mixed $recordId
     * @return array
     */
    private function getRow($recordId)
    {
        /** @var \components\db\events\Select $query */
        $query = $this->select(['*'])->from($this->tableName())->where(['=', $this->primaryKey, $recordId]);
        return $query->one();
    }

    /**
     * @return bool
     */
    public function save()
    {
        if ($this->isNewRecord()) {
            $result = $this->create();
        } else {
            $result = $this->refresh();
        }

        return $result;
    }

    /**
     * @return bool|string
     */
    private function create()
    {
        $result = $this->insert($this->tableName(), $this->attributes);

        if ($result) {
            $data = $this->getRow($this->lastInsertId());
            $this->load($data);

            return true;
        }

        return false;
    }

    /**
     * @return bool
     */
    private function refresh()
    {
        return (bool)$this->update(
            $this->tableName(),
            $this->attributes,
            ['=', $this->primaryKey, $this->attributes[$this->primaryKey]]
        );
    }

    /**
     * @return bool
     */
    public function clear()
    {
        $result = $this->delete($this->tableName(), ['=', $this->primaryKey, $this->attributes[$this->primaryKey]]);

        if ($result) {
            $this->attributes = [];
            return true;
        }

        return false;
    }

    /**
     * @return bool
     */
    public function isNewRecord()
    {
        return false === $this->hasPrimaryKey() || false === $this->hasDuplicatedId();
    }

    /**
     * @return bool
     */
    private function hasPrimaryKey()
    {
        return array_key_exists($this->primaryKey, $this->attributes);
    }

    private function hasDuplicatedId()
    {
        return $this->isSelected || (bool)self::findOne($this->attributes[$this->primaryKey]);
    }

    /**
     * @return string
     */
    abstract public function tableName();

    /**
     * @return array|mixed|null
     * @throws DbException
     */
    private function primaryKey()
    {
        $sql = "SHOW KEYS FROM {$this->tableName()} WHERE Key_name = 'PRIMARY'";
        $statement = Application::getDb()->getConnection()->prepare($sql);
        $statement->execute();

        $primaryKey = ArrayHelper::getValue($statement->fetch(PDO::FETCH_ASSOC), 'Column_name');

        if (empty($primaryKey)) {
            throw new DbException("Table '{$this->tableName()}' must have primary key");
        }

        return $primaryKey;
    }

    /**
     * @return array
     */
    private function schema()
    {
        $sql = "SHOW COLUMNS FROM {$this->tableName()}";
        $statement = Application::getDb()->getConnection()->prepare($sql);
        $statement->execute();

        $schema = [];
        foreach ($statement->fetchAll(PDO::FETCH_ASSOC) as $column) {
            $schema[] = $column['Field'];
        }

        return $schema;
    }
}
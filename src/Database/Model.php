<?php

namespace Whis\Database;

use Whis\Database\Drivers\DatabaseDriver;
use Whis\Exceptions\DatabaseException;
use Whis\Exceptions\NoFillableAttributesError;
use ReflectionClass;

abstract class Model
{
    protected ?string $table = null;

    protected string $primaryKey = "id";

    protected array $hidden = [];

    protected array $fillable = [];

    protected array $attributes = [];

    protected bool $insertTimestamps = true;

    private static ?DatabaseDriver $driver = null;

    public static function setDatabaseDriver(DatabaseDriver $driver)
    {
        self::$driver = $driver;
    }

    public function __construct()
    {
        if (is_null($this->table)) {
            $subclass = new ReflectionClass(static::class);
            $this->table = snake_case("{$subclass->getShortName()}s");
        }
    }

    public function __set($name, $value)
    {
        $this->attributes[$name] = $value;
    }

    public function __get($name)
    {
        return $this->attributes[$name] ?? null;
    }
    public function __sleep()
    {
        foreach ($this->hidden as $hide) {
            unset($this->attributes[$hide]);
        }
        return array_keys(get_object_vars($this));
    }

    protected function setAttributes(array $attributes): static
    {
        foreach ($attributes as $key => $value) {
            $this->__set($key, $value);
        }
        return $this;
    }

    protected function massAssign(array $attributes): static
    {
        if (count($this->fillable) == 0) {
            throw new NoFillableAttributesError("No fillable attributes were found on model {$this->table}.");
        }
        foreach ($attributes as $key => $value) {
            if (in_array($key, $this->fillable)) {
                $this->__set($key, $value);
            }
        }
        return $this;
    }

    public function save(): static
    {
        if ($this->insertTimestamps) {
            $this->attributes["created_at"] = date("Y-m-d H:m:s");
        }
        $databaseColumns = implode(",", array_keys($this->attributes));
        $bind = implode(",", array_fill(0, count($this->attributes), "?"));
            self::$driver->statement(
                "INSERT INTO $this->table ($databaseColumns) VALUES ($bind)",
                array_values($this->attributes)
            );
        

        return $this;
    }

    public static function create(array $attributes): static
    {
            return (new static())->massAssign($attributes)->save();
        
    }

    public function toArray(): array
    {
        return array_filter($this->attributes, fn ($attr) => !in_array($attr, $this->hidden));
    }

    public static function first(mixed $orderBy = null, bool $desc = false): ?static
    {
        $instance = new static();
        //Check if orderBy column exists
        if (!is_null($orderBy) && (!in_array($orderBy, $instance->fillable) && !in_array($orderBy, $instance->hidden))) {
            throw new DatabaseException("Column $orderBy does not exist in table {$instance->table}.");
            return null;
        }

        if (is_null($orderBy)) {
            $orderBy = $instance->primaryKey;
        }
        $result = self::$driver->statement("SELECT * FROM $instance->table ORDER BY $orderBy" . (($desc == true) ? ' DESC ' : ' ASC ') . " LIMIT 1");
        if (count($result) == 0) {
            return null;
        }
        return $instance->setAttributes($result[0]);
    }

    public static function find(mixed $id): ?static
    {
        $instance = new static();
        $result = self::$driver->statement("SELECT * FROM $instance->table WHERE $instance->primaryKey = ?", [$id]);
        if (count($result) == 0) {
            return null;
        }
        return $instance->setAttributes($result[0]);
    }

    public static function all(mixed $orderBy = null, bool $desc = false): array
    {
        $instance = new static();
        if (!is_null($orderBy) && (!in_array($orderBy, $instance->fillable) && !in_array($orderBy, $instance->hidden))) {
            throw new DatabaseException("Column $orderBy does not exist in table {$instance->table}.");
        }

        if (is_null($orderBy)) {
            $orderBy = $instance->primaryKey;
        }
        $result = self::$driver->statement("SELECT * FROM $instance->table ORDER BY $orderBy" . (($desc == true) ? ' DESC ' : ' ASC '));

        if (count($result) == 0) {
            return [];
        }

        $models = [$instance->setAttributes($result[0])];
        for ($i = 1; $i < count($result); $i++) {
            $models[] = (new static())->setAttributes($result[$i]);
        }

        return array_map(fn ($user) => $user->toArray(), $models);
    }

    public static function where(mixed $column, mixed $value, mixed $orderBy = null, bool $desc = false): ?array
    {
        $instance = new static();
        /*if ((!in_array($column, $instance->fillable) && !in_array($column, $instance->hidden)) || is_null($column)) {
            throw new DatabaseException("Column $orderBy does not exist in table {$instance->table}.");
        }*/
        if (!is_null($orderBy) && (!in_array($orderBy, $instance->fillable) && !in_array($orderBy, $instance->hidden))) {
            throw new DatabaseException("Column $orderBy does not exist in table {$instance->table}.");
        }

        if (is_null($orderBy)) {
            $orderBy = $instance->primaryKey;
        }
        //return ([$column, $value]);
        $column = htmlentities($column);
        $column = filter_var($column, FILTER_SANITIZE_FULL_SPECIAL_CHARS);

        $query = 'SELECT * FROM ' . $instance->table . ' WHERE ' . $column . ' = :value ORDER BY ' . $orderBy . (($desc == true) ? ' DESC' : ' ASC');
        //return [$query];

        $result = self::$driver->statement($query, [':value' => $value]);
        //return [count($result), $result];
        //return [$result];
        //exit;
        if (count($result) == 0) {
            return [];
        }
        //return [$result];
        $models = [$instance->setAttributes($result[0])];
        for ($i = 1; $i < count($result); $i++) {
            $models[] = (new static())->setAttributes($result[$i]);
        }

        return array_map(fn ($user) => $user->toArray(), $models);
    }

    public static function firstWhere(mixed $column, mixed $value, mixed $orderBy = null, bool $desc = false): ?static
    {
        $instance = new static();
        /*if ((!in_array($column, $instance->fillable) && !in_array($column, $instance->hidden)) || is_null($column)) {
            throw new DatabaseException("Column $orderBy does not exist in table {$instance->table}.");
        }*/
        if (!is_null($orderBy) && (!in_array($orderBy, $instance->fillable) && !in_array($orderBy, $instance->hidden))) {
            throw new DatabaseException("Column $orderBy does not exist in table {$instance->table}.");
        }

        if (is_null($orderBy)) {
            $orderBy = $instance->primaryKey;
        }
        //return ([$column, $value]);
        $column = htmlentities($column);
        $column = filter_var($column, FILTER_SANITIZE_FULL_SPECIAL_CHARS);

        $query = 'SELECT * FROM ' . $instance->table . ' WHERE ' . $column . ' = :value ORDER BY ' . $orderBy . (($desc == true) ? ' DESC' : ' ASC') . " LIMIT 1";
        //return [$query];

        $result = self::$driver->statement($query, [':value' => $value]);
        //return [count($result), $result];
        //return [$result];
        //exit;
        if (count($result) == 0) {
            return null;
        }
        //return [$result];
        return $instance->setAttributes($result[0]);
    }


    public function update(mixed $column, mixed $value, mixed $orderBy = null, bool $desc = false): ?static
    {
        if ($this->insertTimestamps) {
            $this->attributes["updated_at"] = date("Y-m-d H:m:s");
        }

        $databaseColumns = array_keys($this->attributes);
        $bind = implode(",", array_map(fn ($column) => "$column = ?", $databaseColumns));
        $id = $this->attributes[$this->primaryKey];

        self::$driver->statement(
            "UPDATE $this->table SET $bind WHERE $this->primaryKey = $id",
            array_values($this->attributes)
        );

        return $this;
    }

    public function delete(): static
    {
        self::$driver->statement(
            "DELETE FROM $this->table WHERE $this->primaryKey = {$this->attributes[$this->primaryKey]}"
        );

        return $this;
    }

    public static function between(mixed $maxValue = null, mixed $minValue = null, mixed $column = null, mixed $orderBy = null, bool $desc = false): ?array
    {
        $instance = new static();
        /*if ((!in_array($column, $instance->fillable) && !in_array($column, $instance->hidden)) || is_null($column)) {
            throw new DatabaseException("Column $orderBy does not exist in table {$instance->table}.");
        }*/
        if (!is_null($orderBy) && (!in_array($orderBy, $instance->fillable) && !in_array($orderBy, $instance->hidden))) {
            throw new DatabaseException("Column $orderBy does not exist in table {$instance->table}.");
        }

        if (is_null($orderBy)) {
            $orderBy = $instance->primaryKey;
        }

        if (is_null($minValue)) {
            $minValue = 1;
        }

        if (is_null($maxValue)) {
            $maxValue = 25;
        }

        if (is_null($column)) {
            $column = $instance->primaryKey;
        }

        //return ([$column, $value]);
        $column = htmlentities($column);
        $column = filter_var($column, FILTER_SANITIZE_FULL_SPECIAL_CHARS);

        $query = 'SELECT * FROM ' . $instance->table . ' WHERE ' . $column . ' BETWEEN :minvalue AND :maxvalue ORDER BY ' . $orderBy . (($desc == true) ? ' DESC' : ' ASC');
        //return [$query];

        $result = self::$driver->statement($query, [':minvalue' => $minValue, ':maxvalue' => $maxValue]);
        //return [count($result), $result];
        //return [$result];
        //exit;
        if (count($result) == 0) {
            return [];
        }
        //return [$result];
        $models = [$instance->setAttributes($result[0])];
        for ($i = 1; $i < count($result); $i++) {
            $models[] = (new static())->setAttributes($result[$i]);
        }

        return array_map(fn ($user) => $user->toArray(), $models);
    }

}

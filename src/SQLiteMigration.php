<?php

namespace JJCLane\SQLiteMigration;

use Schema;
use Illuminate\Database\Schema\Blueprint;

trait TransformMigration
{
    private $types = [
        'tinyInteger' => 'smallInteger',
        'tinyIncrements' => 'smallIncrements',
        'mediumInteger' => 'integer',
        'mediumIncrements' => 'increments',
        'char' => 'string',
        'double' => 'float',
        //'enum' => 'string', ?
        'geomety' => 'string',
        'geometryCollection' => 'string',
        'ipAddress' => 'string',
        'jsonb' => 'json',
        'lineString' => 'string',
        'macAddress' => 'string',
        'multiLineString' => 'string',
        'multiPoint' => 'string',
        'multiPolygon' => 'string',
        'point' => 'string',
        'polygon' => 'string',
        'timestamp' => 'string',
        'timestampTz' => 'string',
        'uuid' => 'string',
        'year' => 'integer'
    ];

    private function table($table, $callback)
    {
        $this->transformMigration($table, $callback);
    }

    private function transformMigration($table, $callback)
    {
        $blueprint = new Blueprint($table);
        call_user_func($callback, $blueprint);
        $usingSqlLite = Schema::connection($this->getConnection())
            ->getConnection()->getDriverName() === 'sqlite';
        $nonNullableCols = [];
        $userInput = [];

        foreach ($blueprint->getColumns() as &$col) {
            $obj = $col->getAttributes();

            if ($usingSqlLite && !$col->nullable) {
                $obj['nullable'] = true;
                $obj['type'] = $this->mapType($obj['type']);
                $userInput[] = $obj;

                $obj['nullable'] = false;
                $obj['change'] = true;
                $nonNullableCols[] = $obj;
                continue;
            }

            $userInput[] = $obj;
        }
        // For some reason running a dropColumn command overwrites addColumn
        // so we need to run a seperate blueprint.
        $commands = $blueprint->getCommands();
        if ($commands) {
            $this->runMigration($table, [], $commands);
        }
        $this->runMigration($table, $userInput);
        if ($usingSqlLite) {
            $this->runMigration($table, $nonNullableCols);
        }
    }

    private function mapType($type)
    {
        if (array_key_exists($type, $this->types)) {
            return $this->types[$type];
        }
        return $type;
    }

    private function runMigration($tableName, $input = [], $commands = [])
    {
        Schema::table($tableName, function (Blueprint $table) use ($tableName, $input, $commands) {
            foreach ($input as $col) {
                $table->addColumn($col['type'], $col['name'], $col);
            }
            foreach ($commands as $command) {
                $func = $command->name;
                $table->$func($command->columns);
            }
        });
    }
}

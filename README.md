# Laravel SQLite Migrations
A trait to translate Laravel migrations into SQLite safe migrations. 
This avoids the `Cannot add a NOT NULL column with default value NULL` issue that you receive when trying to add a non-nullable column to 
an existing table in a migration by initially adding the column as nullable and then modifying the column in a separate migration.
It also maps Laravel datatypes that aren't supported in SQLite to avoid [this](https://github.com/laravel/framework/issues/8840).

## Installation
`composer require jjclane/laravel-sqlite-migrations --dev`

## How to use
````php
<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use JJCLane\SQLiteMigration\TransformMigration;

class AddColumnToTable extends Migration
{
    use TransformMigration;

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $this->table('table', function (Blueprint $table) {
            // Normal migrations
            $table->decimal('my_col', 10, 1)->unsigned()->after('my_other_col');
        });
        
        // or if you prefer to be more explicit
        $this->transformMigration('table', function (Blueprint $table) {
            // Normal migrations
            $table->decimal('my_col', 10, 1)->unsigned()->after('my_other_col');
        });
    }
}
````

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class Broker extends Migration
{
    private $stock_info_table = 'stock_info';
    private $table = 'broker';
    private $child_table = 'broker_branch';
    private $data_table = 'broker_data';
    private $start_year = 2019;

    /**
     * Run the migrations.
     */
    public function up()
    {
        // 證券

        if (!Schema::hasTable($this->table)) {
            Schema::create($this->table, function (Blueprint $table) {
                $table->increments('id');
                $table->string('code');
                $table->string('name');
                $table->timestamps();
            });
        }

        // 分行

        if (!Schema::hasTable($this->child_table)) {
            Schema::create($this->child_table, function (Blueprint $table) {
                $table->increments('id');
                $table->integer('parent_id')->unsigned();
                $table->boolean('is_total');
                $table->string('code');
                $table->string('name');
                $table->timestamps();
            });

            Schema::table($this->child_table, function ($table) {
                $table->foreign('parent_id')->references('id')->on($this->table);
            });
        }

        Schema::create($this->data_table, function (Blueprint $table) {
            $table->increments('id');
            $table->integer('stock_id')->unsigned();
            $table->integer('broker_branch_id')->unsigned();
            $table->date('data_date');
            $table->boolean('is_total');
            $table->integer('buy_number');
            $table->integer('sell_number');
            $table->timestamps();
        });

        Schema::table($this->data_table, function ($table) {
            $table->foreign('stock_id')->references('id')->on($this->stock_info_table);
            $table->foreign('broker_branch_id')->references('id')->on($this->child_table);
        });

        for ($i = $this->start_year; $i < date('Y'); ++$i) {
            $table = $this->data_table.'_'.$i;

            if (!Schema::hasTable($table)) {
                Schema::create($table, function (Blueprint $table) {
                    $table->increments('id');
                    $table->integer('stock_id')->unsigned();
                    $table->integer('broker_branch_id')->unsigned();
                    $table->date('data_date');
                    $table->boolean('is_total');
                    $table->integer('buy_number');
                    $table->integer('sell_number');
                    $table->timestamps();
                });

                Schema::table($table, function ($table) {
                    $table->foreign('stock_id')->references('id')->on($this->stock_info_table);
                    $table->foreign('broker_branch_id')->references('id')->on($this->child_table);
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        for ($i = $this->start_year; $i < date('Y'); ++$i) {
            $table = $this->data_table.'_'.$i;
            Schema::dropIfExists($table);
        }

        Schema::dropIfExists($this->data_table);
        Schema::dropIfExists($this->child_table);
        Schema::dropIfExists($this->table);
    }
}

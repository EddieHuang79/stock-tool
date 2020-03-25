<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class StockDataBackup extends Migration
{
    private $table = 'stock_data';
    private $stock_info_table = 'stock_info';
    private $start_year = '2016';

    /**
     * Run the migrations.
     */
    public function up()
    {
        for ($i = $this->start_year; $i < date('Y'); ++$i) {
            $table = $this->table.'_'.$i;

            if (!Schema::hasTable($table)) {
                Schema::create($table, function (Blueprint $table) {
                    $table->increments('id');
                    $table->integer('stock_id')->unsigned();
                    $table->date('data_date');
                    $table->string('volume');
                    $table->string('open');
                    $table->string('highest');
                    $table->string('lowest');
                    $table->string('close');
                    $table->timestamps();
                });

                Schema::table($table, function ($table) {
                    $table->foreign('stock_id')->references('id')->on($this->stock_info_table);
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
            $table = $this->table.'_'.$i;

            Schema::dropIfExists($table);
        }
    }
}

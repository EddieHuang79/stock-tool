<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class TechnicalAnalysisBackup extends Migration
{
    private $table = 'technical_analysis';
    private $stock_data_table = 'stock_data';
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
                    $table->integer('stock_data_id')->unsigned();
                    $table->integer('code');
                    $table->integer('step')->default(0);
                    $table->date('data_date');
                    $table->float('RSV');
                    $table->float('K9');
                    $table->float('D9');
                    $table->float('RSI5');
                    $table->float('RSI10');
                    $table->float('DIFF');
                    $table->float('MACD');
                    $table->float('OSC');
                    $table->float('MA20')->default(0.0);
                    $table->float('upperBand')->default(0.0);
                    $table->float('lowerBand')->default(0.0);
                    $table->float('percentB')->default(0.0);
                    $table->float('bandwidth')->default(0.0);
                    $table->timestamps();
                });

                Schema::table($table, function ($table) {
                    $table->foreign('stock_id')->references('id')->on($this->stock_info_table);
                    $table->foreign('stock_data_id')->references('id')->on($this->stock_data_table);
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

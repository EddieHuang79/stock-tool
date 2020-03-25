<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class TechnicalAnalysis extends Migration
{
    protected $table = 'technical_analysis';
    protected $stock_data_table = 'stock_data';
    protected $stock_info_table = 'stock_info';

    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create($this->table, function (Blueprint $table) {
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
            $table->timestamps();
        });

        Schema::table($this->table, function ($table) {
            $table->foreign('stock_id')->references('id')->on($this->stock_info_table);
            $table->foreign('stock_data_id')->references('id')->on($this->stock_data_table);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists($this->table);
    }
}

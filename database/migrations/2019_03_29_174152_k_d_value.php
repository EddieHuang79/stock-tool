<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class KDValue extends Migration
{

    protected $table = 'technical_analysis';
    protected $stock_data_table = 'stock_data';

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        Schema::create($this->table, function (Blueprint $table) {
            $table->increments('id');
            $table->integer('stock_data_id')->unsigned();
            $table->integer('type'); // 1: KD, 2: MACD, 3: RSI
            $table->string('value')->default('0');
            $table->timestamps();
        });

        Schema::table($this->table, function($table) {
           $table->foreign('stock_data_id')->references('id')->on($this->stock_data_table);
        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {

        Schema::dropIfExists( $this->table );

    }
}

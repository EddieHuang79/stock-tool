<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class SellBuyPercent extends Migration
{
    private $stock_data_table = 'stock_data';
    private $sell_buy_percent_table = 'sell_buy_percent';
    private $stock_info_table = 'stock_info';

    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create($this->sell_buy_percent_table, function (Blueprint $table) {
            $table->increments('id');
            $table->integer('stock_id')->unsigned();
            $table->integer('stock_data_id')->unsigned();
            $table->integer('code');
            $table->date('data_date');
            $table->float('spread');
            $table->float('buy1');
            $table->float('sell1');
            $table->float('buy2');
            $table->float('sell2');
            $table->float('rally_total');
            $table->float('tumbled_total');
            $table->float('rally_num1');
            $table->float('tumbled_num1');
            $table->string('rally_total_20days');
            $table->string('tumbled_total_20days');
            $table->float('result');
            $table->timestamps();
        });

        Schema::table($this->sell_buy_percent_table, function ($table) {
            $table->foreign('stock_id')->references('id')->on($this->stock_info_table);
            $table->foreign('stock_data_id')->references('id')->on($this->stock_data_table);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists($this->sell_buy_percent_table);
    }
}

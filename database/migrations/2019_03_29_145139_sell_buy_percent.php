<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class SellBuyPercent extends Migration
{

    protected $stock_data_table = 'stock_data';
    protected $sell_buy_percent_table = 'sell_buy_percent';

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        Schema::create($this->sell_buy_percent_table, function (Blueprint $table) {
            $table->increments('id');
            $table->integer('stock_data_id')->unsigned();
            $table->string('spread')->default('0');
            $table->string('buy1')->default('0');
            $table->string('sell1')->default('0');
            $table->string('buy2')->default('0');
            $table->string('sell2')->default('0');
            $table->string('rally_total')->default('0');
            $table->string('tumbled_total')->default('0');
            $table->string('rally_num1')->default('0');
            $table->string('tumbled_num1')->default('0');
            $table->string('rally_total_20days')->default('0');
            $table->string('tumbled_total_20days')->default('0');
            $table->string('result')->default('0');
            $table->timestamps();
        });

        Schema::table($this->sell_buy_percent_table, function($table) {
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

        Schema::dropIfExists( $this->sell_buy_percent_table );

    }

}

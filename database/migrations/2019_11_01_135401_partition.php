<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class Partition extends Migration
{
    private $stock_data_table = 'stock_data';
    private $sell_buy_percent_table = 'sell_buy_percent';
    private $stock_info_table = 'stock_info';

    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('sell_buy_percent_partition', function (Blueprint $table) {
            $table->integer('id')->unsigned();
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

        Schema::table('sell_buy_percent_partition', function ($table) {
            $table->primary(['id', 'data_date']);
            // $table->foreign('stock_id')->references('id')->on($this->stock_info_table);
            // $table->foreign('stock_data_id')->references('id')->on($this->stock_data_table);
        });

        DB::statement('ALTER TABLE sell_buy_percent_partition PARTITION BY RANGE( YEAR(data_date) ) (
            PARTITION p2016 VALUES LESS THAN (2017),
            PARTITION p2017 VALUES LESS THAN (2018),
            PARTITION p2018 VALUES LESS THAN (2019),
            PARTITION p2019 VALUES LESS THAN (2020)
        )');
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('sell_buy_percent_partition');
    }
}

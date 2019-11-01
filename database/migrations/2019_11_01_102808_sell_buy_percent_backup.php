<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class SellBuyPercentBackup extends Migration
{

    private $stock_data_table = 'stock_data';
    private $sell_buy_percent_table = 'sell_buy_percent';
    private $stock_info_table = 'stock_info';
    private $start_year = '2016';

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        
        for ($i=$this->start_year; $i < date("Y"); $i++) 
        { 

            $table = $this->sell_buy_percent_table . '_' . $i;

            if (!Schema::hasTable($table)) 
            {

                Schema::create($table, function (Blueprint $table) {
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

                Schema::table($table, function($table) {
                    $table->foreign('stock_id')->references('id')->on($this->stock_info_table);
                    $table->foreign('stock_data_id')->references('id')->on($this->stock_data_table);
                });
                
            }

        }

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        
        for ($i=$this->start_year; $i < date("Y"); $i++) 
        {

            $table = $this->sell_buy_percent_table . '_' . $i; 

            Schema::dropIfExists( $table );

        }
        
    }
}

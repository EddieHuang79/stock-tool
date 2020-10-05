<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class Dividend extends Migration
{
    private $stock_info_table = 'stock_info';
    private $table = 'dividend';

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create($this->table, function (Blueprint $table) {
            $table->increments('id');
            $table->integer('stock_id')->unsigned();
            $table->date('dividend_date');
            $table->integer('type')->index(); // 1: cash, 2: stock, 3: other
            $table->float('cash');
            $table->float('stock');
            $table->timestamps();
        });

        Schema::table($this->table, function ($table) {
            $table->foreign('stock_id')->references('id')->on($this->stock_info_table);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists($this->table);
    }
}

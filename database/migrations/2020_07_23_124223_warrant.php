<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class Warrant extends Migration
{
    private $stock_info_table = 'stock_info';
    private $table = 'warrant';

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
            $table->boolean('exist');
            $table->integer('bull_type_count');
            $table->integer('bear_type_count');
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

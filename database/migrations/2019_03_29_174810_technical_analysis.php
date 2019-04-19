<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class TechnicalAnalysis extends Migration
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
            $table->integer('type'); // 1: RSV, 2: K9, 3: D9, 4: RSI - 5T, 5: RSI - 10T
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

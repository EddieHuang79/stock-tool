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

        Schema::create($this->stock_data_table, function (Blueprint $table) {
            $table->increments('id');
            $table->integer('code');
            $table->integer('type');
            $table->string('name');
            $table->timestamps();
        });

        Schema::create($this->sell_buy_percent_table, function (Blueprint $table) {
            $table->increments('id');
            $table->integer('stock_data_id')->unsigned();
            $table->date('data_date');
            $table->string('weak_market')->default('0');
            $table->string('begin')->default('0');
            $table->string('highest')->default('0');
            $table->string('lowest')->default('0');
            $table->string('finish')->default('0');
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

        $file = Storage::get('stock/股票列表 - 上市.csv');

        $data = explode("\r\n", $file);

        unset($data[0]);

        foreach ($data as $row) 
        {

            $tmp = explode(",", $row);

            DB::table($this->stock_data_table)->insert(
                [
                    "code"          => (int)$tmp[0],
                    "name"          => $tmp[1],
                    "type"          => 1,
                    "created_at"    => date("Y-m-d H:i:s"),
                    "updated_at"    => date("Y-m-d H:i:s")
                ]
            );

        }

        $file = Storage::get('stock/股票列表 - 上櫃.csv');

        $data = explode("\r\n", $file);

        foreach ($data as $row) 
        {

            $tmp = explode(",", $row);

            DB::table($this->stock_data_table)->insert(
                [
                    "code"          => (int)$tmp[0],
                    "name"          => $tmp[1],
                    "type"          => 2,
                    "created_at"    => date("Y-m-d H:i:s"),
                    "updated_at"    => date("Y-m-d H:i:s")
                ]
            );

        }

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {

        Schema::dropIfExists( $this->sell_buy_percent_table );
        Schema::dropIfExists( $this->stock_data_table );

    }
}

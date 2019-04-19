<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class Stock extends Migration
{

    protected $stock_info_table = 'stock_info';

    protected $stock_data_table = 'stock_data';

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        Schema::create($this->stock_info_table, function (Blueprint $table) {
            $table->increments('id');
            $table->integer('code');
            $table->integer('type');
            $table->string('name');
            $table->timestamps();
        });

        Schema::create($this->stock_data_table, function (Blueprint $table) {
            $table->increments('id');
            $table->integer('stock_id')->unsigned();
            $table->date('data_date');
            $table->string('volume');
            $table->string('open');
            $table->string('highest');
            $table->string('lowest');
            $table->string('close');
            $table->timestamps();
        });

        Schema::table($this->stock_data_table, function($table) {
           $table->foreign('stock_id')->references('id')->on($this->stock_info_table);
        });


        $file = Storage::get('stock/股票列表 - 上市.csv');

        $data = explode("\r\n", $file);

        unset($data[0]);

        foreach ($data as $row) 
        {

            $tmp = explode(",", $row);

            DB::table($this->stock_info_table)->insert(
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

            DB::table($this->stock_info_table)->insert(
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

        Schema::dropIfExists( $this->stock_data_table );
        Schema::dropIfExists( $this->stock_info_table );

    }
}

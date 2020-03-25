<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class Strategy extends Migration
{
    protected $table = 'strategy';
    protected $rel_table = 'strategy_rel';

    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create($this->table, function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->text('description');
            $table->timestamps();
        });

        /*

            type: 1 - FB, 2 - Line
            rel_id: table id

        */

        Schema::create($this->rel_table, function (Blueprint $table) {
            $table->increments('id');
            $table->integer('type')->default(0);
            $table->integer('strategy_id')->unsigned();
            $table->integer('rel_id');
            $table->timestamps();
        });

        Schema::table($this->rel_table, function ($table) {
            $table->foreign('strategy_id')->references('id')->on($this->table);
        });

        $list = [
            [
                'name' => '策略1: KD - 多',
                'description' => json_encode([
                    ['篩選目標: 所有股票'],
                    ['KD金叉, K或D < 20'],
                    ['5日總成交量 > 2500'],
                    ['金叉日期與下引線日期之間不得超過10天(不考慮工作日)'],
                    ['股價介於 20 - 80塊 之間'],
                ]),
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            [
                'name' => '策略2: MACD線 - 多,空',
                'description' => json_encode([
                    ['篩選目標: 大盤'],
                    ['MACD達到最高之後，轉折連續兩天或三天低於前日，為目標賣出訊號'],
                    ['MACD達到最低之後，轉折連續兩天或三天低於前日，為目標買進訊號'],
                ]),
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
        ];

        DB::table($this->table)->insert($list);
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists($this->rel_table);
        Schema::dropIfExists($this->table);
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class Fund extends Migration
{
    private $stock_info_table = 'stock_info';
    private $table = 'fund';
    private $start_year = 2016;

    /**
     * Run the migrations.
     */
    public function up()
    {
        if (!Schema::hasTable($this->table)) {
            Schema::create($this->table, function (Blueprint $table) {
                $table->increments('id');
                $table->integer('code');
                $table->integer('stock_id')->unsigned();
                $table->date('data_date');
                $table->integer('type')->index();
                $table->integer('foreign_investor_buy'); // 外資 買進
                $table->integer('foreign_investor_sell'); // 外資 賣出
                $table->integer('foreign_investor_total'); // 外資 總共
                $table->integer('investment_trust_buy'); // 投信 買進
                $table->integer('investment_trust_sell'); // 投信 賣出
                $table->integer('investment_trust_total'); // 投信 總共
                $table->integer('dealer_buy'); // 自營商 買進
                $table->integer('dealer_sell'); // 自營商 賣出
                $table->integer('dealer_total'); // 自營商 總共
                $table->timestamps();
            });

            Schema::table($this->table, function ($table) {
                $table->foreign('stock_id')->references('id')->on($this->stock_info_table);
            });
        }

        for ($i = $this->start_year; $i <= date('Y'); ++$i) {
            $table = $this->table.'_'.$i;

            if (!Schema::hasTable($table)) {
                Schema::create($table, function (Blueprint $table) {
                    $table->increments('id');
                    $table->integer('code');
                    $table->integer('stock_id')->unsigned();
                    $table->date('data_date');
                    $table->integer('type')->index();
                    $table->integer('foreign_investor_buy'); // 外資 買進
                    $table->integer('foreign_investor_sell'); // 外資 賣出
                    $table->integer('foreign_investor_total'); // 外資 總共
                    $table->integer('investment_trust_buy'); // 投信 買進
                    $table->integer('investment_trust_sell'); // 投信 賣出
                    $table->integer('investment_trust_total'); // 投信 總共
                    $table->integer('dealer_buy'); // 自營商 買進
                    $table->integer('dealer_sell'); // 自營商 賣出
                    $table->integer('dealer_total'); // 自營商 總共
                    $table->timestamps();
                });

                Schema::table($table, function ($table) {
                    $table->foreign('stock_id')->references('id')->on($this->stock_info_table);
                });
            }
        }

        // $stockInfo = Stock::getInstance()->get_stock_list()->mapWithKeys(function($item) {
        //     return [$item->code => $item->id];
        // })->toArray();

        // $startYear = 2013;
        // $range = ['150_', '20_150', '1-19'];

        // for ($year=$startYear; $year <= date("Y"); $year++)
        // {

        //     $table = $this->table . '_' . $year;

        //     foreach ($range as $item)
        //     {
        //         $fileName = $year . '-' . $item . '.csv';

        //         if(Storage::exists( 'profit_year/' . $fileName ))
        //         {

        //             $file = Storage::get('profit_year/' . $fileName);

        //             $fileData = explode("\r\n", str_replace('=', '', $file));

        //             unset($fileData[0]);

        //             $fileData = collect($fileData)->filter(function($item) use($stockInfo) {
        //                 $data = explode(",", str_replace('"', '', $item));
        //                 return isset($stockInfo[$data[0]]) && $data[5] !== '';
        //             })->map(function($item) use($stockInfo, $year) {
        //                 $data = explode(",", str_replace('"', '', $item));
        //                 $year = substr($year, 2);
        //                 return [
        //                     'stock_id' => $stockInfo[$data[0]],
        //                     'revenue' => floatval($data[6]),
        //                     'revenue_growth' => floatval($data[7]),
        //                     'gross_profit' => floatval($data[8]),
        //                     'gross_profit_growth' => floatval($data[9]),
        //                     'net_income' => floatval($data[10]),
        //                     'net_income_growth' => floatval($data[11]),
        //                     'gross_profit_percent' => floatval($data[12]),
        //                     'gross_profit_percent_growth' => floatval($data[13]),
        //                     'net_income_percent' => floatval($data[14]),
        //                     'net_income_percent_growth' => floatval($data[15]),
        //                     'roa' => floatval($data[16]),
        //                     'roa_diff' => floatval($data[17]),
        //                     'roe' => floatval($data[18]),
        //                     'roe_diff' => floatval($data[19]),
        //                     'eps' => floatval($data[20]),
        //                     'eps_growth' => floatval($data[21]),
        //                     'score' => floatval($data[22]),
        //                     'created_at' => date("Y-m-d H:i:s"),
        //                     'updated_at' => date("Y-m-d H:i:s"),
        //                 ];
        //             })->values()->toArray();

        //             DB::table($table)->insert($fileData);

        //         }
        //     }
        // }
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        for ($i = $this->start_year; $i < date('Y'); ++$i) {
            $table = $this->table.'_'.$i;

            Schema::dropIfExists($table);
        }

        Schema::dropIfExists($this->table);
    }
}

<?php

use App\model\Stock;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class ProfitData extends Migration
{
    private $stock_info_table = 'stock_info';
    private $table = 'profit';
    private $start_year = '2013';

    /**
     * Run the migrations.
     */
    public function up()
    {
        for ($i = $this->start_year; $i <= date('Y'); ++$i) {
            $table = $this->table.'_'.$i;

            if (!Schema::hasTable($table)) {
                Schema::create($table, function (Blueprint $table) {
                    $table->increments('id');
                    $table->integer('stock_id')->unsigned();
                    $table->string('quarter'); // 季
                    $table->float('revenue'); // 營收(億)
                    $table->float('revenue_growth'); // 營收成長(%)
                    $table->float('gross_profit'); // 毛利(億)
                    $table->float('gross_profit_growth'); // 毛利成長(%)
                    $table->float('net_income'); // 淨利(億)
                    $table->float('net_income_growth'); // 淨利成長(%)
                    $table->float('gross_profit_percent'); // 毛利(%)
                    $table->float('gross_profit_percent_growth'); // 毛率增減
                    $table->float('net_income_percent'); // 淨利(%)
                    $table->float('net_income_percent_growth'); // 淨率增減
                    $table->float('roa'); // 單季ROA(%)
                    $table->float('roa_growth'); // 單季ROA增減
                    $table->float('roa_year'); // 年估ROA(%)
                    $table->float('roe_quarter'); // 單季ROE(%)
                    $table->float('roe_quarter_growth'); // 單季ROE增減
                    $table->float('roe_year'); // 年估ROE(%)
                    $table->float('eps'); // EPS(元)
                    $table->float('eps_growth'); // EPS增減(元)
                    $table->integer('score'); // 財報評分
                    $table->timestamps();
                });

                Schema::table($table, function ($table) {
                    $table->foreign('stock_id')->references('id')->on($this->stock_info_table);
                });
            }
        }

        $stockInfo = Stock::getInstance()->get_stock_list()->mapWithKeys(function ($item) {
            return [$item->code => $item->id];
        })->toArray();

        $startYear = 2013;
        $range = ['150_', '20_150', '1-19'];

        for ($year = $startYear; $year <= date('Y'); ++$year) {
            $table = $this->table.'_'.$year;

            for ($quature = 1; $quature <= 4; ++$quature) {
                foreach ($range as $item) {
                    $fileName = $year.'Q'.$quature.'-'.$item.'.csv';

                    if (Storage::exists('profit/'.$fileName)) {
                        $file = Storage::get('profit/'.$fileName);

                        $fileData = explode("\r\n", str_replace('=', '', $file));

                        unset($fileData[0]);

                        $fileData = collect($fileData)->filter(function ($item) use ($stockInfo) {
                            $data = explode(',', str_replace('"', '', $item));

                            return isset($stockInfo[$data[0]]) && $data[5] !== '';
                        })->map(function ($item) use ($stockInfo, $year) {
                            $data = explode(',', str_replace('"', '', $item));
                            $year = substr($year, 2);

                            return [
                                'stock_id' => $stockInfo[$data[0]],
                                'quarter' => str_replace($year.'Q', '', $data[4]),
                                'revenue' => (float) ($data[5]),
                                'revenue_growth' => (float) ($data[6]),
                                'gross_profit' => (float) ($data[7]),
                                'gross_profit_growth' => (float) ($data[8]),
                                'net_income' => (float) ($data[9]),
                                'net_income_growth' => (float) ($data[10]),
                                'gross_profit_percent' => (float) ($data[11]),
                                'gross_profit_percent_growth' => (float) ($data[12]),
                                'net_income_percent' => (float) ($data[13]),
                                'net_income_percent_growth' => (float) ($data[14]),
                                'roa' => (float) ($data[15]),
                                'roa_growth' => (float) ($data[16]),
                                'roa_year' => (float) ($data[17]),
                                'roe_quarter' => (float) ($data[18]),
                                'roe_quarter_growth' => (float) ($data[19]),
                                'roe_year' => (float) ($data[20]),
                                'eps' => (float) ($data[21]),
                                'eps_growth' => (float) ($data[22]),
                                'score' => (float) ($data[23]),
                                'created_at' => date('Y-m-d H:i:s'),
                                'updated_at' => date('Y-m-d H:i:s'),
                            ];
                        })->values()->toArray();

                        DB::table($table)->insert($fileData);
                    }
                }
            }
        }
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
    }
}

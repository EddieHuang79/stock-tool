<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\model\Stock;

class ProfitYear extends Migration
{

    private $stock_info_table = 'stock_info';
    private $table = 'profit_year';
    private $start_year = '2013';

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        for ($i=$this->start_year; $i <= date("Y"); $i++) 
        { 

            $table = $this->table . '_' . $i;

            if (!Schema::hasTable($table)) 
            {

                Schema::create($table, function (Blueprint $table) {
                    $table->increments('id');
                    $table->integer('stock_id')->unsigned();
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
                    $table->float('roa'); // ROA(%)
                    $table->float('roa_diff'); // ROA增減
                    $table->float('roe'); // ROE(%)
                    $table->float('roe_diff'); // ROE增減
                    $table->float('eps'); // EPS(元)
                    $table->float('eps_growth'); // EPS增減(元)
                    $table->integer('score'); // 財報評分
                    $table->timestamps();
                });

                Schema::table($table, function($table) {
                   $table->foreign('stock_id')->references('id')->on($this->stock_info_table);
                });
                
            }

        }

        $stockInfo = Stock::getInstance()->get_stock_list()->mapWithKeys(function($item) {
            return [$item->code => $item->id];
        })->toArray();

        $startYear = 2013;
        $range = ['150_', '20_150', '1-19'];

        for ($year=$startYear; $year <= date("Y"); $year++) 
        { 

            $table = $this->table . '_' . $year;

            foreach ($range as $item) 
            {
                $fileName = $year . '-' . $item . '.csv';

                if(Storage::exists( 'profit_year/' . $fileName )) 
                {

                    $file = Storage::get('profit_year/' . $fileName);

                    $fileData = explode("\r\n", str_replace('=', '', $file));

                    unset($fileData[0]);

                    $fileData = collect($fileData)->filter(function($item) use($stockInfo) {
                        $data = explode(",", str_replace('"', '', $item));
                        return isset($stockInfo[$data[0]]) && $data[5] !== '';
                    })->map(function($item) use($stockInfo, $year) {
                        $data = explode(",", str_replace('"', '', $item));                         
                        $year = substr($year, 2);
                        return [
                            'stock_id' => $stockInfo[$data[0]],
                            'revenue' => floatval($data[6]),
                            'revenue_growth' => floatval($data[7]),
                            'gross_profit' => floatval($data[8]),
                            'gross_profit_growth' => floatval($data[9]),
                            'net_income' => floatval($data[10]),
                            'net_income_growth' => floatval($data[11]),
                            'gross_profit_percent' => floatval($data[12]),
                            'gross_profit_percent_growth' => floatval($data[13]),
                            'net_income_percent' => floatval($data[14]),
                            'net_income_percent_growth' => floatval($data[15]),
                            'roa' => floatval($data[16]),
                            'roa_diff' => floatval($data[17]),
                            'roe' => floatval($data[18]),
                            'roe_diff' => floatval($data[19]),
                            'eps' => floatval($data[20]),
                            'eps_growth' => floatval($data[21]),
                            'score' => floatval($data[22]),
                            'created_at' => date("Y-m-d H:i:s"),
                            'updated_at' => date("Y-m-d H:i:s"),
                        ];
                    })->values()->toArray();

                    DB::table($table)->insert($fileData);

                }
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

            $table = $this->table . '_' . $i; 

            Schema::dropIfExists( $table );

        }

    }
}

<?php

namespace App\jobs;

use App\logic\Stock_logic;
use App\logic\TechnicalAnalysis_logic;
use App\logic\SellBuyPercent_logic;
use Illuminate\Support\Facades\Storage;
use App\Traits\Mathlib;
use App\logic\Record_logic;


class BollingerBandsStrategySimulation
{

    use Mathlib;

    private $file_name = 'Strategy/strategy3.txt';

    private $start = '2016-01-01';

    private $end = '2018-12-31';

    private $ori_content = '';

    private $content = '';

    private $last_code = 0;

    private $Tech = '';

    private $Stock = '';

    private $Stock_data = '';

    private $not_read = '';

    private $Tech_data = '';

    private $buy_date = [];

    private $sell_date = [];

    private $code = 0;

    private $result = [];

    private $insert_content = [];

    private $sellBuyPercent = [];

    private $new_content = '';

    private function create_file()
    {

        if ( file_exists( storage_path( 'app/' . $this->file_name ) ) === false )
        {

            Storage::put( $this->file_name , '');

        }

    }

    private function clear()
    {

        Storage::put( $this->file_name , '');

    }

    private function set()
    {

        $content = Storage::get( $this->file_name );

        $this->ori_content = $content;

        $content = explode("\n", $content);

        $content = array_filter( $content, "trim" );

        $last = explode(",", end($content));

        $this->content = $content;

        $this->last_code = $last[0] = 2375-1;

        $this->Tech = TechnicalAnalysis_logic::getInstance();

        $this->Stock = Stock_logic::getInstance();

    }

    private function set_filter_code()
    {

        $this->not_read = $this->Stock->get_stock_by_none_price()->pluck("code")->toArray();

    }

    private function setStockData()
    {

        try{

            $this->Stock_data = $this->Stock->get_stock_data_by_date_range( $this->start, $this->end, $this->code );

            if ( empty($this->Stock_data) )
            {

                throw new \Exception( "無股價資料" );

            }

            $this->Stock_data = collect( $this->Stock_data[$this->code] )->mapWithKeys(function ($item) {
                return [
                    $item->data_date => $this->except( $item->highest + $item->lowest, 2 )
                ];
            })->toArray();

        }catch (\Exception $e) {

            return $e->getMessage();

        }

        return '';

    }

    private function setPercentBData()
    {

        $this->Tech_data = $this->Tech->get_data( $this->code )->filter( function ($item) {
            return $item->step === 4 && $item->percentB !== 0.0 && $this->start <= $item->data_date && $item->data_date <= $this->end;
        } )->map(function ($item) {
            return [
                "data_date" => $item->data_date,
                "percentB"  => $item->percentB
            ];
        })->values()->toArray();

    }

    private function setSellBuyPercentData()
    {

        $this->sellBuyPercent = SellBuyPercent_logic::getInstance()->get_data( $this->code )->filter(function ($item){
            return $item->result > 0;
        })->mapWithKeys(function ($item){
            return [$item->data_date => $item->result];
        })->toArray();

    }

    private function setTradeDate()
    {

        $has_stock = false;

        foreach ($this->Tech_data as $row )
        {

            if ( $row["percentB"] >= 0.8 && $has_stock === false )
            {

                $has_stock = true;

                $this->buy_date[] = $row;

            }

            if ( $row["percentB"] < 0.8 && $has_stock === true )
            {

                $has_stock = false;

                $this->sell_date[] = $row;

            }

        }

    }

    private function count_avg()
    {

        $sum = array_sum( $this->Stock_data );

        $cnt = count( $this->Stock_data );

        return round( $sum/$cnt, 2 );

    }

    private function error_filter()
    {

        try {

            //  價格太低的濾掉

            if ( $this->count_avg() < 30 )
            {
                throw new \Exception( "均價低於30" );
            }

            //  沒資料

            if ( empty($this->buy_date) )
            {

                throw new \Exception( "沒有符合的percentB資料" );

            }
            else
            {

                collect( $this->sell_date )->map(function ($item, $key){

                    //  日期不對的過濾掉

                    if ( !isset($this->Stock_data[ $this->buy_date[$key]["data_date"] ]) )
                    {

                        throw new \Exception( "買進日期比對失敗" );
                    }

                    if ( !isset($this->Stock_data[ $item["data_date"] ]) )
                    {

                        throw new \Exception( "賣出日期比對失敗" );
                    }

                });

            }


        }
        catch (\Exception $e) {

            return $e->getMessage();

        }

        return '';

    }

    private function format()
    {

        $this->result = collect( $this->sell_date )->filter(function ($item){
            $sell_date = $item["data_date"];
            return isset($this->Stock_data[$sell_date]) && !empty($this->Stock_data[$sell_date]);
        })->map(function ($item, $key){

            $buy_date = $this->buy_date[$key]["data_date"];

            $sell_date = $item["data_date"];

            return implode(",", [
                "code"          =>  $this->code,
                "buy_date"      =>  $buy_date,
                "buy_percentB"  =>  $this->buy_date[$key]["percentB"],
                "buy_price"     =>  $this->Stock_data[$buy_date],
                "sell_date"     =>  $sell_date,
                "sell_percentB" =>  $item["percentB"],
                "sell_price"    =>  $this->Stock_data[$sell_date],
                "diff"          =>  round( $this->Stock_data[$sell_date] - $this->Stock_data[$buy_date], 2 ),
                "sellBuyPercentAtBuy"       =>  isset( $this->sellBuyPercent[ $buy_date ] ) ? $this->sellBuyPercent[ $buy_date ] : 0,
                "sellBuyPercentAtSell"      =>  isset( $this->sellBuyPercent[ $sell_date ] ) ? $this->sellBuyPercent[ $sell_date ] : 0,
                "error"         =>  "Correct"
            ]);

        });

    }

    private function process()
    {

        $this->insert_content = $this->Stock->get_all_stock_info()->filter(function ($item) {
            return $item->code > $this->last_code && !in_array( $item->code, $this->not_read ) ;
        })->forPage(0, 1)->map(function ($item) {

            try
            {

                $this->code = $item->code;

                $error = $this->setStockData();

                if ( !empty($error) )
                {

                    throw new \Exception( $error );

                }

                $this->setPercentBData();

                $this->setTradeDate();

                $this->setSellBuyPercentData();

                $error = $this->error_filter();

                if ( !empty($error) )
                {

                    throw new \Exception( $error );

                }

                $this->format();

            }
            catch (\Exception $e)
            {

                return collect( [implode(",", [
                    "code"                      =>  $item->code,
                    "buy_date"                  =>  '-',
                    "buy_percentB"              =>  '-',
                    "buy_price"                 =>  0,
                    "sell_date"                 =>  '-',
                    "sell_percentB"             =>  '-',
                    "sell_price"                =>  0,
                    "diff"                      =>  0,
                    "sellBuyPercentAtBuy"       =>  0,
                    "sellBuyPercentAtSell"      =>  0,
                    "error"                     =>  $e->getMessage()
                ])] );

            }

            return $this->result;

        });

    }

    private function checkError()
    {

        try{

            $data = $this->insert_content->map(function ($item){
                $item = $item->map(function ($item){
                    $tmp = explode(",", $item);
                    return $tmp[1];
                });
                return $item;
            })->toArray();

            foreach ($data as $row)
            {

                foreach ($row as $key => $date)
                {

                    if ( isset($row[$key - 1]) && strtotime($date) <= strtotime($row[$key - 1]) ) {

                        throw new \Exception( true );
                    }

                }

            }

        }
        catch (\Exception $e)
        {

            return true;

        }

        return false;

    }

    private function record()
    {

        $this->insert_content->map(function ($item){
            collect( $item )->map(function ($item){
                $this->ori_content .= $item . "\n";
                $this->new_content .= $item . "\n";
            });
        });

        Storage::put( $this->file_name , $this->ori_content );

    }

    public function count()
    {

//        $this->clear();

        Record_logic::getInstance()->write_operate_log( $action = 'BollingerBandsStrategySimulation', $content = 'in process' );

        //  建立檔案

        $this->create_file();

        //  設定變數

        $this->set();

        //  股價為 -- 的項目計算上有誤差，撈出來排除掉

        $this->set_filter_code();

        //  過程

        $this->process();

        //  檢查

        if ( $this->checkError() === true ) {

            return false;

        }

        //  記錄結果

        $this->record();

        return true;

    }

    public static function getInstance()
    {

        return new self;

    }

}

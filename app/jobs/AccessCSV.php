<?php

namespace App\jobs;

use App\Traits\SchemaFunc;
use Ixudra\Curl\Facades\Curl;
use App\Traits\stockFileLib;
use App\logic\Stock_logic;
use App\logic\Record_logic;

class AccessCSV
{

    use SchemaFunc, stockFileLib;

    // 		上市網址

    private function get_TWSE_listed_url( $date, $code )
    {

        return 'http://www.twse.com.tw/exchangeReport/STOCK_DAY?response=csv&date=' . $date . '&stockNo=' . $code;

    }


    // 		上櫃網址
    //		$response = Curl::to( $url )->withResponseHeaders()->returnResponseObject()->get(); 破解

    private function get_TPEx_listed_url( $date, $code )
    {

        $date = $this->year_change( $date );

        return 'https://www.tpex.org.tw/web/stock/aftertrading/daily_trading_info/st43_download.php?l=zh-tw&d=' . $date . '&stkno=' . $code . '&s=[0,asc,0]';

    }


    // 	多筆cron的分批執行

    private function get_delay_config( $type )
    {

        $result = [
            "sleep_second" 	=> 0,
            "code_start" 	=> 0,
            "code_end" 		=> 0,
            "file"			=> []
        ];

        switch ( $type )
        {

            case 1:

                $result = [
                    "sleep_second" 	=> 0,
                    "code_start" 	=> 3500,
                    "code_end" 		=> 3799,
                    "file"			=> $this->get_dir_files( "st3000" )
                ];

                break;

            case 2:

                $result = [
                    "sleep_second" 	=> 5,
                    "code_start" 	=> 3500,
                    "code_end" 		=> 3799,
                    "file"			=> $this->get_dir_files( "st3000" )
                ];

                break;

            case 3:

                $result = [
                    "sleep_second" 	=> 10,
                    "code_start" 	=> 3500,
                    "code_end" 		=> 3799,
                    "file"			=> $this->get_dir_files( "st3000" )
                ];

                break;

            case 4:

                $result = [
                    "sleep_second" 	=> 15,
                    "code_start" 	=> 3500,
                    "code_end" 		=> 3799,
                    "file"			=> $this->get_dir_files( "st3000" )
                ];

                break;

            case 5:

                $result = [
                    "sleep_second" 	=> 20,
                    "code_start" 	=> 3500,
                    "code_end" 		=> 3799,
                    "file"			=> $this->get_dir_files( "st3000" )
                ];

                break;

            case 6:

                $result = [
                    "sleep_second" 	=> 25,
                    "code_start" 	=> 3500,
                    "code_end" 		=> 3799,
                    "file"			=> $this->get_dir_files( "st3000" )
                ];

                break;

            case 7:

                $result = [
                    "sleep_second" 	=> 30,
                    "code_start" 	=> 3500,
                    "code_end" 		=> 3799,
                    "file"			=> $this->get_dir_files( "st3000" )
                ];

                break;

        }

        return $result;

    }


    // 		取得股票基本五項資料轉存到文字檔
    /*

            開盤: open
            收盤: close
            最高: highest
            最低: lowest
            成交量: trading_volume

    */

    public function get_stock_file( $code, $start, $end)
    {

        try
        {

            $end = mktime( 0, 0, 0, date('m', strtotime($end)), 1, date('Y', strtotime($end)) );

            $start = mktime( 0, 0, 0, date('m', strtotime($start)), 1, date('Y', strtotime($start)) );

            $now = $start;

            $i = 0;

            while ( $now <= $end )
            {

                $date = date("Ymd",  $now );

                $stock_data = Stock_logic::getInstance()->get_stock( $code );

                $url = $stock_data->type === 1 ? $this->get_TWSE_listed_url( $date, $code ) : $this->get_TPEx_listed_url( $date, $code ) ;

                $data = Curl::to( $url )->get();

                $this->saveStockFile( $data, $date, $code, $stock_data->type );

                $i = $i + 1;

                $now = mktime( 0, 0, 0, (int)date('m', $start) + $i, 1, date('Y', $start) );

            }


        }
        catch (\Exception $e)
        {

            $this->set_error_msg( $e, $position = 'get_stock_file' );

        }

        return true;

    }

    /*

        條件
        5個工作天內(簡單做，日期-7)
        Macd 最高（最低）後，轉折連續兩天或三天低於（高於）前日，隔一天放空（做多
        目標: 台指期

    */


    // 	更新每日各股資訊，取完檔案立刻寫入資料庫，每一次限制100筆，每筆資料取的區間為5秒
    // 	每寫入一次就更新redis值 [code => date]

    public function update_daily_data( $type = 1 )
    {

        // 待更新的股票資料

        $list = Stock_logic::getInstance()->get_all_stock_update_date( $type );

        // 取得股票類型

        $code_type_mapping = Stock_logic::getInstance()->get_stock_type();

        foreach ($list as $code => $date)
        {

            $date = date("Ym01");

            $type = isset($code_type_mapping[$code]) ? $code_type_mapping[$code] : 1 ;

            $url = $type === 1 ? $this->get_TWSE_listed_url( $date, $code ) : $this->get_TPEx_listed_url( $date, $code ) ;

            $data = Curl::to( $url )->get();

            $this->saveStockFile( $data, $date, $code, $type );

            Record_logic::getInstance()->write_operate_log( $action = 'update_daily_data', $content = $code );

        }

        return true;

    }


    // 		Cron Job 自動取得所有股票資料
    /*

            區間: 近3年
            每次: 1份檔案(避免鎖IP)
            type: 撈資料的區間

    */

    public function auto_get_data( $type = 0 )
    {

        $config = $this->get_delay_config( $type );

        $limit = 1;

        $start = mktime( 0, 0, 0, 1, 1, date("Y") - 3 );

        // 存在的檔案

        $exist_file = collect( $config["file"] )->filter(function( $value ) {
            return strpos( $value, date("Ym") ) !== false;
        })->map(function( $value ) {
            $tmp = explode("/", $value);
            return intval($tmp[2]);
        })->values()->toArray();

        // 股票資料

        $list = Stock_logic::getInstance()->get_stock_option();

        $list = collect( $list["data"] )->pluck( 'value' )->filter( function( $value ) use($exist_file, $config) {
            return !in_array( intval($value), $exist_file) && $value >= $config["code_start"] && $value <= $config["code_end"] ;
        } )->sort()->values()->toArray();

        Record_logic::getInstance()->write_operate_log( $action = 'auto_get_data type' . $type, $content = 'in process' );

        foreach ($list as $code)
        {

            $i = 0;

            while ( $limit > 0 )
            {

                // 已存在的檔案

                $exists_stock_file = $this->get_exist_data( $code );

                $loop_date = date( "Ymd", mktime( 0, 0, 0, (int)date('m', $start) + $i, 1, date('Y', $start) ) );

                $i++;

                if ( strtotime($loop_date) > time() )
                {

                    break;

                }

                if ( !in_array($loop_date, $exists_stock_file) )
                {

                    $this->get_stock_file( $code, $loop_date, $loop_date );

                    $limit--;

                }
                else
                {

                    continue;

                }

                if ( $limit <= 0 || in_array( date("Ymd"), $exists_stock_file) )
                {

                    break 2;

                }

            }

        }

        return true;

    }


    public static function getInstance()
    {

        return new self;

    }

}

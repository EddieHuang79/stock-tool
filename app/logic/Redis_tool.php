<?php

namespace App\logic;

use Illuminate\Support\Facades\Redis;
use App\Traits\SchemaFunc;

class Redis_tool
{

	use SchemaFunc;

    private $update_daily_key = "updateDaily_";
    private $update_fail_key = "updateFail_";
    private $update_fail_process_key = "updateFailProcess_";
    private $divide_stock_key = "divideStockDataDate";
    private $divide_tech_key = "divideTechDate";
    private $divide_sellBuy_key = "divideSellBuyDate";
    private $fund_excute_key = "fundExcute";
    private $fund_excute_key2 = "fundExcute2";


    // 設定當日股票更新清單

    public function setUpdateDaily( $date, $code )
    {

        $result = false;

        if ( !empty($date) && is_string($date) && !empty($code) && is_int($code) )
        {

            $update_daily_key = $this->update_daily_key . $date;

            Redis::RPUSH( $update_daily_key, $code );

            $result = true;

        }

        return $result;

    }

    // 取得當日股票更新清單

    public function getUpdateDaily( $date )
    {

        $update_daily_key = $this->update_daily_key . $date;

        return Redis::LRANGE( $update_daily_key, 0, -1 );

    }

    // 尋找並刪除過去的股票更新清單

    public function delUpdateDaily()
    {

        $list = Redis::Keys( $this->update_daily_key . "*" );

        foreach ($list as $row)
        {

            if ( $row !== $this->update_daily_key . date("Ymd") )
            {

                Redis::del( $row );

            }

        }

        return true;

    }

    // 設定當日股票更新失敗清單

    public function setUpdateFailDaily( $date, $code )
    {

        $result = false;

        if ( !empty($date) && is_string($date) && !empty($code) && is_int($code) )
        {

            $update_fail_key = $this->update_fail_key . $date;

            Redis::RPUSH( $update_fail_key, $code );

            $result = true;

        }

        return $result;

    }

    // 取得當日股票更新失敗清單

    public function getUpdateFailDaily( $date )
    {

        $update_fail_key = $this->update_fail_key . $date;

        return Redis::LRANGE( $update_fail_key, 0, -1 );

    }

    // 尋找並刪除過去的股票更新失敗清單

    public function delUpdateFailDaily()
    {

        $list = Redis::Keys( $this->update_fail_key . "*" );

        foreach ($list as $row)
        {

            if ( $row !== $this->update_daily_key . date("Ymd") )
            {

                Redis::del( $row );

            }

        }

        return true;

    }


    // 資料處理用

    public function setUpdateFailProcessDaily( $date, $code )
    {

        $result = false;

        if ( !empty($date) && is_string($date) && !empty($code) && is_int($code) )
        {

            $update_fail_process_key = $this->update_fail_process_key . $date;

            Redis::RPUSH( $update_fail_process_key, $code );

            $result = true;

        }

        return $result;

    }

    // 取得當日股票更新失敗清單

    public function getUpdateFailProcessDaily( $date )
    {

        $update_fail_process_key = $this->update_fail_process_key . $date;

        return Redis::LRANGE( $update_fail_process_key, 0, -1 );

    }

    // 尋找並刪除過去的股票更新失敗清單

    public function delUpdateFailProcessDaily()
    {

        $list = Redis::Keys( $this->update_fail_process_key . "*" );

        foreach ($list as $row)
        {

            if ( $row !== $this->update_daily_key . date("Ymd") )
            {

                Redis::del( $row );

            }

        }

        return true;

    }

    // 設定劃分key的日期

    public function setDivideStockDataKey($date)
    {

        return Redis::set( $this->divide_stock_key, $date );

    }

    // 取得劃分key的日期

    public function getDivideStockDataKey()
    {

        return Redis::get( $this->divide_stock_key );

    }

    // 設定劃分key的日期

    public function setDivideTechKey($date)
    {

        return Redis::set( $this->divide_tech_key, $date );

    }

    // 取得劃分key的日期

    public function getDivideTechKey()
    {

        return Redis::get( $this->divide_tech_key );

    }

    // 設定劃分key的日期

    public function setDivideSellBuyKey($date)
    {

        return Redis::set( $this->divide_sellBuy_key, $date );

    }

    // 取得劃分key的日期

    public function getDivideSellBuyKey()
    {

        return Redis::get( $this->divide_sellBuy_key );

    }

    // 設定劃分key的日期

    public function setFundExcuteKey($date)
    {

        return Redis::set( $this->fund_excute_key, $date );

    }

    // 取得劃分key的日期

    public function getFundExcuteKey()
    {

        $date = Redis::get( $this->fund_excute_key );

        return !empty($date) ? $date : mktime( 0, 0, 0, 1, 1, 2016 );

    }

    // 設定劃分key的日期

    public function setFundExcuteKey2($date)
    {

        return Redis::set( $this->fund_excute_key2, $date );

    }

    // 取得劃分key的日期

    public function getFundExcuteKey2()
    {

        $date = Redis::get( $this->fund_excute_key2 );

        return !empty($date) ? $date : mktime( 0, 0, 0, 1, 1, 2016 );

    }

    public static function getInstance()
    {

        return new self;

    }


}



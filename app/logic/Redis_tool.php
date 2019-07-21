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

    public static function getInstance()
    {

        return new self;

    }


}



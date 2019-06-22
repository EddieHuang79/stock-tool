<?php

namespace App\logic;

use Illuminate\Support\Facades\Redis;
use App\Traits\SchemaFunc;

class Redis_tool
{

	use SchemaFunc;

    private $filter_stock_key = "filterStock_";

    private $update_daily_key = "updateDaily_";

	// 設定股票過濾清單

	public function setFilterStock( $code )
	{

		$result = false;

		if ( !empty($code) && is_int($code) )
		{

			$filter_stock_key = $this->filter_stock_key;

			Redis::RPUSH( $filter_stock_key, $code );

			$result = true;

		}

		return $result;

	}


	// 取得股票過濾清單

	public function getFilterStock()
	{

		$filter_stock_key = $this->filter_stock_key;

		return Redis::LRANGE( $filter_stock_key, 0, -1 );

	}


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

    public static function getInstance()
    {

        return new self;

    }


}



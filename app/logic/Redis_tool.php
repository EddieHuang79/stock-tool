<?php

namespace App\logic;

use Illuminate\Support\Facades\Redis;
use App\Traits\SchemaFunc;

class Redis_tool
{

	use SchemaFunc;

    private $filter_stock_key = "filterStock_";

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

    public static function getInstance()
    {

        return new self;

    }


}



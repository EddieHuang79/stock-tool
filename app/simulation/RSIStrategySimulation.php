<?php

namespace App\simulation;

use App\abstracts\RSIStrategy;

/*
 買進條件:
    1.  RSI > 80 超過3日
    2.  20日平均成交量 > 500
 賣出條件:
    1.  RSI < 80
    2.  收盤價 < 5MA
*/

class RSIStrategySimulation extends RSIStrategy
{

    // 交易策略

    protected function setTradeDate()
    {

        $has_stock = false;

        foreach ($this->Tech_data as $row )
        {

            $MA5 = isset($this->MA5[$row["data_date"]]) ? $this->MA5[$row["data_date"]] : 0 ;

            if ( $row["continueDays"] > 3 && $has_stock === false )
            {

                $this->set_volume( $row["data_date"] );

                if ( $this->volume_data > $this->volume_limit )
                {

                    $has_stock = true;

                    $this->buy_date[] = $row;

                }

            }

            if ( $has_stock === true && $row["RSI5"] < 80 && $MA5 > $this->Stock_avg_price[$row["data_date"]] )
            {

                $has_stock = false;

                $this->sell_date[] = $row;

            }

        }

    }

    public function do()
    {

        $this->set_file_name( "Strategy/RSIStrategySimulation1.txt" );

        $this->set_log_title( "RSIStrategySimulation1" );

        $this->count();

        return true;

    }


    public static function getInstance()
    {

        return new self;

    }

}

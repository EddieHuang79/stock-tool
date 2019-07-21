<?php

namespace App\simulation;

use App\abstracts\BollingerBandsStrategy;


/*
 買進條件:
    1.  均價 > 20
    2.  percentB >= 0.8
    3.  sellBuyPercent < 0.8
    4.  10日平均成交量 > 500
 賣出條件:
    1.  percentB < 0.8
    2.  sellBuyPercent > 0.7
*/

class BollingerBandsStrategySimulation7 extends BollingerBandsStrategy
{

    // 交易策略

    protected function setTradeDate()
    {

        $has_stock = false;

        foreach ($this->Tech_data as $row )
        {

            $sellBuyPercent = isset($this->sellBuyPercent[$row["data_date"]]) ? $this->sellBuyPercent[$row["data_date"]] : 0 ;

            if ( $row["percentB"] >= 0.8 && !empty($sellBuyPercent) && $sellBuyPercent <= 0.8 && $has_stock === false )
            {

                $this->set_volume( $row["data_date"] );

                if ( $this->volume_data > $this->volume_limit )
                {

                    $has_stock = true;

                    $this->buy_date[] = $row;

                }

            }

            if ( $has_stock === true && $row["percentB"] < 0.8 && $sellBuyPercent > 0.7 )
            {

                $has_stock = false;

                $this->sell_date[] = $row;

            }

        }

    }

    public function do()
    {

        $this->set_file_name( "Strategy/BollingerBandsStrategySimulation7.txt" );

        $this->set_log_title( "BollingerBandsStrategySimulation7" );

        $this->count();

        return true;

    }

    public static function getInstance()
    {

        return new self;

    }

}

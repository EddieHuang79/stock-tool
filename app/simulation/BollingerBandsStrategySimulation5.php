<?php

namespace App\simulation;

use App\abstracts\BollingerBandsStrategy;


/*
 買進條件:
    1.  均價 > 20
    2.  percentB >= 0.8
    3.  sellBuyPercent < 0.8
    4.  bandwidth <= 0.05
 賣出條件:
    1.  percentB < 0.8
    2.  sellBuyPercent > 0.8
*/

class BollingerBandsStrategySimulation5 extends BollingerBandsStrategy
{

    // 交易策略

    protected function setTradeDate()
    {

        $has_stock = false;

        foreach ($this->Tech_data as $row )
        {

            $sellBuyPercent = isset($this->sellBuyPercent[$row["data_date"]]) ? $this->sellBuyPercent[$row["data_date"]] : 0 ;

            if ( $row["percentB"] >= 0.8 && !empty($sellBuyPercent) && $sellBuyPercent <= 0.8 && $has_stock === false && $row["bandwidth"] <= 0.05 )
            {

                $has_stock = true;

                $this->buy_date[] = $row;

            }

            if ( $has_stock === true && $row["percentB"] < 0.8 && $sellBuyPercent > 0.8 )
            {

                $has_stock = false;

                $this->sell_date[] = $row;

            }

        }

    }

    public function do()
    {

        $this->set_file_name( "Strategy/BollingerBandsStrategySimulation5.txt" );

        $this->set_log_title( "BollingerBandsStrategySimulation5" );

        $this->count();

        return true;

    }

    public static function getInstance()
    {

        return new self;

    }

}

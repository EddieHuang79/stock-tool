<?php

namespace App\jobs;

use App\abstracts\BollingerBandsStrategy;

/*
 買進條件:
    1.  均價 > 20
    2.  percentB >= 0.8
    3.  sellBuyPercent < 0.8
    4.  10日平均成交量 > 500
 賣出條件:
    1.  percentB < 0.8
    2.  獲利 虧損時設定不同條件
        獲利時 sellBuyPercent放寬到0.9，虧損時 維持在0.7
*/

class BollingerBandsStrategySimulation9 extends BollingerBandsStrategy
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

            if ( $has_stock === true && $row["percentB"] < 0.8 )
            {

                // 獲利 虧損時設定不同條件
                // 獲利時 sellBuyPercent放寬到0.9，虧損時 維持在0.7

                $last_buy_data = end($this->buy_date);

                $buy_price = isset($this->Stock_data[ $last_buy_data["data_date"] ]) ? $this->Stock_data[ $last_buy_data["data_date"] ] : '' ;

                $sell_price = isset($this->Stock_data[ $row["data_date"] ]) ? $this->Stock_data[ $row["data_date"] ] : '' ;

                if ( !empty($buy_price) && !empty($sell_price) )
                {

                    $limit = $sell_price > $buy_price ? 0.9 : 0.7;

                    if ( $sellBuyPercent > $limit )
                    {

                        $has_stock = false;

                        $this->sell_date[] = $row;

                    }

                }

            }

        }

    }

    public function do()
    {

        $this->set_file_name( "Strategy/BollingerBandsStrategySimulation9.txt" );

        $this->set_log_title( "BollingerBandsStrategySimulation9" );

        $this->count();

        return true;

    }

    public static function getInstance()
    {

        return new self;

    }

}

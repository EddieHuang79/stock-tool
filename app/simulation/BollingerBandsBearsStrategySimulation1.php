<?php

namespace App\simulation;

use App\abstracts\BollingerBandsStrategy;

/*
 做空策略 熊市策略
 賣出條件:
    1.  percentB < 0.2
    2.  sellBuyPercent > 1.3
 買進條件:
    1.  percentB >= 0.3
    2.  sellBuyPercent <= 1.2
*/

class BollingerBandsBearsStrategySimulation1 extends BollingerBandsStrategy
{

    // 交易策略

    protected function setTradeDate()
    {

        $has_stock = false;

        foreach ($this->Tech_data as $stock_id => $item )
        {

            foreach ($item as $date => $row) 
            {

                $sellBuyPercent = isset($this->sellBuyPercent[$stock_id][$date]) ? $this->sellBuyPercent[$stock_id][$date] : 0 ;

                // 先賣

                if ( $has_stock === false && $row->percentB < 0.2 && $sellBuyPercent > 1.3 )
                {

                    $has_stock = true;

                    $this->buy_date[$stock_id][] = get_object_vars($row);

                }

                // 後買

                if ( $row->percentB >= 0.3 && !empty($sellBuyPercent) && $sellBuyPercent <= 1.2 && $has_stock === true )
                {

                    $has_stock = false;

                    $this->sell_date[$stock_id][] = get_object_vars($row);

                }

            }

        }

    }

    public function do(int $page, int $limit, int $year)
    {

        $this->year = $year;

        $this->page = $page;

        $this->limit = $limit;

        $this->volume_limit = 500;

        $this->set_file_name( "Strategy/BollingerBandsBearsStrategySimulation1.txt" );

        $this->set_log_title( "BollingerBandsBearsStrategySimulation1" );

        $this->count();

        return true;

    }

    public static function getInstance()
    {

        return new self;

    }

}

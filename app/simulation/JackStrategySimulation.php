<?php

namespace App\simulation;

use App\abstracts\JackStrategy;

/*
    買進條件

    20日成交量平均 > 500
    突破季線(MA60)不超過5日
    突破月線(MA20)不超過3日

    近3年毛利率平均 > 20%
    每股盈餘(EPS)連續3年遞增

    賣出條件

    跌破10日線

*/

class JackStrategySimulation extends JackStrategy
{

    // 交易策略

    protected function setTradeDate()
    {

        foreach ($this->year as $year) 
        {

            $this->volume_data[$year]->filter(function($item, $year) {
                return count($item) > 0;
            })->map(function($item, $stock_id) use($year) {
                $has_stock = false;
                $closePrice = collect($this->simulate_data[$year][$stock_id])->mapWithKeys(function($item, $date) {
                                    return [$date => $item['close']];
                                })->toArray();
                foreach ($item as $date => $volume) 
                {

                    $over_MA60_days = $this->count_over_avg_days($year, 60, $date, $closePrice, $stock_id);
                    $over_MA20_days = $this->count_over_avg_days($year, 20, $date, $closePrice, $stock_id);

                    if ($has_stock && round($closePrice[$date], 2) < round($this->MA[$year][10][$stock_id][$date], 2)) 
                    {
                        
                        $has_stock = false;

                        $sellData = $this->simulate_data[$year][$stock_id][$date];

                        $verify = [
                            "closePrice"    => $closePrice[$date],
                            "MA10Price"     => $this->MA[$year][10][$stock_id][$date],
                        ];

                        $this->sell_date[$stock_id][] = array_merge($sellData, $verify);

                    }

                    if ($volume > 500 && $over_MA60_days["days"] <= 5 && $over_MA20_days["days"] <= 3 && $over_MA60_days["status"] && $over_MA20_days["status"] && $has_stock === false) 
                    {

                        $has_stock = true;

                        $buyData = $this->simulate_data[$year][$stock_id][$date];

                        $verify = [
                            "over_MA60_days" => $over_MA60_days["days"],
                            "over_MA20_days" => $over_MA20_days["days"],
                        ];

                        $this->buy_date[$stock_id][] = array_merge($buyData, $verify);

                    }
                    
                }
                // echo "<br>";
                return '';
            });

        }

    }

    public function do(array $year)
    {

        $this->year = $year;

        $this->set_file_name( "Strategy/JackStrategySimulation1.txt" );

        $this->set_log_title( "JackStrategySimulation1" );

        $this->count();

        return true;

    }

    public static function getInstance()
    {

        return new self;

    }

}

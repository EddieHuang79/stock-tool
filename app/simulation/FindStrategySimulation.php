<?php

namespace App\simulation;

use App\abstracts\FindStrategy;
use App\logic\Holiday_logic;

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

class FindStrategySimulation extends FindStrategy
{

    // 交易策略

    protected function setTradeDate()
    {
        $target = [];

        foreach ($this->year as $year) 
        {

            $target[$year] = $target[$year] ?? [];

            $target[$year] = collect($this->bollingerBandsData[$year])->map(function($item, $stock_id) use($year) {
                return collect($item)->filter(function($item, $date) {
                    return $item->bandwidth > 0 && $item->bandwidth < 0.05 && $item->percentB > 0.8;
                })->filter(function($item, $date) use($stock_id, $year) {
                    return isset($this->stock_data[$year][$stock_id][$date]) && isset($this->volume_data[$year][$stock_id][$date]);
                })->map(function($item, $date) use($stock_id, $year) {
                    return [
                        '20DaysAvgVolume' => $this->volume_data[$year][$stock_id][$date],
                        'volume' => $this->stock_data[$year][$stock_id][$date]['volume'],
                        'closePrice' => $this->stock_data[$year][$stock_id][$date]['close'],
                        'timeStamp' => strtotime($date),
                    ];
                })->map(function($item, $date) use($stock_id, $year) {
                    $nextDays = Holiday_logic::getInstance()->get_work_date( $before_days = 1, $now_date = date("Y-m-d", $item['timeStamp']), $type = 2 );
                    if (isset($this->stock_data[$year][$stock_id][$nextDays])) 
                    {
                        $item['isUp'] = $this->stock_data[$year][$stock_id][$nextDays]['close'] > $item['closePrice'];
                        $item['priceDiff'] = $this->stock_data[$year][$stock_id][$nextDays]['close'] - $item['closePrice'];
                        $item['priceDiffPercent'] = round($item['priceDiff']/$item['closePrice'], 2);
                        $item['volumeGrowthPercent'] = round($this->stock_data[$year][$stock_id][$date]['volume']/$item['volume'], 2);
                        $item['volumeAvgGrowthPercent'] = round($this->stock_data[$year][$stock_id][$date]['volume']/$item['20DaysAvgVolume'], 2);                    
                    }
                    return $item;
                }); 
            })->filter(function($item) {
                return collect($item)->count() > 0;
            })->toArray();

            $total = 0;
            $up = 0;
            $priceDiff = 0;

            foreach ($target[$year] as $stock_id => $item) {
                foreach ($item as $date => $data) {
                    $total++;
                    $up += isset($data['isUp']) && $data['isUp'] === true ? 1 : 0;
                    $priceDiff += $data['priceDiff'] ?? 0;
                }
            }

            echo $total . "<br>";
            echo $up . "<br>";
            echo $priceDiff . "<br>";

            dd(1);

        }

    }

    public function do(array $year)
    {

        $this->year = $year;

        $this->count();

        return true;

    }

    public static function getInstance()
    {

        return new self;

    }

}

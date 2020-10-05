<?php

namespace App\simulation;

use App\logic\Stock_logic;

class TopHV
{
    public function do()
    {
        $stockInfo = Stock_logic::getInstance()->get_all_stock_info();
        $stockIds = $stockInfo->pluck('id')->values()->toArray();
        $stockData = Stock_logic::getInstance()->get_stock_data($stockIds, '2020-03-01', '2020-03-31');
        $result = $stockData->filter(function ($item) {
            $avgVolume = $item->pluck('volume')->avg();

            return $avgVolume > 1000;
        })->map(function ($item) {
            return $item->filter(function ($item) {
                return $item->close > 0;
            })->map(function ($item) {
                $item->maxPriceDiffPercent = round((($item->highest - $item->lowest) / $item->close) * 100, 2);

                return $item;
            })->pluck('maxPriceDiffPercent')->avg();
        })->sort()->reverse();
        dd($result);
    }

    public static function getInstance()
    {
        return new self();
    }
}

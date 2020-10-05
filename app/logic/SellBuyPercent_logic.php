<?php

namespace App\logic;

use App\model\SellBuyPercent;
use App\Traits\Mathlib;
use App\Traits\SchemaFunc;

class SellBuyPercent_logic
{
    use SchemaFunc;
    use Mathlib;

    private $stock_id;

    private $stock_data;

    private $sellBuyPercentDate;

    // 		取得最後一筆寫入的股票代號  帶入目標工作日

    public function get_count_done_stock_id(string $last_working_date)
    {
        $result = SellBuyPercent::getInstance()->get_count_done_stock_id($last_working_date);

        return $result;
    }

    // 取得指定區間內的買賣壓資料

    public function get_data_assign_range(array $stock_id, string $start, string $end)
    {
        return SellBuyPercent::getInstance()->get_data_assign_range($stock_id, $start, $end)->groupBy('stock_id');
    }

    // 		計算買賣壓力

    public function count_data_logic($stockInfo, $statistics, $sellBuyPercentDate)
    {
        $this->stock_info = $stockInfo;

        $this->stock_data = $statistics;

        $this->sellBuyPercentDate = $sellBuyPercentDate;

        // 		計算收盤成交價差

        $this->count_spread();

        // 		計算買盤1

        $this->count_buy1();

        // 		計算賣盤1

        $this->count_sell1();

        // 		計算買盤2

        $this->count_buy2();

        // 		計算賣盤2

        $this->count_sell2();

        // 		計算漲幅總和、跌幅總和、買盤力道張數、賣盤力道張數

        $this->count_pro_data();

        // 		計算20天總買盤、20天總賣盤、買賣壓力道比例

        $this->count_20days_data_and_result();

        //      格式化

        $this->format();

        //      寫入資料庫

        $this->add_sell_buy_percent_data();

        return true;
    }

    //  取得買賣壓力資料

    public function get_data($code)
    {
        return SellBuyPercent::getInstance()->get_data($code);
    }

    //  取得買賣壓力資料

    public function get_data_by_year(int $year, array $stock_id)
    {
        return SellBuyPercent::getInstance()->get_data_by_year($year, $stock_id);
    }

    public function get_data_by_range($start, $end)
    {
        return SellBuyPercent::getInstance()->get_data_by_range($start, $end);
    }

    public function get_today_result($stock_id = [], $date)
    {
        return SellBuyPercent::getInstance()->get_today_result($stock_id, $date);
    }

    public function get_today_exist_stock_data(string $today)
    {
        return SellBuyPercent::getInstance()->get_today_exist_stock_data($today);
    }

    public static function getInstance()
    {
        return new self();
    }

    // 		計算收盤成交價差
    // 		公式: 當日收盤 - 前日收盤

    private function count_spread()
    {
        $this->stock_data = $this->stock_data->map(function ($item, $key) {
            try {
                if ($key < 1) {
                    throw new \Exception(true);
                }

                //  前日資料

                $yesterday_data = $this->stock_data[$key - 1];

                //  前日收盤

                $item->last_close = $yesterday_data->close;

                //  收盤成交價差

                $spread = round($item->close - $item->last_close, 2);

                $item->spread = $spread;
            } catch (\Exception $e) {
                $item->spread = 0;
            }

            return $item;
        });

        return true;
    }

    // 		計算買盤1
    // 		公式:
    /*
            若 今日收盤價 > 昨日收盤價
            買盤1 = 今日開盤價 - 昨日收盤價
            若 今日收盤價 <= 昨日收盤價
            買盤1 = 今日最高價 - 今日開盤價
    */

    private function count_buy1()
    {
        $this->stock_data = $this->stock_data->map(function ($item, $key) {
            try {
                if ($key < 1) {
                    throw new \Exception(true);
                }

                //  買盤1

                $buy1 = $item->close > $item->last_close ? round($item->open - $item->last_close, 2) : round($item->highest - $item->open, 2);

                $item->buy1 = $buy1;
            } catch (\Exception $e) {
                $item->buy1 = 0;
            }

            return $item;
        });

        return true;
    }

    // 		計算賣盤1
    // 		公式:
    /*
            若 今日收盤價 > 昨日收盤價
            賣盤1 = 今日開盤價 - 今日最低價
            若 今日收盤價 <= 昨日收盤價
            賣盤1 = 昨日收盤價 - 今日開盤價
    */

    private function count_sell1()
    {
        $this->stock_data = $this->stock_data->map(function ($item, $key) {
            try {
                if ($key < 1) {
                    throw new \Exception(true);
                }

                //  買盤1

                $sell1 = $item->close > $item->last_close ? round($item->open - $item->lowest, 2) : round($item->last_close - $item->open, 2);

                $item->sell1 = $sell1;
            } catch (\Exception $e) {
                $item->sell1 = 0;
            }

            return $item;
        });

        return true;
    }

    // 		計算買盤2
    // 		公式:
    /*
            若 今日收盤價 > 昨日收盤價
            買盤2 = 今日最高價 - 今日最低價
            若 今日收盤價 <= 昨日收盤價
            買盤2 = 今日收盤價 - 今日最低價
    */

    private function count_buy2()
    {
        $this->stock_data = $this->stock_data->map(function ($item, $key) {
            try {
                if ($key < 1) {
                    throw new \Exception(true);
                }

                //  買盤1

                $buy2 = $item->close > $item->last_close ? round($item->highest - $item->lowest, 2) : round($item->close - $item->lowest, 2);

                $item->buy2 = $buy2;
            } catch (\Exception $e) {
                $item->buy2 = 0;
            }

            return $item;
        });

        return true;
    }

    // 		計算賣盤2
    // 		公式:
    /*
            若 今日收盤價 > 昨日收盤價
            賣盤2 = 今日最高價 - 今日收盤價
            若 今日收盤價 <= 昨日收盤價
            賣盤2 = 今日最高價 - 今日最低價
    */

    private function count_sell2()
    {
        $this->stock_data = $this->stock_data->map(function ($item, $key) {
            try {
                if ($key < 1) {
                    throw new \Exception(true);
                }

                //  買盤1

                $sell2 = $item->close > $item->last_close ? round($item->highest - $item->close, 2) : round($item->highest - $item->lowest, 2);

                $item->sell2 = $sell2;
            } catch (\Exception $e) {
                $item->sell2 = 0;
            }

            return $item;
        });

        return true;
    }

    // 		計算漲幅總和、跌幅總和、買盤力道張數、賣盤力道張數
    // 		公式:
    /*
            漲幅總和 = 買盤1+買盤2
            跌幅總和 = 賣盤1+賣盤2
            買盤力道張數 = 成交量 * ( 漲幅總和 / ( 漲幅總和+跌幅總和) )
            賣盤力道張數 = 成交量 * ( 跌幅總和 / ( 漲幅總和+跌幅總和) )
    */

    private function count_pro_data()
    {
        $this->stock_data = $this->stock_data->map(function ($item, $key) {
            try {
                if ($key < 1) {
                    throw new \Exception(true);
                }

                // 漲幅總和

                $item->rally_total = round($item->buy1 + $item->buy2, 2);

                // 跌幅總和

                $item->tumbled_total = round($item->sell1 + $item->sell2, 2);

                // 買盤力道張數

                $item->rally_num1 = round($item->volume * $this->except($item->rally_total, $item->rally_total + $item->tumbled_total), 2);

                // 賣盤力道張數

                $item->tumbled_num1 = round($item->volume * $this->except($item->tumbled_total, $item->rally_total + $item->tumbled_total), 2);
            } catch (\Exception $e) {
                $item->rally_total = 0;
                $item->tumbled_total = 0;
                $item->rally_num1 = 0;
                $item->tumbled_num1 = 0;
            }

            return $item;
        });

        return true;
    }

    // 		計算20天總買盤、20天總賣盤、買賣壓力道比例
    // 		公式:
    /*
            20天總買盤 = 買盤力道張數過去20天加總
            20天總賣盤 = 賣盤力道張數過去20天加總
            買賣壓力道比例 = 20天總賣盤/20天總買盤
    */

    private function count_20days_data_and_result()
    {
        $this->stock_data = $this->stock_data->map(function ($item, $key) {
            try {
                if ($key < 19) {
                    throw new \Exception(true);
                }

                $start_key = $key - 19;

                // 20天總買盤

                $item->rally_total_20days = round($this->stock_data->slice($start_key, 20)->pluck('rally_num1')->sum(), 2);

                // 20天總賣盤

                $item->tumbled_total_20days = round($this->stock_data->slice($start_key, 20)->pluck('tumbled_num1')->sum(), 2);

                // 買賣壓力道比例

                $item->result = round($this->except($item->tumbled_total_20days, $item->rally_total_20days), 2);
            } catch (\Exception $e) {
                $item->rally_total_20days = 0;
                $item->tumbled_total_20days = 0;
                $item->result = 0;
            }

            return $item;
        });

        return true;
    }

    //      格式化

    private function format()
    {
        $this->stock_data = $this->stock_data->filter(function ($item) {
            return !\in_array($item->data_date, $this->sellBuyPercentDate, true);
        })->map(function ($item) {
            return [
               'stock_id' => $this->stock_info->id,
               'stock_data_id' => $item->data_id,
               'code' => $this->stock_info->code,
               'data_date' => $item->data_date,
               'spread' => $item->spread,
               'buy1' => $item->buy1,
               'sell1' => $item->sell1,
               'buy2' => $item->buy2,
               'sell2' => $item->sell2,
               'rally_total' => $item->rally_total,
               'tumbled_total' => $item->tumbled_total,
               'rally_num1' => $item->rally_num1,
               'tumbled_num1' => $item->tumbled_num1,
               'rally_total_20days' => $item->rally_total_20days,
               'tumbled_total_20days' => $item->tumbled_total_20days,
               'result' => $item->result,
               'created_at' => date('Y-m-d H:i:s'),
               'updated_at' => date('Y-m-d H:i:s'),
           ];
        });
    }

    // 		寫入資料

    private function add_sell_buy_percent_data()
    {
        SellBuyPercent::getInstance()->add_sell_buy_percent_data($this->stock_data->values()->toArray());

        return true;
    }
}

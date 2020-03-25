<?php

namespace App\query;

use App\logic\Holiday_logic;
use App\logic\Record_logic;
use App\logic\Stock_logic;
use App\Traits\stockFileLib;
use Illuminate\Support\Facades\DB;

class updateNoDataStock
{
    // 1101,1103,1110,1203,1213,1216,1219,1225,1229,1235,1236,1315,1316,1324,1325,1410,1413,1418,1419,1432,1435,1436,1438,1439,1441,1443,1449,1453,1454,1456,1457,1465,1466,1470,1472,1475,1516,1529,1535,1538,1541,1583,1603,1615,1617,1713,1726,1735,1776,1805,1903,2008,2012,2025,2027,2028,2033,2102,2115,2206,2231,2243,2303,2317,2321,2324,2327,2348,2354,2364,2748,9110,9157,9188,9918,9926,9928,9929,9931,1240,1258,1259,1264,1268,1333,1566,1570,1584,1591,1593,1595,1599,1742,1777,1788,1796,1813,4131,4152,5011,5016,5202,5205,5206,5209,5210,5212,6026,6101,6103,6130,7402,8032,8047,8067,8077

    use stockFileLib;

    private $date_close_mapping;

    //  刪除重複資料

    public function update()
    {
        // 取得目標股票

        $Stock = Stock_logic::getInstance();

        $not_read = $Stock->get_stock_by_none_price()->pluck('code')->toArray();

        $content = \count($not_read) > 0 ? 'process' : 'no data';

        Record_logic::getInstance()->write_operate_log($action = 'updateNoDataStock', $content);

        if ($content === 'no data') {
            return true;
        }

        // 取出目標股票資料

        $stock_id = $Stock->get_stock_id($not_read)->pluck('id')->toArray();

        $Stock_data = $Stock->get_assign_code_stock_data($stock_id);

        // 做出日期比較表 [日期 => 收盤]

        $this->date_close_mapping = $Stock_data->mapToGroups(function ($item) {
            return [$item->stock_id => $item];
        })->map(function ($item, $stock_id) {
            $item = $item->mapWithKeys(function ($item) {
                return [$item->data_date => $item->close];
            })->toArray();

            return $item;
        })->toArray();

        // 找出沒價格的日子

        $bad_data = $Stock_data->mapToGroups(function ($item) {
            return [$item->stock_id => $item];
        })->map(function ($item, $stock_id) {
            $item = $item->filter(function ($item) {
                return $item->close === '--';
            });

            return $item;
        });

        // format

        $Holiday_logic = Holiday_logic::getInstance();

        $format = $bad_data->map(function ($item) use ($Holiday_logic) {
            $item = $item->map(function ($item) use ($Holiday_logic) {
                $last_work_day = $Holiday_logic->get_work_date($before_days = 1, $now_date = $item->data_date, $type = 1);
                $this->date_close_mapping[$item->stock_id][$last_work_day] =
                    isset($this->date_close_mapping[$item->stock_id][$last_work_day]) ?
                        $this->date_close_mapping[$item->stock_id][$last_work_day] : '';
                $last_close = $this->date_close_mapping[$item->stock_id][$last_work_day];
                $this->date_close_mapping[$item->stock_id][$item->data_date] = $last_close;
                $item->open = $last_close;
                $item->close = $last_close;
                $item->highest = $last_close;
                $item->lowest = $last_close;

                return $item;
            });

            return $item;
        });

        // update

        $format->map(function ($item) {
            $item->map(function ($item) {
                DB::table('stock_data')->where('id', $item->id)->update([
                    'open' => $item->open,
                    'close' => $item->close,
                    'highest' => $item->highest,
                    'lowest' => $item->lowest,
                ]);
            });
        });

        return true;
    }

    // 回傳自己

    public static function getInstance()
    {
        return new self();
    }
}

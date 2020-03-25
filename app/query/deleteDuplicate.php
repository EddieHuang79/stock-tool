<?php

namespace App\query;

use Illuminate\Support\Facades\DB;

class deleteDuplicate
{
    //  刪除重複資料

    // SELECT `id`, `stock_data_id`, COUNT(*) FROM `technical_analysis` GROUP BY `stock_data_id` HAVING COUNT(*) > 1;

    public function deleteTechQuery()
    {
        $data = DB::table('technical_analysis')
            ->select(
                'stock_data_id',
                DB::raw('COUNT(*)')
            )
            ->groupBy('stock_data_id')
            ->havingRaw('COUNT(*) > ?', [1])
            ->get();

        $data->pluck('stock_data_id')->map(function ($stock_data_id) {
            $data = DB::table('technical_analysis')
                ->where('step', 0)
                ->where('stock_data_id', $stock_data_id)
                ->get();
            DB::table('technical_analysis')->where('id', $data[0]->id)->delete();
        });

        return true;
    }

    // SELECT `id`, `stock_id`, COUNT(*) FROM `stock_data` GROUP BY `stock_id` HAVING COUNT(*) > 1;

    public function deleteStockDataQuery()
    {
        $data = DB::table('stock_data')
            ->select(
                'stock_id',
                DB::raw('COUNT(*)')
            )
            ->groupBy('stock_id')
            ->havingRaw('COUNT(*) > ?', [1])
            ->get();

        $data->pluck('stock_id')->map(function ($stock_id) {
            $data = DB::table('stock_data')
                ->where('stock_id', $stock_id)
                ->get();
            DB::table('stock_data')->where('id', $data[0]->id)->delete();
        });

        return true;
    }

    //   SELECT CONCAT(`stock_id`,`data_date`), COUNT(*) FROM `stock_data` GROUP BY CONCAT(`stock_id`,`data_date`) HAVING COUNT(*) > 1;
    //   SELECT CONCAT(`stock_id`,`data_date`), COUNT(*) FROM `technical_analysis` GROUP BY CONCAT(`stock_id`,`data_date`) HAVING COUNT(*) > 1;

    // 回傳自己

    public static function getInstance()
    {
        return new self();
    }
}

<?php

namespace App\model;

use Illuminate\Support\Facades\DB;

class SellBuyPercent
{
    private $stock_info_table = 'stock_info';
    private $stock_data_table = 'stock_data';
    private $table = 'sell_buy_percent';

    public function add_sell_buy_percent_data($data)
    {
        $result = DB::table($this->table)->insert($data);

        return $result;
    }

    public function edit_sell_buy_percent_data($data, $id)
    {
        $result = DB::table($this->table)->where('id', $id)->update($data);

        return $result;
    }

    public function get_data_assign_range(array $stock_id, string $start, string $end)
    {
        $result = DB::table($this->table)
                    ->whereIn($this->table.'.stock_id', $stock_id)
                    ->whereBetween($this->table.'.data_date', [$start, $end])
                    ->get();

        return $result;
    }

    public function get_first_data_time()
    {
        $result = DB::table($this->stock_data_table)
                    ->select(
                        $this->stock_data_table.'.stock_id',
                        DB::raw('min(data_date) as data_date')
                    )
                    ->groupBy($this->stock_data_table.'.stock_id')
                    ->get();

        return $result;
    }

    public function get_last_update_time()
    {
        $result = DB::table($this->stock_data_table)
                    ->select(
                        $this->stock_data_table.'.stock_id',
                        DB::raw('max(data_date) as data_date')
                    )
                    ->groupBy($this->stock_data_table.'.stock_id')
                    ->get();

        return $result;
    }

    public function get_count_done_stock_id(string $last_working_date)
    {
        $result = DB::table($this->table)->select('stock_id')->where('data_date', $last_working_date)->get();

        return $result;
    }

    public function get_data($code)
    {
        $result = DB::table($this->table)->where('code', $code)->get();

        return $result;
    }

    public function get_data_by_year(int $year, array $stock_id)
    {
        $table = $this->table.'_'.$year;

        $result = DB::table($table)->whereIn('stock_id', $stock_id)->get();

        return $result;
    }

    public function get_data_by_range($start, $end)
    {
        $result = DB::table($this->table)->whereBetween('data_date', [$start, $end])->get();

        return $result;
    }

    public function get_today_result($stock_id, $date)
    {
        $result = DB::table($this->table)
            ->select(
                'code',
                'result'
            )
            ->whereIn('stock_id', $stock_id)
            ->where('data_date', $date)
            ->get();

        return $result;
    }

    public function get_today_exist_stock_data(string $today)
    {
        $result = DB::table($this->stock_data_table)
                    ->leftJoin($this->stock_info_table, $this->stock_info_table.'.id', '=', $this->stock_data_table.'.stock_id')
                    ->select(
                        $this->stock_info_table.'.code',
                        $this->stock_data_table.'.stock_id as id'
                    )
                    ->where($this->stock_data_table.'.data_date', $today)
                    ->orderBy($this->stock_data_table.'.data_date')
                    ->get();

        return $result;
    }

    public static function getInstance()
    {
        return new self();
    }
}

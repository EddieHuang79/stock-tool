<?php

namespace App\model;

use Illuminate\Support\Facades\DB;

class Profit
{
    protected $quarter_table = 'profit_';
    protected $year_table = 'profit_year_';

    public function get_list(int $year)
    {
        $result = DB::table($this->year_table.$year)->select('stock_id', 'gross_profit_percent', 'eps')->get();

        return $result;
    }

    public static function getInstance()
    {
        return new self();
    }
}

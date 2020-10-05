<?php

namespace App\logic;

use App\model\Profit;
use App\Traits\Mathlib;
use App\Traits\SchemaFunc;

class Profit_logic
{
    use SchemaFunc;
    use Mathlib;

    public function get_list(int $year)
    {
        return Profit::getInstance()->get_list($year)->mapWithKeys(function ($item) {
            return [
                $item->stock_id => [
                    'gross_profit_percent' => $item->gross_profit_percent,
                    'eps' => $item->eps,
                ],
            ];
        });
    }

    public static function getInstance()
    {
        return new self();
    }
}

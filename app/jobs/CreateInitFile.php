<?php

namespace App\jobs;

use App\model\Stock;
use App\Traits\stockFileLib;

class CreateInitFile
{
    use stockFileLib;

    // 	月初自動產生所有股票的空文字檔

    public function create_init_file()
    {
        Stock::getInstance()->get_stock_list()->pluck('code')->map(function ($code) {
            $this->create_empty_file($code);
        });

        return true;
    }

    public static function getInstance()
    {
        return new self();
    }
}

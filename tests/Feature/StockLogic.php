<?php

namespace Tests\Feature;

use App\logic\Stock_logic;
use Tests\TestCase;

class StockLogic extends TestCase
{
    public function testGetList()
    {
        $test1 = Stock_logic::get_list();

        $this->assertTrue(\is_array($test1));
        $this->assertTrue(isset($test1['error']));
        $this->assertTrue(isset($test1['data']));
    }

    public function testGetStockListLogic()
    {
        $test1 = Stock_logic::get_stock_option();

        $this->assertTrue(\is_array($test1));
        $this->assertTrue(isset($test1['error']));
        $this->assertTrue(isset($test1['data']));
    }
}

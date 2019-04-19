<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

use App\logic\SellBuyPercent_logic;


class SellBuyPercentLogic extends TestCase
{

    public function testAddSellBuyPercentData()
    {

        $test1 = SellBuyPercent_logic::add_sell_buy_percent_data( 0 );

        $this->assertFalse( $test1 );

        $test1 = SellBuyPercent_logic::add_sell_buy_percent_data( '' );

        $this->assertFalse( $test1 );

        $test1 = SellBuyPercent_logic::add_sell_buy_percent_data( array() );

        $this->assertFalse( $test1 );

    }


    public function testEditSellBuyPercentData()
    {

        $test1 = SellBuyPercent_logic::edit_sell_buy_percent_data( 0, 0 );

        $this->assertFalse( $test1 );

        $test1 = SellBuyPercent_logic::edit_sell_buy_percent_data( '', '' );

        $this->assertFalse( $test1 );

        $test1 = SellBuyPercent_logic::edit_sell_buy_percent_data( array(), array() );

        $this->assertFalse( $test1 );

    }


    public function testCountSpread()
    {

        $test1 = SellBuyPercent_logic::count_spread( 0 );

        $this->assertFalse( $test1 );

        $test1 = SellBuyPercent_logic::count_spread( '' );

        $this->assertFalse( $test1 );

        $test1 = SellBuyPercent_logic::count_spread( array() );

        $this->assertFalse( $test1 );

    }

    public function testCountBuy1()
    {

        $test1 = SellBuyPercent_logic::count_buy1( 0 );

        $this->assertFalse( $test1 );

        $test1 = SellBuyPercent_logic::count_buy1( '' );

        $this->assertFalse( $test1 );

        $test1 = SellBuyPercent_logic::count_buy1( array() );

        $this->assertFalse( $test1 );

    }

    public function testCountBuy2()
    {

        $test1 = SellBuyPercent_logic::count_buy2( 0 );

        $this->assertFalse( $test1 );

        $test1 = SellBuyPercent_logic::count_buy2( '' );

        $this->assertFalse( $test1 );

        $test1 = SellBuyPercent_logic::count_buy2( array() );

        $this->assertFalse( $test1 );

    }

    public function testCountSell1()
    {

        $test1 = SellBuyPercent_logic::count_sell1( 0 );

        $this->assertFalse( $test1 );

        $test1 = SellBuyPercent_logic::count_sell1( '' );

        $this->assertFalse( $test1 );

        $test1 = SellBuyPercent_logic::count_sell1( array() );

        $this->assertFalse( $test1 );

    }

    public function testCountSell2()
    {

        $test1 = SellBuyPercent_logic::count_sell2( 0 );

        $this->assertFalse( $test1 );

        $test1 = SellBuyPercent_logic::count_sell2( '' );

        $this->assertFalse( $test1 );

        $test1 = SellBuyPercent_logic::count_sell2( array() );

        $this->assertFalse( $test1 );

    }

    public function testCountProData()
    {

        $test1 = SellBuyPercent_logic::count_pro_data( 0 );

        $this->assertFalse( $test1 );

        $test1 = SellBuyPercent_logic::count_pro_data( '' );

        $this->assertFalse( $test1 );

        $test1 = SellBuyPercent_logic::count_pro_data( array() );

        $this->assertFalse( $test1 );

    }

    public function testCount20daysDataAndResult()
    {

        $test1 = SellBuyPercent_logic::count_20days_data_and_result( 0 );

        $this->assertFalse( $test1 );

        $test1 = SellBuyPercent_logic::count_20days_data_and_result( '' );

        $this->assertFalse( $test1 );

        $test1 = SellBuyPercent_logic::count_20days_data_and_result( array() );

        $this->assertFalse( $test1 );

    }

}

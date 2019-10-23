<?php

namespace App\jobs;

use App\query\updateNoDataStock;
use App\simulation\RSIStrategySimulation;

class CrontabCenter
{

	private $date;

	//	自動取得股價

	public function update_daily_data()
	{

		$AccessCSV = AccessCSV::getInstance();

		$AccessCSV->update_daily_data( 1 );

		$AccessCSV->update_daily_data( 2 );

		$AccessCSV->update_daily_data( 3 );

		$AccessCSV->update_daily_data( 4 );

		$AccessCSV->update_daily_data( 5 );

		$AccessCSV->update_daily_data( 6 );

		$AccessCSV->update_daily_data( 7 );

		$AccessCSV->update_daily_data( 8 );

		$AccessCSV->update_daily_data( 9 );

		$AccessCSV->update_daily_data( 10 );

		$AccessCSV->update_daily_data( 11 );

		$AccessCSV->update_daily_data( 12 );

	}


	// 轉存基本股價資料

	public function auto_save_this_month_file_to_db()
	{

		SaveFromCSV::getInstance()->auto_save_this_month_file_to_db( $this->date );

		sleep(3);

		updateNoDataStock::getInstance()->update();

	}


	//	自動建立技術指標初始資料

	public function create_init_data()
	{

		SyncFromStockData::getInstance()->create_init_data();

	}


	//	KD

	public function count_KD()
	{

		CountTechnicalAnalysis::getInstance()->auto_count_technical_analysis( 1 );

	}


	//	RSI

	public function count_RSI()
	{

		CountTechnicalAnalysis::getInstance()->auto_count_technical_analysis( 2 );

	}


	//	MACD

	public function count_MACD()
	{

		CountTechnicalAnalysis::getInstance()->auto_count_technical_analysis( 3 );

	}


	//	布林

	public function count_Bollinger()
	{

		CountTechnicalAnalysis::getInstance()->auto_count_technical_analysis( 4 );

	}


	//	買賣壓力

	public function count_sellBuyPercent()
	{

		CountSellBuyPercent::getInstance()->auto_count_SellBuyPercent( $this->date );

	}


	// 	布林買進

	public function BollingerBuy()
	{

		BollingerBandsStrategyBuyingJobs::getInstance()->count( $this->date );

	}


	// 	布林賣出

	public function BollingerSell()
	{

		// BollingerBandsStrategySellingJobs::getInstance()->count();
		BollingerBandsStrategyGetAssignStock::getInstance()->count( $this->date );

	}


	//	建立空白檔案

	public function create_empty_file()
	{

		CreateInitFile::getInstance()->create_init_file();

	}


	//	更新失敗通知

	public function update_fail_notice()
	{

		getNotUpdateStock::getInstance()->process( $this->date );

	}


	//	重新取得沒拿到的

	public function update_fail_daily_data()
	{

		$AccessCSV = AccessCSV::getInstance();

		$AccessCSV->update_fail_daily_data( $this->date );

	}

    //	策略模擬

    public function simulation()
    {

        $RSIStrategySimulation = RSIStrategySimulation::getInstance();

        $RSIStrategySimulation->do();

        sleep(10);

        $RSIStrategySimulation->do();

        sleep(10);

        $RSIStrategySimulation->do();

        sleep(10);

        $RSIStrategySimulation->do();

    }

    // 一次算4種指標

	public function count_all()
	{

		CountTechnicalAnalysis::getInstance()->count_all();

	}

	public static function getInstance($days = 0)
	{
        $_this = new self;

        $_this->date = date("Y-m-d", strtotime("-". $days ." days"));

		return $_this;

	}

}

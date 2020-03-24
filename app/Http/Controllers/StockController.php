<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\logic\Stock_logic;
use App\logic\KD_logic;
use App\logic\RSI_logic;
use App\logic\MACD_logic;
use App\logic\TechnicalAnalysis_logic;
use App\logic\SellBuyPercent_logic;
use App\logic\BollingerBands_logic;
use App\logic\Strategy_logic;
use App\logic\Holiday_logic;
use Illuminate\Support\Facades\DB;
use App\jobs\CountTechnicalAnalysis;
use App\jobs\FixHistoryData;
use App\jobs\CountSellBuyPercent;
use App\jobs\AccessCSV;
use App\jobs\SaveFromCSV;
use App\jobs\SyncFromStockData;
use App\jobs\BollingerBandsStrategyGetAssignStock;
use App\jobs\BollingerBandsStrategyBuyingJobs;
use App\jobs\BollingerBandsStrategySellingJobs;
use App\query\deleteDuplicate;
use App\query\deleteNotUpdateCodeFromRedis;
use App\query\updateNoDataStock;
use App\query\countAssignSellBuyPercent;
use Ixudra\Curl\Facades\Curl;
use App\Traits\SchemaFunc;
use App\Traits\stockFileLib;
use App\jobs\CrontabCenter;
use App\jobs\getNotUpdateStock;
use App\simulation\RSIStrategySimulation;
use Illuminate\Support\Facades\Storage;
use App\model\Stock;
use App\logic\Profit_logic;
use App\simulation\JackStrategySimulation;
use App\simulation\JackStrategySimulation1;
use App\logic\Fund_logic;
use App\simulation\FindStrategySimulation;
use App\simulation\BollingerBandsBearsStrategySimulation1;

class StockController extends Controller
{

    use SchemaFunc, stockFileLib;

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {

        return response()->json( Stock_logic::getInstance()->get_list() );

    }

    /**logic
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {

        abort(404);

    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {

        abort(404);

    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {

        return response()->json( Stock_logic::getInstance()->get_stock( (int)$id ) );

    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {

        abort(404);

    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {

        abort(404);

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {

        abort(404);

    }


    // 股票清單[選項用]

    public function get_stock_option(Request $request)
    {

        return response()->json( Stock_logic::getInstance()->get_stock_option()  );

    }


    // 取得資料

    public function get_data(Request $request)
    {

        $code = $request->code;

        $start = $request->s;

        $end = $request->e;

        AccessCSV::getInstance()->get_stock_file( $code, $start, $end );

        return response( "done" , 200 )->header('Content-Type', 'text/plain');

    }


    // 		上市網址

    private function get_TWSE_listed_url( $date, $code )
    {

        return 'https://www.twse.com.tw/exchangeReport/STOCK_DAY?response=csv&date=' . $date . '&stockNo=' . $code;

    }


    // 		上櫃網址
    //		$response = Curl::to( $url )->withResponseHeaders()->returnResponseObject()->get(); 破解

    private function get_TPEx_listed_url( $date, $code )
    {

        $date = $this->year_change( $date );

        return 'https://www.tpex.org.tw/web/stock/aftertrading/daily_trading_info/st43_download.php?l=zh-tw&d=' . $date . '&stkno=' . $code . '&s=[0,asc,0]';

    }

    //  test

    public function test_entrance(Request $request)
    {

       // CrontabCenter::getInstance()->BollingerSell();
       // CrontabCenter::getInstance()->BollingerBuy();

        // AccessCSV::getInstance()->update_daily_data();

        // FindStrategySimulation::getInstance()->do([2016, 2017, 2018]);

        BollingerBandsBearsStrategySimulation1::getInstance()->do($page = 1, $limit = 10, $year = 2016);

        // CrontabCenter::getInstance()->save_fund_data_from_text();

        // FixHistoryData::getInstance()->count_tech(2017);

        return response( "done" , 200 )->header('Content-Type', 'text/plain');

    }

}


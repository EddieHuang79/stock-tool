<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\logic\Stock_logic;
use App\logic\Crontab_logic;
use App\logic\KD_logic;
use App\logic\RSV_logic;
use App\logic\RSI_logic;
use App\logic\MACD_logic;
use App\logic\TechnicalAnalysis_logic;
use App\logic\SellBuyPercent_logic;
use App\logic\Strategy_logic;
use App\logic\Line_logic;

class StockController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        
        return response()->json( Stock_logic::get_list() );    

    }

    /**
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
        
        return response()->json( Stock_logic::create_stock() );    

    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        
        return response()->json( Stock_logic::get_stock( (int)$id ) );    

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
        
        return response()->json( Stock_logic::update_stock( (int)$id ) );   

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

        return response()->json( Stock_logic::get_stock_option()  );  

    }


    // 取得資料

    public function get_data(Request $request)
    {

        $code = $request->code;

        $start = $request->s;

        $end = $request->e;

        Crontab_logic::get_stock_file( $code, $start, $end );

        return response( "done" , 200 )->header('Content-Type', 'text/plain');      

    }


    //  test

    public function test_entrance(Request $request)
    {

        // $code = $request->code;
        // $code = 1225;

        // RSV_logic::count_data( $code );
        // KD_logic::count_data( $code );
        // RSI_logic::count_data( $code );
        // MACD_logic::count_data( $code );
        // TechnicalAnalysis_logic::get_cross_sign( $code, $type = 1, $start = '2019-03-01', $end = '2019-04-04' );
        // Crontab_logic::auto_get_data( 1 );
        // Crontab_logic::auto_save_file_to_db();
        // Crontab_logic::auto_count_technical_analysis( 6 );
        // Strategy_logic::strategy1();
        // Crontab_logic::daily_info( 1 );
        // Crontab_logic::update_daily_data();
        // Stock_logic::get_all_stock_update_date();
        // Line_logic::receive_message( $data = '' );
        // SellBuyPercent_logic::count_data_logic( $code = 2633 );
        Crontab_logic::auto_save_this_month_file_to_db();


        return response( "done" , 200 )->header('Content-Type', 'text/plain');      

    }

}

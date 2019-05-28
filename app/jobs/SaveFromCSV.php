<?php

namespace App\jobs;

use App\Traits\stockFileLib;
use Illuminate\Support\Facades\Storage;
use App\logic\Record_logic;
use App\logic\Stock_logic;

class SaveFromCSV
{

    use stockFileLib;

    private $files = [];

    private $stock_data = [];

    private $data = [];

    private $stock_info = [];

    private $insert_data = [];

    private $sub_dir = [];

    private $parents_dir = 'stock';

    //  取出檔名，比對股票代號，已存在資料庫的資料排除掉之後，依據股票代號分類回傳資料

    private function process()
    {

        $this->data = collect( $this->files )->filter(function( $item ){
            $tmp = explode("/", $item);
            $code = isset($tmp[2]) ? intval($tmp[2]) : '';
            $date = isset($tmp[3]) ? str_replace(".csv", "", $tmp[3]) : '';
            $stock_data[$code] = isset($this->stock_data[$code]) ? $this->stock_data[$code] : [] ;
            $date = date("Ym", strtotime($date));
            return !in_array($date, $this->stock_data[$code]) && $code > 0 && Storage::size($item) > 0 ;
        })->mapToGroups(function ( $item ) {
            $tmp = explode("/", $item);
            $code = isset($tmp[2]) ? intval($tmp[2]) : '';
            return [$code => $item];
        });

    }


    //  格式化

    private function format()
    {

        $this->insert_data = $this->data->map(function ( $item, $code ){
            $item = collect( $item )->filter( function( $fileName ) {
               return Storage::size($fileName) < 1;
            })->map(function ( $fileName ) use ($code) {
                $stock_info = $this->stock_info->toArray();
                $file_data = $this->stock_data_to_array( $fileName );
                $stock_data = isset($stock_info[$code]) ? $stock_info[$code] : [];
                return collect( $file_data )->map(function( $item ) use( $stock_data ) {
                    return [
                        "stock_id" 				=> $stock_data->id,
                        "data_date" 			=> date("Y-m-d", strtotime($item["date"])),
                        "volume" 				=> (int)$stock_data->type === 1 ? $item["volume"] / 1000 : $item["volume"],
                        "open" 					=> $item["open"],
                        "highest" 				=> $item["highest"],
                        "lowest" 				=> $item["lowest"],
                        "close" 				=> $item["close"],
                        "created_at" 			=> date("Y-m-d H:i:s"),
                        "updated_at" 			=> date("Y-m-d H:i:s"),
                    ];
                })->toArray();
            });
            return $item;
        });

    }


    //  寫入資料

    private function add_stock_data()
    {

        Stock_logic::getInstance()->add_stock_data( $this->insert_data );

    }


    // 		轉存基本股價資料
    /*

            無法取得當月資料

    */

    public function auto_save_file_to_db( $type = 1 )
    {

        Record_logic::getInstance()->write_operate_log( $action = 'auto_save_file_to_db', $content = 'in process' );

        //  取得已轉入資料庫內的資料

        $this->stock_data = Stock_logic::getInstance()->get_all_stock_data( 1, $sub_type = $type );

        //  取得檔案

        $this->files = $this->get_dir_files( 'st' . $type . '000' );

        //  資料處理

        $this->process();

        //  股票基本資料

        $this->stock_info = Stock_logic::getInstance()->get_all_stock_info();

        //  格式化

        $this->format();

        //  寫入資料

        $this->add_stock_data();

        return true;

    }

    //  for this month

    private function this_month_process()
    {

        $this->data = $this->stock_info->keys()->filter(function ($code) {
            $sub = floor($code / 1000) * 1000;
            $sub = $sub > 9999 ? 9000 : $sub ;
            $file_path = $this->parents_dir . '/st' . $sub . '/' . $code . '/' . date("Ym01") . '.csv';
            return file_exists(  storage_path( 'app/' . $file_path ) ) === true;
        })->mapWithKeys(function( $code ){
            $sub = floor($code / 1000) * 1000;
            $sub = $sub > 9999 ? 9000 : $sub ;
            $file_path = $this->parents_dir . '/st' . $sub . '/' . $code . '/' . date("Ym01") . '.csv';
            return [ $code => $file_path ];
        })->filter( function ( $fileName ) {
            return Storage::size( $fileName ) > 0;
        } );

    }

    //      格式化

    private function this_month_format()
    {

        $this->insert_data = $this->data->map( function ( $fileName, $code ) {
            $stock_info = $this->stock_info->toArray();
            $file_data = $this->stock_data_to_array( $fileName );
            $stock_info_array = isset($stock_info[$code]) ? $stock_info[$code] : [];
            $stock_data_array = isset($this->stock_data[$code]) ? $this->stock_data[$code] : [];
            return collect( $file_data )->filter(function( $item ) use($stock_data_array) {
                return !in_array( date("Ymd", strtotime($item["date"])), $stock_data_array )  ;
            })->map(function( $item ) use($stock_info_array) {
                return [
                    "stock_id" 				=> $stock_info_array->id,
                    "data_date" 			=> date("Y-m-d", strtotime($item["date"])),
                    "volume" 				=> (int)$stock_info_array->type === 1 ? $item["volume"] / 1000 : $item["volume"],
                    "open" 					=> $item["open"],
                    "highest" 				=> $item["highest"],
                    "lowest" 				=> $item["lowest"],
                    "close" 				=> $item["close"],
                    "created_at" 			=> date("Y-m-d H:i:s"),
                    "updated_at" 			=> date("Y-m-d H:i:s"),
                ];
            })->values()->toArray();
        } )->filter(function ($item){
            return !empty($item);
        })->reduce(function ($carry, $item) {
            return array_merge($carry, $item);
        }, array());

    }


    // 		轉存當月基本股價資料

    public function auto_save_this_month_file_to_db()
    {

        Record_logic::getInstance()->write_operate_log( $action = 'auto_save_this_month_file_to_db', $content = 'in process' );

        //  取得已轉入資料庫內的資料

        $this->stock_data = Stock_logic::getInstance()->get_all_stock_data( $type = 2 );

        //  取得所有股票當月的檔案

        $this->sub_dir = $this->get_sub_dir();

        //  股票基本資料

        $this->stock_info = Stock_logic::getInstance()->get_all_stock_info();

        //  process

        $this->this_month_process();

        //  format

        $this->this_month_format();

        //  寫入資料

        $this->add_stock_data();

        return true;

    }

    public static function getInstance()
    {

        return new self;

    }

}

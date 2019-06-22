<?php

namespace App\query;

use App\logic\Redis_tool;
use App\logic\Stock_logic;
use App\Traits\stockFileLib;
use Illuminate\Support\Facades\DB;


class deleteNotUpdateCodeFromRedis
{

    use stockFileLib;

    //  刪除重複資料

    public function delete()
    {

        $Redis = Redis_tool::getInstance();

        //  取得已更新清單

        $update_list = $Redis->getUpdateDaily( date("Ymd") );

        //  檢查文字檔，沒有更新到今天的程式碼拋出來

        $sub_dir = $this->get_sub_dir();

        $not_update_data = collect($sub_dir)->map(function ($dir){
            $files = $this->get_dir_files( $dir );
            $data = collect($files)->filter(function ($fileName) use($dir){
                $tmp = explode("/", $fileName);
                return $fileName === "stock/" . $dir . "/" . $tmp[2] . "/" . date("Ym01") . ".csv";
            })->mapWithKeys(function ($fileName){
                $tmp = explode("/", $fileName);
                $data = $this->stock_data_to_array($fileName);
                return [$tmp[2] => $data];
            })->filter(function($data, $code){
                $item = collect($data)->pluck("date")->toArray();
                return !in_array(date("Y-m-d"), $item);
            })->map(function ($item, $code){
                return $code;
            })->toArray();
           return $data;
        })->reduce(function ($item, $array){
            return array_merge($item, $array);
        }, []);

        dd($not_update_data);

        $update_list = collect($update_list)->map(function ($item){
            return intval($item);
        })->filter(function ($code) use($not_update_data) {
            return !in_array($code, $not_update_data);
        })->map(function ($code) use ($Redis){
            $Redis->setUpdateDaily( date("Ymd") . '_new', (int)$code );
        });

        dd(1);

        return true;

    }

    // 回傳自己

    public static function getInstance()
    {

        return new self ;

    }

}

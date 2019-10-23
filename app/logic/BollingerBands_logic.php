<?php

namespace App\logic;

use App\Traits\SchemaFunc;
use App\Traits\Mathlib;

/*
 * http://ks1224.pixnet.net/blog/post/367701051-%E4%BB%80%E9%BA%BC%E6%98%AF%E5%B8%83%E6%9E%97%E6%A5%B5%E9%99%90-%EF%BC%88%25-bollinger-bands%EF%BC%8C%25bb%EF%BC%89
 * http://note-barsine.blogspot.com/2017/06/blog-post.html
 * */

class BollingerBands_logic
{

    use SchemaFunc, Mathlib;

    private $n = 20;

    private $data = [];

    private $id_date_mapping = [];

    private $Tech = [];

    private $Tech_data = [];

    private $lazy_start = '';

    //  布林核心

    public function return_data( $stock_id, $id_date_mapping, $Tech, $Tech_data, $start_count_day, $end_count_day )
    {

        $result = false;

        if ( !empty($stock_id) )
        {

            $this->id_date_mapping = $id_date_mapping;

            $this->Tech = $Tech;

            $this->Tech_data = $Tech_data->mapWithKeys(function ($item){
                return [$item->data_date => [
                    "step"              =>  $item->step,
                    "MA20"              =>  $item->MA20,
                    "upperBand"         =>  $item->upperBand,
                    "lowerBand"         =>  $item->lowerBand,
                    "PercentB"          =>  $item->percentB,
                    "Bandwidth"         =>  $item->bandwidth,
                ]];
            })->toArray();

            $this->lazy_start = Holiday_logic::getInstance()->get_work_date( 100, date("Y-m-d"), $type = 1 );

            // 基本五檔

            $this->data = Stock_logic::getInstance()->get_stock_data( $stock_id, $start_count_day, $end_count_day );

            //  MA20

            $this->MA20();

            //  標準差

            $this->standardDeviation();

            //  上軌

            $this->upperBand();

            //  下軌

            $this->lowerBand();

            //  Percent B

            $this->PercentB();

            //  Bandwidth

            $this->Bandwidth();

            //  格式化

            return $this->format_return();

        }

        return $result;

    }

    //  計算MA20
    /*
        有人用最高+最低+收盤/3來算，有人用純20日線，App用純20日線
    */

    private function MA20()
    {

        $this->data = $this->data->map(function ($item, $key) {
            try {

                if ( $key < $this->n - 1 )
                {
                    throw new \Exception(0.0);
                }

                if ( isset($this->Tech_data[$item->data_date]) && $this->Tech_data[$item->data_date]["step"] === 4 )
                {
                    throw new \Exception($this->Tech_data[$item->data_date]["MA20"]);
                }

                if ( strtotime($item->data_date) < strtotime($this->lazy_start) )
                {
                    throw new \Exception(0.0);
                }

                $sub_data = array_slice( $this->data->pluck("close")->values()->toArray(), $key - ($this->n - 1), $this->n );
                $item->MA20 = $this->except( array_sum($sub_data) , $this->n );
                $item->MA20 = round($item->MA20, 2);

            } catch (\Exception $e) {

                $value = $e->getMessage();

                $item->MA20 = $value;

            }
            return $item;
        });

        return true;

    }

    //  計算標準差

    private function standardDeviation()
    {

        $this->data = $this->data->map(function ($item, $key) {
            try {

                if ( $key < $this->n - 1 )
                {
                    throw new \Exception(0.0);
                }

                if ( strtotime($item->data_date) < strtotime($this->lazy_start) )
                {
                    throw new \Exception(0.0);
                }

                $sub_data = array_slice( $this->data->pluck("close")->values()->toArray(), $key - ($this->n - 1), $this->n );
                $avg = $this->except( array_sum($sub_data) , $this->n );
                $process = collect( $sub_data )->map(function ( $item ) use ($avg) {
                    return pow( $item - $avg, 2 );
                })->values()->toArray();
                $sum = array_sum($process);
                $item->standardDeviation = sqrt( $this->except($sum, $this->n) );

            } catch (\Exception $e) {

                $value = $e->getMessage();

                $item->standardDeviation = $value;

            }
            return $item;
        });

        return true;


    }

    //  計算上軌

    private function upperBand()
    {

        $this->data = $this->data->map(function ( $item, $key ) {
            try {

                if ( $key < $this->n - 1 )
                {
                    throw new \Exception(0.0);
                }

                if ( isset($this->Tech_data[$item->data_date]) && $this->Tech_data[$item->data_date]["step"] === 4 )
                {
                    throw new \Exception($this->Tech_data[$item->data_date]["upperBand"]);
                }

                if ( strtotime($item->data_date) < strtotime($this->lazy_start) )
                {
                    throw new \Exception(0.0);
                }

                $item->upperBand = round( $item->MA20 + $item->standardDeviation * 2, 2 );

            } catch (\Exception $e) {

                $value = $e->getMessage();

                $item->upperBand = $value;

            }
            return $item;
        });

        return true;

    }

    //  計算下軌

    private function lowerBand()
    {

        $this->data = $this->data->map(function ( $item, $key ) {
            try {

                if ( $key < $this->n - 1 )
                {
                    throw new \Exception(0.0);
                }

                if ( isset($this->Tech_data[$item->data_date]) && $this->Tech_data[$item->data_date]["step"] === 4 )
                {
                    throw new \Exception($this->Tech_data[$item->data_date]["lowerBand"]);
                }

                if ( strtotime($item->data_date) < strtotime($this->lazy_start) )
                {
                    throw new \Exception(0.0);
                }

                $item->lowerBand = round( $item->MA20 - $item->standardDeviation * 2, 2 );

            } catch (\Exception $e) {

                $value = $e->getMessage();

                $item->lowerBand = $value;

            }
            return $item;
        });

        return true;

    }

    //  計算PercentB

    private function PercentB()
    {

        $this->data = $this->data->map(function ( $item, $key ) {
            try {

                if ( $key < $this->n - 1 )
                {
                    throw new \Exception(0.0);
                }

                if ( isset($this->Tech_data[$item->data_date]) && $this->Tech_data[$item->data_date]["step"] === 4 )
                {
                    throw new \Exception($this->Tech_data[$item->data_date]["PercentB"]);
                }

                if ( strtotime($item->data_date) < strtotime($this->lazy_start) )
                {
                    throw new \Exception(0.0);
                }

                $PercentB = $this->except( $item->close - $item->lowerBand, $item->upperBand - $item->lowerBand );
                $item->PercentB = round( $PercentB, 2 );

            } catch (\Exception $e) {

                $value = $e->getMessage();

                $item->PercentB = $value;

            }
            return $item;
        });

        return true;

    }

    //  計算Bandwidth

    private function Bandwidth()
    {

        $this->data = $this->data->map(function ( $item, $key ) {
            try {

                if ( $key < $this->n - 1 )
                {
                    throw new \Exception(0.0);
                }

                if ( isset($this->Tech_data[$item->data_date]) && $this->Tech_data[$item->data_date]["step"] === 4 )
                {
                    throw new \Exception($this->Tech_data[$item->data_date]["Bandwidth"]);
                }

                if ( strtotime($item->data_date) < strtotime($this->lazy_start) )
                {
                    throw new \Exception(0.0);
                }

                $Bandwidth = $this->except( $item->upperBand - $item->lowerBand, $item->MA20 );
                $item->Bandwidth = round( $Bandwidth, 2 );

            } catch (\Exception $e) {

                $value = $e->getMessage();

                $item->Bandwidth = $value;

            }
            return $item;
        });

        return true;

    }

    //  格式化

    private function format()
    {

        $this->data = $this->data->map(function ( $item ) {
            $result = [
                "MA20"          =>  $item->MA20,
                "upperBand"     =>  $item->upperBand,
                "lowerBand"     =>  $item->lowerBand,
                "PercentB"      =>  $item->PercentB,
                "Bandwidth"     =>  $item->Bandwidth,
                "step"          =>  4,
                "updated_at"    =>  date("Y-m-d H:i:s")
            ];
            return [ "date" => $item->data_date, "data" => $result ];
        });

        $this->data = $this->data->filter(function ($item) {
            return $this->Tech_data[$item["date"]]["step"] === 3;
        });

        return true;

    }

    //  更新

    private function update()
    {

        $data = $this->data->toArray();

        $id_date_mapping = $this->id_date_mapping;

        $Tech = $this->Tech;

        foreach ($data as $row)
        {

            if ( isset($id_date_mapping[$row["date"]]) )
            {

                $Tech->update_data( $row["data"], $id_date_mapping[$row["date"]] );

            }

        }

        return true;

    }

    //  回傳資料

    private function format_return()
    {

        $data = $this->data->map(function ( $item ) {
            $result = [
                "MA20"          =>  $item->MA20,
                "upperBand"     =>  $item->upperBand,
                "lowerBand"     =>  $item->lowerBand,
                "PercentB"      =>  $item->PercentB,
                "Bandwidth"     =>  $item->Bandwidth,
            ];
            return [ "date" => $item->data_date, "data" => $result ];
        })->filter(function ($item) {
            return $this->Tech_data[$item["date"]]["step"] === 0;
        })->values()->toArray();

        return $data ?? [];

    }

    public static function getInstance()
    {

        return new self;

    }

}

<?php

namespace App\logic;

use App\Traits\SchemaFunc;
use App\Traits\Mathlib;

/*

	@	http://nengfang.blogspot.com/2014/09/macd-excel.html

	#	公式

	DI = (最高價 + 最低價 + 2 × 收盤價) ÷ 4

	首日EMA12 = 12天內DI 總和 ÷ 12
	首日EMA26 = 26天內DI 總和 ÷ 26

	EMA12 = [前一日EMA12 × (12 - 1) + 今日DI × 2] ÷ (12+1)
	EMA26 = [前一日EMA26 × (26 - 1) + 今日DI × 2] ÷ (26+1)

	DIF = 12日EMA - 26日EMA

	首日MACD = 9天內DIF總和 ÷ 9
	MACD = (前一日MACD × 8/10 + 今日DIF × 2/10

	OSC = DIF - MACD

*/

class MACD_logic
{

	use SchemaFunc, Mathlib;

	private $n1 = 12;

    private $n2 = 26;

    private $n3 = 9;

    private $data = [];

    private $id_date_mapping = [];

    private $Tech = [];

    private $Tech_data = [];

    private $lazy_start = '';

    // 		計算資料

    /*
     *  部分區段速度緩慢...
     *  30筆 53 sec
     *  add lazy start 30筆 14 sec
     */

	public function count_data( $stock_id, $id_date_mapping, $Tech, $Tech_data )
	{

		$result = false;

		if ( !empty($stock_id) )
		{

            $this->id_date_mapping = $id_date_mapping;

            $this->Tech = $Tech;

            $this->Tech_data = $Tech_data->mapWithKeys(function ($item){
                return [$item->data_date => [
                    "step"              =>  $item->step,
                    "DIFF"              =>  $item->DIFF,
                    "MACD"              =>  $item->MACD,
                    "OSC"               =>  $item->OSC,
                ]];
            })->toArray();

            $this->lazy_start = Holiday_logic::getInstance()->get_work_date( 150, date("Y-m-d"), $type = 1 );

            // 基本五檔

			$this->data = Stock_logic::getInstance()->get_stock_data( $stock_id );

			//  DI

            $this->getDI();

            //  EMA12

            $this->getEMA12();

            //  EMA26

            $this->getEMA26();

            //  DIFF

            $this->getDIFF();

            //  MACD

            $this->getMACD();

            //  OSC

            $this->getOSC();

            //  格式化

            $this->format();

            //  更新

            $this->update();

		}

		return $result;

	}

    // 	DI = (最高價 + 最低價 + 2 × 收盤價) ÷ 4

    private function getDI()
    {

        $this->data = $this->data->map(function ( $item ) {
            $item->DI = $this->except( floatval($item->highest) + floatval($item->lowest) + floatval($item->close) * 2, 4 );
            return $item;
        });

        return true;

    }


    //  首日EMA12 = 12天內DI 總和 ÷ 12
    //  EMA12 = 前一日EMA12 × (11/13) + 今日DI × 2/13

    private function getEMA12()
    {

        $this->data = $this->data->map(function ($item, $key) {
            try {

                if ( $key < $this->n1 - 1 )
                {
                    throw new \Exception(0.0);
                }

                if ( strtotime($item->data_date) < strtotime($this->lazy_start) )
                {
                    throw new \Exception(0.0);
                }

                $sub_data = array_slice( $this->data->pluck("DI")->values()->toArray(), $key - ($this->n1 - 1), $this->n1 );
                $item->EMA12 = $key === $this->n1 - 1 ?
                    $this->except( array_sum( $sub_data ), $this->n1 ) :
                    $this->data[$key - 1]->EMA12 * $this->except( $this->n1 - 1, $this->n1 + 1 ) + $item->DI * $this->except( 2, $this->n1 + 1 ) ;

            } catch (\Exception $e) {

                $value = $e->getMessage();

                $item->EMA12 = $value;

            }
            return $item;
        });

        return true;

    }

    // 首日EMA26 = 26天內DI 總和 ÷ 26
    // EMA26 = 前一日EMA26 × (25/27) + 今日DI × 2/27

    private function getEMA26()
    {

        $this->data = $this->data->map(function ($item, $key) {
            try {

                if ( $key < $this->n2 - 1 )
                {
                    throw new \Exception(0.0);
                }

                if ( strtotime($item->data_date) < strtotime($this->lazy_start) )
                {
                    throw new \Exception(0.0);
                }

                $sub_data = array_slice( $this->data->pluck("DI")->values()->toArray(), $key - ($this->n2 - 1), $this->n2 );
                $item->EMA26 = $key === $this->n2 - 1 ?
                    $this->except( array_sum( $sub_data ), $this->n2 ) :
                    $this->data[$key - 1]->EMA26 * $this->except( $this->n2 - 1, $this->n2 + 1 ) + $item->DI * $this->except( 2, $this->n2 + 1 ) ;

            } catch (\Exception $e) {

                $value = $e->getMessage();

                $item->EMA26 = $value;

            }
            return $item;
        });

        return true;

    }

    // DIFF = 12日EMA - 26日EMA

    private function getDIFF()
    {

        $this->data = $this->data->map(function ($item, $key) {
            try {

                if ( $key < $this->n2 - 1 )
                {
                    throw new \Exception(0.0);
                }

                if ( isset($this->Tech_data[$item->data_date]) && $this->Tech_data[$item->data_date]["step"] === 3 )
                {
                    throw new \Exception($this->Tech_data[$item->data_date]["DIFF"]);
                }

                if ( strtotime($item->data_date) < strtotime($this->lazy_start) )
                {
                    throw new \Exception(0.0);
                }

                $item->DIFF = round($item->EMA12 - $item->EMA26, 2) ;

            } catch (\Exception $e) {

                $value = $e->getMessage();

                $item->DIFF = $value;

            }
            return $item;
        });

        return true;

    }

    // MACD = 前一日MACD × 8/10 + 今日DIF × 2/10

    private function getMACD()
    {

        $this->data = $this->data->map(function ($item, $key) {
            try {

                if ( $key < $this->n2 + $this->n3 - 1 )
                {
                    throw new \Exception(0.0);
                }

                if ( isset($this->Tech_data[$item->data_date]) && $this->Tech_data[$item->data_date]["step"] === 3 )
                {
                    throw new \Exception($this->Tech_data[$item->data_date]["MACD"]);
                }

                if ( strtotime($item->data_date) < strtotime($this->lazy_start) )
                {
                    throw new \Exception(0.0);
                }

                $sub_data = array_slice( $this->data->pluck("DIFF")->values()->toArray(), $key - ($this->n3 - 1), $this->n3 );
                $item->MACD = $key === $this->n2 + $this->n3 - 1 ?
                    $this->except( array_sum( $sub_data ), $this->n3 ) :
                    $this->data[$key - 1]->MACD * 0.8 + $item->DIFF * 0.2 ;
                $item->MACD = round( $item->MACD, 2 );

            } catch (\Exception $e) {

                $value = $e->getMessage();

                $item->MACD = $value;

            }
            return $item;
        });

        return true;

    }

    // OSC = DIFF - MACD

    private function getOSC()
    {

        $this->data = $this->data->map(function ($item) {
            try {

                if ( empty($item->DIFF) || empty($item->MACD) )
                {
                    throw new \Exception(0.0);
                }

                if ( isset($this->Tech_data[$item->data_date]) && $this->Tech_data[$item->data_date]["step"] === 3 )
                {
                    throw new \Exception($this->Tech_data[$item->data_date]["OSC"]);
                }

                if ( strtotime($item->data_date) < strtotime($this->lazy_start) )
                {
                    throw new \Exception(0.0);
                }

                $item->OSC = $item->DIFF - $item->MACD ;
                $item->OSC = round( $item->OSC, 2 );

            } catch (\Exception $e) {

                $value = $e->getMessage();

                $item->OSC = $value;

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
                "DIFF"              =>  $item->DIFF,
                "MACD"              =>  $item->MACD,
                "OSC"               =>  $item->OSC,
                "step"              =>  3,
                "updated_at"        =>  date("Y-m-d H:i:s")
            ];
            return [ "date" => $item->data_date, "data" => $result ];
        });

        $this->data = $this->data->filter(function ($item) {
            return $this->Tech_data[$item["date"]]["step"] === 2;
        }) ;

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

    public static function getInstance()
    {

        return new self;

    }

}

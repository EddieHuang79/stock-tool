<?php

namespace App\logic;

use App\Traits\SchemaFunc;
use App\Traits\Mathlib;

/*

	@	https://www.ezchart.com.tw/inds.php?IND=RSI
	@	https://www.moneydj.com/KMDJ/Wiki/wikiViewer.aspx?keyid=1342fb37-760e-48b0-9f27-65674f6344c9

	#	說明

		RSI 目前已為市場普遍使用，是主要技術指標之一，其主要特點是計算某一段時間內買賣雙方力量，
		作為超買、超賣的參考與Ｋ線圖及其他技術指標（三至五種）一起使用，以免過早賣及買進，造成少賺多賠的損失。

	#	計算公式

		      過去n日內上漲點數總和
		UP＝────────────────────────
		                n
		      過去n日內下跌點數收總和
		DN＝────────────────────────
		                n
		         UP
		RS  ＝  ────
		         DN
		       　　　　　  100
		n日RSI＝100 －　────────
		                 1+RS
    #   威爾德

    UP t = UP t-1 + 1 / N ( Ut – UP t-1)

    其中 N 為平滑平均天數， t 為當日值， t-1為前一日值

	#	使用方法

	1.	以6日RSI值為例，80以上為超買，90以上或M頭為賣點；20以下為超賣，10以下或W底為買點。
	2.	在股價創新高點，同時RSI也創新高點時，表示後市仍強，若未創新高點為賣出訊號。
	3.	在股價創新低點，RSI也創新低點，則後市仍弱，若RSI未創新低點，則為買進訊號。
	4.	當6日RSI由下穿過12日RSI而上時，可視為買點；反之當6日RSI由上貫破12日RSI而下時，可視為賣點。
	5.	當出現類似這樣的訊號：3日RSI>5日RSI>10日RSI>20日RSI....，顯示市場是處於多頭行情；反之則為空頭行情。
	6.	盤整期中，一底比一底高，為多頭勢強，後勢可能再漲一段，是買進時機，反之一底比一底低是賣出時機。

	#	實作目標

	用5日RSI、10日RSI來計算


	#	簡化公式

	RSI= UP / ( DN + UP ) * 100

*/


class RSI_logic
{

	use SchemaFunc, Mathlib;

    private $n1 = 5;

    private $n2 = 10;

    private $data = [];

    private $id_date_mapping = [];

    private $Tech = [];

    private $Tech_data = [];

    private $lazy_start = '';


    // 		計算資料

	public function count_data( $stock_id, $id_date_mapping, $Tech, $Tech_data )
	{

		$result = false;

		if ( !empty($stock_id) )
		{

            $this->id_date_mapping = $id_date_mapping;

            $this->Tech = $Tech;

            $this->Tech_data = $Tech_data->mapWithKeys(function ($item){
                return [$item->data_date => [
                    "step"          => $item->step,
                    "RSI5"          => $item->RSI5,
                    "RSI10"         => $item->RSI10,
                ]];
            })->toArray();

            $this->lazy_start = Holiday_logic::getInstance()->get_work_date( 100, date("Y-m-d"), $type = 1 );

		    //  取得5檔

			$this->data = Stock_logic::getInstance()->get_stock_data( $stock_id );

            // 上漲點數(與前日比)

            $this->get_rise_num_value();

            // 下跌點數(與前日比)

            $this->get_fall_num_value();

            // 5日內上漲總和平滑平均值 威爾德平滑法

            $this->getWildersValue_5days_rise();

            // 5日內下跌總和平滑平均值 威爾德平滑法

            $this->getWildersValue_5days_fall();

            // 計算RSI5

            $this->getRSI5();

            // 10日內上漲總和平滑平均值 威爾德平滑法

            $this->getWildersValue_10days_rise();

            // 10日內下跌總和平滑平均值 威爾德平滑法

            $this->getWildersValue_10days_fall();

            // 計算RSI10

            $this->getRSI10();

            //  格式化

            $this->format();

            //  更新

            $this->update();

		}

		return $result;

	}


	// 	找出上漲點數，與前日相比

	private function get_rise_num_value()
	{

        $this->data = $this->data->map(function ($item, $key) {
            try {

                if ( $key < 1 )
                {
                    throw new \Exception(0.0);
                }

                if ( strtotime($item->data_date) < strtotime($this->lazy_start) )
                {
                    throw new \Exception(0.0);
                }

                $item->rise_num = $item->close - $this->data[$key - 1]->close > 0 ? $item->close - $this->data[$key - 1]->close : 0;

            } catch (\Exception $e) {

                $value = $e->getMessage();

                $item->rise_num = $value;

            }
            return $item;
        });

        return true;

	}


	// 	找出下跌點數，與前日相比

    private function get_fall_num_value()
	{

        $this->data = $this->data->map(function ($item, $key) {
            try {

                if ( $key < 1 )
                {
                    throw new \Exception(0.0);
                }

                if ( strtotime($item->data_date) < strtotime($this->lazy_start) )
                {
                    throw new \Exception(0.0);
                }

                $item->fall_num = $item->close - $this->data[$key - 1]->close < 0 ? abs( $item->close - $this->data[$key - 1]->close ) : 0;

            } catch (\Exception $e) {

                $value = $e->getMessage();

                $item->fall_num = $value;

            }
            return $item;
        });

		return true;

	}


	// 	威爾德平滑法

    /*
        UP t = UP t-1 + 1 / N ( Ut – UP t-1)
     */
    private function getWildersValue_5days_rise()
	{

        $n = $this->n1;

        $this->data = $this->data->map(function ($item, $key) use ( $n ) {
            try {

                if ( $key < $n - 1 )
                {
                    throw new \Exception(0.0);
                }

                if ( strtotime($item->data_date) < strtotime($this->lazy_start) )
                {
                    throw new \Exception(0.0);
                }

                $sub_data = array_slice( $this->data->pluck("rise_num")->values()->toArray(), $key - ($n - 1), $n );

                $item->UP_5days = $key !== $n - 1 ?
                    $this->data[$key - 1]->UP_5days + $this->except( ( $this->data[$key]->rise_num - $this->data[$key - 1]->UP_5days ), $n ) :
                    $this->except( array_sum( $sub_data ), $n ) ;

            } catch (\Exception $e) {

                $value = $e->getMessage();

                $item->UP_5days = $value;

            }
            return $item;
        });

		return true;

	}

    private function getWildersValue_5days_fall()
    {

        $n = $this->n1;

        $this->data = $this->data->map(function ($item, $key) use ( $n ) {
            try {

                if ( $key < $n - 1 )
                {
                    throw new \Exception(0.0);
                }

                if ( strtotime($item->data_date) < strtotime($this->lazy_start) )
                {
                    throw new \Exception(0.0);
                }

                $sub_data = array_slice( $this->data->pluck("fall_num")->values()->toArray(), $key - ($n - 1), $n );

                $item->DN_5days = $key !== $n - 1 ?
                    $this->data[$key - 1]->DN_5days + $this->except( ( $this->data[$key]->fall_num - $this->data[$key - 1]->DN_5days ), $n ) :
                    $this->except( array_sum( $sub_data ), $n ) ;


            } catch (\Exception $e) {

                $value = $e->getMessage();

                $item->DN_5days = $value;

            }
            return $item;
        });

        return true;

    }

    // 	取得RSI5

    private function getRSI5()
    {

        $n = $this->n1;

        $this->data = $this->data->map(function ($item, $key) use ( $n ) {
            try {

                if ( $key < $n - 1 )
                {
                    throw new \Exception(0.0);
                }

                if ( isset($this->Tech_data[$item->data_date]) && $this->Tech_data[$item->data_date]["step"] === 2 )
                {
                    throw new \Exception($this->Tech_data[$item->data_date]["RSI5"]);
                }

                $item->RSI5 = $this->except( $item->UP_5days, $item->UP_5days + $item->DN_5days ) * 100;

                $item->RSI5 = round($item->RSI5, 2);

            } catch (\Exception $e) {

                $value = $e->getMessage();

                $item->RSI5 = $value;

            }
            return $item;
        });

        return true;

    }

    /*
      UP t = UP t-1 + 1 / N ( Ut – UP t-1)
   */
    private function getWildersValue_10days_rise()
    {

        $n = $this->n2;

        $this->data = $this->data->map(function ($item, $key) use ( $n ) {
            try {

                if ( $key < $n - 1 )
                {
                    throw new \Exception(0.0);
                }

                if ( strtotime($item->data_date) < strtotime($this->lazy_start) )
                {
                    throw new \Exception(0.0);
                }

                $sub_data = array_slice( $this->data->pluck("rise_num")->values()->toArray(), $key - ($n - 1), $n );
                $item->UP_10days = $key !== $n - 1 ?
                    $this->data[$key - 1]->UP_10days + $this->except( ( $this->data[$key]->rise_num - $this->data[$key - 1]->UP_10days ), $n ) :
                    $this->except( array_sum( $sub_data ), $n ) ;

            } catch (\Exception $e) {

                $value = $e->getMessage();

                $item->UP_10days = $value;

            }
            return $item;
        });

        return true;

    }

    private function getWildersValue_10days_fall()
    {

        $n = $this->n2;

        $this->data = $this->data->map(function ($item, $key) use ( $n ) {
            try {

                if ( $key < $n - 1 )
                {
                    throw new \Exception(0.0);
                }

                if ( strtotime($item->data_date) < strtotime($this->lazy_start) )
                {
                    throw new \Exception(0.0);
                }

                $sub_data = array_slice( $this->data->pluck("fall_num")->values()->toArray(), $key - ($n - 1), $n );
                $item->DN_10days = $key !== $n - 1 ?
                    $this->data[$key - 1]->DN_10days + $this->except( ( $this->data[$key]->fall_num - $this->data[$key - 1]->DN_10days ), $n ) :
                    $this->except( array_sum( $sub_data ), $n ) ;

            } catch (\Exception $e) {

                $value = $e->getMessage();

                $item->DN_10days = $value;

            }
            return $item;
        });

        return true;

    }


    // 	取得RSI10

    private function getRSI10()
    {

        $n = $this->n2;

        $this->data = $this->data->map(function ($item, $key) use ( $n ) {
            try {

                if ( $key < $n - 1 )
                {
                    throw new \Exception(0.0);
                }

                if ( isset($this->Tech_data[$item->data_date]) && $this->Tech_data[$item->data_date]["step"] === 2 )
                {
                    throw new \Exception($this->Tech_data[$item->data_date]["RSI10"]);
                }

                $item->RSI10 = $this->except( $item->UP_10days, $item->UP_10days + $item->DN_10days ) * 100;

                $item->RSI10 = round($item->RSI10, 2);

            } catch (\Exception $e) {

                $value = $e->getMessage();

                $item->RSI10 = $value;

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
                "RSI5"              =>  $item->RSI5,
                "RSI10"             =>  $item->RSI10,
                "step"              =>  2,
                "updated_at"        =>  date("Y-m-d H:i:s")
            ];
            return [ "date" => $item->data_date, "data" => $result ];
        });

        $this->data = $this->data->filter(function ($item) {
            return $this->Tech_data[$item["date"]]["step"] === 1;
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


    public static function getInstance()
    {

        return new self;

    }

}






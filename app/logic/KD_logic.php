<?php

namespace App\logic;

use App\Traits\SchemaFunc;
use App\Traits\Mathlib;

/*

	@	https://www.ezchart.com.tw/inds.php?IND=KD
	@	https://www.moneydj.com/KMDJ/wiki/wikiViewer.aspx?keyid=02e9d1fa-499f-4952-9f5b-e7cad942c97b

	#	說明	

	KD市場常使用的一套技術分析工具。其適用範圍以中短期投資的技術分析為最佳。
	隨機 指標的理論認為：當股市處於牛市(多頭)時，收盤價往往接近當日最高價； 反之在熊市(空頭)時，收盤價比較接近當日最低價，該指數的目的即在反映 出近期收盤價在該段日子中價格區間的相對位置。

	#	計算公式	

	它是由%K(快速平均值)、%D(慢速平均值)兩條線所組成，假設從n天週期計算出隨機指標時，首先須找出最近n天當中曾經出現過的最高價、最低價與第n天的收盤價，然後利用這三個數字來計算第n天的未成熟隨機值(RSV)

	         第n天收盤價-最近n天內最低價
	RSV ＝     ────────────────          ×100
	      最近n天內最高價-最近n天內最低價

	計算出RSV之後，再來計算K值與D值。
	當日K值(%K)= 2/3 前一日 K值 + 1/3 RSV
	當日D值(%D)= 2/3 前一日 D值＋ 1/3 當日K值
	若無前一日的K值與D值，可以分別用50來代入計算，經過長期的平滑的結果，起算基期雖然不同，但會趨於一致，差異很小。

	#	使用方法	

	1.	如果行情是一個明顯的漲勢，會帶動K線與D線向上升。如漲勢開始遲緩，則會反應到K值與D值，使得K值跌破D值，此時中短期跌勢確立。
	2.	當K值大於D值，顯示目前是向上漲升的趨勢，因此在圖形上K線向上突破D線時，即為買進訊號。
	3. 	當D值大於K值，顯示目前是向下跌落，因此在圖形上K 線向下跌破D線，此即為賣出訊號。
	4. 	上述K線與D線的交叉，須在80以上，20以下(一說70、30；視市場投機程度而彈性擴大範圍)，訊號才正確。
	5. 	當K值大於80，D值大於70時，表示當日收盤價處於偏高之價格區域，即為超買狀態；當K值小於20，D值小於30時，表示當日收盤價處於偏低之價格區域，即為超賣狀態。
	6. 	當D值跌至15以下時，意味市場為嚴重之超賣，其為買入訊號；當D值超過85以上時，意味市場為嚴重之超買，其為賣出訊號。
	7. 	價格創新高或新低，而KD未有此現象，此為背離現象，亦即為可能反轉的重要前兆。

	KD一般用9天算，這邊也用9天

*/


class KD_logic extends Basetool
{

	use SchemaFunc, Mathlib;

	protected $n = 9;

	public static function count_data( $code )
	{

		$_this = new self();

		$result = false;

		$default_KD = 50;

		$K_value = [];

		$D_value = [];

		if ( !empty($code) ) 
		{

			$rsv_data = RSV_logic::get_rsv_data( $code );

			$stock_data_id = $_this->pluck( $rsv_data, "stock_data_id" );

			$exist_array = [
				"K" => $_this->pluck( TechnicalAnalysis_logic::get_data( 2, $stock_data_id ), "stock_data_id" ),
				"D" => $_this->pluck( TechnicalAnalysis_logic::get_data( 3, $stock_data_id ), "stock_data_id" )
			];

			foreach ($rsv_data as $key => $rsv) 
			{

				$last_K_value = isset($K_value[$key - 1]["value"]) ? $K_value[$key - 1]["value"] : $default_KD ;
			
				$last_D_value = isset($D_value[$key - 1]["value"]) ? $D_value[$key - 1]["value"] : $default_KD ;

				// 當日K值(%K)= 2/3 前一日 K值 + 1/3 RSV

				$K_value[$key] = [
					"data_date" => $rsv["data_date"],
					"value" 	=> $_this->except( $last_K_value * 2, 3 ) + $_this->except( $rsv["value"], 3 )
				] ; 

				// 當日D值(%D)= 2/3 前一日 D值＋ 1/3 當日K值

				$D_value[$key] = [
					"data_date" => $rsv["data_date"],
					"value"		=> $_this->except( $last_D_value * 2, 3 ) + $_this->except( $K_value[$key]["value"], 3 )
				];

				// 寫入資料庫

				if ( !in_array($rsv["stock_data_id"], $exist_array["K"]) ) 
				{
					
					$option = [
						"stock_data_id" => $rsv["stock_data_id"],
						"value" 		=> round($K_value[$key]["value"], 2)
					];

					$insert_data = TechnicalAnalysis_logic::insert_format( $option, 2 );

					TechnicalAnalysis_logic::add_data( $insert_data );
					
				}

				if ( !in_array($rsv["stock_data_id"], $exist_array["D"]) ) 
				{

					$option = [
						"stock_data_id" => $rsv["stock_data_id"],
						"value" 		=> round($D_value[$key]["value"], 2)
					];

					$insert_data = TechnicalAnalysis_logic::insert_format( $option, 3 );

					TechnicalAnalysis_logic::add_data( $insert_data );

				}

			}

		}

		return $result;

	}

}







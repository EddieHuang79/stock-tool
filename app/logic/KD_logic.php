<?php

namespace App\logic;

use App\Traits\Mathlib;
use App\Traits\SchemaFunc;

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

class KD_logic
{
    use SchemaFunc;
    use Mathlib;

    protected $n = 9;

    private $data = [];

    private $Tech_data = [];

    public function return_data($Tech_data, $stock_price_data)
    {
        $this->Tech_data = $Tech_data->mapWithKeys(function ($item) {
            return [$item->data_date => [
                'step' => $item->step,
                'RSV' => $item->RSV,
                'K9' => $item->K9,
                'D9' => $item->D9,
            ]];
        })->toArray();

        $this->data = $stock_price_data;

        //  找出N天內最高價

        $this->get_highest_close_value();

        //  找出N天內最低價

        $this->get_lowest_close_value();

        //  計算RSV

        $this->get_RSV_value();

        //  取得K值

        $this->get_K_Value();

        //  取得D值

        $this->get_D_Value();

        //  格式化

        return $this->format_return();
    }

    public static function getInstance()
    {
        return new self();
    }

    // 	找出N天內最高價

    private function get_highest_close_value()
    {
        $this->data->map(function ($item, $key) {
            try {
                if ($key < $this->n - 1) {
                    throw new \Exception(0.0);
                }

                $sub_data = \array_slice($this->data->values()->toArray(), $key - ($this->n - 1), $this->n);
                $highest = collect($sub_data)->pluck('highest')->max();
                $item->highestClose = $highest;
            } catch (\Exception $e) {
                $value = $e->getMessage();

                $item->highestClose = $value;
            }

            return $item;
        });

        return true;
    }

    // 	找出N天內最低價

    private function get_lowest_close_value()
    {
        $this->data->map(function ($item, $key) {
            try {
                if ($key < $this->n - 1) {
                    throw new \Exception(0.0);
                }

                $sub_data = \array_slice($this->data->values()->toArray(), $key - ($this->n - 1), $this->n);
                $lowest = collect($sub_data)->pluck('lowest')->min();
                $item->lowestClose = $lowest;
            } catch (\Exception $e) {
                $value = $e->getMessage();

                $item->lowestClose = $value;
            }

            return $item;
        });

        return true;
    }

    // 	計算RSV

    private function get_RSV_value()
    {
        $this->data->map(function ($item, $key) {
            try {
                if ($key < $this->n - 1) {
                    throw new \Exception(0.0);
                }

                $item->RSV = $this->except($item->close - $item->lowestClose, $item->highestClose - $item->lowestClose) * 100;
                $item->RSV = round($item->RSV, 2);
            } catch (\Exception $e) {
                $value = $e->getMessage();

                $item->RSV = $value;
            }

            return $item;
        });

        return true;
    }

    //  取得K值

    private function get_K_Value()
    {
        $this->data->map(function ($item, $key) {
            try {
                if ($key < $this->n - 1) {
                    throw new \Exception(0.0);
                }

                $last_K_value = $key !== $this->n - 1 ? $this->data[$key - 1]->K9 : 50;
                $item->K9 = $this->except($last_K_value * 2, 3) + $this->except($item->RSV, 3);
                $item->K9 = round($item->K9, 2);
            } catch (\Exception $e) {
                $value = $e->getMessage();

                $item->K9 = $value;
            }

            return $item;
        });

        return true;
    }

    //  取得D值

    private function get_D_Value()
    {
        $this->data->map(function ($item, $key) {
            try {
                if ($key < $this->n - 1) {
                    throw new \Exception(0.0);
                }

                $last_D_value = $key !== $this->n - 1 ? $this->data[$key - 1]->D9 : 50;
                $item->D9 = $this->except($last_D_value * 2, 3) + $this->except($item->K9, 3);
                $item->D9 = round($item->D9, 2);
            } catch (\Exception $e) {
                $value = $e->getMessage();

                $item->D9 = $value;
            }

            return $item;
        });

        return true;
    }

    //  回傳資料

    private function format_return()
    {
        $data = $this->data->map(function ($item) {
            $result = [
                'RSV' => $item->RSV,
                'K9' => $item->K9,
                'D9' => $item->D9,
            ];

            return ['date' => $item->data_date, 'data' => $result];
        })->filter(function ($item) {
            return $this->Tech_data[$item['date']]['step'] === 0;
        })->values()->toArray();

        return $data ?? [];
    }
}

<?php

namespace App\jobs;

use App\logic\Notice_logic;
use App\logic\Redis_tool;
use App\logic\Stock_logic;
use App\Traits\stockFileLib;
use Illuminate\Support\Facades\Storage;

class getNotUpdateStock
{
    use stockFileLib;

    private $parents_dir = 'stock';

    public function process($date)
    {
        $date = date('Ymd', strtotime($date));

        Redis_tool::getInstance()->delUpdateFailDaily();
        Redis_tool::getInstance()->delUpdateFailProcessDaily();

        $data = Stock_logic::getInstance()->get_all_stock_info()->filter(function ($item, $code) use ($date) {
            $sub = floor($code / 1000) * 1000;

            $sub = $sub > 9999 ? 9000 : $sub;

            $fileName = $this->parents_dir.'/st'.$sub.'/'.$code.'/'.date('Ym01', strtotime($date)).'.csv';

            return Storage::exists($fileName);
        })->filter(function ($item, $code) use ($date) {
            $sub = floor($code / 1000) * 1000;

            $sub = $sub > 9999 ? 9000 : $sub;

            $fileName = $this->parents_dir.'/st'.$sub.'/'.$code.'/'.date('Ym01', strtotime($date)).'.csv';

            $data = $this->stock_data_to_array($fileName);

            $last = end($data);

            $last_date = isset($last['date']) ? $last['date'] : '';

            return !empty($last_date) && strtotime($last_date) < strtotime($date);
        })->map(function ($item, $code) use ($date) {
            Redis_tool::getInstance()->setUpdateFailDaily($date, $code);
            Redis_tool::getInstance()->setUpdateFailProcessDaily($date, $code);

            return $code;
        });

        $msg = $date.'更新失敗筆數: '.$data->count();

        // 傳遞資料

        $notice = new Notice_logic();

        $notice->noticeUser($notice_type = 2, $msg, $onlyAdmin = true);
    }

    public static function getInstance()
    {
        return new self();
    }
}

<?php

namespace App\jobs;

use App\model\Holiday;
use App\Traits\stockFileLib;
use Ixudra\Curl\Facades\Curl;

class AccessHoliday
{
    use stockFileLib;

    private $year = [];

    private $data = [];

    private $Holiday = '';

    public function __construct()
    {
        $now = (int) date('Y');

        for ($i = 2016; $i <= $now; ++$i) {
            $this->year[] = $i;
        }

        $this->Holiday = new Holiday();
    }

    // 		自動取得交易休市日期

    public function get_holiday()
    {
        $this->getHolidayByCURL();

        $this->process();

        $this->insert();

        return true;
    }

    public static function getInstance()
    {
        return new self();
    }

    // 		交易休市日期網址

    private function get_holiday_url($year)
    {
        return 'http://www.twse.com.tw//holidaySchedule/holidaySchedule?response=csv&queryYear='.$year;
    }

    //      取得假日資料

    private function getHolidayByCURL()
    {
        collect($this->year)->map(function ($west_year) {
            $taiwan_year = $this->change_to_taiwan_year($west_year);

            $taiwan_year = str_replace('/', '', $taiwan_year);

            $url = $this->get_holiday_url($taiwan_year);

            $source = file_get_contents($url);

            $source = mb_convert_encoding($source, 'utf-8', 'big5');

            $this->data[$west_year] = explode("\n", $source);
        });

        return true;
    }

    //      處理資料

    private function process()
    {
        $filter = ['國曆新年開始交易日', '農曆春節後開始交易日'];

        $exist_holiday = $this->Holiday->getInstance()->get_list()->pluck('holiday_date')->toArray();

        //  中文 > 數字 + 過濾條件

        $this->data = collect($this->data)->map(function ($item) use ($filter) {
            $item = collect($item)->splice(2)->filter(function ($item) use ($filter) {
                $tmp = explode(',', trim($item));

                return mb_strlen(trim($item)) > 0 && !\in_array($tmp[0], $filter, true);
            })->map(function ($item) {
                $tmp = explode(',', trim($item));
                $date = str_replace('月', ',', $tmp[1]);
                $date = str_replace('日', ';', $date);
                $dateArray = explode(';', $date);
                $dateArray = array_filter($dateArray, 'trim');

                return $dateArray;
            })->reduce(function ($carry, $item) {
                $carry = !empty($carry) ? $carry : [];

                return array_merge($carry, $item);
            });

            return $item;
        });

        //  (107年)1,1 >> 註解文字過濾 + format

        $this->data = collect($this->data)->map(function ($item, $west_year) use ($exist_holiday) {
            $item = collect($item)->filter(function ($item) {
                $tmp = explode(',', $item);

                return is_numeric($tmp[0]) && is_numeric($tmp[1]);
            })->map(function ($item) use ($west_year) {
                $tmp = explode(',', $item);

                return date('Y-m-d', mktime(0, 0, 0, $tmp[0], $tmp[1], $west_year));
            })->filter(function ($item) use ($exist_holiday) {
                return !\in_array((int) date('w', strtotime($item)), [0, 6], true) && !\in_array($item, $exist_holiday, true);
            })->map(function ($item) {
                return ['holiday_date' => $item];
            })->values()->toArray();

            return $item;
        });

        return true;
    }

    //      寫入資料

    private function insert()
    {
        $data = $this->data;

        foreach ($data as $item) {
            if (!empty($item)) {
                $this->Holiday->add_data($item);
            }
        }
    }
}

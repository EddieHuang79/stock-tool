<?php

namespace App\Traits;

use App\logic\Record_logic;
use Illuminate\Support\Facades\Session;

trait SchemaFunc
{
    // 取得錯誤訊息

    protected function set_error_msg($e, $position)
    {
        $error_result = $e->getMessage();

        Session::put('ErrorMsg', $error_result);

        $action = $position;

        $content = json_encode($error_result);

        Record_logic::getInstance()->write_error_log($action, $content);
    }

    // 		map array handle

    protected function pluck($data, $key)
    {
        return collect($data)
            ->pluck($key)
            ->toArray();
    }

    // 		map group

    protected function map_to_groups($data, $key1, $key2)
    {
        return collect($data)
            ->mapToGroups(function ($item) use ($key1, $key2) {
                $item = get_object_vars($item);

                return [$item[$key1] => $item[$key2]];
            })
            ->toArray();
    }

    // 		map with key

    protected function map_with_key($data, $key1, $key2)
    {
        return collect($data)
            ->mapWithKeys(function ($item) use ($key1, $key2) {
                $item = \is_object($item) ? get_object_vars($item) : $item;

                return [$item[$key1] => $item[$key2]];
            })
            ->toArray();
    }

    // 		map with key assign default value

    protected function map_with_key_assign_default_value($data, $key1, $default)
    {
        return collect($data)
            ->mapWithKeys(function ($item) use ($key1, $default) {
                $item = \is_object($item) ? get_object_vars($item) : $item;

                return [$item[$key1] => $default];
            })
            ->toArray();
    }

    // 		only return assign attribute

    protected function values($data)
    {
        return collect($data)
            ->values()
            ->toArray();
    }

    // 		password rule
    // 		至少一碼英文+數字
    // 		介於6-8碼

    protected function pwd_rule($pwd)
    {
        $result = false;

        if (!empty($pwd) && \is_string($pwd)) {
            $result = preg_match("/[^A-Za-z\s]/i", $pwd) > 0 &&
                        preg_match('/[^0-9\s]/i', $pwd) > 0 &&
                        \strlen($pwd) >= 6 ? true : false;
        }

        return $result;
    }

    // 西元年轉民國年

    protected function year_change($date)
    {
        return (int) substr($date, 0, 4) - 1911 .'/'.substr($date, 4, 2);
    }
}

<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\logic\Holiday_logic;

class HolidayController extends Controller
{
    public function getHoliday(Request $request)
    {
        $before_days = $request->days;
        $now_date = $request->date;
        $type = $request->type === 'add' ? 2 : 1;

        $result = Holiday_logic::getInstance()->get_work_date($before_days, $now_date, $type);

        return response($result, 200)->header('Content-Type', 'text/plain');
    }
}

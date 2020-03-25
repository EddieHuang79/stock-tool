<?php

namespace App\Http\Controllers;

use App\logic\Line_logic;
use App\logic\Record_logic;
use Illuminate\Http\Request;

class LineController extends Controller
{
    public function index(Request $request)
    {
        Record_logic::getInstance()->write_operate_log($action = 'Line', $content = $request);

        return response('done', 200)->header('Content-Type', 'text/plain');
    }

    public function indexPost(Request $request)
    {
        $data = $request->getContent();

        Line_logic::getInstance()->receive_message($data);

        Record_logic::getInstance()->write_operate_log($action = 'Line', $content = $data);

        return response('done', 200)->header('Content-Type', 'text/plain');
    }
}

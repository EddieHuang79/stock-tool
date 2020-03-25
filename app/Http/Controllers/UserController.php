<?php

namespace App\Http\Controllers;

use App\logic\Admin_user_logic;
use Illuminate\Http\Request;

class UserController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        // $this->middleware('token.verify');
    }

    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index()
    {
        return response()->json(Admin_user_logic::get_list());
    }

    public function store()
    {
        return response()->json(Admin_user_logic::create_account());
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     *
     * @return \Illuminate\Http\Response
     */
    public function show($id, Request $request)
    {
        return response()->json(Admin_user_logic::get_data((int) $id, $request->_token));
    }

    /**
     * @param $id
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function update($id, Request $request)
    {
        return response()->json(Admin_user_logic::edit_account((int) $id));
    }

    //      檢查email是否存在

    public function check_mail_exist()
    {
        return response()->json(Admin_user_logic::is_mail_exist());
    }

    //      密碼重置

    public function pwd_reset(Request $request)
    {
        return response()->json(Admin_user_logic::reset_password($request));
    }

    //      token過期檢查

    public function is_expire($id)
    {
        return response()->json(Admin_user_logic::is_expire((int) $id));
    }

    //      修改密碼

    public function change_password(Request $request)
    {
        return response()->json(Admin_user_logic::change_password($request));
    }

    //      get_token

    public function get_token()
    {
        return response(encrypt(time()), 200)->header('Content-Type', 'text/plain');
    }
}

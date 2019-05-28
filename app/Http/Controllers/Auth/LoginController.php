<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use App\logic\Admin_user_logic;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = '/home';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        // $this->middleware('guest')->except('logout');
    }


    public function username()
    {

        return 'account';

    }


    public function showLoginForm()
    {

        abort(404);

    }

    public function login(Request $request)
    {

        return response()->json( Admin_user_logic::login_verify( $request )  );

    }

    public function logout(Request $request)
    {

        // Auth::logout();

        return response( "done" , 200 )->header('Content-Type', 'text/plain');

    }

}



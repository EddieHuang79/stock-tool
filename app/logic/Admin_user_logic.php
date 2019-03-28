<?php

namespace App\logic;

use App\model\Admin_user;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Auth;
use App\Traits\SchemaFunc;
use Hash;
use Uuid;

class Admin_user_logic extends Basetool
{

   use SchemaFunc;

   protected $txt = array();

   protected $key = 'user';

   protected $iv = 'user';

   public function __construct()
   {

      $this->txt = __('user');

   }


   public static function insert_format( $data )
   {

         $result = array();

         if ( !empty($data) && is_array($data) ) 
         {

            $result = array(
                           "account"       => isset($data["account"]) ? trim($data["account"]) : "",
                           "ori_password"  => isset($data["password"]) ? trim($data["password"]) : "",
                           "password"      => isset($data["password"]) ? bcrypt(trim($data["password"])) : "",
                           "email"         => isset($data["email"]) ? trim($data["email"]) : "",
                           "status"        => isset($data["status"]) ? intval($data["status"]) : 1,
                           "created_at"    => date("Y-m-d H:i:s"),
                           "updated_at"    => date("Y-m-d H:i:s")
                        );

         }

         return $result;

   }


   public static function update_format( $data )
   {

         $result = array();

         if ( !empty($data) && is_array($data) ) 
         {

            $result = array(
                           "account"                  => isset($data["account"]) ? trim($data["account"]) : "",
                           "ori_password"             => isset($data["password"]) ? trim($data["password"]) : "",
                           "password"                 => isset($data["password"]) ? bcrypt(trim($data["password"])) : "",
                           "email"                    => isset($data["email"]) ? trim($data["email"]) : "",
                           "status"                   => isset($data["status"]) ? (int)($data["status"]) : 1,
                           "updated_at"               => date("Y-m-d H:i:s")
                        );
         }

         return $result;

   }


   public static function add_user_role_format( $user_id, $role_id )
   {

         $result = array();

         if ( !empty($user_id) && is_int($user_id) && !empty($role_id) && is_int($role_id) ) 
         {

            $result[] = array(
                              "user_id"   => intval($user_id),
                              "role_id"   => intval($role_id)
                        );

         }

         return $result;

   }


   public static function get_data_logic( $id )
   {

      $_this = new self();

      $result = [];

      if ( !empty($id) && is_int($id) ) 
      {

         $data = Admin_user::get_data( $id );

         $role_id_list = $data->pluck("role_id")->toArray();

         $auth = Role_logic::get_role_service_data( $role_id_list );

         $user_data = isset($data[0]) ? $data[0] : [] ; 

         $auth = $_this->pluck( $auth[$user_data->role_id], $key = 'id' );
         
         $result = array(
            "id"           => isset( $user_data->id ) ? $user_data->id : 0,
            "account"      => isset( $user_data->account ) ? $user_data->account : '',
            "password"     => isset( $user_data->ori_password ) ? $user_data->ori_password : '',
            "status"       => isset( $user_data->status ) ? $user_data->status : '',
            "email"        => isset( $user_data->email ) ? $user_data->email : '',
            "auth"         => $auth
         );
         
      }

      return $result;

   }


   //    本案並不是群組架構，所以每個有獨立的權限

   public static function get_data( $id = 0, $token )
   {

         $_this = new self();

         $txt = $_this->txt;

         try 
         {
            
            //    判定權限

            $auth = Service_logic::has_auth( 1, $token );

            if ( $auth === false ) 
            {

               throw new \Exception( $txt["auth_error"] );

            }

            if ( empty($id) || !is_int($id) ) 
            {

               throw new \Exception( $txt["variable_error"] );

            }

            $result = array(
               "error"     => false,
               "msg"       => "",
               "data"      => $_this->get_data_logic( $id )
            );

         } 
         catch (\Exception $e) 
         {
            
            $result = array(
               "error"     => true,
               "msg"       => $e->getMessage(),
               "data"      => []
            );

         }

         return $result;

   }


   public static function get_list()
   {

      $_this = new self();

      $txt = $_this->txt;

      $status_txt = [
         1  => __('base.enable'),
         2  => __('base.disable')
      ];

      try 
      {
         
         //    判定權限

         $auth = Service_logic::has_auth( 2, $_GET["_token"] );

         if ( $auth === false ) 
         {

            throw new \Exception( $txt["auth_error"] );

         }

         $list_data = [];

         $data = Admin_user::get_list( $page_size = 10, $orderBy = 'created_at', $sort = 'desc' );

         $role_id_list = $data->pluck("role_id")->toArray();

         $auth = Role_logic::get_role_service_data( $role_id_list );

         foreach ($data as $row) 
         {

            $list_data[] = array(
               "id"           => $row->id,
               "account"      => $row->account,
               "email"        => $row->email,
               "status"       => $status_txt[$row->status],
               "auth"         => isset( $auth[$row->role_id] ) ? $auth[$row->role_id] : []
            );

         }

         $result = array(
            "error"     => false,
            "msg"       => '',
            "data"      => $list_data
         );

      } 
      catch (\Exception $e) 
      {
         
         $result = array(
            "error"     => true,
            "msg"       => $e->getMessage(),
            "data"      => []
         );

      }

      return $result;

   }


   public static function add_user( $data )
   {

         $result = false;

         if ( !empty($data) && is_array($data) ) 
         {

            $result = Admin_user::add_user( $data );

         }

         return $result ;

   }


   public static function edit_user( $data, $user_id )
   {

         $result = false;

         if ( !empty($data) && is_array($data) && !empty($user_id) && is_int($user_id) ) 
         {

            Admin_user::edit_user( $data, $user_id );

            $result = true;

         }

         return $result;

   }


   public static function add_user_role( $data )
   {

         $result = false;

         if ( !empty($data) && is_array($data) ) 
         {

             Admin_user::add_user_role( $data );

            $result = true;

         }

         return $result;

   }


   //    搜尋重複的帳號

   public static function is_Duplicate( $column, $data, $id = 0 )
   {

      $result = false;

      if ( !empty($column) && is_string($column) && !empty($data) && is_string($data) ) 
      {

         $data = Admin_user::find_user_by_assign_column( $column, $data, $id );

         $result = $data->count() > 0 ? true : false ;

      }

      return $result;

   }


   //    login verify

   public static function login_verify( $request )
   {

      $_this = new self();

      $txt = $_this->txt;

      try {

         if ( !is_object($request) || empty($request) ) {

            $msg = $txt['operate_error'];

            throw new \Exception($msg);

         }

         if ( Auth::attempt(['account' => $request->account, 'password' => $request->password]) === false ) {

            $msg = $txt['verify_error'];

            throw new \Exception($msg);

         }

         if ( Auth::user()->status !== 1 ) {

            $msg = $txt['account_disable'];

            throw new \Exception($msg);

         }

         $token = encrypt( time() );

         // 刪除在線人員

         Redis_tool::del_assign_online_user( Auth::user()->account, 'account' );

         Redis_tool::set_online_user( [ "id" => Auth::user()->id, "account" => Auth::user()->account, "login_time" => time(), "token" => $token ] );

         $data = Redis_tool::get_user_service_data();

         // 資料過期的處理

         if ( empty($data) ) 
         {

            // 寫入user service array

            Service_logic::set_user_service_data();

         }

         $result = [
            "error"     =>    false,
            "isAdmin"   =>    $_this->is_admin( Auth::user()->id ),
            "msg"       =>    '',
            "data"      =>    [ "id" => Auth::user()->id, "name" => Auth::user()->account ],
            "token"     =>    $token
         ];

      } catch (\Exception $e) {

         $result = [
            "error"     =>    true,
            "isAdmin"   =>    false,
            "msg"       =>    $e->getMessage(),
            "data"      =>    []
         ];

      }

      return $result;

   }


   //    檢查email是否重複

   public static function is_mail_exist()
   {

      $_this = new self();

      $result = [ 
         "exist" => false 
      ];

      $mail = isset($_POST["email"]) ? trim($_POST["email"]) : '' ;

      if ( !empty($mail) && is_string($mail) ) 
      {

         $result["exist"] = Admin_user::is_mail_exist( $mail )->count() > 0;
         
      }

      return $result;

   }


   //    忘記密碼

   public static function forget_and_update_password( $password, $mail )
   {

         $result = false;

         if ( !empty($password) && is_string($password) && !empty($mail) && is_string($mail) ) 
         {

            Admin_user::forget_and_update_password( $password, $mail );

            $result = true;

         }

         return $result;

   }


   //    reset password

   public static function reset_password( $request )
   {

      $_this = new self();

      $txt = $_this->txt;

      try {

         if ( !is_object($request) || empty($request) ) {

            $msg = $txt['operate_error'];

            throw new \Exception($msg);

         }

         if ( $request->new_pwd === $request->old_pwd ) {

            $msg = $txt['reset_pwd_error_1'];

            throw new \Exception($msg);

         }

         if ( $request->new_pwd !== $request->check_pwd ) {

            $msg = $txt['reset_pwd_error_2'];

            throw new \Exception($msg);

         }

         //    確認密碼特殊規則

         if ( $_this->pwd_rule( $request->new_pwd ) === false ) {

            $msg = $txt['reset_pwd_error_3'];

            throw new \Exception($msg);

         }      

         // 更新密碼

         Admin_user_logic::forget_and_update_password( bcrypt( $request->new_pwd ), Auth::user()->email );         

         $result = [
            "error"     =>    false,
            "msg"       =>    ''
         ];

      } catch (\Exception $e) {

         $result = [
            "error"     =>    true,
            "msg"       =>    $e->getMessage()
         ];

      }

      return $result;

   }


   //    change password

   public static function change_password( $request )
   {

      $_this = new self();

      $txt = $_this->txt;

      $result = [
         "error"     =>    false,
         "msg"       =>    ''
      ];

      try {

         if ( !is_object($request) || empty($request) ) {

            $msg = $txt['operate_error'];

            throw new \Exception($msg);

         }

         // 驗證必要參數是否為空值

         if ( empty($request->user_id) || empty($request->old_pwd) || empty($request->new_pwd) || empty($request->check_pwd) ) 
         {

            $msg = $txt['variable_error'];

            throw new \Exception( $msg );

         }

         //    確認舊密碼是否正確

         if ( $_this->compare_password( (int)$request->user_id, $request->old_pwd ) === false ) {

            $msg = $txt['reset_pwd_error_0'];

            throw new \Exception($msg);

         }

         //    新舊密碼相同

         if ( $request->new_pwd === $request->old_pwd ) {

            $msg = $txt['reset_pwd_error_1'];

            throw new \Exception($msg);

         }

         //    兩次密碼不一致

         if ( $request->new_pwd !== $request->check_pwd ) {

            $msg = $txt['reset_pwd_error_2'];

            throw new \Exception($msg);

         }

         //    確認密碼特殊規則

         if ( $_this->pwd_rule( $request->new_pwd ) === false ) {

            $msg = $txt['reset_pwd_error_3'];

            throw new \Exception($msg);

         }      

         // 更新密碼

         $_this->edit_user( ["password" => bcrypt( $request->new_pwd )], (int)$request->user_id );         

         $result = [
            "error"     =>    false,
            "msg"       =>    $txt['password_update_success']
         ];

      } catch (\Exception $e) {

         $result = [
            "error"     =>    true,
            "msg"       =>    $e->getMessage()
         ];

      }

      return $result;

   }


   //    compare password

   protected function compare_password( $user_id, $password )
   {

      $result = false;

      if ( !empty($user_id) && is_int($user_id) && !empty($password) && is_string($password) ) 
      {

         $data = Admin_user::get_password( $user_id );

         $result = Hash::check( $password, $data->password);
         
      }

      return $result;

   }


   //    帳號的到期檢查

   public static function is_expire( $user_id )
   {

      $result = [
         "status" => false
      ];

      if ( !empty($user_id) && is_int($user_id) ) 
      {

         try 
         {

            $data = Redis_tool::get_online_user();

            $filter_data = collect( $data )->filter(function ($value, $key) use( $user_id ) {
                               return $value["id"] === $user_id;
                           })->shift();
            
            
            if ( empty($filter_data) ) 
            {

                  throw new \Exception(1);

            }


            if ( time() - $filter_data["login_time"] > (int)config('session.lifetime') * 60 ) 
            {

                  throw new \Exception(1);

            }
               
            $result = [
               "status" => false
            ];

         } 
         catch (\Exception $e) 
         {
            
            $result = [
               "status" => true
            ];

         }

      }

      return $result;

   }


   //    create account api

   public static function create_account()
   {

      $_this = new self();

      $txt = $_this->txt;

      try
      {

         //    判定權限

         $auth = Service_logic::has_auth( 1, $_POST["_token"] );

         if ( $auth === false ) 
         {

            throw new \Exception( $txt["auth_error"] );

         }

         $data = Admin_user_logic::insert_format( $_POST );

         //    帳號規則驗證

         if ( strlen($data["account"]) < 3 )
         {

            throw new \Exception( $txt["account_length_error"] );

         }

         //    密碼規則驗證

         if ( $_this->pwd_rule( $data["ori_password"] ) === false )
         {

            throw new \Exception( $txt["reset_pwd_error_3"] );

         }

         //    帳號是否重複

         if ( Admin_user_logic::is_Duplicate( 'account', $data["account"] ) === true )
         {

            throw new \Exception( $txt["account_duplicate"] );

         }

         //    email是否重複

         if ( Admin_user_logic::is_Duplicate( 'email', $data["email"] ) === true )
         {

            throw new \Exception( $txt["email_duplicate"] );

         }

         //    是否勾選權限

         if ( !isset($_POST["auth"]) || empty($_POST["auth"]) )
         {

            throw new \Exception( $txt["not_assgign_auth"] );

         }

         //    權限設定錯誤的情境
         //    若有勾選客服頁面，權限只能有一個

         if ( in_array(1, $_POST["auth"]) && count($_POST["auth"]) > 1 )
         {

            throw new \Exception( $txt["auth_setting_error"] );

         }

         $user_id = Admin_user_logic::add_user( $data );

         // user role add

         $_POST["auth"] = isset($_POST["auth"]) ? $_POST["auth"] : "" ;

         // 不分群組，所以每個人都是獨立群組，將user_id當成role_id寫入

         // 建立role

         $data = Role_logic::insert_format( [ "name" => (int)$user_id ] ); 

         $role_id = Role_logic::add_role( $data );

         // 寫入user_role

         $data = Admin_user_logic::add_user_role_format( (int)$user_id, (int)$role_id );

         Admin_user_logic::add_user_role( $data );

         // 寫入role service

         $data = Role_logic::add_role_service_format( (int)$role_id, $_POST["auth"] );

         Role_logic::add_role_service( $data );

         // clear redis

         Redis_tool::clear_user_service_data();

         // add redis

         Service_logic::set_user_service_data();

         $result = [
            "error"     =>  false,
            "msg"       =>  '帳號新增成功！'           
         ];         

      }
      catch(\Exception $e)
      {

         $result = [
            "error"     =>  true,
            "msg"       =>  $e->getMessage()           
         ];   

      }

      return $result;

   }


   //    edit account api

   public static function edit_account( $user_id )
   {

      $_this = new self();

      $txt = $_this->txt;

      try
      {

         //    判定修改權限

         $auth = Service_logic::has_auth( 6, $_POST["_token"] );

         if ( $auth === false ) 
         {

            throw new \Exception( $txt["auth_error"] );

         }

         //    判定停用權限

         $auth = Service_logic::has_auth( 7, $_POST["_token"] );

         if ( $auth === false ) 
         {

            throw new \Exception( $txt["auth_error"] );

         }

         if ( $user_id < 1 ) 
         {

            throw new \Exception( $txt["variable_error"] ); 
            
         }

         $data = Admin_user_logic::update_format( $_POST );

         //    帳號是否重複

         if (  Admin_user_logic::is_Duplicate( 'account', $data["account"], $user_id ) === true )
         {

             throw new \Exception( $txt["account_duplicate"] );

         }

         //    email是否重複

         if ( Admin_user_logic::is_Duplicate( 'email', $data["email"], $user_id ) === true )
         {

             throw new \Exception( $txt["email_duplicate"] );

         }

         //    是否勾選權限

         if ( !isset($_POST["auth"]) || empty($_POST["auth"]) )
         {

            throw new \Exception( $txt["not_assgign_auth"] );

         }

         //    權限設定錯誤的情境
         //    若有勾選客服頁面，權限只能有一個

         if ( in_array(1, $_POST["auth"]) && count($_POST["auth"]) > 1 )
         {

            throw new \Exception( $txt["auth_setting_error"] );

         }

         Admin_user_logic::edit_user( $data, $user_id );

         // 取得role_id

         $role_id = Role_logic::get_role_id_by_user_id( $user_id );

         // 刪掉role_service

         Service_logic::delete_role_service_data( $role_id );

         // 寫入role_service

         $data = Role_logic::add_role_service_format( (int)$role_id, $_POST["auth"] );

         Role_logic::add_role_service( $data );

         // clear redis

         Redis_tool::clear_user_service_data();

         // add redis

         Service_logic::set_user_service_data();

         $result = [
            "error"     =>  false,
            "msg"       =>  '帳號修改成功！'           
         ];         

      }
      catch(\Exception $e)
      {

         $result = [
            "error"     =>  true,
            "msg"       =>  $e->getMessage()           
         ];   

      }

      return $result;

   }


   //    取得上線中的客服

   public static function get_online_user()
   {

      $_this = new self();

      $data = Redis_tool::get_online_user();

      $result = collect( $data )->filter(function ($item, $key) use( $_this ) {
                  return  $_this->is_admin( $item["id"] ) === false && time() - $item["login_time"] <= (int)config('session.lifetime') * 60  ;
               })->values()->toArray();

      return $result;

   }


   public static function get_user_data_mapping()
   {

      $_this = new self();

      $data = Admin_user::get_user_data();

      $result = $_this->map_with_key( $data, $key1 = 'id', $key2 = 'account' );

      return $result;

   }


   //    管理者帳號的判斷依據

   protected function is_admin( $user_id )
   {

      // 取得權限

      $data = Redis_tool::get_user_service_data();

      // 資料過期的處理

      if ( empty($data) ) 
      {

         // 寫入user service array

         Service_logic::set_user_service_data();

         $data = Redis_tool::get_user_service_data();

      }

      $auth = isset($data[$user_id]) ? $data[$user_id] : [] ;

      return !in_array(1, $auth) && !empty($auth);

   }


}









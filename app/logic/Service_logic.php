<?php

namespace App\logic;

use App\model\Service;
use Illuminate\Support\Facades\Auth;
use App\Traits\SchemaFunc;

class Service_logic extends Basetool
{

   use SchemaFunc;

   // 判定是否有權限

   public static function has_auth( $service_id, $token )
   {

      $_this = new self();

      $result = false;

      if ( !empty($service_id) && is_int($service_id) && !empty($token) && is_string($token) ) 
      {

            $user_data = Redis_tool::get_user( $token );

            $user_id = isset($user_data["id"]) ? $user_data["id"] : 0 ;

            $data = Redis_tool::get_user_service_data();

            // 資料過期的處理

            if ( empty($data) ) 
            {

               // 寫入user service array

               $_this->set_user_service_data();

               $data = Redis_tool::get_user_service_data();
                  
            }

            $result = isset($data[$user_id]) && in_array($service_id, $data[$user_id]);

      }

      if ( env("auth_check") !== true )
      {

         $result = true;

      }
         
      return $result;

   }


   // 設定使用者權限

   public static function set_user_service_data()
   {

      $result = array();

      $data = Service::get_user_service_data()->toArray();

      foreach ($data as $row) 
      {

         $result[$row->user_id][] = $row->service_id;

      }

      Redis_tool::set_user_service_data( $result );

   }


   // 刪除使用者權限

   public static function delete_role_service_data( $role_id )
   {

      $result = false;

      if ( !empty($role_id) && is_int($role_id) ) 
      {
         
         Service::delete_role_service_data( $role_id );

         $result = true;
         
      }

      return $result;

   }


   // 取得全部的服務
   // 因為沒有別的用途，所以暫時先做成父子格式


   public static function get_list()
   {

      $result = [
         "error"  => false,
         "data"   => []
      ];

      $data = Service::get_list()->toArray();

      foreach ($data as $row) 
      {

         if ( (int)$row->parents_id === 0 ) 
         {

            $result["data"][$row->id] = [
               "value"     => $row->id,
               "text"      => $row->name,
               "child"     => []
            ];

         }
         else
         {

            $result["data"][$row->parents_id]["child"][] = [
               "value"     => $row->id,
               "text"      => $row->name,
            ];

         }

      }

      return $result;

   }

}

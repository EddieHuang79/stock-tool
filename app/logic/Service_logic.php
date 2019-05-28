<?php

namespace App\logic;

use App\model\Service;
use App\Traits\SchemaFunc;

class Service_logic
{

   use SchemaFunc;


   // 刪除使用者權限

   public function delete_role_service_data( $role_id )
   {

      $result = false;

      if ( !empty($role_id) && is_int($role_id) )
      {

         Service::getInstance()->delete_role_service_data( $role_id );

         $result = true;

      }

      return $result;

   }


   // 取得全部的服務
   // 因為沒有別的用途，所以暫時先做成父子格式


   public function get_list()
   {

      $result = [
         "error"  => false,
         "data"   => []
      ];

      $data = Service::getInstance()->get_list()->toArray();

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

    public static function getInstance()
    {

        return new self;

    }

}

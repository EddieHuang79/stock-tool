<?php

namespace App\logic;

use App\model\Role;
use App\Traits\SchemaFunc;

class Role_logic extends Basetool
{
    use SchemaFunc;

    protected $txt = [];

    protected $key = 'role';

    protected $action = [];

    public function __construct()
    {
        $this->txt = __($this->key);

        $this->action = [
            'name' => $this->txt['action_name'],
            'link' => '/'.$this->key,
        ];
    }

    // 新增格式

    public function insert_format($data)
    {
        $result = [];

        if (!empty($data) && \is_array($data)) {
            $result = [
                'name' => isset($data['name']) ? $this->strFilter($data['name']) : '',
                'status' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ];
        }

        return $result;
    }

    // 新增角色

    public function add_role($data)
    {
        $result = false;

        if (!empty($data) && \is_array($data) && !empty($data['name'])) {
            $result = Role::getInstance()->add_role($data);
        }

        return $result;
    }

    // 新增權限格式

    public function add_role_service_format($role_id, $data)
    {
        $result = [];

        if (!empty($data) && $role_id > 0) {
            foreach ($data as $service_id) {
                $result[] = [
                    'role_id' => (int) $role_id,
                    'service_id' => (int) $service_id,
                ];
            }
        }

        return $result;
    }

    // 新增權限

    public function add_role_service($data)
    {
        $result = false;

        if (!empty($data) && \is_array($data)) {
            Role::getInstance()->add_role_service($data);

            $result = true;
        }

        return $result;
    }

    // 以角色資料取得權限清單

    public function get_role_service_data($role_id)
    {
        $result = [];

        if (!empty($role_id) && \is_array($role_id)) {
            $data = Role::getInstance()->get_role_service_data($role_id);

            foreach ($data as $row) {
                $result[$row->role_id] = isset($result[$row->role_id]) ? $result[$row->role_id] : [];

                if ((int) $row->parents_id === 0) {
                    $result[$row->role_id][$row->service_id] = [
                        'id' => $row->service_id,
                        'name' => $row->service_name,
                        'child' => [],
                    ];
                } else {
                    $result[$row->role_id][$row->parents_id]['child'][] = [
                        'id' => $row->service_id,
                        'name' => $row->service_name,
                    ];
                }
            }
        }

        return $result;
    }

    // 取得role_id

    public function get_role_id_by_user_id($user_id)
    {
        $result = 0;

        if (!empty($user_id) && \is_int($user_id)) {
            $data = Role::getInstance()->get_role_id_by_user_id($user_id);

            $result = (int) $data->role_id;
        }

        return $result;
    }

    public static function getInstance()
    {
        return new self();
    }
}

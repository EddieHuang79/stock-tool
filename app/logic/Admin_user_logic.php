<?php

namespace App\logic;

use App\model\Admin_user;
use App\Traits\SchemaFunc;
use Hash;
use Illuminate\Support\Facades\Auth;

class Admin_user_logic
{
    use SchemaFunc;

    protected $txt = [];

    protected $key = 'user';

    protected $iv = 'user';

    public function __construct()
    {
        $this->txt = __('user');
    }

    public function insert_format($data)
    {
        $result = [];

        if (!empty($data) && \is_array($data)) {
            $result = [
                'account' => isset($data['account']) ? trim($data['account']) : '',
                'ori_password' => isset($data['password']) ? trim($data['password']) : '',
                'password' => isset($data['password']) ? bcrypt(trim($data['password'])) : '',
                'email' => isset($data['email']) ? trim($data['email']) : '',
                'status' => isset($data['status']) ? (int) ($data['status']) : 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ];
        }

        return $result;
    }

    public function update_format($data)
    {
        $result = [];

        if (!empty($data) && \is_array($data)) {
            $result = [
                'account' => isset($data['account']) ? trim($data['account']) : '',
                'ori_password' => isset($data['password']) ? trim($data['password']) : '',
                'password' => isset($data['password']) ? bcrypt(trim($data['password'])) : '',
                'email' => isset($data['email']) ? trim($data['email']) : '',
                'status' => isset($data['status']) ? (int) ($data['status']) : 1,
                'updated_at' => date('Y-m-d H:i:s'),
            ];
        }

        return $result;
    }

    public function add_user_role_format($user_id, $role_id)
    {
        $result = [];

        if (!empty($user_id) && \is_int($user_id) && !empty($role_id) && \is_int($role_id)) {
            $result[] = [
                'user_id' => (int) $user_id,
                'role_id' => (int) $role_id,
            ];
        }

        return $result;
    }

    public function get_data_logic($id)
    {
        $result = [];

        if (!empty($id) && \is_int($id)) {
            $data = Admin_user::getInstance()->get_data($id);

            $role_id_list = $data->pluck('role_id')->toArray();

            $auth = Role_logic::getInstance()->get_role_service_data($role_id_list);

            $user_data = isset($data[0]) ? $data[0] : [];

            $auth = $this->pluck($auth[$user_data->role_id], $key = 'id');

            $result = [
                'id' => isset($user_data->id) ? $user_data->id : 0,
                'account' => isset($user_data->account) ? $user_data->account : '',
                'password' => isset($user_data->ori_password) ? $user_data->ori_password : '',
                'status' => isset($user_data->status) ? $user_data->status : '',
                'email' => isset($user_data->email) ? $user_data->email : '',
                'auth' => $auth,
            ];
        }

        return $result;
    }

    //    本案並不是群組架構，所以每個有獨立的權限

    public function get_data($id = 0, $token = '')
    {
        $txt = $this->txt;

        try {
            if (empty($id) || !\is_int($id)) {
                throw new \Exception($txt['variable_error']);
            }

            $result = [
                'error' => false,
                'msg' => '',
                'data' => $this->get_data_logic($id),
            ];
        } catch (\Exception $e) {
            $result = [
                'error' => true,
                'msg' => $e->getMessage(),
                'data' => [],
            ];
        }

        return $result;
    }

    public function get_list()
    {
        $status_txt = [
            1 => __('base.enable'),
            2 => __('base.disable'),
        ];

        try {
            $list_data = [];

            $data = Admin_user::getInstance()->get_list($page_size = 10, $orderBy = 'created_at', $sort = 'desc');

            $role_id_list = $data->pluck('role_id')->toArray();

            $auth = Role_logic::getInstance()->get_role_service_data($role_id_list);

            foreach ($data as $row) {
                $list_data[] = [
                    'id' => $row->id,
                    'account' => $row->account,
                    'email' => $row->email,
                    'status' => $status_txt[$row->status],
                    'auth' => isset($auth[$row->role_id]) ? $auth[$row->role_id] : [],
                ];
            }

            $result = [
                'error' => false,
                'msg' => '',
                'data' => $list_data,
            ];
        } catch (\Exception $e) {
            $result = [
                'error' => true,
                'msg' => $e->getMessage(),
                'data' => [],
            ];
        }

        return $result;
    }

    public function add_user($data)
    {
        $result = false;

        if (!empty($data) && \is_array($data)) {
            $result = Admin_user::getInstance()->add_user($data);
        }

        return $result;
    }

    public function edit_user($data, $user_id)
    {
        $result = false;

        if (!empty($data) && \is_array($data) && !empty($user_id) && \is_int($user_id)) {
            Admin_user::getInstance()->edit_user($data, $user_id);

            $result = true;
        }

        return $result;
    }

    public function add_user_role($data)
    {
        $result = false;

        if (!empty($data) && \is_array($data)) {
            Admin_user::getInstance()->add_user_role($data);

            $result = true;
        }

        return $result;
    }

    //    搜尋重複的帳號

    public function is_Duplicate($column, $data, $id = 0)
    {
        $result = false;

        if (!empty($column) && \is_string($column) && !empty($data) && \is_string($data)) {
            $data = Admin_user::getInstance()->find_user_by_assign_column($column, $data, $id);

            $result = $data->count() > 0 ? true : false;
        }

        return $result;
    }

    //    login verify

    public function login_verify($request)
    {
        $txt = $this->txt;

        try {
            if (!\is_object($request) || empty($request)) {
                $msg = $txt['operate_error'];

                throw new \Exception($msg);
            }

            if (Auth::attempt(['account' => $request->account, 'password' => $request->password]) === false) {
                $msg = $txt['verify_error'];

                throw new \Exception($msg);
            }

            if (Auth::user()->status !== 1) {
                $msg = $txt['account_disable'];

                throw new \Exception($msg);
            }

            $token = encrypt(time());

            $result = [
                'error' => false,
                'isAdmin' => $this->is_admin(Auth::user()->id),
                'msg' => '',
                'data' => ['id' => Auth::user()->id, 'name' => Auth::user()->account],
                'token' => $token,
            ];
        } catch (\Exception $e) {
            $result = [
                'error' => true,
                'isAdmin' => false,
                'msg' => $e->getMessage(),
                'data' => [],
            ];
        }

        return $result;
    }

    //    檢查email是否重複

    public function is_mail_exist()
    {
        $result = [
            'exist' => false,
        ];

        $mail = isset($_POST['email']) ? trim($_POST['email']) : '';

        if (!empty($mail) && \is_string($mail)) {
            $result['exist'] = Admin_user::getInstance()->is_mail_exist($mail)->count() > 0;
        }

        return $result;
    }

    //    忘記密碼

    public function forget_and_update_password($password, $mail)
    {
        $result = false;

        if (!empty($password) && \is_string($password) && !empty($mail) && \is_string($mail)) {
            Admin_user::getInstance()->forget_and_update_password($password, $mail);

            $result = true;
        }

        return $result;
    }

    //    reset password

    public function reset_password($request)
    {
        $txt = $this->txt;

        try {
            if (!\is_object($request) || empty($request)) {
                $msg = $txt['operate_error'];

                throw new \Exception($msg);
            }

            if ($request->new_pwd === $request->old_pwd) {
                $msg = $txt['reset_pwd_error_1'];

                throw new \Exception($msg);
            }

            if ($request->new_pwd !== $request->check_pwd) {
                $msg = $txt['reset_pwd_error_2'];

                throw new \Exception($msg);
            }

            //    確認密碼特殊規則

            if ($this->pwd_rule($request->new_pwd) === false) {
                $msg = $txt['reset_pwd_error_3'];

                throw new \Exception($msg);
            }

            // 更新密碼

            $this->forget_and_update_password(bcrypt($request->new_pwd), Auth::user()->email);

            $result = [
                'error' => false,
                'msg' => '',
            ];
        } catch (\Exception $e) {
            $result = [
                'error' => true,
                'msg' => $e->getMessage(),
            ];
        }

        return $result;
    }

    //    change password

    public function change_password($request)
    {
        $txt = $this->txt;

        $result = [
            'error' => false,
            'msg' => '',
        ];

        try {
            if (!\is_object($request) || empty($request)) {
                $msg = $txt['operate_error'];

                throw new \Exception($msg);
            }

            // 驗證必要參數是否為空值

            if (empty($request->user_id) || empty($request->old_pwd) || empty($request->new_pwd) || empty($request->check_pwd)) {
                $msg = $txt['variable_error'];

                throw new \Exception($msg);
            }

            //    確認舊密碼是否正確

            if ($this->compare_password((int) $request->user_id, $request->old_pwd) === false) {
                $msg = $txt['reset_pwd_error_0'];

                throw new \Exception($msg);
            }

            //    新舊密碼相同

            if ($request->new_pwd === $request->old_pwd) {
                $msg = $txt['reset_pwd_error_1'];

                throw new \Exception($msg);
            }

            //    兩次密碼不一致

            if ($request->new_pwd !== $request->check_pwd) {
                $msg = $txt['reset_pwd_error_2'];

                throw new \Exception($msg);
            }

            //    確認密碼特殊規則

            if ($this->pwd_rule($request->new_pwd) === false) {
                $msg = $txt['reset_pwd_error_3'];

                throw new \Exception($msg);
            }

            // 更新密碼

            $this->edit_user(['password' => bcrypt($request->new_pwd)], (int) $request->user_id);

            $result = [
                'error' => false,
                'msg' => $txt['password_update_success'],
            ];
        } catch (\Exception $e) {
            $result = [
                'error' => true,
                'msg' => $e->getMessage(),
            ];
        }

        return $result;
    }

    //    create account api

    public function create_account()
    {
        $txt = $this->txt;

        try {
            $data = $this->insert_format($_POST);

            //    帳號規則驗證

            if (\strlen($data['account']) < 3) {
                throw new \Exception($txt['account_length_error']);
            }

            //    密碼規則驗證

            if ($this->pwd_rule($data['ori_password']) === false) {
                throw new \Exception($txt['reset_pwd_error_3']);
            }

            //    帳號是否重複

            if ($this->is_Duplicate('account', $data['account']) === true) {
                throw new \Exception($txt['account_duplicate']);
            }

            //    email是否重複

            if ($this->is_Duplicate('email', $data['email']) === true) {
                throw new \Exception($txt['email_duplicate']);
            }

            //    是否勾選權限

            if (!isset($_POST['auth']) || empty($_POST['auth'])) {
                throw new \Exception($txt['not_assgign_auth']);
            }

            //    權限設定錯誤的情境
            //    若有勾選客服頁面，權限只能有一個

            if (\in_array(1, $_POST['auth'], true) && \count($_POST['auth']) > 1) {
                throw new \Exception($txt['auth_setting_error']);
            }

            $user_id = $this->add_user($data);

            // user role add

            $_POST['auth'] = isset($_POST['auth']) ? $_POST['auth'] : '';

            // 不分群組，所以每個人都是獨立群組，將user_id當成role_id寫入

            // 建立role

            $data = Role_logic::getInstance()->insert_format(['name' => (int) $user_id]);

            $role_id = Role_logic::getInstance()->add_role($data);

            // 寫入user_role

            $data = $this->add_user_role_format((int) $user_id, (int) $role_id);

            $this->add_user_role($data);

            // 寫入role service

            $data = Role_logic::getInstance()->add_role_service_format((int) $role_id, $_POST['auth']);

            Role_logic::getInstance()->add_role_service($data);

            $result = [
                'error' => false,
                'msg' => '帳號新增成功！',
            ];
        } catch (\Exception $e) {
            $result = [
                'error' => true,
                'msg' => $e->getMessage(),
            ];
        }

        return $result;
    }

    //    edit account api

    public function edit_account($user_id)
    {
        $txt = $this->txt;

        try {
            if ($user_id < 1) {
                throw new \Exception($txt['variable_error']);
            }

            $data = $this->update_format($_POST);

            //    帳號是否重複

            if ($this->is_Duplicate('account', $data['account'], $user_id) === true) {
                throw new \Exception($txt['account_duplicate']);
            }

            //    email是否重複

            if ($this->is_Duplicate('email', $data['email'], $user_id) === true) {
                throw new \Exception($txt['email_duplicate']);
            }

            //    是否勾選權限

            if (!isset($_POST['auth']) || empty($_POST['auth'])) {
                throw new \Exception($txt['not_assgign_auth']);
            }

            //    權限設定錯誤的情境
            //    若有勾選客服頁面，權限只能有一個

            if (\in_array(1, $_POST['auth'], true) && \count($_POST['auth']) > 1) {
                throw new \Exception($txt['auth_setting_error']);
            }

            $this->edit_user($data, $user_id);

            // 取得role_id

            $role_id = Role_logic::getInstance()->get_role_id_by_user_id($user_id);

            // 刪掉role_service

            Service_logic::getInstance()->delete_role_service_data($role_id);

            // 寫入role_service

            $data = Role_logic::getInstance()->add_role_service_format((int) $role_id, $_POST['auth']);

            Role_logic::getInstance()->add_role_service($data);

            $result = [
                'error' => false,
                'msg' => '帳號修改成功！',
            ];
        } catch (\Exception $e) {
            $result = [
                'error' => true,
                'msg' => $e->getMessage(),
            ];
        }

        return $result;
    }

    public function get_user_data_mapping()
    {
        $data = Admin_user::getInstance()->get_user_data();

        $result = $this->map_with_key($data, $key1 = 'id', $key2 = 'account');

        return $result;
    }

    public static function getInstance()
    {
        return new self();
    }

    //    compare password

    private function compare_password($user_id, $password)
    {
        $result = false;

        if (!empty($user_id) && \is_int($user_id) && !empty($password) && \is_string($password)) {
            $data = Admin_user::getInstance()->get_password($user_id);

            $result = Hash::check($password, $data->password);
        }

        return $result;
    }
}

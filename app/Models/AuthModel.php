<?php
namespace App\Models;
use CodeIgniter\Model;

class AuthModel extends Model
{
    function __construct(){
        $this->db      = \Config\Database::connect();   
    }

    function login($post){
        $admin = $this->db->table(ADMIN);
        $admin->select('admin_uid,admin_fname,admin_mname,admin_lname,admin_email,admin_password,admin_phone,admin_modifiedon,admin_status');
        $admin->where('admin_email',$post['username']);
        if($post['type']=='admin')
        {
            $admin->where('admin_type','owner');
        }
        elseif($post['type']=='receptionist')
        {
            $admin->where('admin_type','receptionist');
        }
        elseif($post['type']=='accountant')
        {
            $admin->where('admin_type','accountant');
        }
        $data = $admin->get()->getRowArray();
        if(!empty($data)){
            
            if($data['admin_status'] == 0)
            {
                $res['status'] = 0;
                $res['message'] = lang('App.inactive_account');
                return $res;
            }
            elseif(decrypt($data['admin_password']) != $post['password']){
                $res['status'] = 0;
                $res['message'] = lang('App.invalid_password');
                return $res;
            }
            elseif($data['admin_status'] == 1)
            {
                unset($data['admin_password']);
                $res['status'] = 1;
                $res['message'] = lang('App.login_success');
                $res['data'] = $data;
                return $res;
            }
        }
        $res['status'] = 0;
        $res['message'] = lang('App.email_not_found');
        return $res;                
    }
}

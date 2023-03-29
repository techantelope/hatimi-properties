<?php
namespace App\Controllers;
use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\API\ResponseTrait;
use CodeIgniter\HTTP\RequestInterface;
use App\Models\AuthModel;
use App\Models\CommonModel;

class DevAuth extends ResourceController
{
    public function __construct()
    {
        helper(['form', 'url','common_helper']);
        $this->validation =  \Config\Services::validation();
        $this->request = \Config\Services::request();
        $this->AutM = new AuthModel();
        $this->CM = new CommonModel();
    }

    function requiredCheck($fields){
        if($fields){
            $rules = array();
            foreach ($fields as $field) {
                $rules[$field] = [
                        "label" => lang('App.'.$field), 
                        "rules" => "required",
                        'errors' => [
                            'required' => lang('App.'.$field).lang('App.is_required')
                        ]
                    ];
            }
            if ($this->validate($rules)) 
            {
                $data['status'] = 1;
                $data['data'] = '';
                return $data;
            }else{
                $data['status'] = 0;
                $data['data'] = $this->validation->getErrors();
                return $data;
            }
        }
        return true;
    }

    public function login()
    {   
        
        if(empty($this->request->getVar('its')))
        {
          $data['status'] = 1;
          $data['message'] = 'ITS ID is required';
          return $this->apiresponse($data);
        }
        $row = $this->CM->getRowData(CUSTOMER,array('customer_its'=>$this->request->getVar('its')));
        if($row)
        {
          $data['status'] = 1;
          $data['message'] = 'Logged in successfully';
        }
        else
        {
          $data['status'] = 0;
          $data['message'] = 'User not found';
        }
        $data['data'] = $row;
        return $this->apiresponse($data);
    }
}

// https://www.positronx.io/codeigniter-rest-api-tutorial-with-example/
// https://onlinewebtutorblog.com/basic-auth-rest-api-development-in-codeigniter-4/
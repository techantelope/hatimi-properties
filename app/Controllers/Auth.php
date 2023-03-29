<?php
namespace App\Controllers;
use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\API\ResponseTrait;
use CodeIgniter\HTTP\RequestInterface;
use App\Models\AuthModel;

class Auth extends ResourceController
{
    public function __construct()
    {
        helper(['form', 'url','common_helper']);
        $this->validation =  \Config\Services::validation();
        $this->request = \Config\Services::request();
        $this->post = (array)$this->request->getVar();
        $this->AutM = new AuthModel();
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
        $requiredFields = array( 'username','password','type' );
        $isValid = $this->requiredCheck($requiredFields);
 
        
        if ($isValid['status']) 
        {
            $data = $this->AutM->login($this->post);
        }else{
            $data['status'] = 0;
            $data['data'] = $isValid['data'];
        }
        return $this->apiresponse($data);
    }
}

// https://www.positronx.io/codeigniter-rest-api-tutorial-with-example/
// https://onlinewebtutorblog.com/basic-auth-rest-api-development-in-codeigniter-4/
<?php
namespace App\Controllers;
use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\API\ResponseTrait;
use CodeIgniter\HTTP\RequestInterface;
use App\Models\ItsAuthModel;

class Its_auth extends ResourceController
{
    public function __construct()
    {
        helper(['form', 'url','common_helper']);
        $this->validation =  \Config\Services::validation();
        $this->request = \Config\Services::request();
        $this->post = (array)$this->request->getVar();
        $this->ItsAutM = new ItsAuthModel();
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
        $requiredFields = array( 'sid','token','dt' );
        $isValid = $this->requiredCheck($requiredFields);
 
        
        if ($isValid['status']) 
        {
            $data = $this->ItsAutM->login($this->post);
        }else{
            $data['status'] = 0;
            $data['data'] = $isValid['data'];
        }
        return $this->respond($data);
    }
}

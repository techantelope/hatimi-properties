<?php
namespace App\Controllers;
use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\API\ResponseTrait;
use CodeIgniter\HTTP\RequestInterface;
use App\Models\CustomerModel;
use App\Models\AdminModel;

class Customer extends ResourceController
{
    public function __construct()
    {
        helper(['form', 'url','common_helper']);
        $this->validation =  \Config\Services::validation();
        $this->request = \Config\Services::request();
        $this->post = (array)$this->request->getVar();
        $this->CST = new CustomerModel();
        $this->AM = new AdminModel();
    }

    function requiredCheck($fields){
        if($fields){
            $validation =  \Config\Services::validation();
            $rules = array();
            foreach ($fields as $field) {
                $rules[$field] = array(
                                        "label" => lang('App.'.$field), 
                                        "rules" => "required",
                                        'errors' => [
                                                        'required' => lang('App.'.$field).lang('App.is_required')
                                                    ]
                                        );
            }
            if ($this->validate($rules)) 
            {
                $data['status'] = 1;
                return $data;
            }else{
                $data['status'] = 0;
                $data['data'] = $this->validation->getErrors();
                return $data;
            }
        }
        $data['status'] = 1;
        return $data;
    }

    function basicCheck($post){
        $requiredFields = array( 'admin_uid' );
        $isValid = $this->requiredCheck($requiredFields);

        if ($isValid['status']) 
        {
            $data = $this->AM->profile($post);
            if($data['status']){
                return $data;
            }else{
                $this->apiresponse($data);
            }
        }else{
            $data['status'] = 0;
            $data['data'] = $isValid['data'];
            $this->apiresponse($data);
        }
    }

    public function list()
    {   
        $post = $this->post;
        $details = $this->basicCheck($post);
        $post['details'] = $details['data'];

        $result = $this->Amenity_M->list($post);
        $this->apiresponse($result);
    }

    public function detailByITS()
    {   
        $requiredFields = array('customer_its');
        $isValid = $this->requiredCheck($requiredFields);
        if ($isValid['status'] == 0) 
        {
            $data['status'] = 0;
            $data['data'] = $isValid['data'];
            $this->apiresponse($data);
        }

        $post = $this->post;
        $details = $this->basicCheck($post);
        $post['details'] = $details['data'];

        $result = $this->CST->detailByITS($post['customer_its'],'customer_uid,customer_its,customer_fname,customer_mname,customer_lname,customer_email,customer_phone,customer_addedon,customer_modifiedon,customer_lastloginon,customer_status');
        $this->apiresponse($result);
    }

    public function add()
    {
        $requiredFields = array('customer_its','fname','email','phone');
        $isValid = $this->requiredCheck($requiredFields);
        if ($isValid['status'] == 0) 
        {
            $data['status'] = 0;
            $data['data'] = $isValid['data'];
            $this->apiresponse($data);
        }
        $post = $this->post;

        $details = $this->basicCheck($post);
        $post['details'] = $details['data'];

        $result = $this->CST->add($post);
        $this->apiresponse($result);
    }

    public function guestList()
    {   
        $post = $this->post;
        $details = $this->basicCheck($post);

        $result = $this->CST->guestList($post);
        $this->apiresponse($result);
    }

    public function contactUsList()
    {   
        $post = $this->post;
        $details = $this->basicCheck($post);

        $result = $this->CST->contactUsList($post);
        $this->apiresponse($result);
    }
}
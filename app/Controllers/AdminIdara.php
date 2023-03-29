<?php
namespace App\Controllers;
use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\API\ResponseTrait;
use CodeIgniter\HTTP\RequestInterface;
use App\Models\AdminIdaraModel;
use App\Models\AdminModel;

class AdminIdara extends ResourceController
{
    public function __construct()
    {
        helper(['form', 'url','common_helper']);
        $this->validation =  \Config\Services::validation();
        $this->request = \Config\Services::request();
        $this->post = (array)$this->request->getVar();
        $this->AIM = new AdminIdaraModel();
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

        $result = $this->AIM->list($post);
        $this->apiresponse($result);
    }

    public function updateIdara()
    {   
        $post = $this->post;
        $details = $this->basicCheck($post);
        $post['details'] = $details['data'];

        $result = $this->AIM->updateIdara($post);
        $this->apiresponse($result);
    }

    public function deleteIdara()
    {
        $post = $this->post;
        $details = $this->basicCheck($post);
        $post['details'] = $details['data'];
        
        $requiredFields = array('idara_uid');
        $isValid = $this->requiredCheck($requiredFields);
        if ($isValid['status'] == 0) 
        {
            $data['status'] = 0;
            $data['data'] = $isValid['data'];
            $this->apiresponse($data);
            return;
        }

        $post = $this->post;
        $result = $this->AIM->deleteByUid($post['idara_uid']);
        $resData = array();
        if($result)
        {
          $resData['status'] = '1';
          $resData['message'] = lang('App.deleted_success');
        }
        else{
          $resData['status'] = '0';
          $resData['message'] = lang('App.try_again');
        }
        $this->apiresponse($resData);
    }

    public function detailsByUid()
    {   
        $post = $this->post;
        $details = $this->basicCheck($post);
        $post['details'] = $details['data'];

        $requiredFields = array('idara_uid');
        $isValid = $this->requiredCheck($requiredFields);
        if ($isValid['status'] == 0) 
        {
            $data['status'] = 0;
            $data['data'] = $isValid['data'];
            $this->apiresponse($data);
            return;
        }

        $result = $this->AIM->detailsByUid($post['idara_uid']);
        $res = array();
        $res['status']=1;
        $res['data']=$result;
        $this->apiresponse($res);
    }

    public function checkIdaraCustomer()
    {   
        $post = $this->post;
        $details = $this->basicCheck($post);
        $post['details'] = $details['data'];

        $requiredFields = array('customer_uid');
        $isValid = $this->requiredCheck($requiredFields);
        if ($isValid['status'] == 0) 
        {
            $data['status'] = 0;
            $data['data'] = $isValid['data'];
            $this->apiresponse($data);
            return;
        }

        $result = $this->AIM->checkIdaraCustomer($post);
        $this->apiresponse($result);
    }
}
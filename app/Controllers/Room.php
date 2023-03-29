<?php
namespace App\Controllers;
use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\API\ResponseTrait;
use CodeIgniter\HTTP\RequestInterface;
use App\Models\RoomModel;
use App\Models\AdminModel;

class Room extends ResourceController
{
    public function __construct()
    {
        helper(['form', 'url','common_helper']);
        $this->validation =  \Config\Services::validation();
        $this->request = \Config\Services::request();
        $this->post = (array)$this->request->getVar();
        $this->RM = new RoomModel();
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

        $result = $this->RM->list($post);
        $this->apiresponse($result);
    }

    public function listByProperty()
    {   
        $requiredFields = array('property_uid');
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

        $result = $this->RM->listByProperty($post);
        $this->apiresponse($result);
    }

    public function updateStatus()
    {   
        $post = $this->post;
        $details = $this->basicCheck($post);
        $post['details'] = $details['data'];

        $result = $this->RM->updateStatus($post);
        $this->apiresponse($result);
    }

    public function add()
    {
        $requiredFields = array('property_uid','number','type','capacity_min','capacity_max','rent');
        $isValid = $this->requiredCheck($requiredFields);
        if ($isValid['status'] == 0) 
        {
            $data['status'] = 0;
            $data['data'] = $isValid['data'];
            $this->apiresponse($data);
        }

        $post = $this->post;

        if($post['capacity_min'] > $post['capacity_max'])
        {
          $data['status'] = 0;
          $data['data'] = lang('App.capacity_min_cannot_be_greater_than_capacity_max');
          $this->apiresponse($data); 
        }
        $details = $this->basicCheck($post);
        $post['details'] = $details['data'];

        $result = $this->RM->add($post);
        $this->apiresponse($result);
    }

    function assignedNumbersByProperty(){
        $requiredFields = array('property_uid');
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

        $result = $this->RM->assignedNumbersByProperty($post);
        $this->apiresponse($result);
    }

    public function details()
    {   
        $requiredFields = array('room_uid');
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

        $result = $this->RM->details($post);
        $this->apiresponse($result);
    }
}
<?php
namespace App\Controllers;
use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\API\ResponseTrait;
use CodeIgniter\HTTP\RequestInterface;
use App\Models\AdminModel;
use App\Models\BookingsModel;

class Admin extends ResourceController
{
    public function __construct()
    {
        helper(['form', 'url','common_helper']);
        $this->validation =  \Config\Services::validation();
        $this->request = \Config\Services::request();
        $this->post = (array)$this->request->getVar();
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
        return true;
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

    public function profile()
    {   
        $requiredFields = array( 'admin_uid' );
        $isValid = $this->requiredCheck($requiredFields); 
        
        if ($isValid['status']) 
        {
            $data = $this->AM->profile($this->post);
            unset($data['data']['admin_id']);
            unset($data['data']['admin_addedon']);
            unset($data['data']['admin_type']);
            unset($data['data']['admin_password']);
        }else{
            $data['status'] = 0;
            $data['data'] = $isValid['data'];
        }
        $this->apiresponse($data);
    }

    public function updateProfile()
    {
        $requiredFields = array( 'admin_uid','fname','lname','mname','email','phone','its_id' );
        $isValid = $this->requiredCheck($requiredFields);

        $post = $this->post;
        $details = $this->basicCheck($post);
        $post['details'] = $details['data'];
        
        if ($isValid['status']) 
        {
            $data = $this->AM->updateProfile($post);
        }else{
            $data['status'] = 0;
            $data['data'] = $isValid['data'];
        }
        $this->apiresponse($data);
    }

    public function changePassword()
    {
        $requiredFields = array( 'admin_uid','password','confirm_password','current_password' );
        $isValid = $this->requiredCheck($requiredFields);
        $post = $this->post;
        $details = $this->basicCheck($post);
        $post['details'] = $details['data'];
        
        if ($isValid['status']) 
        {
            $data = $this->AM->changePassword($post);
        }else{
            $data['status'] = 0;
            $data['data'] = $isValid['data'];
        }
        $this->apiresponse($data);
    }

    function dashboard(){
        $post = $this->post;
        $details = $this->basicCheck($post);
        $post['details'] = $details['data'];

        $result = $this->AM->getDashboardData($post);
        $this->apiresponse($result);
    }

    function currentBookings(){
        $post = $this->post;
        $details = $this->basicCheck($post);
        $post['details'] = $details['data'];
        $post['book_list'] = 'current';

        $this->BK = new BookingsModel();
        $result = $this->BK->getBookings($post);
        $this->apiresponse($result);
    }

    function allBookings(){
        $post = $this->post;
        $details = $this->basicCheck($post);
        $post['details'] = $details['data'];
        $post['book_list'] = 'all';

        $this->BK = new BookingsModel();
        $result = $this->BK->getBookings($post);
        $this->apiresponse($result);
    }
}
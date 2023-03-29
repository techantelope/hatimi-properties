<?php
namespace App\Controllers;
use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\API\ResponseTrait;
use CodeIgniter\HTTP\RequestInterface;
use App\Models\FrontendModel;
use App\Models\AdminIdaraModel;

class Frontend extends ResourceController
{
    public function __construct()
    {
        helper(['form', 'url','common_helper']);
        $this->validation =  \Config\Services::validation();
        $this->request = \Config\Services::request();
        $this->post = (array)$this->request->getVar();
        $this->FEM = new FrontendModel();
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
        $requiredFields = array( 'customer_uid' );
        $isValid = $this->requiredCheck($requiredFields);

        if ($isValid['status']) 
        {
            $data = $this->FEM->profile($post);
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
        $requiredFields = array( 'customer_uid' );
        $isValid = $this->requiredCheck($requiredFields); 
        
        if ($isValid['status']) 
        {
            $data = $this->FEM->profile($this->post);
            unset($data['data']['customer_id']);
            unset($data['data']['customer_addedon']);
            unset($data['data']['customer_status']);
            unset($data['data']['customer_modifiedon']);
            unset($data['data']['customer_lastloginon']);
            unset($data['data']['customer_logininfo']);
        }else{
            $data['status'] = 0;
            $data['data'] = $isValid['data'];
        }
        $this->apiresponse($data);
    }

    public function updateProfile()
    {
        $requiredFields = array( 'customer_uid','fname','lname','mname','email','phone' );
        $isValid = $this->requiredCheck($requiredFields);

        $post = $this->post;
        $details = $this->basicCheck($post);
        $post['details'] = $details['data'];
        
        if ($isValid['status']) 
        {
            $data = $this->FEM->updateProfile($post);
        }else{
            $data['status'] = 0;
            $data['data'] = $isValid['data'];
        }
        $this->apiresponse($data);
    }

    public function searchRooms()
    {   
        $requiredFields = array('property_uid','check_in','check_out','adults','children','room_type');
        $isValid = $this->requiredCheck($requiredFields);
        if ($isValid['status'] == 0) 
        {
            $data['status'] = 0;
            $data['data'] = $isValid['data'];
            $this->apiresponse($data);
        }
        
        $post = $this->post;
        /*$details = $this->basicCheck($post);
        $post['details'] = $details['data'];*/

        $result = $this->FEM->searchRooms($post);
        $this->apiresponse($result);
    }

    public function getPaymentURL()
    {
        $requiredFields = array('amount_paid');
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

        // $result = $this->FEM->getPaymentURL($post);
        $result['status'] = 1;
        $result['message'] = lang('App.success');
        $this->apiresponse($result);
    }

    public function fetchTransfer()
    {
        $requiredFields = array('payment_id','payment_link_id','amount_paid');
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

        $result = $this->FEM->fetchTransfer($post['payment_id'],$post['payment_link_id'],$post['amount_paid']);
        $this->apiresponse($result);
    }

    public function normalRefund()
    {
        $requiredFields = array('payment_id','amount_paid');
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

        $result = $this->FEM->normalRefund($post['payment_id'],$post['amount_paid']);
        $this->apiresponse($result);
    }

    /*public function addBookingOld()
    {   
        $requiredFields = array('customer_uid','property_uid','check_in','check_out','adults','children','sgst','cgst','total','amount_paid','payment_id','payment_link_id');
        $isValid = $this->requiredCheck($requiredFields);
        if ($isValid['status'] == 0) 
        {
            $data['status'] = 0;
            $data['data'] = $isValid['data'];
            $this->apiresponse($data);
        }

        $post = $this->post;
        if(!isset($post['rooms_and_extra_beds']))
        {
            $data['status'] = 0;
            $data['data'] = 'Room is required';
            $this->apiresponse($data);
        }

        $post = $this->post;
        $details = $this->basicCheck($post);
        $post['details'] = $details['data'];

        $result = $this->FEM->addBookingOld($post);
        $this->apiresponse($result);
    }*/

    public function addBooking()
    {   
        $requiredFields = array('customer_uid','property_uid','check_in','check_out','adults','children','sgst','cgst','total','amount_paid','transaction_id');
        $isValid = $this->requiredCheck($requiredFields);
        if ($isValid['status'] == 0) 
        {
            $data['status'] = 0;
            $data['data'] = $isValid['data'];
            $this->apiresponse($data);
        }

        $post = $this->post;
        if(!isset($post['rooms_and_extra_beds']))
        {
            $data['status'] = 0;
            $data['data'] = 'Room is required';
            $this->apiresponse($data);
        }

        $post = $this->post;
        $details = $this->basicCheck($post);
        $post['details'] = $details['data'];

        $result = $this->FEM->addBooking($post);
        $this->apiresponse($result);
    }

    public function cancelBooking()
    {   
        $requiredFields = array('booking_uid');
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

        $result = $this->FEM->cancelBooking($post);
        $this->apiresponse($result);
    }

    /*public function cancelBookingOld()
    {   
        $requiredFields = array('booking_uid');
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

        $result = $this->FEM->cancelBooking($post);
        $this->apiresponse($result);
    }*/

    public function getBookings()
    {   
        $post = $this->post;
        $details = $this->basicCheck($post);
        $post['details'] = $details['data'];

        $result = $this->FEM->getBookings($post);
        $this->apiresponse($result);
    }

    public function getBookingDetails()
    {   
        $requiredFields = array('booking_uid');
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

        $result = $this->FEM->getBookingDetails($post);
        $this->apiresponse($result);
    }

    public function getAllActiveProperties()
    {   
        $post = $this->post;
        /*$details = $this->basicCheck($post);
        $post['details'] = $details['data'];*/

        $result = $this->FEM->getAllActiveProperties($post);
        $this->apiresponse($result);
    }

    public function getProperty()
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
        /*$details = $this->basicCheck($post);
        $post['details'] = $details['data'];*/

        $result = $this->FEM->getProperty($post);
        $this->apiresponse($result);
    }

    public function getRoom()
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
        /*$details = $this->basicCheck($post);
        $post['details'] = $details['data'];*/

        $result = $this->FEM->getRoom($post);
        $this->apiresponse($result);
    }

    public function contactUs()
    {   
        $requiredFields = array('name','email','message');
        $isValid = $this->requiredCheck($requiredFields);
        if ($isValid['status'] == 0) 
        {
            $data['status'] = 0;
            $data['data'] = $isValid['data'];
            $this->apiresponse($data);
        }

        $post = $this->post;
        /*$details = $this->basicCheck($post);
        $post['details'] = $details['data'];*/

        $result = $this->FEM->contactUs($post);
        $this->apiresponse($result);
    }

    public function addReview()
    {   
        $requiredFields = array('customer_uid','booking_uid','room_uid','review');
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

        $result = $this->FEM->addReview($post);
        $this->apiresponse($result);
    }

    public function getAllReviews()
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
        /*$details = $this->basicCheck($post);
        $post['details'] = $details['data'];*/

        $result = $this->FEM->getAllReviews($post);
        $this->apiresponse($result);
    }

    public function getPropertyInfo()
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
        $result = $this->FEM->getPropertyInfo($post);
        $this->apiresponse($result);
    }

    public function getAvailability()
    {   
        $requiredFields = array('check_in','check_out','property_uid');
        $isValid = $this->requiredCheck($requiredFields);
        if ($isValid['status'] == 0) 
        {
            $data['status'] = 0;
            $data['data'] = $isValid['data'];
            $this->apiresponse($data);
        }

        $post = $this->post;
        /*$details = $this->basicCheck($post);
        $post['details'] = $details['data'];*/

        $result = $this->FEM->getAvailability($post);
        $this->apiresponse($result);
    }

    public function checkIdaraCustomer()
    {   
        $requiredFields = array('customer_uid');
        $isValid = $this->requiredCheck($requiredFields);
        if ($isValid['status'] == 0) 
        {
            $data['status'] = 0;
            $data['data'] = $isValid['data'];
            $this->apiresponse($data);
        }
        
        $this->AIM = new AdminIdaraModel();
        $post = $this->post;
        $result = $this->AIM->checkIdaraCustomer($post);
        $this->apiresponse($result);
    }
}
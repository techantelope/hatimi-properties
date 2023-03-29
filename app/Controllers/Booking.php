<?php
namespace App\Controllers;
use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\API\ResponseTrait;
use CodeIgniter\HTTP\RequestInterface;
use App\Models\BookingsModel;
use App\Models\AdminModel;

class Booking extends ResourceController
{
    public function __construct()
    {
        helper(['form', 'url','common_helper']);
        $this->validation =  \Config\Services::validation();
        $this->request = \Config\Services::request();
        $this->post = (array)$this->request->getVar();
        $this->BM = new BookingsModel();
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

    public function adminAddBooking()
    {   
        $requiredFields = array('customer_uid','property_uid','check_in','check_out','adults','children','sgst','cgst','total','amount_paid');
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

        $result = $this->BM->adminAddBooking($post);
        $this->apiresponse($result);
    }

    public function adminSearchRooms()
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
        $details = $this->basicCheck($post);
        $post['details'] = $details['data'];

        $result = $this->BM->adminSearchRooms($post);
        $this->apiresponse($result);
    }

    public function adminCancelBooking()
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

        $result = $this->BM->adminCancelBooking($post);
        $this->apiresponse($result);
    }

    public function adminGetBookings()
    {   
        $post = $this->post;
        $details = $this->basicCheck($post);
        $post['details'] = $details['data'];

        $result = $this->BM->adminGetBookings($post);
        $this->apiresponse($result);
    }

    public function adminGetBookingDetails()
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

        $result = $this->BM->adminGetBookingDetails($post);
        $this->apiresponse($result);
    }

    public function adminGetAllReviews()
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

        $result = $this->BM->adminGetAllReviews($post);
        $this->apiresponse($result);
    }

    function adminApproveBooking(){
      $requiredFields = array('booking_uid','payment_uid');
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

        $result = $this->BM->adminApproveBooking($post);
        $this->apiresponse($result);
    }

    function adminApproveRefund(){
      $requiredFields = array('booking_uid','payment_uid');
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

        $result = $this->BM->adminApproveRefund($post);
        $this->apiresponse($result);
    }

    public function adminGetAvailability()
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

        $result = $this->BM->adminGetAvailability($post);
        $this->apiresponse($result);
    }
}
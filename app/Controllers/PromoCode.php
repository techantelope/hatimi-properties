<?php
namespace App\Controllers;
use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\API\ResponseTrait;
use CodeIgniter\HTTP\RequestInterface;
use App\Models\PromoCodeModel;
use App\Models\FrontPromoCodeModel;
use App\Models\AdminModel;

class PromoCode extends ResourceController
{
    public function __construct()
    {
        helper(['form', 'url','common_helper']);
        $this->validation =  \Config\Services::validation();
        $this->request = \Config\Services::request();
        $this->post = (array)$this->request->getVar();
        $this->PCM = new PromoCodeModel();
        $this->FPCM = new FrontPromoCodeModel();
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

        $result = $this->PCM->list($post);
        $this->apiresponse($result);
    }

    public function updateStatus()
    {   
        $post = $this->post;
        $details = $this->basicCheck($post);
        $post['details'] = $details['data'];

        $result = $this->PCM->updateStatus($post);
        $this->apiresponse($result);
    }

    public function updatePromoCode()
    {
        $requiredFields = array('promo_code','discount','validity');
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

        $result = $this->PCM->updatePromoCode($post);
        $this->apiresponse($result);
    }

    public function details()
    {   
        $post = $this->post;
        $details = $this->basicCheck($post);
        $post['details'] = $details['data'];

        $result = $this->PCM->details($post);
        $this->apiresponse($result);
    }

    public function detailsByCode()
    {   
        $post = $this->post;
        $details = $this->basicCheck($post);
        $post['details'] = $details['data'];

        $result = $this->FPCM->details($post);
        $this->apiresponse($result);
    }
}
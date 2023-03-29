<?php
namespace App\Controllers;
use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\API\ResponseTrait;
use CodeIgniter\HTTP\RequestInterface;
use App\Models\UploadModel;

class UploadFile extends ResourceController
{
    public function __construct()
    {        
        helper(['form', 'url','common_helper']);
        $this->validation =  \Config\Services::validation();
        $this->request = \Config\Services::request();
        $this->post = (array)$this->request->getVar();
        // $this->PM = new PropertyModel();
        $this->UM = new UploadModel();
    }

    public function upload()
    {
        $result = $this->UM->uploadImage('filename','./uploads/temp/','image');
        return $this->respond($result);
    }
}

// https://www.positronx.io/codeigniter-rest-api-tutorial-with-example/
// https://onlinewebtutorblog.com/basic-auth-rest-api-development-in-codeigniter-4/
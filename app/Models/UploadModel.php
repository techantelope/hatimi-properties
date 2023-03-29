<?php
namespace App\Models;
use CodeIgniter\Model;
use CodeIgniter\Files\File;

class UploadModel extends Model
{
    function __construct(){
        // $this->db      = \Config\Database::connect();   
        $this->CM = new CommonModel();
        helper(['form', 'url']);
        $this->request = \Config\Services::request();

    }

    function uploadImage($filename,$path,$type='image'){
        if(!file_exists($path)) 
        {
            mkdir($path, 0777, true);
        }
        if($type=='file'){
            $allowedTypes = 'PDF|pdf|doc|docx';
        }elseif($type=='image'){
            $allowedTypes = 'image/jpg,image/jpeg,image/gif,image/png,image/webp';
        }elseif($type=='audio'){
            $allowedTypes = '*';
        }elseif($type=='video'){
            $allowedTypes = '*';
        }elseif($type=='any'){
            $allowedTypes = 'image/jpg,image/jpeg,image/gif,image/png,image/webp';
        }elseif($type=='any-bs'){
            $allowedTypes = 'image/jpg,image/jpeg,image/gif,image/png,image/webp|PDF|pdf';
        }

        $validationRule = [
                'userfile' => [
                    'label' => 'File',
                    'rules' => 'uploaded['.$filename.']'
                        . '|is_image['.$filename.']'
                        . '|mime_in['.$filename.','.$allowedTypes.']',
                ],
            ];
        if (! $this->validate($validationRule)) {

            $resData['status'] = 0;
            $resData['data'] = $this->validator->getErrors();
            return $resData;
        } else {

            $img = $this->request->getFile($filename);
            $ext = $img->getClientExtension();
            $newName = $img->getRandomName();
            $img->move($path . $newName);

            $fileUniqueName = uniqid().".$ext";
            $dirname = $path . $newName;
            copy($dirname."/".$_FILES[$filename]['name'], $path.'/'.$fileUniqueName);
            array_map('unlink', glob("$dirname/*.*"));
            rmdir($dirname);
    
            /*$uploadData = [
               'name' =>  $img->getName(),
               'type'  => $img->getClientMimeType()
            ];*/
            $uploadData = [
               'name' =>  $fileUniqueName,
               'path'  => BASEURL.str_replace('./', '', $path)
            ];
    
            $resData['status'] = 1;
            $resData['data'] = $uploadData;
            return $resData;
        }
    }
}

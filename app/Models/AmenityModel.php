<?php
namespace App\Models;
use CodeIgniter\Model;

class AmenityModel extends Model
{
    function __construct(){
        $this->db      = \Config\Database::connect();   
        $this->CM = new CommonModel();
    }

    function list($post){    
        $amenity = $this->db->table(AMENITY);
        $amenity->select('amenity_uid,amenity');
        $amenity->where('amenity_status',1);
        
        $result = $amenity->get()->getResultArray();
        // echo $this->db->getLastQuery();

        $res['status'] = 1;
        $res['data'] = $result;
        $res['message'] = lang('App.success');
        return $res;            
    }

    function add($post){
      $amenity = $post['amenity'];
      $amenityUid = !empty($post['amenity_uid'])?$post['amenity_uid']:'';
      $datetime = date('Y-m-d H:i:s');
      
      $updateArr = array('amenity'=>$amenity,'amenity_datetime_modified'=>$datetime);

      if($amenityUid)
      {
        $isExist = $this->getByUid($amenityUid,'amenity_id');
        $amenityId = !empty($isExist['amenity_id'])?$isExist['amenity_id']:'';
        if(empty($amenityId))
        {
          $res['status'] = 0;
          $res['data'] = '';
          $res['message'] = lang('App.amenity_not_exist');
          return $res;
        }

        $this->CM->updateData(AMENITY,array('amenity_id'=>$amenityId),$updateArr);
      }
      else
      {
        $isExist = $this->getByName($amenity,'amenity_id');
        $amenityId = !empty($isExist['amenity_id'])?$isExist['amenity_id']:'';
        if(!empty($amenityId))
        {
          $res['status'] = 0;
          $res['data'] = '';
          $res['message'] = lang('App.amenity_exist');
          return $res;
        }

        $amenityUid = generateUid();
        $updateArr['amenity_datetime_added'] = $datetime;
        $updateArr['amenity_uid'] = $amenityUid;
        $updateArr['amenity_status'] = $datetime;
        $this->CM->insertData(AMENITY,$updateArr);
      }

      $res['status'] = 1;
      $res['amenity_uid'] = $amenityUid;
      $res['message'] = lang('App.update_success');
      return $res; 
    }

    function updateStatus($post){
        $status = $post['status'];
        $amenityUid = $post['amenity_uid'];
        
        $details = $this->getByUid($amenityUid,'amenity_id');
        $amenityId = !empty($isExist['amenity_id'])?$isExist['amenity_id']:'';
        if(empty($amenityId))
        {
          $res['status'] = 0;
          $res['data'] = '';
          $res['message'] = lang('App.amenity_not_exist');
          return $res;
        }

        $data = array();
        if($status == 'activate'){
            $data['amenity_status'] = 1;
        }
        elseif($status == 'deactivate'){
            $data['amenity_status'] = 0;
        }
        elseif($status == 'delete'){
            $data['amenity_status'] = 2;
        }
        if($data){
            $updateStatus = $this->CM->updateData(AMENITY,array('amenity_id'=>$amenityId),$data);
            $res['status'] = 1;
            $res['message'] = $updateStatus?lang('App.update_success'):lang('App.up_to_date');
            return $res;
        }else{
            $res['status'] = 0;
            $res['message'] = lang('App.something_wrong');
            return $res;
        }
    }

    function getByUid($amenityUid,$column='*')
    {
      $amenity = $this->db->table(AMENITY);
      $amenity->select($column);
      $amenity->where('amenity_status !=',2);
      $amenity->where('amenity_uid',$amenityUid);
      $row = $amenity->get()->getRowArray();
      return $row;
    }

    function getById($amenityId,$column='*')
    {
      $amenity = $this->db->table(AMENITY);
      $amenity->select($column);
      $amenity->where('amenity_status !=',2);
      $amenity->where('amenity_id',$amenityId);
      $row = $amenity->get()->getRowArray();
      return $row;
    }

    function getByName($name,$column='*')
    {
      $amenity = $this->db->table(AMENITY);
      $amenity->select($column);
      $amenity->where('amenity_status !=',2);
      $amenity->where('amenity',$name);
      $row = $amenity->get()->getRowArray();
      return $row;
    }

}

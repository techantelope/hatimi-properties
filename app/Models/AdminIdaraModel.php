<?php
namespace App\Models;
use CodeIgniter\Model;

class AdminIdaraModel extends Model
{
    function __construct(){
        $this->db      = \Config\Database::connect();   
        $this->CM = new CommonModel();
    }

    function list($post){

        $builder = $this->db->table(IDARA);
        $builder->select('idara_uid,name,discount');
        $data = $builder->get()->getResultArray();
        
        $res['status'] = 1;
        $res['data'] = $data;
        $res['message'] = lang('App.success');
        return $res;
    }

    function detailsByUid($uid){

        $builder = $this->db->table(IDARA);
        $builder->select('idara_uid,name,discount');
        $builder->where('idara_uid',$uid);
        $data = $builder->get()->getRowArray();
        return $data;
    }

    function detailsById($id){

        $builder = $this->db->table(IDARA);
        $builder->select('idara_uid,name,discount');
        $builder->where('idara_id',$id);
        $data = $builder->get()->getRowArray();
        return $data;
    }

    function deleteByUid($uid){

        $builder = $this->db->table(IDARA);
        $builder->where('idara_uid',$uid);
        $data = $builder->delete();
        return true;
    }

    function updateIdara($post){
      $idaraUid = !empty($post['idara_uid'])?$post['idara_uid']:'';
      $name = !empty($post['name'])?$post['name']:'';
      $discount = !empty($post['discount'])?$post['discount']:'';

      if($name == '')
      {
        return array('status'=>0,'message'=>lang('App.idara_name_required'));
      }

      $insData = array();
      $insData['name'] = $name;
      $insData['discount'] = $discount;
      if($idaraUid)
      {
        $builder = $this->db->table(IDARA);
        $builder->select('idara_id');
        $builder->where('name',$name);
        $builder->where('idara_uid !=',$idaraUid);
        $data = $builder->get()->getRowArray();
        if($data){
          return array('status'=>0,'message'=>lang('App.idara_name_exist'));
        }

        $this->CM->updateData(IDARA,array('idara_uid'=>$idaraUid),$insData);
      }
      else
      {
        $builder = $this->db->table(IDARA);
        $builder->select('idara_id');
        $builder->where('name',$name);
        $data = $builder->get()->getRowArray();
        if($data){
          return array('status'=>0,'message'=>lang('App.idara_name_exist'));
        }

        $idaraUid = uniqid();
        $insData['idara_uid'] = $idaraUid;
        $this->CM->insertData(IDARA,$insData);
      }
      return array('status'=>1,'idara_uid'=>$idaraUid,'message'=>lang('App.update_success'));
    }

    function checkIdaraCustomer($post){
      $customerUid = $post['customer_uid'];
      
      $details = $this->CM->getRowData(CUSTOMER,array('customer_uid'=>$customerUid));
      if($details)
      { 
        $builder = $this->db->table(PROMO_CODE);
        $builder->select('pc_uid,pc_id,promo_code,discount,status,added_date,valid_till,validity,max_amount,description');
        $builder->where('promo_code','IDARA');
        $pc = $builder->get()->getRowArray();

        $idara = $this->CM->getRowData(IDARA,array('name'=>$details['idara']));
        if($idara && $pc)
        { 
          $res = array();
          $res['status'] = 1;
          $res['data'] = $pc;
          return $res;
        }
        else
        {
          $res = array();
          $res['status'] = 0;
          $res['message'] = lang('App.no_discount');
          return $res;
        }
      }      
      else
      {
        $res = array();
        $res['status'] = 0;
        $res['message'] = lang('App.customer_not_found');
        return $res;
      }
    }
}

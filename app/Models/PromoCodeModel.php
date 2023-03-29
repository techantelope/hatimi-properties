<?php
namespace App\Models;
use CodeIgniter\Model;

class PromoCodeModel extends Model
{
    function __construct(){
        $this->db      = \Config\Database::connect();   
        $this->CM = new CommonModel();
    }

    function list($post){

        $sort_field = 'PC.added_date';
        $sort_order = 'ASC';
        $limit      = 50;
        $page       = 0;

        if(isset($post['items_perpage']))
        {
            $limit = $post['items_perpage'];
        }

        if(isset($post['current_page']))
        {
            $page = $post['current_page']-1;
        }

        $sortFieldArray = array('added_date','discount','promo_code');
        if(isset($post['sort_field']) && in_array($post['sort_field'],$sortFieldArray))
        {
            $sort_field = 'PC.'.$post['sort_field'];
        }

        if(isset($post['sort_order']) && in_array($post['sort_order'],array('DESC','ASC')))
        {
            $sort_order = $post['sort_order'];
        }

        $offset = $limit * $page;

        $room = $this->db->table(PROMO_CODE.' PC');
        $room->select('PC.pc_uid,PC.promo_code,PC.discount,PC.status,PC.added_date,PC.valid_till,PC.validity,PC.max_amount,PC.description');
        $room->where('PC.status !=',2);

        $tempdb = clone $room; //to get rows for pagination
        $total = $tempdb->countAllResults();

        $room->limit($limit,$offset);
        $result = $room->get()->getResultArray();
        // echo $this->db->getLastQuery();
        
        $res['status'] = 1;
        $res['data'] = $result;
        $res['total'] = $total;
        $res['message'] = lang('App.success');
        return $res;
    }

    function updateStatus($post){
        $status = $post['status'];
        $pcUid = $post['pc_uid'];
        
        $pcDetails = $this->CM->getPromoCodeByUid($pcUid,'pc_id');

        if(empty($pcDetails['pc_id'])){
            $res['status'] = 0;
            $res['message'] = lang('App.details_not_found');
            return $res;
        }

        $data = array();
        if($status == 'activate'){
            $data['status'] = 1;
        }
        elseif($status == 'deactivate'){
            $data['status'] = 0;
        }
        elseif($status == 'delete'){
            $data['status'] = 2;
        }
        if($data){
            $updateStatus = $this->CM->updateData(PROMO_CODE,array('pc_id'=>$pcDetails['pc_id']),$data);
            $res['status'] = 1;
            $res['message'] = $updateStatus?lang('App.update_success'):lang('App.up_to_date');
            return $res;
        }else{
            $res['status'] = 0;
            $res['message'] = lang('App.something_wrong');
            return $res;
        }
    }

    function updatePromoCode($post){
        $promoCode = $post['promo_code'];
        $discount = $post['discount'];
        $validity = $post['validity'];
        $description = !empty($post['description'])?$post['description']:'';
        $maxAmount = !empty($post['max_amount'])?$post['max_amount']:'';
        $validTill = date('Y-m-d',strtotime("+$validity days"));
        $date = date('Y-m-d H:i:s');
        
        $dataArr = array(
                        'promo_code'=>$promoCode,
                        'discount'=>$discount,
                        'status'=>1,
                        'valid_till'=>$validity,
                        'validity'=>0,
                        'max_amount'=>$maxAmount,
                        'description'=>$description
                    );
        if(empty($post['pc_uid'])){
            $isExist = $this->isPromoCodeExist($promoCode);
            if($isExist){
                $res['status'] = 0;
                $res['data'] = array('pc_uid'=>'');
                $res['message'] = lang('App.promo_code_exist');
                return $res;
            }

            $pcUid = generateUid();
            $dataArr['pc_uid'] = $pcUid;
            $dataArr['added_date'] = $date;
            $pcId = $this->CM->insertData(PROMO_CODE,$dataArr);
        }else{
            $pcDetails = $this->CM->getPromoCodeByUid($post['pc_uid']);
            $pcUid = !empty($pcDetails['pc_uid'])?$pcDetails['pc_uid']:'';
            if(empty($pcDetails['pc_id'])){
                $res['status'] = 0;
                $res['message'] = lang('App.details_not_found');
                return $res;
            }

            if($pcDetails['promo_code'] != $promoCode){
                $isExist = $this->isPromoCodeExist($promoCode);
                if($isExist){
                    $res['status'] = 0;
                    $res['data'] = array('pc_uid'=>'');
                    $res['message'] = lang('App.promo_code_exist');
                    return $res;
                }
            }

            $pcId = $pcDetails['pc_id'];
            $this->CM->updateData(PROMO_CODE,array('pc_id'=>$pcId),$dataArr);
        }

        if($pcId){
            $res['status'] = 1;
            $res['data'] = array('pc_uid'=>$pcUid);
            $res['message'] = lang('App.update_success');
            return $res;
        }else{
            $res['status'] = 0;
            $res['data'] = array('pc_uid'=>'');
            $res['message'] = lang('App.something_wrong');
            return $res;
        }
    }

    function isPromoCodeExist($promoCode){
        $isExist = $this->CM->getRowData(PROMO_CODE,array('promo_code'=>$promoCode,'status !='=>2));
        if($isExist){
            return true;
        }else{
            return false;
        }
    }

    function details($post){
        $details = $this->CM->getPromoCodeByUid($post['pc_uid'],'pc_uid,promo_code,discount,status,added_date,valid_till,validity,max_amount,description');
        $data['status'] = 0;
        if($details){
            $data['status'] = 1;
        }

        $data['data'] = $details;
        return $data;
    }
}

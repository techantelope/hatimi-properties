<?php
namespace App\Models;
use CodeIgniter\Model;

class FrontPromoCodeModel extends Model
{
    function __construct(){
        $this->db      = \Config\Database::connect();   
        $this->CM = new CommonModel();
    }

    function details($post){
        $customer = $this->CM->getRowData( CUSTOMER , array( 'customer_uid'=> $post['customer_uid'] ),'customer_id');
        if(empty($customer['customer_id'])){
            $data['status'] = 0;
            $data['message'] = lang('App.customer_not_found');
            return $data;
        }
        $where = array(
            'promo_code'=>$post['promo_code'], 
            'promo_code !='=>'IDARA', 
            'status'=> 1,
            'valid_till >='=>date('Y-m-d')
        );
        $details = $this->CM->getRowData( PROMO_CODE , $where ,'pc_uid,pc_id,promo_code,discount,status,added_date,valid_till,validity,max_amount,description');
        if($details){
            $pcId = $details['pc_id'];

            $where = array(
                'customer_id'=>$customer['customer_id'],
                'promo_code_id'=>$pcId
            );
            $isApplied = $this->CM->getRowData( PROMO_CODE_USER , $where ,'upc_uid');
            if(!empty($isApplied['upc_uid']))
            {
                $data['status'] = 0;
                $data['message'] = lang('App.promo_code_applied');
                return $data;
            }
            else
            {
                unset($details['pc_id']);
                $data['status'] = 1;
                $data['message'] = '';
                $data['data'] = $details;
                return $data;
            }
        }
        else
        {
            $data['status'] = 0;
            $data['message'] = lang('App.invalid_promo_code');
            return $data;
        }
    }
}

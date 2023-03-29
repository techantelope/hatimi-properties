<?php
namespace App\Models;
use CodeIgniter\Model;

class CustomerModel extends Model
{
    function __construct(){
        $this->db      = \Config\Database::connect();   
        $this->CM = new CommonModel();
    }

    function list($post){

        $sort_field = 'C.customer_addedon';
        $sort_order = 'DESC';
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

        $sortFieldArray = array('customer_addedon','customer_fname','customer_phone');
        if(isset($post['sort_field']) && in_array($post['sort_field'],$sortFieldArray))
        {
            $sort_field = 'C.'.$post['sort_field'];
        }

        if(isset($post['sort_order']) && in_array($post['sort_order'],array('DESC','ASC')))
        {
            $sort_order = $post['sort_order'];
        }

        $offset = $limit * $page;

        $customer = $this->db->table(CUSTOMER.' C');
        $customer->select('C.customer_uid,C.customer_its,C.customer_fname,C.customer_mname,C.customer_lname,C.customer_email,C.customer_phone,C.customer_addedon,C.customer_modifiedon,C.customer_lastloginon,C.customer_status');
        if(!empty($post['search_keyword']))
        {
            $k = $post['search_keyword'];
            $customer->where("(C.customer_phone LIKE '%$k%' OR C.customer_fname LIKE '%$k%' OR C.customer_email LIKE '%$k%')");    
        }
        if(!empty($post['status']))
        {
            if($post['status'] == 'active'){
                $customer->where('C.customer_status','1');
            }
        }

        $tempdb = clone $customer; //to get rows for pagination
        $total = $tempdb->countAll();

        $customer->limit($limit,$offset);
        $result = $customer->get()->getResultArray();
        // echo $this->db->getLastQuery();

        if(!empty($result)){
            $res['status'] = 1;
            $res['data'] = $result;
            $res['total'] = $total;
            $res['message'] = lang('App.success');
            return $res;            
        }
        $res['total'] = $total;
        $res['status'] = 0;
        $res['data'] = '';
        $res['message'] = lang('App.details_not_found');
        return $res;                
    }

    function detailByITS($customerITS,$column='*'){

        $customer = $this->db->table(CUSTOMER);
        $customer->select($column);
        $customer->where('customer_its',$customerITS);
        $result = $customer->get()->getRowArray();

        if($result){
            $res['status'] = 1;
            $res['data'] = $result;
            $res['message'] = lang('App.success');
        }else{
            $res['status'] = 0;
            $res['message'] = lang('App.details_not_found');
        }

        return $res;            
    }

    function detailByUid($customerUid,$column='*'){

        $customer = $this->db->table(CUSTOMER);
        $customer->select($column);
        $customer->where('customer_uid',$customerUid);
        $result = $customer->get()->getRowArray();

        if($result){
            $res['status'] = 1;
            $res['data'] = $result;
            $res['message'] = lang('App.success');
        }else{
            $res['status'] = 0;
            $res['message'] = lang('App.details_not_found');
        }
        return $res;            
    }

    function detailById($customerId,$column='*'){

        $customer = $this->db->table(CUSTOMER);
        $customer->select($column);
        $customer->where('customer_id',$customerId);
        $result = $customer->get()->getRowArray();

        if($result){
            $res['status'] = 1;
            $res['data'] = $result;
            $res['message'] = lang('App.success');
        }else{
            $res['status'] = 0;
            $res['message'] = lang('App.details_not_found');
        }
        return $res;            
    }

    function add($post){
        $customerUidPost = !empty($post['customer_uid'])?$post['customer_uid']:'';
        $customerITS = $post['customer_its'];
        $fname = $post['fname'];
        // $lname = $post['lname'];
        // $mname = $post['mname'];
        $email = $post['email'];
        $phone = $post['phone'];
        $adminId = $post['details']['admin_id'];
        $date = date('Y-m-d H:i:s');
        $customerUid = generateUid();

        if($customerUidPost == ''){
            $details = $this->CM->getRowData(CUSTOMER,['customer_email'=>$email]);
            if(!empty($details['customer_id'])){
                $res['status'] = 1;
                $res['data'] = '';
                $res['message'] = lang('App.email_exist');
                return $res;
            }

            $details = $this->CM->getRowData(CUSTOMER,['customer_phone'=>$phone]);
            if(!empty($details['customer_id'])){
                $res['status'] = 1;
                $res['data'] = '';
                $res['message'] = lang('App.phone_exist');
                return $res;
            }

            $details = $this->CM->getRowData(CUSTOMER,['customer_its'=>$customerITS]);
            if(!empty($details['customer_id'])){
                $res['status'] = 1;
                $res['data'] = '';
                $res['message'] = lang('App.its_exist');
                return $res;
            }
        }else{
            $cDetails = $this->detailByUid($customerUidPost,'customer_id,customer_phone,customer_email,customer_its');
            $cDetails = $cDetails['data'];
            if($cDetails['customer_email'] != $email){
                $details = $this->CM->getRowData(CUSTOMER,['customer_email'=>$email]);
                if(!empty($details['customer_id'])){
                    $res['status'] = 1;
                    $res['data'] = '';
                    $res['message'] = lang('App.email_exist');
                    return $res;
                }
            }

            if($cDetails['customer_phone'] != $phone){
                $details = $this->CM->getRowData(CUSTOMER,['customer_phone'=>$phone]);
                if(!empty($details['customer_id'])){
                    $res['status'] = 1;
                    $res['data'] = '';
                    $res['message'] = lang('App.phone_exist');
                    return $res;
                }
            }

            if($cDetails['customer_its'] != $customerITS){
                $details = $this->CM->getRowData(CUSTOMER,['customer_its'=>$customerITS]);
                if(!empty($details['customer_id'])){
                    $res['status'] = 1;
                    $res['data'] = '';
                    $res['message'] = lang('App.its_exist');
                    return $res;
                }
            }
        }

        $dataArr = array(
                        'customer_its'=>$customerITS,
                        'customer_fname'=>$fname,
                        // 'customer_mname'=>$mname,
                        // 'customer_lname'=>$lname,
                        'customer_email'=>$email,
                        'customer_phone'=>$phone,
                        'customer_modifiedon'=>$date
                    );
        if(empty($post['customer_uid'])){
            $propertyUid = generateUid();
            $dataArr['customer_uid'] = $customerUid;
            $dataArr['customer_addedon'] = $date;
            $dataArr['customer_status'] = 1;
            $customerId = $this->CM->insertData(CUSTOMER,$dataArr);
        }else{
            $customerUid = $customerUidPost;
            if(empty($cDetails['customer_id'])){
                $res['status'] = 0;
                $res['message'] = lang('App.details_not_found');
                return $res;
            }

            $customerId = $cDetails['customer_id'];
            $this->CM->updateData(CUSTOMER,array('customer_id'=>$customerId),$dataArr);
        }

        $res['status'] = 1;
        $res['data'] = array('customer_uid'=>$customerUid);
        $res['message'] = lang('App.update_success');
        return $res;
    }

    function guestList($post)
    {
        $customer = $this->db->table(CUSTOMER);
        $customer->select('customer_uid,customer_its,customer_fname,customer_mname,customer_lname,customer_email,customer_phone,customer_addedon');
        $customer->where('customer_status',1);
        $result = $customer->get()->getResultArray();

        if($result){
            $res['status'] = 1;
            $res['data'] = $result;
            $res['message'] = lang('App.success');
        }else{
            $res['status'] = 0;
            $res['message'] = lang('App.details_not_found');
        }
        return $res;            
    }

    function contactUsList($post)
    {
        $contacts = $this->db->table(CONTACTUS);
        $contacts->select('contact_uid,contact_name,contact_email,contact_message,contact_addedon');
        $result = $contacts->get()->getResultArray();

        if($result){
            $res['status'] = 1;
            $res['data'] = $result;
            $res['message'] = lang('App.success');
        }else{
            $res['status'] = 0;
            $res['message'] = lang('App.details_not_found');
        }
        return $res;            
    }
}

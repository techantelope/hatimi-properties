<?php
namespace App\Models;
use CodeIgniter\Model;

class PaymentsModel extends Model
{
    function __construct(){
        $this->db      = \Config\Database::connect();   
        $this->CM = new CommonModel();
    }

    function paymentsList($post)
    {
        $payments = $this->db->table(PAYMENTS.' AS P');
        $payments->select('P.payment_uid,P.payment_totalamount,P.payment_amountpaid,P.payment_mode,P.payment_response,P.payment_status,P.payment_datetime,C.customer_fname,C.customer_mname,C.customer_lname,C.customer_its,B.booking_uid');
        $payments->join(CUSTOMER.' AS C','C.customer_id=P.customer_id');
        $payments->join(BOOKINGS.' AS B','B.booking_id=P.booking_id');
        $result = $payments->get()->getResultArray();

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

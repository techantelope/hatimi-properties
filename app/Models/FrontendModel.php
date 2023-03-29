<?php
namespace App\Models;
use CodeIgniter\Model;
use App\Models\RazorpayModel;
use App\Models\BookingsModel;
use App\Models\EmailModel;

class FrontendModel extends Model
{
    function __construct(){
        $this->db      = \Config\Database::connect();   
        $this->CM = new CommonModel();
        $this->RZRPM = new RazorpayModel();
        $this->BM = new BookingsModel();
        $this->EM = new EmailModel();
    }

    function profile($post){
        $customer = $this->db->table(CUSTOMER);
        $customer->where('customer_uid',$post['customer_uid']);
        $data = $customer->get()->getRowArray();
        if(!empty($data)){
            $res['status'] = 1;
            $res['data'] = $data;
            $res['message'] = lang('App.success');
            return $res;            
        }
        $res['status'] = 0;
        $res['message'] = lang('App.details_not_found');
        return $res;                
    }

    function updateProfile($post){
        $email = $post['email'];
        if($email != $post['details']['customer_email']){
            $check = $this->isEmailExist($email);
            if($check['status']){
                $res['status'] = 0;
                $res['message'] = lang('App.email_exist');
                return $res;
            }
        }

        $data = array(
                    'customer_fname'=>$post['fname'],
                    'customer_mname'=>$post['mname'],
                    'customer_lname'=>$post['lname'],
                    'customer_email'=>$post['email'],
                    'customer_phone'=>$post['phone'],
                    'customer_its'=>$post['its_id'],
                    'customer_modifiedon'=>date('Y-m-d H:i:s')
                    );
        $updateStatus = $this->CM->updateData(CUSTOMER,array('customer_uid'=>$post['customer_uid']),$data);
        $res['status'] = 1;
        $res['message'] = $updateStatus?lang('App.update_success'):lang('App.up_to_date');
        return $res;
    }

    function isEmailExist($email){
        $customer = $this->db->table(CUSTOMER);
        $customer->select('customer_uid');
        $customer->where('customer_email',$email);
        $data = $customer->get()->getRowArray();
        if(!empty($data)){
            $res['status'] = 1;     
        }else{
            $res['status'] = 0;
        }
        return $res;
    }

    function searchRooms($post){
        $property_uid = $post['property_uid'];
        $check_in = $post['check_in'];
        $check_out = $post['check_out'];
        $adults = $post['adults'];
        $children = $post['children'];
        $room_type = $post['room_type'];
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
        
        $offset = $limit * $page;
        
        // Get property information
        $property = $this->db->table(PROPERTY);
        $property->select('property_id');
        $property->where('property_uid',$property_uid);
        $property->where('property_status',1);
        $propertyInfo = $property->get()->getRowArray();

        if(empty($propertyInfo))
        {
            $res['status'] = 0;
            $res['message'] = lang('App.property').' '.lang('App.details_not_found');
            return $res;
        }

        // Get occupied rooms between checkin and checkout dates

        //$where = "( ( booking_checkin >= '".$check_in."' AND booking_checkin <= '".$check_out."') OR ( booking_checkout >= '".$check_in."' AND booking_checkout <= '".$check_out."') )";

        $newCheckin = $check_in.' 10:00:00';
        $newCheckout = $check_out.' 08:00:00';

        $where = "( ( booking_checkin >= '".$newCheckin."' AND booking_checkin <= '".$newCheckout."') OR ( booking_checkout >= '".$newCheckin."' AND booking_checkout <= '".$newCheckout."') OR ( booking_checkin <= '".$newCheckin."' AND booking_checkout >= '".$newCheckout."') )";
        $or_where = "( rb_status = 'booked' OR rb_status = 'reserved' OR rb_status = 'waiting_for_approval_from_admin' OR rb_status = 'request_for_cancellation' )";
        $bookedRooms = $this->db->table(ROOM_BOOKINGS);
        $bookedRooms->select('room_id,rb_status');
        $bookedRooms->where($where);
        $bookedRooms->where($or_where);
        $bookedRoomsIds = $bookedRooms->get()->getResultArray();
        //print_r($bookedRoomsIds); 
        //echo $this->db->getLastQuery(); 
        
        //Get all rooms of property
        $rooms = $this->db->table(ROOMS.' AS R');
        $rooms->select('R.room_id,R.room_uid,R.room_number,R.room_type,R.room_bedcapacity_min,R.room_bedcapacity_max,R.room_phone,R.room_charge,R.room_extrabedcharge,R.room_description,R.room_amenities,P.property_name,P.property_address,P.property_city');
        $rooms->join(PROPERTY.' AS P','P.property_id=R.property_id');
        $rooms->where('R.property_id',$propertyInfo['property_id']);
        $rooms->where('R.room_status',1);
        if($room_type=='ac')
        {
           $rooms->where('R.room_acroom',1);
        }
        else
        {
            $rooms->where('R.room_acroom',0);
        }

        $tempdb = clone $rooms; //to get rows for pagination
        $total = $tempdb->countAllResults();

        $rooms->limit($limit,$offset);
        $roomsAvailable = $rooms->get()->getResultArray();
        
        if(!empty($roomsAvailable))
        {
            $i=0;
            foreach($roomsAvailable AS $ra)
            {
                if(!empty($bookedRoomsIds))
                {
                    foreach($bookedRoomsIds AS $bri)
                    {
                        if($ra['room_id'] == $bri['room_id'])
                        {
                            if($bri['rb_status']=='booked')
                            {
                                $roomsAvailable[$i]['room_booking_status'] = 1;
                                break;
                            }
                            elseif(($bri['rb_status']=='reserved') || ($bri['rb_status']=='waiting_for_approval_from_admin') || ($bri['rb_status']=='request_for_cancellation'))
                            {
                                $roomsAvailable[$i]['room_booking_status'] = 2;
                                break;
                            }
                            else
                            {
                                $roomsAvailable[$i]['room_booking_status'] = 0;   
                                break;
                            }    
                        }             
                        else
                        {
                            $roomsAvailable[$i]['room_booking_status'] = 0;   
                        }                        
                    }            
                } 
                else
                {
                    $roomsAvailable[$i]['room_booking_status'] = 0;
                }   
                
                $roomsAvailable[$i]['room_images'] = $this->roomImages($ra['room_id'],1);
                
                unset($roomsAvailable[$i]['room_id']);                           
                
                $i++;
            }            
            $res['status'] = 1;
            $res['data'] = $roomsAvailable;
            $res['total'] = $total;
            $res['message'] = lang('App.success');
            return $res;
        }
        else
        {
            $res['status'] = 0;
            $res['message'] = lang('App.details_not_found');
            return $res;
        }
    } 

    function getRoom($post)
    {
        $room_uid = $post['room_uid'];
        
        // Get room information
        $rooms = $this->db->table(ROOMS.' AS R');
        $rooms->select('R.room_id,R.room_uid,R.room_number,R.room_type,R.room_bedcapacity_min,R.room_bedcapacity_max,R.room_phone,R.room_charge,R.room_extrabedcharge,R.room_amenities,R.room_description,P.property_name,P.property_address,P.property_description,P.property_amenities,P.property_city');
        $rooms->join(PROPERTY.' AS P','P.property_id=R.property_id');
        $rooms->where('R.room_uid',$room_uid);
        $rooms->where('R.room_status',1);
        $roomInfo = $rooms->get()->getResultArray();

        if(empty($roomInfo))
        {
            $res['status'] = 0;
            $res['message'] = lang('App.rooms').' '.lang('App.details_not_found');
            return $res;
        }
        else
        {            
            $i=0;
            foreach($roomInfo AS $r)
            {
                $roomInfo[$i]['room_images'] = $this->roomImages($r['room_id'],1);
                unset($roomInfo[$i]['room_id']);                                           
                $i++;
            }         
            $res['status'] = 1;
            $res['data'] = $roomInfo;
            $res['message'] = lang('App.success');
            return $res;
        }   
    }

    function roomImages($roomId,$status='')
    {
        if($roomId){

            $image = $this->db->table(IMAGES);
            $image->select('image_uid,image_name');
            $image->where('id',$roomId);
            $image->where('type','room');
            if($status){
                $image->where('image_status',$status);    
            }
            $result = $image->get()->getResultArray();
            return $result;
        }
        return array();
    }

    function getProperty($post){
        $property_uid = $post['property_uid'];
        // Get properties information
        $property = $this->db->table(PROPERTY.' AS P');
        $property->select('P.property_id,P.property_uid,P.property_name,P.property_type,P.property_phone,P.property_address');
        $property->where('P.property_uid',$property_uid);
        $property->where('P.property_status',1);
        $propertyInfo = $property->get()->getResultArray();

        if(empty($propertyInfo))
        {
            $res['status'] = 0;
            $res['message'] = lang('App.property').' '.lang('App.details_not_found');
            return $res;
        }
        else
        {
            $i=0;
            foreach($propertyInfo as $p)
            {
                $propertyInfo[$i]['property_images'] = $this->propertyImages($p['property_id'],1);
                unset($propertyInfo[$i]['property_id']); 
                $i++;
            }

            $res['status'] = 1;
            $res['data'] = $propertyInfo;
            $res['message'] = lang('App.success');
            return $res;
        }
    }

    function propertyImages($propertyId,$status='')
    {
        if($propertyId){

            $image = $this->db->table(IMAGES);
            $image->select('image_uid,image_name');
            $image->where('id',$propertyId);
            $image->where('type','property');
            if($status){
                $image->where('image_status',$status);    
            }
            $result = $image->get()->getResultArray();
            return $result;
        }
        return array();
    }

    /*function getPaymentURL($post)
    {
        if(base_url()=='http://localhost/estate/api')
        {
            $callbackurl = 'http://localhost/test.php';
        }
        else
        {
            $callbackurl = '';
        }

        $amount = $post['amount_paid'];
        $customer_uid = $post['customer_uid'];

        // Get customer information
        $customer = $this->db->table(CUSTOMER);
        $customer->select('customer_id,customer_fname,customer_mname,customer_lname,customer_email,customer_phone');
        $customer->where('customer_uid',$customer_uid);
        $customer->where('customer_status',1);
        $customerInfo = $customer->get()->getRowArray();

        if(empty($customerInfo))
        {
            $res['status'] = 0;
            $res['message'] = lang('App.customer').' '.lang('App.details_not_found');
            return $res;
        }

        $name = $customerInfo['customer_fname'];
        if($customerInfo['customer_mname']!='')
        {
            $name = $name.' '.$customerInfo['customer_mname'];
        }
        $name = $name.' '.$customerInfo['customer_lname'];

        $transaction_uid = generateUid();
        $request = array(
                'amount'=>$amount,
                'transaction_uid'=>$transaction_uid,
                'cust_name'=>$name,
                'cust_email'=>$customerInfo['customer_email'],
                'cust_phone'=>$customerInfo['customer_phone'],
                'callback_url'=>$callbackurl.'?transaction_uid='.$transaction_uid,
                'note'=>'Room booking'
            );

        $response = $this->RZRPM->createPayLink($request);

        // Add Transaction information
        $transactionArr = array(
                            'transaction_uid'=>$transaction_uid,
                            'customer_id'=>$customerInfo['customer_id'],
                            'payment_amount'=>$amount,
                            'callback_url'=>$callbackurl.'?transaction_uid='.$transaction_uid,
                            'payment_url_id'=>$response['pay_id'],
                            'payment_url_response'=>json_encode($response),
                            'payment_url_datetime'=>date('Y-m-d H:i:s')
                            );
        $this->CM->insertData(TRANSACTIONS,$transactionArr);

        if($response['status'])
        {    
            $res['status'] = 1;
            $res['data'] = $response;
            $res['message'] = lang('App.success');
            return $res;
        }
        else
        {
            $res['status'] = 0;
            $res['message'] = lang('App.something_wrong');
            return $res;
        }    
    }

    function fetchTransfer($paymentId,$paymentLinkId,$amountPaid)
    {
        $payment_id = $paymentId;
        $payment_link_id = $paymentLinkId;
        $amount = $amountPaid;

        $result = $this->RZRPM->fetchtransfer($payment_id);

        if($result['status']!=1)
        {
            $res['status'] = 0;
            $res['message'] = $result['msg'];
            return $res;
        }

        $payment_url_id = $result['response']['id'];

        if($amount!=$result['amount'])
        {
            $res['status'] = 0;
            $res['message'] = 'Amount paid is not matching';
            return $res;
        }

        $transaction = $this->db->table(TRANSACTIONS);
        $transaction->where('payment_url_id',$payment_link_id);
        $transactionInfo = $transaction->get()->getRowArray();

        if(empty($transactionInfo))
        {
            $res['status'] = 0;
            $res['message'] = 'Transaction information not found';
            return $res;
        }

        $updateData = array(
                            'payment_id'=>$payment_id,
                            'payment_status'=>$result['pay_status'],
                            'payment_response'=>json_encode($result),
                            'payment_check_datetime'=>date('Y-m-d H:i:s')
                        );
        $where = array(
                        'payment_url_id'=>$payment_link_id,
                        'payment_amount'=>$result['amount']
                    );
        $r = $this->CM->updateData(TRANSACTIONS,$where,$updateData);
        //echo $this->db->getLastQuery(); die;

        if($result['status'] && ($result['pay_status']=='captured' || $result['pay_status']=='authorized'))
        {    
            $res['status'] = 1;
            return $res;
        }
        else
        {
            $res['status'] = 0;
            $res['message'] = $result['msg'];
            return $res;
        }
    }

    function normalRefund($paymentId,$amountPaid)
    {
        $payment_id = $paymentId;
        $amount = $amountPaid;
        
        // Get Transaction information
        $transaction = $this->db->table(TRANSACTIONS);
        $transaction->select('transaction_id,transaction_uid,payment_amount');
        $transaction->where('payment_id',$payment_id);
        $transactionInfo = $transaction->get()->getRowArray();

        if(empty($transactionInfo))
        {
            $res['status'] = 0;
            $res['message'] = 'Transaction information not found';
            return $res;
        }

        if($transactionInfo['payment_amount']!=$amount)
        {
            $res['status'] = 0;
            $res['message'] = 'Transaction amount not same';
            return $res;
        }

        $data = array(
                    'payment_id'=>$payment_id,
                    'amount'=>$amount
                    );

        $result = $this->RZRPM->normalrefund($data);

        if($result['status']!=1)
        {
            $res['status'] = 0;
            $res['message'] = $result['msg'];
            return $res;
        }

        $updateData = array(
                            
                            'payment_status'=>'refund',
                            'payment_refund_id'=>$result['response_array']['id'],
                            'payment_refund_response'=>json_encode($result),
                            'payment_refund_datetime'=>date('Y-m-d H:i:s')
                        );
        $where = array(
                        'payment_id'=>$payment_id,
                        'payment_amount'=>$amount
                    );
        $this->CM->updateData(TRANSACTIONS,$where,$updateData);
        //echo $this->db->getLastQuery(); die;

        if($result['status'])
        {    
            $res['status'] = 1;
            return $res;
        }
        else
        {
            $res['status'] = 0;
            $res['message'] = $result['msg'];
            return $res;
        }
    }

    function addBookingOld($post){
        $customer_uid = $post['customer_uid'];
        $property_uid = $post['property_uid'];
        $check_in = $post['check_in'];
        $check_out = $post['check_out'];
        $adults = $post['adults'];
        $children = $post['children'];
        $sgst = $post['sgst'];
        $cgst = $post['cgst'];
        $total = $post['total'];
        $amount_paid = $post['amount_paid'];
        $payment_id = $post['payment_id'];
        $paymentLinkId = $post['payment_link_id'];

        $transactionStatus = $this->fetchTransfer($payment_id,$paymentLinkId,$amount_paid);

        if($transactionStatus['status']==0)
        {
            $res['status'] = 0;
            $res['message'] = $transactionStatus['message'];
            return $res;
        }

        // Get customer information
        $customer = $this->db->table(CUSTOMER);
        $customer->select('customer_id');
        $customer->where('customer_uid',$customer_uid);
        $customer->where('customer_status',1);
        $customerInfo = $customer->get()->getRowArray();

        if(empty($customerInfo))
        {
            $res['status'] = 0;
            $res['message'] = lang('App.customer').' '.lang('App.details_not_found');
            return $res;
        }

        // Get property information
        $property = $this->db->table(PROPERTY);
        $property->select('property_id');
        $property->where('property_uid',$property_uid);
        $property->where('property_status',1);
        $propertyInfo = $property->get()->getRowArray();

        if(empty($propertyInfo))
        {
            $res['status'] = 0;
            $res['message'] = lang('App.property').' '.lang('App.details_not_found');
            return $res;
        }

        // Get rooms information
        $roomData = array();
        if(isset($post['rooms_and_extra_beds']))
        {
            foreach($post['rooms_and_extra_beds'] as $r)
            {
                $rooms = $this->db->table(ROOMS);
                $rooms->select('room_id,room_number');
                $rooms->where('room_uid',$r->room_uid);
                $rooms->where('property_id',$propertyInfo['property_id']);
                $rooms->where('room_status',1);
                $roomInfo = $rooms->get()->getRowArray();

                if(empty($roomInfo))
                {
                    $res['status'] = 0;
                    $res['message'] = lang('App.room').' '.lang('App.details_not_found');
                    return $res;
                }
                else
                {
                    $room_id = $roomInfo['room_id'];
                    $roomData[$room_id] = $r->extra_bed;
                }
            }
        }

        if(empty($roomData))
        {
            $res['status'] = 0;
            $res['message'] = 'Room information not found';
            return $res;
        }

        // Get transaction information
        $transaction = $this->db->table(TRANSACTIONS);
        $transaction->select('transaction_id');
        $transaction->where('payment_id',$payment_id);
        $transaction->where('payment_url_id',$paymentLinkId);
        $transactionInfo = $transaction->get()->getRowArray();

        if(empty($transactionInfo))
        {
            $res['status'] = 0;
            $res['message'] = lang('App.transaction').' '.lang('App.details_not_found');
            return $res;
        }

        $date = date('Y-m-d H:i:s');
        $bookingUid = generateUid();
        $dataArr = array(
                        'booking_uid'=>$bookingUid,
                        'customer_id'=>$customerInfo['customer_id'],
                        'property_id'=>$propertyInfo['property_id'],
                        'room_adults'=>$adults,
                        'room_children'=>$children,
                        'booking_checkin'=>$check_in,
                        'booking_checkout'=>$check_out,
                        'booking_sgst'=>$sgst,
                        'booking_cgst'=>$cgst,
                        'booking_total'=>$total,
                        'booking_amount_paid'=>$amount_paid,
                        'booking_status'=>'booked',
                        'booking_addedon'=>$date
                    );
        $bookingId = $this->CM->insertData(BOOKINGS,$dataArr);

        if($bookingId)
        {
            foreach ($roomData as $key => $value) 
            {
                $rooms = $this->db->table(ROOMS);
                $rooms->select('room_id,room_charge,room_extrabedcharge,room_type,room_acroom,room_bedcapacity');
                $rooms->where('room_id',$key);
                $rooms->where('property_id',$propertyInfo['property_id']);
                $rooms->where('room_status',1);
                $roomInfo = $rooms->get()->getRowArray();

                if(empty($roomInfo))
                {
                    $res['status'] = 0;
                    $res['message'] = lang('App.room').' '.lang('App.details_not_found');
                }
                else
                {
                    $room_id = $roomInfo['room_id'];
                    $room_charge = $roomInfo['room_charge'];
                    if($value==0)
                    {
                        $room_extrabedcharge = 0;
                        $room_extrabedtotalcharge = 0;
                    }
                    else
                    {
                        $room_extrabedcharge = $roomInfo['room_extrabedcharge'];
                        $room_extrabedtotalcharge = $room_extrabedcharge*$value;
                    }    
                }

                $roomArr = array(
                                'rb_uid'=>generateUid(),
                                'booking_id'=>$bookingId,
                                'room_id'=>$key,
                                'room_charges'=>$room_charge,
                                'extra_beds'=>$value,
                                'extra_bed_charges'=>$room_extrabedcharge,
                                'total_extra_bed_charges'=>$room_extrabedtotalcharge,
                                'booking_checkin'=>$check_in,
                                'booking_checkout'=>$check_out,
                                'room_type'=>$roomInfo['room_type'],
                                'room_acroom'=>$roomInfo['room_acroom'],
                                'room_bedcapacity'=>$roomInfo['room_bedcapacity'],
                                'datetime_added'=>$date,
                                'rb_status'=>'booked'
                                );
                $roomBookingId = $this->CM->insertData(ROOM_BOOKINGS,$roomArr);                
            }

            // Add Payment information
            $paymentArr = array(
                                'payment_uid'=>generateUid(),
                                'booking_id'=>$bookingId,
                                'transaction_id'=>$transactionInfo['transaction_id'],
                                'customer_id'=>$customerInfo['customer_id'],
                                'payment_totalamount'=>$total,
                                'payment_amountpaid'=>$amount_paid,
                                'payment_mode'=>'online',
                                'payment_response'=>'booked_by_customer',
                                'payment_status'=>'success',
                                'payment_datetime'=>$date
                                );
            $paymentId = $this->CM->insertData(PAYMENTS,$paymentArr);

            // Generate Receipt
            $response = $this->BM->generateInvoice($bookingUid);
            if($response['status'])
            {
                $updateData = array('receipt_name'=>$response['invoice_name']);
                $where = array('booking_uid'=>$bookingUid);
                $this->CM->updateData(BOOKINGS,$where,$updateData);
            }

            $res['status'] = 1;
            $res['data'] = array('booking_uid'=>$bookingUid);
            $res['message'] = lang('App.success');
            return $res;
        }
        else
        {
            $res['status'] = 0;
            $res['data'] = array('booking_uid'=>'');
            $res['message'] = lang('App.something_wrong');
            return $res;
        }
    }*/

    function addBooking($post){
        $customer_uid = $post['customer_uid'];
        $property_uid = $post['property_uid'];
        $check_in = $post['check_in'];
        $check_out = $post['check_out'];
        $adults = $post['adults'];
        $children = $post['children'];
        $sgst = $post['sgst'];
        $cgst = $post['cgst'];
        $total = $post['total'];
        $amount_paid = $post['amount_paid'];
        $transaction_id = $post['transaction_id'];
        $promocode_uid = $post['promocode_uid'];

        $check_in = $check_in.' 10:00:00';
        $check_out = $check_out.' 09:00:00';

        // Get customer information
        $customer = $this->db->table(CUSTOMER);
        $customer->select('customer_id,customer_fname,customer_email');
        $customer->where('customer_uid',$customer_uid);
        $customer->where('customer_status',1);
        $customerInfo = $customer->get()->getRowArray();

        if(empty($customerInfo))
        {
            $res['status'] = 0;
            $res['message'] = lang('App.customer').' '.lang('App.details_not_found');
            return $res;
        }

        // Get property information
        $property = $this->db->table(PROPERTY);
        $property->select('property_id');
        $property->where('property_uid',$property_uid);
        $property->where('property_status',1);
        $propertyInfo = $property->get()->getRowArray();

        if(empty($propertyInfo))
        {
            $res['status'] = 0;
            $res['message'] = lang('App.property').' '.lang('App.details_not_found');
            return $res;
        }

        // Get rooms information
        $roomData = array();
        if(isset($post['rooms_and_extra_beds']))
        {
            foreach($post['rooms_and_extra_beds'] as $r)
            {
                $rooms = $this->db->table(ROOMS);
                $rooms->select('room_id,room_number');
                $rooms->where('room_uid',$r->room_uid);
                $rooms->where('property_id',$propertyInfo['property_id']);
                $rooms->where('room_status',1);
                $roomInfo = $rooms->get()->getRowArray();

                if(empty($roomInfo))
                {
                    $res['status'] = 0;
                    $res['message'] = lang('App.room').' '.lang('App.details_not_found');
                    return $res;
                }
                else
                {
                    $room_id = $roomInfo['room_id'];
                    $roomData[$room_id] = !empty($r->extra_bed)?$r->extra_bed:0;
                }
            }
        }

        if(empty($roomData))
        {
            $res['status'] = 0;
            $res['message'] = 'Room information not found';
            return $res;
        }

        // Get promocode information
        if($promocode_uid!='')
        {
            $promo = $this->db->table(PROMO_CODE);
            $promo->select('pc_id,pc_uid,discount');
            $promo->where('pc_uid',$promocode_uid);
            $promo->where('status',1);
            $promoInfo = $promo->get()->getRowArray();

            if(empty($promoInfo))
            {
                $res['status'] = 0;
                $res['message'] = lang('App.promo_code').' '.lang('App.details_not_found');
                return $res;
            }
        }  

        $date = date('Y-m-d H:i:s');
        $bookingUid = generateUid();
        $dataArr = array(
                        'booking_uid'=>$bookingUid,
                        'customer_id'=>$customerInfo['customer_id'],
                        'property_id'=>$propertyInfo['property_id'],
                        'room_adults'=>$adults,
                        'room_children'=>$children,
                        'booking_checkin'=>$check_in,
                        'booking_checkout'=>$check_out,
                        'booking_sgst'=>$sgst,
                        'booking_cgst'=>$cgst,
                        'booking_total'=>$total,
                        'booking_amount_paid'=>$amount_paid,
                        'booking_status'=>'waiting_for_approval_from_admin',
                        'booking_addedon'=>$date
                    );
        if($promocode_uid!='' && !empty($promoInfo))
        {
            $dataArr['promocode_id'] = $promoInfo['pc_id'];
            $dataArr['discount'] = $promoInfo['discount'];
        }

        $bookingId = $this->CM->insertData(BOOKINGS,$dataArr);

        if($bookingId)
        {
            foreach ($roomData as $key => $value) 
            {
                $rooms = $this->db->table(ROOMS);
                $rooms->select('room_id,room_charge,room_extrabedcharge,room_type,room_acroom,room_bedcapacity_min');
                $rooms->where('room_id',$key);
                $rooms->where('property_id',$propertyInfo['property_id']);
                $rooms->where('room_status',1);
                $roomInfo = $rooms->get()->getRowArray();

                if(empty($roomInfo))
                {
                    $res['status'] = 0;
                    $res['message'] = lang('App.room').' '.lang('App.details_not_found');
                }
                else
                {
                    $room_id = $roomInfo['room_id'];
                    $room_charge = $roomInfo['room_charge'];
                    if($value==0)
                    {
                        $room_extrabedcharge = 0;
                        $room_extrabedtotalcharge = 0;
                    }
                    else
                    {
                        $room_extrabedcharge = $roomInfo['room_extrabedcharge'];
                        $room_extrabedtotalcharge = $room_extrabedcharge*$value;
                    }    
                }

                $roomArr = array(
                                'rb_uid'=>generateUid(),
                                'booking_id'=>$bookingId,
                                'room_id'=>$key,
                                'room_charges'=>$room_charge,
                                'extra_beds'=>$value,
                                'extra_bed_charges'=>$room_extrabedcharge,
                                'total_extra_bed_charges'=>$room_extrabedtotalcharge,
                                'booking_checkin'=>$check_in,
                                'booking_checkout'=>$check_out,
                                'room_type'=>$roomInfo['room_type'],
                                'room_acroom'=>$roomInfo['room_acroom'],
                                'room_bedcapacity'=>$roomInfo['room_bedcapacity_min'],
                                'datetime_added'=>$date,
                                'rb_status'=>'waiting_for_approval_from_admin'
                                );
                $roomBookingId = $this->CM->insertData(ROOM_BOOKINGS,$roomArr);                
            }

            // Add Payment information
            $paymentArr = array(
                                'payment_uid'=>generateUid(),
                                'booking_id'=>$bookingId,
                                'transaction_id'=>$transaction_id,
                                'customer_id'=>$customerInfo['customer_id'],
                                'payment_totalamount'=>$total,
                                'payment_amountpaid'=>$amount_paid,
                                'payment_mode'=>'online',
                                'payment_response'=>'booked_by_customer',
                                'payment_status'=>'waiting_for_approval_from_admin',
                                'payment_datetime'=>$date
                                );
            $paymentId = $this->CM->insertData(PAYMENTS,$paymentArr);

            // Add Booking Status information
            $bookingStatusArr = array(
                                'status_uid'=>generateUid(),
                                'booking_id'=>$bookingId,
                                'customer_id'=>$customerInfo['customer_id'],
                                'status'=>'waiting_for_approval_from_admin',
                                'status_datetime'=>$date
                                );
            $statusId = $this->CM->insertData(BOOKING_STATUS,$bookingStatusArr);

            // Generate Receipt
            $response = $this->BM->generateInvoice($bookingUid);
            if($response['status'])
            {
                $updateData = array('receipt_name'=>$response['invoice_name']);
                $where = array('booking_uid'=>$bookingUid);
                $this->CM->updateData(BOOKINGS,$where,$updateData);
            }

            // Send booking email
            if($customerInfo['customer_email']!='')
            {
                $emailData = array();
                $emailData['name'] = $customerInfo['customer_fname'];
                $emailData['checkin'] = $check_in;
                $emailData['checkout'] = $check_out;
                $emailData['booking_uid'] = $bookingUid;
                $message = view('email/booking_email',$emailData);
                $this->EM->sendEmail($customerInfo['customer_email'],'Booking',$message,array(BASEURL.'/uploads/invoice/'.$bookingUid.'.pdf'));
            }
            
            $res['status'] = 1;
            $res['data'] = array('booking_uid'=>$bookingUid);
            $res['message'] = lang('App.success');
            return $res;
        }
        else
        {
            $res['status'] = 0;
            $res['data'] = array('booking_uid'=>'');
            $res['message'] = lang('App.something_wrong');
            return $res;
        }
    }

    function cancelBooking($post){
        $booking_uid = $post['booking_uid'];

        // Get booking information
        $booking = $this->db->table(BOOKINGS);
        $booking->select('booking_id,booking_amount_paid,customer_id,booking_uid,booking_checkin,booking_checkout');
        $booking->where('booking_uid',$booking_uid);
        $booking->where('booking_status','booked');
        $bookingInfo = $booking->get()->getRowArray();

        if(empty($bookingInfo))
        {
            $res['status'] = 0;
            $res['message'] = lang('App.booking').' '.lang('App.details_not_found');
            return $res;
        }
        else
        {
            // Get customer information
            $customer = $this->db->table(CUSTOMER);
            $customer->select('customer_id,customer_fname,customer_email');
            $customer->where('customer_id',$bookingInfo['customer_id']);
            $customer->where('customer_status',1);
            $customerInfo = $customer->get()->getRowArray();

            if(empty($customerInfo))
            {
                $res['status'] = 0;
                $res['message'] = lang('App.customer').' '.lang('App.details_not_found');
                return $res;
            }

            $payments = $this->db->table(PAYMENTS);
            $payments->select('transaction_id');
            $payments->where('booking_id',$bookingInfo['booking_id']);
            $paymentsInfo = $payments->get()->getRowArray();

            if(empty($paymentsInfo))
            {
                $res['status'] = 0;
                $res['message'] = lang('App.payment').' '.lang('App.details_not_found');
                return $res;
            }

            $updateData = array('booking_status'=>'request_for_cancellation');
            $where = array('booking_id'=>$bookingInfo['booking_id']);
            $this->CM->updateData(BOOKINGS,$where,$updateData);

            $updateData = array('rb_status'=>'request_for_cancellation');
            $where = array('booking_id'=>$bookingInfo['booking_id']);
            $this->CM->updateData(ROOM_BOOKINGS,$where,$updateData);

            $updateData = array('payment_status'=>'request_for_cancellation');
            $where = array('booking_id'=>$bookingInfo['booking_id']);
            $this->CM->updateData(PAYMENTS,$where,$updateData);

            // Add Booking Status information
            $bookingStatusArr = array(
                                'status_uid'=>generateUid(),
                                'booking_id'=>$bookingInfo['booking_id'],
                                'customer_id'=>$bookingInfo['customer_id'],
                                'status'=>'request_for_cancellation',
                                'status_datetime'=>date('Y-m-d H:i:s')
                                );
            $statusId = $this->CM->insertData(BOOKING_STATUS,$bookingStatusArr);
            
            // Send booking email
            if($customerInfo['customer_email']!='')
            {
                $emailData = array();
                $emailData['name'] = $customerInfo['customer_fname'];
                $emailData['checkin'] = $bookingInfo['booking_checkin'];
                $emailData['checkout'] = $bookingInfo['booking_checkout'];
                $emailData['booking_uid'] = $bookingInfo['booking_uid'];
                $message = view('email/cancel_email',$emailData);
                $this->EM->sendEmail($customerInfo['customer_email'],'Booking',$message);
            }
            
            $res['status'] = 1;
            $res['message'] = lang('App.update_success');
            return $res;
        }
    }

    /*function cancelBookingOld($post){
        $booking_uid = $post['booking_uid'];

        // Get booking information
        $booking = $this->db->table(BOOKINGS);
        $booking->select('booking_id,booking_amount_paid');
        $booking->where('booking_uid',$booking_uid);
        $booking->where('booking_status','booked');
        $bookingInfo = $booking->get()->getRowArray();

        if(empty($bookingInfo))
        {
            $res['status'] = 0;
            $res['message'] = lang('App.booking').' '.lang('App.details_not_found');
            return $res;
        }
        else
        {
            $payments = $this->db->table(PAYMENTS);
            $payments->select('transaction_id');
            $payments->where('booking_id',$bookingInfo['booking_id']);
            $paymentsInfo = $payments->get()->getRowArray();

            if(empty($paymentsInfo))
            {
                $res['status'] = 0;
                $res['message'] = lang('App.payment').' '.lang('App.details_not_found');
                return $res;
            }

            $transaction = $this->db->table(TRANSACTIONS);
            $transaction->select('transaction_id,payment_id');
            $transaction->where('transaction_id',$paymentsInfo['transaction_id']);
            $transactionInfo = $transaction->get()->getRowArray();

            if(empty($transactionInfo))
            {
                $res['status'] = 0;
                $res['message'] = lang('App.transaction').' '.lang('App.details_not_found');
                return $res;
            }

            $response = $this->normalRefund($transactionInfo['payment_id'],$bookingInfo['booking_amount_paid']);

            if($response['status']!=1)
            {
               $res['status'] = 0;
                $res['message'] = $response['message'];
                return $res; 
            }

            $updateData = array('booking_status'=>'cancelled');
            $where = array('booking_id'=>$bookingInfo['booking_id']);
            $this->CM->updateData(BOOKINGS,$where,$updateData);

            $updateData = array('rb_status'=>'cancelled');
            $where = array('booking_id'=>$bookingInfo['booking_id']);
            $this->CM->updateData(ROOM_BOOKINGS,$where,$updateData);

            $updateData = array('payment_status'=>'refund');
            $where = array('booking_id'=>$bookingInfo['booking_id']);
            $this->CM->updateData(PAYMENTS,$where,$updateData);

            $res['status'] = 1;
            $res['message'] = lang('App.update_success');
            return $res;
        }
    }*/

    function getBookings($post){
        
        // Get customer information
        $customer = $this->db->table(CUSTOMER);
        $customer->where('customer_uid',$post['customer_uid']);
        $customer->where('customer_status',1);
        $customerInfo = $customer->get()->getRowArray();

        if(empty($customerInfo))
        {
            $res['status'] = 0;
            $res['message'] = lang('App.customer').' '.lang('App.details_not_found');
            return $res;
        }

        // Get booking information
        $booking = $this->db->table(BOOKINGS.' AS B');
        $booking->select('B.booking_uid,P.property_name,B.room_adults,B.room_children,B.booking_checkin,B.booking_checkout,B.booking_sgst,B.booking_cgst,B.booking_total,B.booking_amount_paid,B.booking_status,B.booking_addedon,PY.payment_uid,PY.payment_totalamount,PY.payment_amountpaid,PY.payment_mode,PY.payment_response,PY.payment_status,PY.payment_datetime,C.customer_its,C.customer_fname,C.customer_mname,C.customer_lname,C.customer_email,C.customer_phone');
        $booking->join(PROPERTY.' AS P','P.property_id=B.property_id');
        $booking->join(PAYMENTS.' AS PY','PY.booking_id=B.booking_id');
        $booking->join(CUSTOMER.' AS C','C.customer_id=B.customer_id');
        $booking->where('B.customer_id',$customerInfo['customer_id']);
        $bookingInfo = $booking->get()->getResultArray();

        if(empty($bookingInfo))
        {
            $res['status'] = 0;
            $res['message'] = lang('App.booking').' '.lang('App.details_not_found');
            return $res;
        }
        else
        {
            $res['status'] = 1;
            $res['data'] = $bookingInfo;
            $res['message'] = lang('App.success');
            return $res;
        }
    }

    function getBookingDetails($post){
        $booking_uid = $post['booking_uid'];

        // Get booking information
        $booking = $this->db->table(BOOKINGS);
        $booking->select('booking_id');
        $booking->where('booking_uid',$booking_uid);
        $bookingInfo = $booking->get()->getRowArray();

        if(empty($bookingInfo))
        {
            $res['status'] = 0;
            $res['message'] = lang('App.booking').' '.lang('App.details_not_found');
            return $res;
        }
        else
        {
            $rooms = $this->db->table(ROOM_BOOKINGS.' AS RB');
            $rooms->select('RB.rb_uid,R.room_uid,R.room_number,RB.room_charges,RB.extra_beds,RB.extra_bed_charges,RB.total_extra_bed_charges,RB.booking_checkin,RB.booking_checkout,RB.room_type,RB.room_acroom,RB.room_bedcapacity,RB.rb_status,RB.datetime_added');
            $rooms->join(ROOMS.' AS R','R.room_id=RB.room_id');
            $rooms->where('booking_id',$bookingInfo['booking_id']);
            $roomsInfo = $rooms->get()->getResultArray();

            if(empty($roomsInfo))
            {
                $res['status'] = 0;
                $res['message'] = lang('App.room').' '.lang('App.details_not_found');
                return $res;
            }

            $res['status'] = 1;
            $res['data'] = $roomsInfo;
            $res['message'] = lang('App.success');
            return $res;
        }
    }

    function getAllActiveProperties($post){
        
        // Get properties information
        $property = $this->db->table(PROPERTY.' AS P');
        $property->select('P.property_id,P.property_uid,P.property_name,P.property_type,P.property_phone,P.property_address,P.property_city');
        $property->where('P.property_status',1);
        $propertyInfo = $property->get()->getResultArray();

        if(!empty($propertyInfo))
        {
            $i=0;
            foreach($propertyInfo as $pi)
            {
                $totalRooms = 0;
                $totalPersons = 0;
                
                $rooms = $this->db->table(ROOMS.' AS R');
                $rooms->where('R.room_status',1);
                $rooms->where('R.property_id',$pi['property_id']);
                $roomsInfo = $rooms->get()->getResultArray();

                if(!empty($roomsInfo))
                {
                    foreach($roomsInfo as $ri)
                    {
                        $totalRooms++;
                        $totalPersons = $totalPersons + $ri['room_bedcapacity_max'];
                    }
                }
                $propertyInfo[$i]['total_rooms'] = $totalRooms;
                $propertyInfo[$i]['total_persons'] = $totalPersons;
                unset($propertyInfo[$i]['property_id']);
                $i++;
            }
        }

        if(empty($propertyInfo))
        {
            $res['status'] = 0;
            $res['message'] = lang('App.property').' '.lang('App.details_not_found');
            return $res;
        }
        else
        {
            $res['status'] = 1;
            $res['data'] = $propertyInfo;
            $res['message'] = lang('App.success');
            return $res;
        }
    }

    function contactUs($post)
    {
        $name = $post['name'];
        $email = $post['email'];
        $message = $post['message'];

        // Add Contact information
        $contactData = array(
                            'contact_uid'=>generateUid(),
                            'contact_name'=>$name,
                            'contact_email'=>$email,
                            'contact_message'=>$message,
                            'contact_addedon'=>date('Y-m-d H:i:s')
                            );
        $id = $this->CM->insertData(CONTACTUS,$contactData);

        if($id)
        {
            $res['status'] = 1;
            $res['message'] = lang('App.success');
            return $res;
        }
        else
        {
            $res['status'] = 0;
            $res['message'] = lang('App.something_wrong');
            return $res;
        }
    }

    function addReview($post){
        $customer_uid = $post['customer_uid'];
        $booking_uid = $post['booking_uid'];
        $room_uid = $post['room_uid'];
        $review = $post['review'];
        if(isset($post['star']))
        {
            $star = $post['star'];
        }    

        // Get customer information
        $customer = $this->db->table(CUSTOMER);
        $customer->select('customer_id');
        $customer->where('customer_uid',$customer_uid);
        $customer->where('customer_status',1);
        $customerInfo = $customer->get()->getRowArray();

        if(empty($customerInfo))
        {
            $res['status'] = 0;
            $res['message'] = lang('App.customer').' '.lang('App.details_not_found');
            return $res;
        }

        // Get booking information
        $booking = $this->db->table(BOOKINGS);
        $booking->select('booking_id,booking_uid');
        $booking->where('booking_uid',$booking_uid);
        $booking->where('customer_id',$customerInfo['customer_id']);
        $bookingInfo = $booking->get()->getRowArray();

        if(empty($bookingInfo))
        {
            $res['status'] = 0;
            $res['message'] = lang('App.booking').' '.lang('App.details_not_found');
            return $res;
        }

        // Get room information
        $room = $this->db->table(ROOMS);
        $room->select('room_id');
        $room->where('room_uid',$room_uid);
        $roomInfo = $room->get()->getRowArray();

        if(empty($roomInfo))
        {
            $res['status'] = 0;
            $res['message'] = lang('App.room').' '.lang('App.details_not_found');
            return $res;
        }

        // Check if room associated with booking
        $roomBooking = $this->db->table(ROOM_BOOKINGS);
        $roomBooking->select('booking_id');
        $roomBooking->where('room_id',$roomInfo['room_id']);
        $roomBooking->where('booking_id',$bookingInfo['booking_id']);
        $roomBookingInfo = $roomBooking->get()->getRowArray();

        if(empty($roomBookingInfo))
        {
            $res['status'] = 0;
            $res['message'] = 'Room not associated with booking';
            return $res;
        }

        $date = date('Y-m-d H:i:s');
        $reviewUid = generateUid();
        $dataArr = array(
                        'review_uid'=>$reviewUid,
                        'customer_id'=>$customerInfo['customer_id'],
                        'booking_id'=>$bookingInfo['booking_id'],
                        'room_id'=>$roomInfo['room_id'],
                        'review'=>$review,
                        'review_datetime'=>$date
                    );
        if(isset($post['star']) && $star>=0 && $star<=5)
        {
            $dataArr['star'] = $star;
        }

        $reviewId = $this->CM->insertData(REVIEWS,$dataArr);

        if($reviewId)
        {
            $res['status'] = 1;
            $res['data'] = array('review_uid'=>$reviewUid);
            $res['message'] = lang('App.success');
            return $res;
        }
        else
        {
            $res['status'] = 0;
            $res['data'] = array('review_uid'=>'');
            $res['message'] = lang('App.something_wrong');
            return $res;
        }
    }

    function getAllReviews($post){
        $room_uid = $post['room_uid'];
        
        // Get room information
        $room = $this->db->table(ROOMS);
        $room->select('room_id');
        $room->where('room_uid',$room_uid);
        $roomInfo = $room->get()->getRowArray();

        if(empty($roomInfo))
        {
            $res['status'] = 0;
            $res['message'] = lang('App.room').' '.lang('App.details_not_found');
            return $res;
        }

        // Get reviews information
        $review = $this->db->table(REVIEWS.' AS R');
        $review->select('R.review_uid,R.review,R.star,R.review_datetime,C.customer_fname,C.customer_lname,C.customer_mname');
        $review->join(CUSTOMER.' AS C','C.customer_id=R.customer_id');
        $review->where('R.room_id',$roomInfo['room_id']);
        $reviewsInfo = $review->get()->getResultArray();

        if(!empty($reviewsInfo))
        {
            $res['status'] = 1;
            $res['data'] = $reviewsInfo;
            $res['message'] = lang('App.success');
            return $res;
        }
        else
        {
            $res['status'] = 0;
            $res['message'] = lang('App.reviews').' '.lang('App.details_not_found');
            return $res;
        }
    }

    function getPropertyInfo($post){
        $property_uid = $post['property_uid'];
        // Get properties information
        $property = $this->db->table(PROPERTY.' AS P');
        $property->select('P.property_id,P.property_uid,P.property_name,P.property_type,P.property_phone,P.property_address,P.property_description,P.property_amenities,P.property_city');
        $property->where('P.property_uid',$property_uid);
        $propertyInfo = $property->get()->getRowArray();

        if(empty($propertyInfo))
        {
            $res['status'] = 0;
            $res['message'] = lang('App.details_not_found');
            return $res;
        }
        else
        {
          $propertyInfo['property_images'] = $this->propertyImages($propertyInfo['property_id'],1);
          $propertyInfo['image_path'] = SITEURL.'/uploads/property';
          unset($propertyInfo['property_id']); 
          
          $res['status'] = 1;
          $res['data'] = $propertyInfo;
          $res['message'] = lang('App.success');
          return $res;
        }
    }

    function getAvailability($post){
        $property_uid = $post['property_uid'];
        $check_in = $post['check_in'];
        $check_out = $post['check_out'];
        
        // Get property information
        $property = $this->db->table(PROPERTY);
        $property->select('property_id');
        $property->where('property_uid',$property_uid);
        $property->where('property_status',1);
        $propertyInfo = $property->get()->getRowArray();

        if(empty($propertyInfo))
        {
            $res['status'] = 0;
            $res['message'] = lang('App.property').' '.lang('App.details_not_found');
            return $res;
        }

        // Get occupied rooms between checkin and checkout dates

        $newCheckin = $check_in.' 10:00:00';
        $newCheckout = $check_out.' 08:00:00';

        $where = "( ( booking_checkin >= '".$newCheckin."' AND booking_checkin <= '".$newCheckout."') OR ( booking_checkout >= '".$newCheckin."' AND booking_checkout <= '".$newCheckout."') OR ( booking_checkin <= '".$newCheckin."' AND booking_checkout >= '".$newCheckout."') )";        
        $or_where = "( rb_status = 'booked' OR rb_status = 'reserved' OR rb_status = 'waiting_for_approval_from_admin' OR rb_status = 'request_for_cancellation' )";
        $bookedRooms = $this->db->table(ROOM_BOOKINGS);
        $bookedRooms->select('room_id,booking_checkin,booking_checkout');
        $bookedRooms->where($where);
        $bookedRooms->where($or_where);
        //$bookedRooms->where('rb_status','booked');
        $bookedRoomsIds = $bookedRooms->get()->getResultArray();
        //echo $this->db->getLastQuery(); 
        //print_r($bookedRoomsIds); die;

        //Get all rooms of property
        $rooms = $this->db->table(ROOMS.' AS R');
        $rooms->select('R.room_id,R.room_uid,R.room_number');
        $rooms->where('R.property_id',$propertyInfo['property_id']);
        $rooms->where('R.room_status',1);
        $roomsAvailable = $rooms->get()->getResultArray();
        
        if(!empty($roomsAvailable))
        {
            $i=0;
            foreach($roomsAvailable AS $ra)
            {
                if(!empty($bookedRoomsIds))
                {
                    $total_bookings = array();
                    $b = 0;
                    foreach($bookedRoomsIds AS $bri)
                    {
                        if($bri['room_id']==$ra['room_id'])
                        {
                            $total_bookings[$b]['check_in'] = $bri['booking_checkin'];
                            $total_bookings[$b]['check_out'] = $bri['booking_checkout'];                            
                            $b++;
                        }
                    }            
                    $roomsAvailable[$i]['bookings'] = $total_bookings;
                }
                unset($roomsAvailable[$i]['room_id']);                           
                $i++;
            }   

            $res['status'] = 1;
            $res['data'] = $roomsAvailable;
            $res['message'] = lang('App.success');
            return $res;
        }
        else
        {
            $res['status'] = 0;
            $res['message'] = lang('App.details_not_found');
            return $res;
        }
    }
}
<?php
namespace App\Models;
use CodeIgniter\Model;
// use \Mpdf\Mpdf;

class BookingsModel extends Model
{
    function __construct(){
        $this->db      = \Config\Database::connect();   
        $this->CM = new CommonModel();
        $this->EM = new EmailModel();
    }

    function getBookings($post){

        $sort_field = 'B.booking_addedon';
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

        $sortFieldArray = array('customer_fname,booking_addedon');
        if(isset($post['sort_field']) && in_array($post['sort_field'],$sortFieldArray))
        {
            if($post['sort_field'] == 'customer_fname'){
                $sort_field = 'C.'.$post['sort_field'];
            }else{
                $sort_field = 'B.'.$post['sort_field'];
            }
        }

        if(isset($post['sort_order']) && in_array($post['sort_order'],array('DESC','ASC')))
        {
            $sort_order = $post['sort_order'];
        }

        $offset = $limit * $page;

        $booking = $this->db->table(BOOKINGS.' B');
        $booking->select('B.booking_uid,B.room_adults,B.room_children,B.booking_checkin,B.booking_checkout,B.booking_extrabed,B.booking_charges,B.booking_extrabedcharges,B.booking_sgst,B.booking_cgst,B.booking_total,B.booking_status,B.booking_addedon,C.customer_uid,C.customer_fname,C.customer_mname,C.customer_lname,P.property_uid,P.property_name,P.property_type,R.room_uid,R.room_number,R.room_type');
        $booking->join(CUSTOMER.' C','C.customer_id = B.customer_id');
        $booking->join(PROPERTY.' P','P.property_id = B.property_id');
        $booking->join(ROOMS.' R','R.room_id = B.room_id');
        if($post['book_list'] == 'current'){
            $booking->where('booking_status','booked');
            $booking->where('DATE(booking_checkout) >=',date('Y-m-d'));
        }
        $booking->groupBy('B.booking_id');
        if(!empty($post['search_keyword']))
        {
            $k = $post['search_keyword'];
            $booking->where("(R.room_type LIKE '%$k%' OR P.property_name LIKE '%$k%' OR C.customer_fname LIKE '%$k%')");    
        }

        $tempdb = clone $booking; //to get rows for pagination
        $total = $tempdb->countAllResults();

        $booking->limit($limit,$offset);
        $result = $booking->get()->getResultArray();
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

    function updateProfile($post){
        $email = $post['email'];
        if($email != $post['details']['admin_email']){
            $check = $this->isEmailExist($email);
            if($check['status']){
                $res['status'] = 0;
                $res['message'] = lang('App.email_exist');
                return $res;
            }
        }

        $data = array(
                    'admin_fname'=>$post['fname'],
                    'admin_mname'=>$post['mname'],
                    'admin_lname'=>$post['lname'],
                    'admin_email'=>$post['email'],
                    'admin_phone'=>$post['phone'],
                    'admin_its'=>$post['its_id'],
                    'address'=>$post['address'],
                    'admin_modifiedon'=>date('Y-m-d H:i:s')
                    );
        $updateStatus = $this->CM->updateData(ADMIN,array('admin_uid'=>$post['admin_uid']),$data);
        $res['status'] = 1;
        $res['message'] = $updateStatus?lang('App.update_success'):lang('App.up_to_date');
        return $res;
    }

    function isEmailExist($email){
        $admin = $this->db->table(ADMIN);
        $admin->select('admin_uid');
        $admin->where('admin_email',$email);
        $data = $admin->get()->getRowArray();
        if(!empty($data)){
            $res['status'] = 1;     
        }else{
            $res['status'] = 0;
        }
        return $res;
    }

    function changePassword($post){
        $currentPass = $post['current_password'];
        $password = $post['password'];
        $cPassword = $post['confirm_password'];

        if($currentPass != decrypt($post['details']['admin_password'])){
            $res['status'] = 0;
            $res['message'] = lang('App.incorrect_current_password');
            return $res;
        }

        if($password != $cPassword){
            $res['status'] = 0;
            $res['message'] = lang('App.password_n_confirm_not_same');
            return $res;
        }

        $data = array(
                    'admin_password'=>encrypt($password),
                    'admin_modifiedon'=>date('Y-m-d H:i:s')
                    );
        $updateStatus = $this->CM->updateData(ADMIN,array('admin_uid'=>$post['admin_uid']),$data);
        $res['status'] = 1;
        $res['message'] = $updateStatus?lang('App.update_success'):lang('App.up_to_date');
        return $res;

    }

    function adminAddBooking($post){
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
                        'booking_status'=>'booked',
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
                $rooms->select('room_id,room_charge,room_extrabedcharge,room_type,room_acroom,room_bedcapacity_min,room_bedcapacity_max');
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
                                'rb_status'=>'booked'
                                );
                $roomBookingId = $this->CM->insertData(ROOM_BOOKINGS,$roomArr);                
            }

            // Add Payment information
            $paymentArr = array(
                                'payment_uid'=>generateUid(),
                                'booking_id'=>$bookingId,
                                'customer_id'=>$customerInfo['customer_id'],
                                'payment_totalamount'=>$total,
                                'payment_amountpaid'=>$amount_paid,
                                'payment_mode'=>'online',
                                'payment_response'=>'booked_by_admin',
                                'payment_status'=>'success',
                                'payment_datetime'=>$date
                                );
            $paymentId = $this->CM->insertData(PAYMENTS,$paymentArr);

            // Add Booking Status information
            $bookingStatusArr = array(
                                'status_uid'=>generateUid(),
                                'booking_id'=>$bookingId,
                                'customer_id'=>$customerInfo['customer_id'],
                                'status'=>'booked',
                                'status_datetime'=>$date
                                );
            $statusId = $this->CM->insertData(BOOKING_STATUS,$bookingStatusArr);         
            
            // Generate Receipt
            $response = $this->generateInvoice($bookingUid);
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
    
    function adminSearchRoomsOLD($post){
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
        $where = "( ( booking_checkin >= '".$check_in."' AND booking_checkin <= '".$check_out."') OR ( booking_checkout >= '".$check_in."' AND booking_checkout <= '".$check_out."') )";
        $bookedRooms = $this->db->table(ROOM_BOOKINGS);
        $bookedRooms->select('room_id');
        $bookedRooms->where($where);
        $bookedRooms->where('rb_status','booked');
        $bookedRoomsIds = $bookedRooms->get()->getResultArray();
        //echo $this->db->getLastQuery(); 
        if(!empty($bookedRoomsIds))
        {
            $ids = array();
            foreach($bookedRoomsIds AS $bri)
            {
                $ids[] = $bri['room_id'];
            }

            //Get other free rooms of property
            $rooms = $this->db->table(ROOMS);
            $rooms->select('room_uid,room_number,room_type,room_bedcapacity_min,room_bedcapacity_max,room_phone,room_charge,room_extrabedcharge,amenity_id');
            $rooms->where('property_id',$propertyInfo['property_id']);
            $rooms->whereNotIn('room_id', $ids);
            if($room_type=='ac')
            {
               $rooms->where('room_acroom',1);
            }
            else
            {
                $rooms->where('room_acroom',0);
            }
            
            $tempdb = clone $rooms; //to get rows for pagination
            $total = $tempdb->countAll();

            $rooms->limit($limit,$offset);
            $roomsAvailable = $rooms->get()->getResultArray();
            //echo $this->db->getLastQuery();
        }
        else
        {
            //Get all rooms of property
            $rooms = $this->db->table(ROOMS);
            $rooms->select('room_uid,room_number,room_type,room_bedcapacity_min,room_bedcapacity_max,room_phone,room_charge,room_extrabedcharge,amenity_id');
            $rooms->where('property_id',$propertyInfo['property_id']);
            if($room_type=='ac')
            {
               $rooms->where('room_acroom',1);
            }
            else
            {
                $rooms->where('room_acroom',0);
            }
            
            $tempdb = clone $rooms; //to get rows for pagination
            $total = $tempdb->countAll();

            $rooms->limit($limit,$offset);
            $roomsAvailable = $rooms->get()->getResultArray();
        }
        if(!empty($roomsAvailable))
        {
            $i=0;
            foreach($roomsAvailable AS $ra)
            {
                $amenities_list = array();

                if($ra['amenity_id']!='')
                {
                    $amenities = explode(',',$ra['amenity_id']);

                    for($a=0;$a<count($amenities);$a++)
                    {
                        // Get amenities information
                        $amenity = $this->db->table(AMENITY);
                        $amenity->select('amenity');
                        $amenity->where('amenity_id',$amenities[$a]);
                        $amenity->where('amenity_status',1);
                        $amenityInfo = $amenity->get()->getRowArray();
                        //echo $this->db->getLastQuery(); 
                        if(empty($amenityInfo))
                        {
                            $res['status'] = 0;
                            $res['message'] = lang('App.amenity').' '.lang('App.details_not_found');
                            return $res;
                        }
                        else
                        {
                            $amenities_list[] = $amenityInfo['amenity'];
                        }
                    }    

                    if(!empty($amenities_list))
                    {
                        $roomsAvailable[$i]['amenities'] = implode(',',$amenities_list);
                    }                    
                }
                unset($roomsAvailable[$i]['amenity_id']);
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

    function adminSearchRooms($post){
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
        //echo $this->db->getLastQuery(); 
        
        //Get all rooms of property
        $rooms = $this->db->table(ROOMS);
        $rooms->select('room_id,room_uid,room_number,room_type,room_bedcapacity_min,room_bedcapacity_max,room_phone,room_charge,room_extrabedcharge,amenity_id');
        $rooms->where('property_id',$propertyInfo['property_id']);
        $rooms->where('room_status',1);
        if($room_type=='ac')
        {
           $rooms->where('room_acroom',1);
        }
        else
        {
            $rooms->where('room_acroom',0);
        }
        
        $tempdb = clone $rooms; //to get rows for pagination
        $total = $tempdb->countAll();

        $rooms->limit($limit,$offset);
        $roomsAvailable = $rooms->get()->getResultArray();
        
        if(!empty($roomsAvailable))
        {
            $i=0;
            foreach($roomsAvailable AS $ra)
            {
                $amenities_list = array();

                if($ra['amenity_id']!='')
                {
                    $amenities = explode(',',$ra['amenity_id']);

                    for($a=0;$a<count($amenities);$a++)
                    {
                        // Get amenities information
                        $amenity = $this->db->table(AMENITY);
                        $amenity->select('amenity');
                        $amenity->where('amenity_id',$amenities[$a]);
                        $amenity->where('amenity_status',1);
                        $amenityInfo = $amenity->get()->getRowArray();
                        //echo $this->db->getLastQuery(); 
                        if(empty($amenityInfo))
                        {
                            $res['status'] = 0;
                            $res['message'] = lang('App.amenity').' '.lang('App.details_not_found');
                            return $res;
                        }
                        else
                        {
                            $amenities_list[] = $amenityInfo['amenity'];
                        }
                    }    

                    if(!empty($amenities_list))
                    {
                        $roomsAvailable[$i]['amenities'] = implode(',',$amenities_list);
                    }                    
                }
                unset($roomsAvailable[$i]['amenity_id']);

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

    function adminCancelBooking($post){
        $booking_uid = $post['booking_uid'];

        // Get booking information
        $booking = $this->db->table(BOOKINGS);
        $booking->select('booking_id,booking_uid,customer_id,booking_checkin,booking_checkout');
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

            $updateData = array('booking_status'=>'cancelled');
            $where = array('booking_id'=>$bookingInfo['booking_id']);
            $this->CM->updateData(BOOKINGS,$where,$updateData);

            $updateData = array('rb_status'=>'cancelled');
            $where = array('booking_id'=>$bookingInfo['booking_id']);
            $this->CM->updateData(ROOM_BOOKINGS,$where,$updateData);

            $updateData = array('payment_status'=>'refund');
            $where = array('booking_id'=>$bookingInfo['booking_id']);
            $this->CM->updateData(PAYMENTS,$where,$updateData);
            
            // Add Booking Status information
            $bookingStatusArr = array(
                                'status_uid'=>generateUid(),
                                'booking_id'=>$bookingInfo['booking_id'],
                                'customer_id'=>$bookingInfo['customer_id'],
                                'status'=>'cancelled',
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

    function getBookedRooms($bookingId){
        $bookingId = (int)$bookingId;
        if($bookingId){
            $booking = $this->db->table(ROOM_BOOKINGS.' RB');
            $booking->join(ROOMS.' R','RB.room_id=R.room_id');
            $booking->where('RB.booking_id',$bookingId);
            //$booking->whereIn('RB.rb_status',['reserved','booked']);
            $result = $booking->get()->getResultArray();
            // echo $this->db->getLastQuery();
            return $result;
        }else{
            return array();
        }
    }

    public function generateInvoice($bookingUid)
    {
        if($bookingUid){
            require_once APPPATH.'Libraries/mpdf/vendor/autoload.php';
            $mpdf = new \Mpdf\Mpdf();

            $booking = $this->db->table(BOOKINGS.' B');
            $booking->select('P.property_uid,P.property_name,P.property_type,P.property_phone,P.property_city,C.customer_id,C.customer_fname,C.customer_mname,C.customer_lname,C.customer_email,C.customer_its,C.customer_address,C.customer_phone,B.*,PM.transaction_id');
            $booking->join(PROPERTY.' P','B.property_id=P.property_id');
            $booking->join(CUSTOMER.' C','B.customer_id=C.customer_id');
            $booking->join(PAYMENTS.' PM','PM.booking_id=B.booking_id');
            $booking->where('B.booking_uid',$bookingUid);
            $row = $booking->get()->getRowArray();
            if($row['booking_id']){

                $roomsBooked = $this->getBookedRooms($row['booking_id']);
                $invData = array();
                $invData['booking'] = $row;
                $invData['rooms'] = $roomsBooked;
                $invData['invoice_date'] = date('d-m-Y');
                // echo '<pre>';print_r($invData);die;

                $invoiceName = $bookingUid.'.pdf';
                
                $html = view('invoice/invoice_new',$invData);
                // echo $html;die;
                $mpdf->WriteHTML($html);
                $mpdf->Output(FCPATH.'uploads/invoice/'.$invoiceName,'F');
                return array('status'=>true,'invoice_name'=>'uploads/invoice/'.$invoiceName,'message'=>'');
            }
        }
        return array('status'=>false,'message'=>lang('App.details_not_found'));
    }

    function adminGetBookings($post){
        
        // Get booking information
        $booking = $this->db->table(BOOKINGS.' AS B');
        $booking->select('B.booking_uid,P.property_name,B.room_adults,B.room_children,B.booking_checkin,B.booking_checkout,B.booking_sgst,B.booking_cgst,B.booking_total,B.booking_amount_paid,B.booking_status,B.booking_addedon,PY.payment_uid,PY.payment_totalamount,PY.payment_amountpaid,PY.payment_mode,PY.payment_response,PY.payment_status,PY.payment_datetime,PY.transaction_id,C.customer_its,C.customer_fname,C.customer_mname,C.customer_lname,C.customer_email,C.customer_phone');
        $booking->join(PROPERTY.' AS P','P.property_id=B.property_id');
        $booking->join(PAYMENTS.' AS PY','PY.booking_id=B.booking_id');
        $booking->join(CUSTOMER.' AS C','C.customer_id=B.customer_id');
        $booking->orderBy('B.booking_addedon','DESC');
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

    function adminGetBookingDetails($post){
        $booking_uid = $post['booking_uid'];

        // Get booking information
        $booking = $this->db->table(BOOKINGS);
        $booking->select('booking_id,discount');
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
            $rooms->select('RB.rb_uid,R.room_number,RB.room_charges,RB.extra_beds,RB.extra_bed_charges,RB.total_extra_bed_charges,RB.booking_checkin,RB.booking_checkout,RB.room_type,RB.room_acroom,RB.room_bedcapacity,RB.rb_status,RB.datetime_added');
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
            $res['discount'] = $bookingInfo['discount'];
            $res['message'] = lang('App.success');
            return $res;
        }
    }

    function adminGetAllReviews($post){
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

        // Get reviews
        $reviews = $this->db->table(REVIEWS.' AS R');
        $reviews->select('R.review_uid,R.review,R.star,R.review_datetime,C.customer_its,C.customer_fname,C.customer_mname,C.customer_lname,C.customer_email,C.customer_phone');
        $reviews->join(CUSTOMER.' AS C','C.customer_id=R.customer_id');

        $reviews = $reviews->get()->getResultArray();

        if(empty($reviews))
        {
            $res['status'] = 0;
            $res['message'] = lang('App.reviews').' '.lang('App.details_not_found');
            return $res;
        }
        else
        {
            $res['status'] = 1;
            $res['data'] = $reviews;
            $res['message'] = lang('App.success');
            return $res;
        }
    }

    public function adminApproveBooking($post){
      $bookingUid = $post['booking_uid'];
      $paymentUid = $post['payment_uid'];

      $paymentDetail = $this->CM->getRowData(PAYMENTS,array('payment_uid'=>$paymentUid));
      if($paymentDetail){

        $data = array( 'payment_status'=>'success' );
        $where = array( 'payment_id'=>$paymentDetail['payment_id'] );
        $this->CM->updateData(PAYMENTS,$where,$data);

        $data = array( 'booking_status'=>'booked' );
        $where = array( 'booking_id'=>$paymentDetail['booking_id'] );
        $this->CM->updateData(BOOKINGS,$where,$data);

        $data = array( 'rb_status'=>'booked' );
        $where = array( 'booking_id'=>$paymentDetail['booking_id'] );
        $this->CM->updateData(ROOM_BOOKINGS,$where,$data);
        
        $bsData = array(
                    'status_uid'=>uniqid(),
                    'booking_id'=>$paymentDetail['booking_id'],
                    'customer_id'=>$paymentDetail['customer_id'],
                    'status'=>'booked',
                    'status_datetime'=>date('Y-m-d H:i:s')
                  );
        $this->CM->insertData(BOOKING_STATUS,$bsData);
        $res['status'] = 1;
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

    public function adminApproveRefund($post){
      $bookingUid = $post['booking_uid'];
      $paymentUid = $post['payment_uid'];

      $paymentDetail = $this->CM->getRowData(PAYMENTS,array('payment_uid'=>$paymentUid));
      if($paymentDetail){

        $data = array( 'payment_status'=>'refund' );
        $where = array( 'payment_id'=>$paymentDetail['payment_id'] );
        $this->CM->updateData(PAYMENTS,$where,$data);

        $data = array( 'booking_status'=>'cancelled' );
        $where = array( 'booking_id'=>$paymentDetail['booking_id'] );
        $this->CM->updateData(BOOKINGS,$where,$data);

        $data = array( 'rb_status'=>'cancelled' );
        $where = array( 'booking_id'=>$paymentDetail['booking_id'] );
        $this->CM->updateData(ROOM_BOOKINGS,$where,$data);
        
        $bsData = array(
                    'status_uid'=>uniqid(),
                    'booking_id'=>$paymentDetail['booking_id'],
                    'customer_id'=>$paymentDetail['customer_id'],
                    'status'=>'cancelled',
                    'status_datetime'=>date('Y-m-d H:i:s')
                  );
        $this->CM->insertData(BOOKING_STATUS,$bsData);
        $res['status'] = 1;
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

    function adminGetAvailability($post){
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
        
        //$where = "( ( booking_checkin >= '".$check_in."' AND booking_checkin <= '".$check_out."') OR ( booking_checkout >= '".$check_in."' AND booking_checkout <= '".$check_out."') )";        
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

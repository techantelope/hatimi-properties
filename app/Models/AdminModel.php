<?php
namespace App\Models;
use CodeIgniter\Model;

class AdminModel extends Model
{
    function __construct(){
        $this->db      = \Config\Database::connect();   
        $this->CM = new CommonModel();
    }

    function profile($post){
        $admin = $this->db->table(ADMIN);
        $admin->where('admin_uid',$post['admin_uid']);
        $data = $admin->get()->getRowArray();
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

    function getDashboardData($post){
        $resData = array();

        $tProperty = $this->db->table(PROPERTY.' P');
        $tProperty->where('P.property_status','1');
        $totalProperties = $tProperty->countAllResults();
        $resData['total_property'] = $totalProperties;

        $cBooking = $this->db->table(BOOKINGS);
        $cBooking->where('booking_status','booked');
        $cBooking->where('DATE(booking_checkout) >=',date('Y-m-d'));
        $total = $cBooking->countAllResults();
        $resData['current_bookings'] = $total;
        // echo $this->db->getLastQuery();

        $aBooking = $this->db->table(BOOKINGS);
        $aBooking->whereIn('booking_status',['booked','reserved']);
        $total = $aBooking->countAllResults();
        $resData['bookings'] = $total;

        $tRooms = $this->db->table(ROOMS.' R');
        $tRooms->where('R.room_status','1');
        $totalRooms = $tRooms->countAllResults();
        $resData['total_rooms'] = $totalRooms;

        $bRooms = $this->db->table(ROOMS.' R');
        $bRooms->join(ROOM_BOOKINGS.' RB','R.room_id=RB.room_id');
        $bRooms->whereIn('RB.rb_status',['booked','reserved']);
        $bRooms->where('R.room_status','1');
        $bRooms->where('DATE(RB.booking_checkout) >=',date('Y-m-d'));
        $bRooms->groupBy('R.room_id');
        $total = $bRooms->get()->getResultArray();
        $resData['booked_rooms'] = count($total);

        $resData['available_rooms'] = $resData['total_rooms']-$resData['booked_rooms'];

        $payments = $this->db->table(PAYMENTS);
        $payments->select('SUM(payment_amountpaid) as total_earnings');
        $payments->where('payment_status','success');
        $earning = $payments->get()->getRowArray();
        $resData['total_earnings'] = $earning['total_earnings']?$earning['total_earnings']:0;

        // Bookings for graph
        $bPerMonth = $this->db->table(BOOKINGS);
        $bPerMonth->select('DATE(booking_addedon) as book_date,count(booking_id) as total_bookings');
        $bPerMonth->whereIn('booking_status',['booked','reserved']);
        $bPerMonth->groupBy('book_date');
        $bpm = $bPerMonth->get()->getResultArray();
        $resData['booking_per_month'] = $bpm;

        $res['status'] = 1;
        $res['data'] = $resData;
        return $res;
    }
}

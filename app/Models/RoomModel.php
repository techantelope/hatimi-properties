<?php
namespace App\Models;
use CodeIgniter\Model;

class RoomModel extends Model
{
    function __construct(){
        $this->db      = \Config\Database::connect();   
        $this->CM = new CommonModel();
    }

    function list($post){

        $sort_field = 'R.room_number';
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

        $sortFieldArray = array('room_type','room_number','room_charge');
        if(isset($post['sort_field']) && in_array($post['sort_field'],$sortFieldArray))
        {
            $sort_field = 'R.'.$post['sort_field'];
        }

        if(isset($post['sort_order']) && in_array($post['sort_order'],array('DESC','ASC')))
        {
            $sort_order = $post['sort_order'];
        }

        $offset = $limit * $page;

        $room = $this->db->table(ROOMS.' R');
        $room->select('R.room_uid,R.room_id,R.room_number,R.room_type,IF(R.room_acroom=1,"yes","no") as is_ac_room,R.room_bedcapacity_min,R.room_bedcapacity_max,R.room_phone,R.room_charge,R.room_description,R.room_extrabedcharge,amenity_id,room_amenities');
        $room->where('R.room_status !=',2);
        $room->groupBy('R.room_id');

        $tempdb = clone $room; //to get rows for pagination
        $total = $tempdb->countAllResults();

        $room->limit($limit,$offset);
        $result = $room->get()->getResultArray();
        // echo $this->db->getLastQuery();

        $this->PM = new PropertyModel();
        if(!empty($result)){
            $dataArr = array();
            foreach ($result as $r) {
                $rId = $r['room_id'];
                $r['images'] = $this->roomImages($rId,1);
                $r['amenities'] = $this->PM->getAmenities($r['amenity_id'],1);

                unset($r['room_id']);
                unset($r['amenity_id']);
                $dataArr[] = $r;
            }
            $res['status'] = 1;
            $res['data'] = $dataArr;
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

    function listByProperty($post){

        $pDetails = $this->CM->getPropertyByUid($post['property_uid'],'property_id');
        if(empty($pDetails['property_id'])){
            $data['status'] = 0;
            $data['data'] = lang('App.details_not_found');
            return $data;
        }
        $propertyId = $pDetails['property_id'];

        $sort_field = 'R.room_number';
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

        $sortFieldArray = array('room_type','room_number','room_charge');
        if(isset($post['sort_field']) && in_array($post['sort_field'],$sortFieldArray))
        {
            $sort_field = 'R.'.$post['sort_field'];
        }

        if(isset($post['sort_order']) && in_array($post['sort_order'],array('DESC','ASC')))
        {
            $sort_order = $post['sort_order'];
        }

        $offset = $limit * $page;

        $room = $this->db->table(ROOMS.' R');
        $room->select('R.room_uid,R.room_id,R.amenity_id,R.room_number,R.room_type,IF(R.room_acroom=1,"yes","no") as is_ac_room,R.room_bedcapacity_min,R.room_bedcapacity_max,R.room_phone,R.room_description,R.room_charge,R.room_extrabedcharge,R.room_status,R.room_amenities,P.property_name,P.property_uid,P.property_address');
        $room->join(PROPERTY.' P' , "R.property_id=P.property_id");
        $room->where('R.property_id',$propertyId);
        $room->where('R.room_status !=',2);
        $room->groupBy('R.room_id');

        $tempdb = clone $room; //to get rows for pagination
        $total = $tempdb->countAllResults();

        $room->limit($limit,$offset);
        $result = $room->get()->getResultArray();
        // echo $this->db->getLastQuery();
        $this->PM = new PropertyModel();
        if(!empty($result)){
            $dataArr = array();
            foreach ($result as $r) {
                $r['images'] = $this->roomImages($r['room_id'],1);
                $r['amenities'] = $this->PM->getAmenities($r['amenity_id'],1);

                unset($r['room_id']);
                unset($r['amenity_id']);
                $dataArr[] = $r;
            }
            $res['status'] = 1;
            $res['data'] = $dataArr;
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

    function updateStatus($post){
        $status = $post['status'];
        $roomUid = $post['room_uid'];
        
        $rDetails = $this->CM->getRoomByUid($roomUid,'room_id');
        $roomId = !empty($rDetails['room_id'])?$rDetails['room_id']:'';

        if(empty($roomId)){
            $res['status'] = 0;
            $res['message'] = lang('App.details_not_found');
            return $res;
        }

        $data = array();
        if($status == 'activate'){
            $data['room_status'] = 1;
        }
        elseif($status == 'deactivate'){
            $data['room_status'] = 0;
        }
        elseif($status == 'delete'){

          $date = date('Y-m-d');
          $sql = "SELECT rb_id FROM ".ROOM_BOOKINGS." WHERE room_id = '$roomId' AND DATE(booking_checkout) >= '$date'";
          $isBookingExist = $this->db->query($sql)->getRowArray();
          if (empty($isBookingExist['rb_id'])) 
          {
            $data['room_status'] = 2;
          }
          else
          {
            $res['status'] = 0;
            $res['message'] = lang('App.cannot_delete_room_booking_exist');
            return $res;
          }
        }
        if($data){
            $updateStatus = $this->CM->updateData(ROOMS,array('room_id'=>$roomId),$data);
            $res['status'] = 1;
            $res['message'] = $updateStatus?lang('App.update_success'):lang('App.up_to_date');
            return $res;
        }else{
            $res['status'] = 0;
            $res['message'] = lang('App.something_wrong');
            return $res;
        }
    }

    function add($post){
        $pDetails = $this->CM->getPropertyByUid($post['property_uid'],'property_id');
        if(empty($pDetails['property_id'])){
            $data['status'] = 0;
            $data['data'] = lang('App.details_not_found');
            return $data;
        }
        
        $propertyId = $pDetails['property_id'];
        $number = $post['number'];
        $type = $post['type'];
        $bedType = $post['bed_type'];
        $capacityMin = $post['capacity_min'];
        $capacityMax = $post['capacity_max'];
        $rent = $post['rent'];
        $extraBedCharge = $post['extra_bed_charge'];
        $description = $post['description'];
        $amenities = $post['amenities'];

        /*$phone = $post['phone'];*/
        // $amenities = (array)$post['amenities'];
        $roomUidPost = $post['room_uid'];
        $images = (array)$post['images'];
        $adminId = $post['details']['admin_id'];
        $date = date('Y-m-d H:i:s');
        // print_r($amenities);die;
        /*$amenityIds = '';
        if($amenities){
            $aIds = '';
            foreach ($amenities as $a) {
                $aIds .= "'".$a->value."',";
            }
            
            $aIds = rtrim($aIds,',');
            if($aIds){
                $amnty = $this->db->table(AMENITY);
                $amnty->select("GROUP_CONCAT(amenity_id) as ids");
                $amnty->where("amenity_uid IN ($aIds)");
                $row = $amnty->get()->getRowArray();
                $amenityIds = $row['ids'];
            }
        }*/
        
        $dataArr = array(
                        'property_id'=>$propertyId,
                        'room_number'=>$number,
                        'room_type'=>$bedType,
                        'room_acroom'=>1,
                        'room_bedcapacity_min'=>$capacityMin,
                        'room_bedcapacity_max'=>$capacityMax,
                        'room_description'=>$description,
                        'room_charge'=>$rent,
                        'room_extrabedcharge'=>$extraBedCharge,
                        'room_amenities'=>$amenities
                        //'room_acroom'=>$type=='ac'?1:0,
                        // 'room_phone'=>$phone,
                        // 'amenity_id'=>$amenityIds,
                    );
        if(empty($post['room_uid'])){
            $roomUid = generateUid();
            $dataArr['room_uid'] = $roomUid;
            $dataArr['room_status'] = 1;
            $roomId = $this->CM->insertData(ROOMS,$dataArr);
        }else{
            $rDetails = $this->CM->getRoomByUid($roomUidPost,'room_id');
            $roomUid = $roomUidPost;
            if(empty($rDetails['room_id'])){
                $res['status'] = 0;
                $res['message'] = lang('App.details_not_found');
                return $res;
            }

            $roomId = $rDetails['room_id'];
            $this->CM->updateData(ROOMS,array('room_id'=>$roomId),$dataArr);
        }

        if($roomId && $post['images']){
            foreach ($images as $img) {
                $imgUid = generateUid();
                $imgArr = array(
                                'image_uid'=>$imgUid,
                                'type'=>'room',
                                'id'=>$roomId,
                                'image_name'=>$img,
                                'image_status'=>1
                            );
                if(file_exists("./uploads/temp/$img")){
                    $imgId = $this->CM->insertData(IMAGES,$imgArr);
                    if($imgId){
                        copy("./uploads/temp/$img", "./uploads/room/$img");
                        unlink("./uploads/temp/$img");
                    }
                }
            }
        }
        if($roomId){
            $res['status'] = 1;
            $res['data'] = array('room_uid'=>$roomUid);
            $res['message'] = lang('App.update_success');
            return $res;
        }else{
            $res['status'] = 0;
            $res['data'] = array('room_uid'=>'');
            $res['message'] = lang('App.something_wrong');
            return $res;
        }
    }

    function assignedNumbersByProperty($post){
        $pDetails = $this->CM->getPropertyByUid($post['property_uid'],'property_id');
        if(empty($pDetails['property_id'])){
            $data['status'] = 0;
            $data['data'] = lang('App.details_not_found');
            return $data;
        }
        
        $propertyId = $pDetails['property_id'];
        $p = $this->db->table(ROOMS);
        $p->select("GROUP_CONCAT(room_number) as assigned_number");
        $p->where("property_id",$propertyId);
        $row = $p->get()->getRowArray();
        $data['status'] = 1;
        $data['data']['assigned_number'] = explode(',', $row['assigned_number']);
        return $data;
    }

    function roomImages($roomId,$status=''){
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

    function details($post){
        $details = $this->CM->getRoomByUid($post['room_uid']);
        $data['status'] = 0;
        if($details){
            $this->PM = new PropertyModel();
            $details['images'] = $this->roomImages($details['room_id'],1);
            $details['amenities'] = $this->PM->getAmenities($details['amenity_id'],1);
            unset($details['amenity_id']);
            unset($details['room_id']);
            $data['status'] = 1;
        }

        $data['data'] = $details;
        return $data;
    }
}

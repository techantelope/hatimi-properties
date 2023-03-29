<?php
namespace App\Models;
use CodeIgniter\Model;

class PropertyModel extends Model
{
    function __construct(){
        $this->db      = \Config\Database::connect();   
        $this->CM = new CommonModel();
        $this->RM = new RoomModel();
    }

    function propertyList($post){

        $sort_field = 'P.property_modifiedon';
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

        $sortFieldArray = array('property_name','property_modifiedon','property_addedon');
        if(isset($post['sort_field']) && in_array($post['sort_field'],$sortFieldArray))
        {
            $sort_field = 'P.'.$post['sort_field'];
        }

        if(isset($post['sort_order']) && in_array($post['sort_order'],array('DESC','ASC')))
        {
            $sort_order = $post['sort_order'];
        }

        $offset = $limit * $page;

        $property = $this->db->table(PROPERTY.' P');
        $property->select('P.property_uid,P.property_name,P.property_type,P.property_phone,P.property_address,P.property_addedon,P.property_modifiedon,P.property_status,P.property_description,P.property_amenities');
        $property->groupBy('P.property_id');
        if(!empty($post['search_keyword']))
        {
            $k = $post['search_keyword'];
            $property->where("(P.property_name LIKE '%$k%')");    
        }
        if(!empty($post['status']))
        {
            if($post['status'] == 'active'){
                $property->where('P.property_status','1');
            }
        }

        $tempdb = clone $property; //to get rows for pagination
        $total = $tempdb->countAllResults();

        $property->limit($limit,$offset);
        $result = $property->get()->getResultArray();
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

    function updateStatus($post){
        $status = $post['status'];
        $propertyUid = $post['property_uid'];
        
        $pDetails = $this->CM->getPropertyByUid($propertyUid,'property_id');
        $propertyId = !empty($pDetails['property_id'])?$pDetails['property_id']:'';

        if(empty($propertyId)){
            $res['status'] = 0;
            $res['message'] = lang('App.invalid_property');
            return $res;
        }

        $data = array();
        if($status == 'activate'){
            $data['property_status'] = 1;
        }
        elseif($status == 'deactivate'){
            $data['property_status'] = 0;
        }
        elseif($status == 'delete'){
          $date = date('Y-m-d');
          $sql = "SELECT booking_id FROM ".BOOKINGS." WHERE property_id = '$propertyId' AND DATE(booking_checkout) >= '$date'";
          $isBookingExist = $this->db->query($sql)->getRowArray();
          if (empty($isBookingExist['booking_id'])) 
          {
            $data['property_status'] = 2;
          }
          else
          {
            $res['status'] = 0;
            $res['message'] = lang('App.cannot_delete_property_booking_exist');
            return $res;
          }
        }
        if($data){
            $updateStatus = $this->CM->updateData(PROPERTY,array('property_id'=>$propertyId),$data);
            if($status == 'delete' || $status == 'deactivate'){
              $this->CM->updateData(ROOMS,array('property_id'=>$propertyId),array('room_status'=>$data['property_status']));
            }
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
        $name = $post['name'];
        $type = $post['type'];
        $phone = $post['phone'];
        $address = $post['location'];
        $description = $post['description'];     
        $amenities = $post['amenities'];

        $city = $post['city'];
        $propertyUidPost = isset($post['property_uid'])?$post['property_uid']:'';
        $images = (array)$post['images'];
        $adminId = $post['details']['admin_id'];
        $date = date('Y-m-d H:i:s');
        $dataArr = array(
                        'admin_id'=>$adminId,
                        'property_name'=>$name,
                        'property_type'=>$type,
                        'property_phone'=>$phone,
                        'property_address'=>$address,
                        'property_city'=>$city,
                        'property_modifiedon'=>$date,
                        'property_status'=>1,
                        'property_description'=>$description,
                        'property_amenities'=>$amenities
                    );
        if(empty($post['property_uid'])){
            $propertyUid = generateUid();
            $dataArr['property_uid'] = $propertyUid;
            $dataArr['property_addedon'] = $date;
            $propertyId = $this->CM->insertData(PROPERTY,$dataArr);
        }else{
            $pDetails = $this->CM->getPropertyByUid($propertyUidPost,'property_id');
            $propertyUid = $propertyUidPost;
            if(empty($pDetails['property_id'])){
                $res['status'] = 0;
                $res['message'] = lang('App.invalid_property');
                return $res;
            }

            $propertyId = $pDetails['property_id'];
            $this->CM->updateData(PROPERTY,array('property_id'=>$propertyId),$dataArr);
        }

        if($propertyId){
            if($images && $post['images']){
                foreach ($images as $img) {
                    $imgUid = generateUid();
                    $imgArr = array(
                                    'image_uid'=>$imgUid,
                                    'type'=>'property',
                                    'id'=>$propertyId,
                                    'image_name'=>$img,
                                    'image_status'=>1
                                );
                    if(file_exists("./uploads/temp/$img")){
                        $imgId = $this->CM->insertData(IMAGES,$imgArr);
                        if($imgId){
                            copy("./uploads/temp/$img", "./uploads/property/$img");
                            unlink("./uploads/temp/$img");
                        }
                    }
                }
            }

            $res['status'] = 1;
            $res['data'] = array('property_uid'=>$propertyUid);
            $res['message'] = lang('App.update_success');
            return $res;
        }else{
            $res['status'] = 0;
            $res['data'] = array('property_uid'=>'');
            $res['message'] = lang('App.something_wrong');
            return $res;
        }
    }

    function activeListWithRooms($post){

        $sort_field = 'P.property_modifiedon';
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

        $sortFieldArray = array('property_name','property_modifiedon','property_addedon');
        if(isset($post['sort_field']) && in_array($post['sort_field'],$sortFieldArray))
        {
            $sort_field = 'P.'.$post['sort_field'];
        }

        if(isset($post['sort_order']) && in_array($post['sort_order'],array('DESC','ASC')))
        {
            $sort_order = $post['sort_order'];
        }

        $offset = $limit * $page;

        $property = $this->db->table(PROPERTY.' P');
        $property->select('P.property_id,P.property_uid,P.property_name,P.property_type,P.property_phone,P.property_address,,P.property_city,P.property_addedon,P.property_modifiedon,P.property_status,COUNT(R.room_id) as total_rooms,P.property_description,P.property_amenities');
        $property->join(ROOMS.' R','P.property_id=R.property_id','LEFT');
        $property->where('P.property_status','1');
        $property->groupBy('P.property_id');
        if(!empty($post['search_keyword']))
        {
            $k = $post['search_keyword'];
            $property->where("(P.property_name LIKE '%$k%')");    
        }

        $tempdb = clone $property; //to get rows for pagination
        $total = $tempdb->countAllResults();

        $property->limit($limit,$offset);
        $result = $property->get()->getResultArray();
        // echo $this->db->getLastQuery();

        $propData = array();
        if($result){
            foreach ($result as $prop) {
                $propertyId = $prop['property_id'];

                $prop['available_rooms'] = 0;
                if($prop['total_rooms'] > 0){
                    $rooms = $this->getAvailableRoomByProperty($propertyId);
                    $prop['available_rooms'] = $rooms['total'];
                }

                $prop['property_images'] = $this->propertyImages($propertyId);
                $prop['property_image_path'] = SITEURL.'/uploads/property/';

                unset($prop['property_id']);
                $propData[] = $prop;
            }
        }

        if(!empty($result)){
            $res['status'] = 1;
            $res['data'] = $propData;
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

    function getAvailableRoomByProperty($propertyId){
        if($propertyId){
            $date = date('Y-m-d'); 
            $room = $this->db->table(ROOMS.' R');
            $room->select('R.*');
            $room->join(ROOM_BOOKINGS.' RB',"RB.room_id != R.room_id AND (DATE(RB.booking_checkout) < '$date' OR RB.rb_status NOT IN ('booked','reserved'))");
            $room->where('R.property_id',$propertyId);
            $room->groupBy('R.room_id');
            $result = $room->get()->getResultArray();
            $data = array();
            if($result){
                foreach ($result as $d) {
                    $d['amenities'] = $this->getAmenities($d['amenity_id'],1);
                    $d['room_images'] = $this->RM->roomImages($d['room_id'],1);
                    $d['room_image_path'] = SITEURL.'/uploads/room/';
                    
                    unset($d['room_id']);
                    unset($d['amenity_id']);
                    $data[] = $d;
                }
            }
        // echo $this->db->getLastQuery();
            return array('status'=>1,'total'=>count($result),'data'=>$data);
        }else{
            return array('status'=>0,'total'=>0,'data'=>[]);
        }
    }

    function getBookedRoomByProperty($propertyId){
        if($propertyId){
            $date = date('Y-m-d'); 
            $room = $this->db->table(ROOMS.' R');
            $room->select('R.*');
            $room->join(BOOKINGS.' B',"B.room_id = R.room_id AND DATE(B.booking_checkout) >= '$date' AND B.booking_status IN ('booked','reserved')");
            $room->where('R.property_id',$propertyId);
            $room->groupBy('R.room_id');
            $result = $room->get()->getResultArray();
            return array('status'=>1,'total'=>count($result),'data'=>$result);
        }else{
            return array('status'=>0,'total'=>0,'data'=>[]);
        }
    }

    function getRoomStatisticsByProperty($post)
    {
        $propertyUid = $post['property_uid'];
        
        // Get properties information
        $property = $this->db->table(PROPERTY.' AS P');
        $property->select('P.property_id');
        $property->where('P.property_status',1);
        $property->where('P.property_uid',$propertyUid);
        $propertyInfo = $property->get()->getRowArray();

        if(empty($propertyInfo))
        {
            $res['status'] = 0;
            $res['message'] = lang('App.property').' '.lang('App.details_not_found');
            return $res;
        }

        $totalRooms = 0;
        $totalPersons = 0;
        
        $rooms = $this->db->table(ROOMS.' AS R');
        $rooms->where('R.room_status',1);
        $rooms->where('R.property_id',$propertyInfo['property_id']);
        $roomsInfo = $rooms->get()->getResultArray();

        if(!empty($roomsInfo))
        {
            foreach($roomsInfo as $ri)
            {
                $totalRooms++;
                $totalPersons = $totalPersons + $ri['room_bedcapacity_max'];
            }
        }            
        
        $res['status'] = 1;
        $res['total_rooms'] = $totalRooms;
        $res['total_persons'] = $totalPersons;
        $res['message'] = lang('App.success');
        return $res;
    }

    function details($post){
        $propertyUid = $post['property_uid'];
        $details = $this->CM->getPropertyByUid($propertyUid);
        if($details){
            $rooms = $this->getAllRoomByProperty($details['property_id'],'room_uid,room_id,room_number,room_type,room_acroom,room_bedcapacity_min,room_bedcapacity_max,room_phone,room_charge,room_extrabedcharge,room_status,amenity_id,room_description,room_amenities',1);
            $details['rooms'] = $rooms['data'];
            $details['property_images'] = $this->propertyImages($details['property_id']);
            $details['property_image_path'] = SITEURL.'/uploads/property/';
            return array('status'=>1,'details'=>$details,'message'=>'');
        }else{
            return array('status'=>0,'details'=>'','message'=>lang('App.details_not_found'));
        }
    }

    function getAllRoomByProperty($propertyId,$column='*',$status=''){
        if($propertyId){
            $room = $this->db->table(ROOMS.' R');
            $room->select($column);
            $room->where('R.property_id',$propertyId);
            if($status){
                $room->where('R.room_status',$status);    
            }
            $room->groupBy('R.room_id');
            $result = $room->get()->getResultArray();
            $data = array();
            if($result){
                foreach ($result as $d) {
                    $d['amenities'] = $this->getAmenities($d['amenity_id'],1);
                    $d['room_images'] = $this->RM->roomImages($d['room_id'],1);
                    $d['room_image_path'] = SITEURL.'/uploads/room/';
                    
                    unset($d['room_id']);
                    unset($d['amenity_id']);
                    $data[] = $d;
                }
            }
            return array('status'=>1,'total'=>count($data),'data'=>$data);
        }else{
            return array('status'=>0,'total'=>0,'data'=>[]);
        }
    }

    function getAmenities($amenities,$status=''){
        if($amenities){
            $amenities = explode(',', $amenities);
            $amenity = $this->db->table(AMENITY);
            $amenity->select('amenity_uid,amenity');
            $amenity->whereIn('amenity_id',$amenities);
            if($status){
                $amenity->where('amenity_status',$status);    
            }
            $result = $amenity->get()->getResultArray();
            return $result;
        }
        return array();
    }

    function propertyImages($propertyId,$status=''){
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

    function deleteImage($post){
        $imageUid = $post['image_uid'];
        
        $image = $this->db->table(IMAGES);
        $image->select('image_name,type,image_id');
        $image->where('image_uid',$imageUid);
        $row = $image->get()->getRowArray();
        if(!empty($row['image_id'])){
            $entity = $row['type'];

            $dimage = $this->db->table(IMAGES);
            $dimage->where('image_id', $row['image_id']);
            $delete = $dimage->delete();
            if($delete && file_exists("./uploads/$entity/".$row['image_name'])){
                unlink("./uploads/$entity/".$row['image_name']);
            }
            $data['status'] = 1;
            $data['message'] = lang('App.deleted_success');
        }else{
            $data['status'] = 0;
            $data['message'] = lang('App.details_not_found');
        }
        return $data;
    }
}

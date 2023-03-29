<?php
namespace App\Models;
use CodeIgniter\Model;

class CommonModel extends Model
{
    function __construct(){
        $this->db      = \Config\Database::connect();   
    }

    function getRowData($table,$where){
        $builder = $this->db->table($table);

        if($where){
            $builder->where($where);
        }
        $data = $builder->get()->getRowArray();
        return $data;
    }

    function getResultData($table,$where){
        $builder = $this->db->table($table);

        if($where){
            $builder->where($where);
        }
        $data = $builder->get()->getResultArray();
        return $data;
    }

    function insertData($table,$data){
        $builder = $this->db->table($table);
        $builder->insert($data);
        return $this->db->insertID();
    }

    function updateData($table,$where,$data){
        $builder = $this->db->table($table);
        $builder->where($where);
        return $builder->update($data);
    }

    function getPropertyByUid($uid,$column='*'){
        if($uid){
            $builder = $this->db->table(PROPERTY);
            $builder->select($column);
            $builder->where('property_uid',$uid);
            $data = $builder->get()->getRowArray();
            return $data;
        }
        return false;
    }

    function getPropertyById($id,$column='*'){
        if($uid){
            $builder = $this->db->table(PROPERTY);
            $builder->select($column);
            $builder->where('property_id',$id);
            $data = $builder->get()->getRowArray();
            return $data;
        }
        return false;
    }

    function getRoomByUid($uid,$column='*'){
        if($uid){
            $builder = $this->db->table(ROOMS);
            $builder->select($column);
            $builder->where('room_uid',$uid);
            $data = $builder->get()->getRowArray();
            return $data;
        }
        return false;
    }

    function getRoomById($id,$column='*'){
        if($uid){
            $builder = $this->db->table(ROOMS);
            $builder->select($column);
            $builder->where('room_id',$id);
            $data = $builder->get()->getRowArray();
            return $data;
        }
        return false;
    }

    function getPromoCodeById($id,$column='*'){
        if($id){
            $builder = $this->db->table(PROMO_CODE);
            $builder->select($column);
            $builder->where('pc_id',$id);
            $data = $builder->get()->getRowArray();
            return $data;
        }
        return false;
    }

    function getPromoCodeByUid($uid,$column='*'){
        if($uid){
            $builder = $this->db->table(PROMO_CODE);
            $builder->select($column);
            $builder->where('pc_uid',$uid);
            $data = $builder->get()->getRowArray();
            return $data;
        }
        return false;
    }
}

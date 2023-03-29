<?php
namespace App\Models;
use CodeIgniter\Model;
use App\Models\CommonModel;

class ItsAuthModel extends Model
{
    function __construct(){
        $this->db      = \Config\Database::connect();   
        $this->CM = new CommonModel();  
        $this->endpoint = 'https://ejas.its52.com/ejamaatservices.asmx'; 
        $this->key = 'hrqsy4AJ0LwitKNsGO8yEynmi63IYmh8'; 
    }

    function login($post){
      $sid = $post['sid'];
      $token = $post['token'];
      $dt = $post['dt'];
      $token = str_replace('%3D', "%3d", $token);
      /*echo $token;
      echo oneLoginDecryptData($token);die;*/
      if(oneLoginDecryptData($token,true) == LOGIN_TOKEN_KEY)
      {
        $data = oneLoginDecryptData($dt);
        $data = explode(',', $data);
        if(!empty(removeSpecialCharacter($data[0])))
        {
          $itsNo = removeSpecialCharacter($data[0]);

          $customer = $this->db->table(CUSTOMER);
          $customer->select('customer_uid,customer_id,customer_its,customer_fname,customer_mname,customer_lname,customer_email,customer_phone,customer_addedon,customer_status,customer_modifiedon,customer_lastloginon');
          $customer->where('customer_its',$itsNo);
          $data = $customer->get()->getRowArray();
          /*if(!empty($data)){
              
              if($data['customer_status'] == 0)
              {
                  $res['status'] = 0;
                  $res['message'] = lang('App.inactive_account');
                  return $res;
              }
              elseif($data['customer_status'] == 1)
              {
                  $res['status'] = 1;
                  $res['message'] = lang('App.login_success');
                  $res['data'] = $data;
                  return $res;
              }
          }
          else
          {*/
          $apiResponse = $this->executeITSAPI($itsNo);
          $plainXML = mungXML( trim($apiResponse) );
          $arrayResult = json_decode(json_encode(SimpleXML_Load_String($plainXML, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
          $apiData = !empty($arrayResult['soap_Body']['Estate_Dept_HatemiResponse']['Estate_Dept_HatemiResult']['diffgr_diffgram']['NewDataSet']['Table'])?$arrayResult['soap_Body']['Estate_Dept_HatemiResponse']['Estate_Dept_HatemiResult']['diffgr_diffgram']['NewDataSet']['Table']:'';
          $Remark       = !empty($apiData['Remark'])?$apiData['Remark']:'';
          $itsId        = !empty($apiData['ITS_ID'])?$apiData['ITS_ID']:'';
          $Fullname     = !empty($apiData['Fullname'])?$apiData['Fullname']:'';
          $Age          = !empty($apiData['Age'])?$apiData['Age']:'';
          $Gender       = !empty($apiData['Gender'])?$apiData['Gender']:'';
          $Email        = !empty($apiData['Email'])?$apiData['Email']:'';
          $Mobile_no    = !empty($apiData['Mobile_no'])?$apiData['Mobile_no']:'';
          $Whatsapp_No  = !empty($apiData['Whatsapp_No'])?$apiData['Whatsapp_No']:'';
          $Address      = !empty($apiData['Address'])?$apiData['Address']:'';
          $Nationality  = !empty($apiData['Nationality'])?$apiData['Nationality']:'';
          $Vatan        = !empty($apiData['Vatan'])?$apiData['Vatan']:'';

          if($itsId)
          {
            $dataArr = array(
                          'customer_its'=>$itsId,
                          'customer_fname'=>$Fullname,
                          'customer_email'=>$Email,
                          'customer_phone'=>$Mobile_no,
                          'customer_modifiedon'=>$date,
                          'customer_lastloginon'=>$date,
                          'customer_address'=>$Address
                      );
            if(empty($data)){
              $date = date('Y-m-d H:i:s');
              $customerUid = generateUid();
              
              $dataArr['customer_uid'] = $customerUid;
              $dataArr['customer_addedon'] = $date;
              $dataArr['customer_status'] = 1;
              $dataArr['its_data'] = $apiResponse;

              $customerId = $this->CM->insertData(CUSTOMER,$dataArr);
            }
            else
            {
              $this->CM->updateData(CUSTOMER,array('customer_id'=>$data['customer_id']),$dataArr);
              $dataArr['customer_uid'] = $data['customer_uid'];
              $dataArr['customer_status'] = 1;
            }
            // echo $customerId;die;
            $res['status'] = 1;
            $res['message'] = lang('App.login_success');
            unset($dataArr['its_data']);
            $res['data'] = $dataArr;
            return $res;
          }
          else
          {
            $res['status'] = 0;
            $res['message'] = lang('App.its_not_found');
            return $res;
          }
          // }
        }
      }
      else
      {
        $res['status'] = 0;
        $res['message'] = lang('App.invalid_request');
        return $res;
      }
    }

    function executeITSAPI($itsNo)
    {
      $curl = curl_init();

      curl_setopt_array($curl, array(
        CURLOPT_URL => $this->endpoint,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS =>'<?xml version="1.0" encoding="utf-8"?>
                                <soap:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
                                  <soap:Body>
                                    <Estate_Dept_Hatemi xmlns="http://localhost/eJAS/EjamaatServices">
                                      <ITS_ID>'.$itsNo.'</ITS_ID>
                                      <strKey>'.$this->key.'</strKey>
                                    </Estate_Dept_Hatemi>
                                  </soap:Body>
                                </soap:Envelope>',
        CURLOPT_HTTPHEADER => array(
          'Content-Type: text/xml'
        ),
      ));

      $response = curl_exec($curl);

      curl_close($curl);
      return $response;
    }
}

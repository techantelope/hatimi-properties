<?php
namespace App\Models;
use CodeIgniter\Model;

class RazorpayModel extends Model
{
    function __construct(){
        $this->db      = \Config\Database::connect();   
        $this->CM = new CommonModel();
        $this->key = 'rzp_test_7lss1FNDV22cwU'; //Sandbox
        $this->secret = 'ZYE1h5auJQhCxx7bjcn04OzN'; //Sandbox

        $this->auth = base64_encode($this->key.":".$this->secret);
        $this->endpoint = 'https://api.razorpay.com/v1';
    }

    function createPayLink($data){
        $status = 0;
        if($data['amount'] && $data['transaction_uid'] && $data['cust_name'] && $data['cust_email'] && $data['cust_phone'] && $data['callback_url'] && $data['note']){

            $expirytime = strtotime('+16 Minutes');
            $curl = curl_init();

            $payArr = array(
                            'amount'=>$data['amount']*100,
                            'currency'=>"INR",
                            'accept_partial'=>false,
                            'first_min_partial_amount'=>0,
                            'expire_by'=>$expirytime,
                            'reference_id'=>$data['transaction_uid'],
                            'description'=>'',
                            'customer'=>array('name'=>$data['cust_name'],'contact'=>$data['cust_phone'],'email'=>$data['cust_email']),
                            'notify'=>array('sms'=>true,'email'=>true),
                            'reminder_enable'=>true,
                            'notes'=>array('Transaction for'=>$data['note']),
                            'callback_url'=>$data['callback_url'],
                            'callback_method'=>'get'
                        );
            $payStr = json_encode($payArr);
            // echo $payStr;die;

            curl_setopt_array($curl, array(
              CURLOPT_URL => $this->endpoint.'/payment_links',
              CURLOPT_RETURNTRANSFER => true,
              CURLOPT_ENCODING => '',
              CURLOPT_MAXREDIRS => 10,
              CURLOPT_TIMEOUT => 0,
              CURLOPT_FOLLOWLOCATION => true,
              CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
              CURLOPT_CUSTOMREQUEST => 'POST',
              CURLOPT_POSTFIELDS =>$payStr,
              CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
                "Authorization: Basic ".$this->auth,
              ),
            ));

            $response = curl_exec($curl);
            curl_close($curl);

            $arr = json_decode($response,true);
            $this->log('create_pay_link',$arr,$payArr);
            
            if(!empty($arr['error'])){
                return ['pay_url'=>'','pay_id'=>'','expire_by'=>'','response_array'=>$arr,'response_json'=>$response,'msg'=>$arr['error']['description'],'status'=>0];
            }else{
                $status = !empty($arr['short_url'])?1:0;
                return ['pay_url'=>$arr['short_url'],'pay_id'=>$arr['id'],'expire_by'=>date('Y-m-d H:i:s',strtotime($arr['expire_by'])),'response_array'=>$arr,'response_json'=>$response,'msg'=>'Created successfully','status'=>$status];

            }
        }else{
            return ['pay_url'=>'','pay_id'=>'','expire_by'=>'','response_array'=>'','response_json'=>'','msg'=>'Amount, Transaction UID, Customer name, Customer phone number, Customer email, Callback URL and Note are required fields.','status'=>$status];
        }
    }

    function normalRefund($data){
        $paymentId = !empty($data['payment_id'])?$data['payment_id']:'';
        $amount = !empty($data['amount'])?(int)$data['amount']:0;
        if($paymentId == ''){
            return array('status'=>0,'msg'=>lang('App.invalid_payment_id'));
            exit();
        }
        if($amount < 1){
            return array('status'=>0,'msg'=>lang('App.invalid_amount'));
            exit();
        }

        $trnx = $this->fetchtransfer($paymentId);
        if($trnx['status'] == 0){
            return array('status'=>0,'msg'=>$trnx['msg']);
            exit();
        }
        if(!isset($trnx['amount']) || $trnx['amount'] != $amount){
            return array('status'=>0,'msg'=>lang('App.transaction_amount_missmatch'));
            exit();
        }
        if($trnx['pay_status'] != 'captured'){
            $msg = str_replace('{STATE}', $trnx['pay_status'], lang('App.transaction_in_process'));
            return array('status'=>0,'msg'=>$msg);
            exit();
        }
        $amount = $trnx['amount'];

        $curl = curl_init();

        curl_setopt_array($curl, array(
          CURLOPT_URL => $this->endpoint.'/payments/'.$paymentId.'/refund',
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => '',
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 0,
          CURLOPT_FOLLOWLOCATION => true,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => 'POST',
          CURLOPT_POSTFIELDS =>'{
                                   "amount": '.($amount*100).'
                                }',
          CURLOPT_HTTPHEADER => array(
            'Authorization: Basic '.$this->auth,
            'Content-Type: application/json'
          ),
        ));

        $response = curl_exec($curl);
        curl_close($curl);

        $arr = json_decode($response,true);
        $this->log('normal_refund',$arr,$data);

        $status = 1;
        $msg = lang('App.refund_initiated_successfully');
        return array('status'=>$status,'msg'=>$msg,'response_array'=>$arr,'response_json'=>$response);
    }

    function instantRefund($data){
        $paymentId = isset($data['payment_id'])?$data['payment_id']:'';
        $amount = isset($data['amount'])?(int)$data['amount']:0;
        if($paymentId == ''){
            return array('status'=>0,'msg'=>lang('App.invalid_payment_id'));
            exit();
        }
        if($amount < 1){
            return array('status'=>0,'msg'=>lang('App.invalid_amount'));
            exit();
        }

        $trnx = $this->fetchtransfer($paymentId);
        if($trnx['status'] == 0){
            return array('status'=>0,'msg'=>$trnx['msg']);
            exit();
        }
        if(!isset($trnx['amount']) || $trnx['amount'] != $amount){
            return array('status'=>0,'msg'=>lang('App.transaction_amount_missmatch'));
            exit();
        }
        if($trnx['pay_status'] != 'captured'){
            $msg = str_replace('{STATE}', $trnx['pay_status'], lang('App.transaction_in_process'));
            return array('status'=>0,'msg'=>$msg);
            exit();
        }
        $amount = $trnx['amount'];

        $curl = curl_init();

        curl_setopt_array($curl, array(
          CURLOPT_URL => $this->endpoint.'/payments/'.$paymentId.'/refund',
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => '',
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 0,
          CURLOPT_FOLLOWLOCATION => true,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => 'POST',
          CURLOPT_POSTFIELDS =>'{
                                   "amount": '.($amount*100).'
                                }',
          CURLOPT_HTTPHEADER => array(
            'Authorization: Basic '.$this->auth,
            'Content-Type: application/json'
          ),
        ));

        $response = curl_exec($curl);
        curl_close($curl);

        $arr = json_decode($response,true);
        $this->log('instant_refund',$arr,$data);

        $status = 1;
        $msg = lang('App.refund_initiated_successfully');
        return array('status'=>$status,'msg'=>$msg,'response_array'=>$arr,'response_json'=>$response);
    }

    function fetchtransfer($paymentId){
        if($paymentId == ''){
            return array('status'=>0,'msg'=>lang('App.invalid_payment_id'),'pay_status'=>'','amount'=>0);
            exit();
        }
        $curl = curl_init();
        curl_setopt_array($curl, array(
          CURLOPT_URL => $this->endpoint.'/payments/'.$paymentId,
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => '',
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 0,
          CURLOPT_FOLLOWLOCATION => true,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => 'GET',
          CURLOPT_HTTPHEADER => array(
            'Authorization: Basic '.$this->auth
          ),
        ));

        $response = curl_exec($curl);
        curl_close($curl);

        $arr = json_decode($response,true);
        $this->log('fetch_transfer',$arr,$paymentId);

        if(empty($arr['id'])){
            return array('status'=>0,'msg'=>lang('App.transaction_not_found'),'pay_status'=>'');
            exit();
        }
        return array('status'=>1,'msg'=>'','amount'=>($arr['amount']/100),'pay_status'=>$arr['status'],'response'=>$arr);
    }

    function log($type,$response,$request){
      $response = $response;
      $content = json_encode(array('type'=>$type,'request'=>$request,'response'=>$response));
      $content = date('Y-m-d H:i:s')." : $content \n";
      $fileName = date('Y-m-d').".txt";
      $myfile = fopen(FCPATH."writable/logs/razorpay/".$fileName, "a");
      fwrite($myfile, $content);
      fclose($myfile);
    }
}

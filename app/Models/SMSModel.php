<?php
namespace App\Models;
use CodeIgniter\Model;

class SMSModel extends Model
{
    function __construct(){
        // $this->db      = \Config\Database::connect();   
        $this->CM = new CommonModel();
        helper(['form', 'url']);
        $this->request = \Config\Services::request();

    }

    function sendSMS_($to,$message){
        if(IS_SMS_ENABLED){
            $account_sid = TWILIO_ACC_SID;
            $auth_token = TWILIO_AUTH_TOKEN;
            try {
                $client = new Client($account_sid, $auth_token);
                $response = $client->messages->create($to,
                    array(
                        'from' => TWILIO_FROM_NUMBER,
                        'body' => $message
                    )
                );
                
            } catch (Exception $e) {
                //print_r($e);
            }
            return true;
        }
        return true;
    }

    function sendSMS($numbers,$message)
    {
      // Account details
      $apiKey = urlencode('NGQ3NzMyNmEzMzc2NDkzNzc5NmM1NDQyNTM0OTU0Njg=');
      // Message details
      $sender = urlencode('HTMP');
      $message = rawurlencode('This is your message');
       
      // Prepare data for POST request
      $data = array('apikey' => $apiKey, 'numbers' => $numbers, 'sender' => $sender, 'message' => $message);
      // print_r($data);die;
      // Send the POST request with cURL
      $ch = curl_init('https://api.textlocal.in/send/');
      curl_setopt($ch, CURLOPT_POST, true);
      curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      $response = curl_exec($ch);
      curl_close($ch);
      // Process your response here
      echo $response;
    }
}

<?php
namespace App\Models;
use CodeIgniter\Model;
use CodeIgniter\Files\File;

class WhatsappModel extends Model
{
    function __construct(){
        // $this->db      = \Config\Database::connect();   
        $this->CM = new CommonModel();
        helper(['form', 'url']);
        $this->request = \Config\Services::request();

    }

    function sendBookingConfirmation($to,$name,$bookingId,$filepath){
      /*$str = '{
  "to": "recipient_wa_id",
  "type": "template",
  "template": {
    "namespace": "your-namespace",
    "language": {
      "policy": "deterministic",
      "code": "your-language-and-locale-code"
    },
    "name": "your-template-name",
    "components": [
    {
      "type" : "header",
      "parameters": [
      {
        "type": "text",
        "text": "replacement_text"
      }
    ]
    },
    {
      "type" : "body",
      "parameters": [
        {
          "type": "text",
          "text": "replacement_text"
        },
        {
          "type": "currency",
          "currency" : {
            "fallback_value": "$100.99",
            "code": "USD",
            "amount_1000": 100990
          }
        },
        {
          "type": "date_time",
          "date_time" : {
            "fallback_value": "February 25, 1977",
            "day_of_week": 5,
            "day_of_month": 25,
            "year": 1977,
            "month": 2,
            "hour": 15,
            "minute": 33,
            "timestamp": 1485470276
          }
        }
      ] 
      }
    ]
  }
}';
echo '<pre>';
print_r(json_decode($str,true));die;*/
      $dArr = array(
                'messaging_product'=>'whatsapp',
                'to'=>$to,
                'type'=>'template',
                'template'=>[
                  'name'=>"sample_flight_confirmation",
                  'language'=>[
                    "code"=>'en_US'
                  ],
                  'components'=>
                      [
                        'type'=>'header',
                        'parameters'=>[
                          [
                            'type'=>'document',
                            'document'=>[
                              'link'=>$filepath
                              ]
                          ]
                        ]
                      ],
                      [
                        'type'=>'body',
                        'parameters'=>[
                          [
                            'type'=>'text',
                            'text'=>$name
                          ],
                          [
                            'type'=>'text',
                            'text'=>$bookingId
                          ],
                          [
                            'type'=>'text',
                            'text'=>$bookingId
                          ]
                        ]
                      ]
                ]
              );
      $jsonData = json_encode($dArr);
      echo '<pre>';
      print_r($jsonData);
die;
      $curl = curl_init();
      curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://graph.facebook.com/v15.0/'.getenv('WHATSAPP_PHONEID').'/messages',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => $jsonData,
        CURLOPT_HTTPHEADER => array(
          'Authorization: Bearer '.getenv('WHATSAPP_ACCESS_TOKEN'),
          'Content-Type: application/json'
        ),
      ));
      $response = curl_exec($curl);
      curl_close($curl);
      $response = json_decode($response,true);
      echo '<pre>';
      print_r($response);
    }
}

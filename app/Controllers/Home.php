<?php

namespace App\Controllers;
use App\Models\BookingsModel;
use App\Models\RazorpayModel;
use App\Models\CommonModel;
use App\Models\EmailModel;

class Home extends BaseController
{
    public function index()
    {
      helper(['form', 'url','common_helper']);
      // echo 'Denied';die;
      // $this->email = new EmailModel();
      $this->CM = new CommonModel();  
      $a = $this->userList();
      echo '<pre>';
      print_r($a);
      die;  
        // echo 'Access Denied';die;
        /*$this->BM = new BookingsModel();
        $this->BM->generateInvoice('test');
        echo 'here';die;
        die;
        return view('welcome_message');*/
    }

    public function testEmail()
    {
      $this->email = new EmailModel();
      echo $this->email->sendEmail('pieces.deepak@gmail.com','$subject','$message');
    }

    public function createPay(){
        $this->RPM = new RazorpayModel();
        $uid = uniqid();
        $data['amount'] = 31;
        $data['transaction_uid'] = $uid;
        $data['cust_name'] = 'Deepak Verma';
        $data['cust_email'] = 'pieces.deepak@gmail.com';
        $data['cust_phone'] = '9098074719';
        $data['callback_url'] = 'http://localhost/razorpay/pay-success.php?payuid='.$uid;
        $data['note'] = 'Test';
        $a = $this->RPM->createPayLink($data);
        echo '<pre>';
        print_r($a);
        // Array ( [payuid] => 636a25702be82 [razorpay_payment_id] => pay_KdV4Wu2oY52sTH [razorpay_payment_link_id] => plink_KdV3zSiYkoT2nm [razorpay_payment_link_reference_id] => 636a25702be82 [razorpay_payment_link_status] => paid [razorpay_signature] => cf87ce86755c057adf3b85a11af64902bb26449035c25924bbabcb9bf4e5680a )
    }

    function refund(){
        $id = 'pay_KdV4Wu2oY52sTH';
        $data['payment_id'] = $id;
        $data['amount'] = 32;
        $this->RPM = new RazorpayModel();
        $a = $this->RPM->normalRefund($data);
        echo '<pre>';
        print_r($a);
    }

    function details(){
        $id = 'pay_KdV4Wu2oY52sTH';
        $this->RPM = new RazorpayModel();
        $a = $this->RPM->fetchtransfer($id);
        echo '<pre>';
        print_r($a);
    }

    function userList(){
      $list = $this->CM->getResultData(CUSTOMER,'');
      if($list)
      {
        foreach ($list as $key => $value) {
          $plainXML = mungXML( trim($value['its_data']) );
          $arrayResult = json_decode(json_encode(SimpleXML_Load_String($plainXML, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
          $apiData = !empty($arrayResult['soap_Body']['Estate_Dept_HatemiResponse']['Estate_Dept_HatemiResult']['diffgr_diffgram']['NewDataSet']['Table'])?$arrayResult['soap_Body']['Estate_Dept_HatemiResponse']['Estate_Dept_HatemiResult']['diffgr_diffgram']['NewDataSet']['Table']:'';
          echo $value['customer_id'].' || ';
          $idara = !empty($apiData['Idara'])?$apiData['Idara']:'';
          $this->CM->updateData(CUSTOMER,array('customer_id'=>$value['customer_id']),array('idara'=>$idara));
          //echo !empty($apiData['Category'])?' || '.$apiData['Category']:'';

          // print_r($apiData);
          echo '<br><hr>';
        }
      }
    }
}

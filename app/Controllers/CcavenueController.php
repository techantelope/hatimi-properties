<?php
namespace App\Controllers;
use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\API\ResponseTrait;
use CodeIgniter\HTTP\RequestInterface;
use App\Models\FrontendModel;
use App\Models\CustomerModel;

class CcavenueController extends ResourceController
{
    public function __construct()
    {
        helper(['form', 'url','common_helper','ccavenue_helper']);
        $this->validation =  \Config\Services::validation();
        $this->request = \Config\Services::request();
        $this->post = (array)$this->request->getVar();
        $this->FEM = new FrontendModel();
        $this->customer = new CustomerModel();
    }

    function requiredCheck($fields){
        if($fields){
            $validation =  \Config\Services::validation();
            $rules = array();
            foreach ($fields as $field) {
                $rules[$field] = array(
                                        "label" => lang('App.'.$field), 
                                        "rules" => "required",
                                        'errors' => [
                                                        'required' => lang('App.'.$field).lang('App.is_required')
                                                    ]
                                        );
            }
            if ($this->validate($rules)) 
            {
                $data['status'] = 1;
                return $data;
            }else{
                $data['status'] = 0;
                $data['data'] = $this->validation->getErrors();
                return $data;
            }
        }
        $data['status'] = 1;
        return $data;
    }

    function basicCheck($post){
        $requiredFields = array( 'customer_uid' );
        $isValid = $this->requiredCheck($requiredFields);

        if ($isValid['status']) 
        {
            $data = $this->FEM->profile($post);
            if($data['status']){
                return $data;
            }else{
                $this->apiresponse($data);
            }
        }else{
            $data['status'] = 0;
            $data['data'] = $isValid['data'];
            $this->apiresponse($data);
        }
    }

    public function generatePayLink($data)
    {
        $customerUid = !empty($data['customer_uid'])?$data['customer_uid']:'';
        $amount = !empty($data['amount'])?$data['amount']:'';
        $cancelUrl = !empty($data['cancel_url'])?$data['cancel_url']:'';
        $callbackUrl = !empty($data['callback_url'])?$data['callback_url']:'';
        $orderId = !empty($data['transaction_uid'])?$data['transaction_uid']:uniqid();
        $tnxUid = rand();

        if(empty($cancelUrl)){
            return array('status'=>0,'message'=>'Cancel url is required');
        }
        if(empty($callbackUrl)){
            return array('status'=>0,'message'=>'Callback url is required');
        }
        if(empty($customerUid)){
            return array('status'=>0,'message'=>'Customer not found');
        }
        if(empty($amount) || $amount <= 0 ){
            return array('status'=>0,'message'=>'Amount should be greater than 0');
        }
        $customerInfo = $this->customer->detailByUid($customerUid);
        if($customerInfo && $customerInfo['status'] == 1){
            $customerInfo = $customerInfo['data'];

            $pgArray = array(
                            'tid'=>$tnxUid,
                            'merchant_id'=>CCAVENUE_MERCHANT_ID,
                            'order_id'=>$orderId,
                            'amount'=>$amount,
                            'currency'=>'INR',
                            'redirect_url'=>$callbackUrl,
                            'cancel_url'=>$cancelUrl,
                            'language'=>'EN',
                            'billing_name'=>$customerInfo['customer_fname'].' '.$customerInfo['customer_lname'],
                            'billing_address'=>'Chawni',
                            'billing_city'=>'Indore',
                            'billing_state'=>'MP',
                            'billing_zip'=>'452001',
                            'billing_country'=>'India',
                            'billing_tel'=>$customerInfo['customer_phone'],
                            'billing_email'=>$customerInfo['customer_email'],
                            'delivery_name'=>$customerInfo['customer_fname'].' '.$customerInfo['customer_lname'],
                            'delivery_address'=>$customerInfo['customer_address'],
                            'delivery_city'=>'',
                            'delivery_state'=>'',
                            'delivery_zip'=>'',
                            'delivery_country'=>'India',
                            'delivery_tel'=>$customerInfo['customer_phone'],
                            'merchant_param1'=>'',
                            'merchant_param2'=>'',
                            'merchant_param3'=>'',
                            'merchant_param4'=>'',
                            'merchant_param5'=>'',
                            'promo_code'=>'',
                            'customer_identifier'=>$customerInfo['customer_uid'],
                            'integration_type'=>'iframe_normal'
                        );
            $merchant_data='';
            foreach ($pgArray as $key => $value){
                $merchant_data.=$key.'='.$value.'&';
            }

            $encrypted_data=CCAencrypt($merchant_data,CCAVENUE_WORKING_KEY); // Method for encrypting the data.
            $production_url=CCAVENUE_URL.'&encRequest='.$encrypted_data.'&access_code='.CCAVENUE_ACCESS_CODE;

            return array('pay_link'=>$production_url,'status'=>1);
        }
        else{
            return array('pay_link'=>'','status'=>0,'message'=>'Customer not found');
        }
    }

    function checkCallback($data){
        $encResponse = !empty($data['encResp'])?$data['encResp']:'';
        if($encResponse){

            //Crypto Decryption used as per the specified working key.
            $rcvdString=CCAdecrypt($encResponse,CCAVENUE_WORKING_KEY);
            if($rcvdString){

                $bank_ref_no = $tracking_id = $order_status="";
                $decryptValues=explode('&', $rcvdString);
                $dataSize=sizeof($decryptValues);
                $resArr = array();
                for($i = 0; $i < $dataSize; $i++) 
                {
                    $information=explode('=',$decryptValues[$i]);
                    $resArr[$information[0]] = $information[1];
                    if($i==3)   $order_status=$information[1];
                    if($i==2)   $bank_ref_no=$information[1];
                    if($i==1)   $tracking_id=$information[1];
                }

                if($order_status==="Success")
                { 
                    return array('status'=>1,'message'=>'Transaction is successful','bank_ref_no'=>$bank_ref_no,'complete_data'=>$resArr);
                }
                else if($order_status==="Aborted")
                {
                    return array('status'=>1,'message'=>'Transaction received, we will keep you posted regarding the status of your order through e-mail');
                }
                else if($order_status==="Failure")
                {
                    return array('status'=>0,'message'=>'Transaction has been declined');
                }
                else
                {
                    return array('status'=>0,'message'=>'Security Error. Illegal access detected');
                }
            }
            else{
                return array('status'=>0,'message'=>'Security Error. Illegal access detected');
            }
        }
        else{
            return array('status'=>0,'message'=>'Empty response');
        }
    }

    function verifyTransaction(){
        $a = $this->checkCallback(['encResp'=>'1bdb4ec0c0238c73ca4dd3054321ec347ddb7c7ae0a9e24e6f65224bcbf44d705fd60991a0a980cb7f06cb5a7c0ec7d4bf83f1c488b3ee987dbe78c0139118f74203da00f893bdd031cbd5fe31782d932cc0461e03940a849e08de7ecac09edc33f8eb1fa1300ddec7357436ca2f68291d6e24e0ff5e2e89ebed2d433e2770ade379843fa24e9e55a2dff672b63ce1d992a3031e909ebdff727397cad468a703bd0950a6a38c3cd0a654e3ae4d445c4d08a5f5cd05b000900d821d00dc33722c40f1a2de64473958b1bfc036ccdb48f74d9dac42e41b7a440456d752b71709c713a8e9b647f2544e33fcb797897c0916abb2fc925d0dd10abfe1be671c681078da34fc366cf384a6e6fb92f6a25151ce47cb5f386720fcad189b1ffe7f05eb5fbae255dc890fa55e310796b9bc91e6ecdae072515cc785da6c753d91bcc76ed4e21ef01121f032d8056cb73113bc546d552dafe77da3bd19997e22b00d91ebaa4a12e24454a4db2eb747ff00f59e4a30d8e1327b98c229f55f1e8fb86abce09015dea70513d66ff8b4b9951779f7fb2e1b3b4c6a3549935c7eb22aad5294cc66c3ae468447ac50cc3a5a423cddb9100ad19c41b4987413949fa4d3200f2712fdabd376c8deb6558d4f64914c6bb68d0613cc758e98cd4a8cc8626731555b34623a9d489b559fe165f21838caf512c35f4f3ad791f330eb31f01493f9d8d17e25ee38a07323484881308195dec0be5f4688b83fb5d20677818b7ce44a3c75d5b32b558162eaaaf12a2ca764e6e25c32d87b0a7c8ab138641376c2fafeef93cca5b81c24aa521180d0ad97efb5f5dbf33bbf2c72a6228b99b2f85e41bf6007c70b206e5bde8f7786dddfb837ddb972259e7b529a757c737ed3ab3924bb5663978bc925b2f9b63386b48a7a22f720632e3f474a93573939824bfd950f9550bf7ccefb40ce1b31df0dfb09b38cceea4e75d97aeeac8540da8118dadc30cf8d881810c72cc52aace83ad852a0bbd52709f8b4f52036823aa0ef84a1102d7fde2e4822dc43244d51963ec39a82f2ddacfd414ee53ce7d19b02daafe5c0be0ea97deee9a1ad6a29ad396c5867b754f8a56f973bbe54b8109e26ac8be8402c2a6fef09459ab28c4f2cc24f5a2cf0f97d6c22f0b7']);
        print_r($a);
        /*$a = $this->generatePayLink($this->post);
        print_r($a);*/
    }
}
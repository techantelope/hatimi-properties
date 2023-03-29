<?php 
$data = '';
$json = file_get_contents('php://input');
if(empty($json)){
    $data = $_REQUEST;
}else{
    $data= json_decode($json,true);
}

/*$content = json_encode($data);
$content = date('Y-m-d H:i:s')." : $content \n";
$fileName = date('Y-m-d')."_webhook.txt";
$myfile = fopen("writable/logs/whatsapp/".$fileName, "a");
fwrite($myfile, $content);
fclose($myfile);*/

// Saving messages to DB
/*$curl = curl_init();
curl_setopt_array($curl, array(
  CURLOPT_URL => 'https://hatimiproperties.com/api/whatsapp-messages',
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_CUSTOMREQUEST => 'POST',
  CURLOPT_POSTFIELDS => $json
));
$response = curl_exec($curl);
curl_close($curl);*/
// echo $response;


echo $data['hub_challenge'];
?>
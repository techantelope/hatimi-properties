<?php 
function encrypt($plaintext)
{
    $encryption = \Config\Services::encrypter();
    $cipher = $encryption->encrypt($plaintext);

    return base64_encode($cipher);
}

function decrypt($ciphertext)
{
    $ciphertext = base64_decode($ciphertext);
    $encryption = \Config\Services::encrypter();
    $plaintext = $encryption->decrypt($ciphertext);

    return $plaintext;
}

function generateUid(){
    $uid = uniqid();
    $uid .= '_'.generateRandomString();
    return strtolower($uid);
}

function generateRandomString($length = 10) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
}
function mungXML($xml)
{
    $obj = SimpleXML_Load_String($xml);
    if ($obj === FALSE) return $xml;

    // GET NAMESPACES, IF ANY
    $nss = $obj->getNamespaces(TRUE);
    if (empty($nss)) return $xml;

    // CHANGE ns: INTO ns_
    $nsm = array_keys($nss);
    foreach ($nsm as $key)
    {
        // A REGULAR EXPRESSION TO MUNG THE XML
        $rgx
        = '#'               // REGEX DELIMITER
        . '('               // GROUP PATTERN 1
        . '\<'              // LOCATE A LEFT WICKET
        . '/?'              // MAYBE FOLLOWED BY A SLASH
        . preg_quote($key)  // THE NAMESPACE
        . ')'               // END GROUP PATTERN
        . '('               // GROUP PATTERN 2
        . ':{1}'            // A COLON (EXACTLY ONE)
        . ')'               // END GROUP PATTERN
        . '#'               // REGEX DELIMITER
        ;
        // INSERT THE UNDERSCORE INTO THE TAG NAME
        $rep
        = '$1'          // BACKREFERENCE TO GROUP 1
        . '_'           // LITERAL UNDERSCORE IN PLACE OF GROUP 2
        ;
        // PERFORM THE REPLACEMENT
        $xml =  preg_replace($rgx, $rep, $xml);
    }

    return $xml;
}
function oneLoginDecryptData($cipherText,$removeSpecial=false) {
    // Mcrypt is UNSECURE and depecated since PHP 7.1, using OpenSSL is recommended as it is resistant to time-replay and side-channel attacks
    // Libsodium, the new defacto PHP cryptography library, does not support AES128 (as it is considerably weak), so we are stuck with OpenSSL

    $token = LOGIN_TOKEN_KEY; // Change to the token issued to your domain

    $key = openssl_pbkdf2($token, 'V*GH^|9^TO#cT', 32, 1000);
    // $key = hash_pbkdf2('SHA1', $key, 'V*GH^|9^TO#cT', 1000, 32, true);
    if($removeSpecial == true)
    {
      $str = removeSpecialCharacter(utf8_decode(openssl_decrypt((urldecode($cipherText)), 'AES-128-CBC', substr($key, 0, 16), OPENSSL_ZERO_PADDING, substr($key, 16, 16))));
    }
    else
    {
      $str = utf8_decode(openssl_decrypt((urldecode($cipherText)), 'AES-128-CBC', substr($key, 0, 16), OPENSSL_ZERO_PADDING, substr($key, 16, 16)));
    }
    return $str;
    // return utf8_decode(trim(mcrypt_decrypt(MCRYPT_RIJNDAEL_128, substr($key, 0, 16), base64_decode(urldecode($cipherText)), MCRYPT_MODE_CBC, substr($key, 16, 16))));
}
function removeSpecialCharacter($str){
  return preg_replace('/[^A-Za-z0-9\-]/', '', $str);
}
?>
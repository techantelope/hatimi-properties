<?php
namespace App\Models;
use CodeIgniter\Model;

class EmailModel extends Model
{
    function __construct(){
        helper(['form', 'url']);
        $this->request = \Config\Services::request();
    }

    function sendEmail($to,$subject,$message,$attach=array())
    {
      // echo $to;
        $email = \Config\Services::email();

        $config['protocol'] = 'smtp';
        $config['SMTPHost'] = 'smtp.eu.mailgun.org';
        $config['SMTPUser'] = 'postmaster@hatimiproperties.com';
        $config['SMTPPass'] = 'eb895cfc05045de02f591124114c3c4d-c2efc90c-d36b4fcc';
        $config['SMTPPort'] = 587;
        $config['newline'] =  "\r\n";
        $config['charset']  = 'iso-8859-1';
        $config['wordWrap'] = true;
        $config['mailType'] = 'html';

        $email->initialize($config);

        $email->setFrom('admin@hatimiproperties.com', 'Hatimiproperties');
        $email->setTo($to);

        $email->setSubject($subject);
        $email->setMessage($message);
        if($attach){
          foreach ($attach as $file) {
            $email->attach($file);
          }
        }

        if($email->send())
        {
          // print_r($email->printDebugger(['headers']));
          
            return true;
        }
        else
        {
          print_r($email->printDebugger(['headers']));
            return false;
        }
    }
}

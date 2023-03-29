<?php
namespace App\Models;
use CodeIgniter\Model;

class MpdfModel extends Model
{
    function __construct(){  
        $this->db      = \Config\Database::connect(); 
        $this->CM = new CommonModel();
        $this->BM = new BookingsModel();
    }

    public function invoice()
    {
        $mpdf = new \Mpdf\Mpdf();
        $html = view('invoice/invoice',[]);
        // echo $html;die;
        $mpdf->WriteHTML($html);
        // $this->response->setHeader('Content-Type', 'application/pdf');
        $mpdf->Output('arjun.pdf','D'); // opens in browser
        //$mpdf->Output('arjun.pdf','D'); // it downloads the file into the user system, with give name
        //return view('welcome_message');
    }
    public function generateInvoice($bookingUid)
    {
        if($bookingUid){
            $booking = $this->db->table(BOOKINGS.' B');
            $booking->select('P.property_name,P.property_uid,C.customer_fname,C.customer_mname,C.customer_lname,C.customer_email,C.customer_its,B.*');
            $booking->join(PROPERTY.' P','B.property_id=P.property_id');
            $booking->join(CUSTOMER.' C','B.customer_id=C.customer_id');
            $booking->where('B.booking_uid',$bookingUid);
            $row = $booking->get()->getRowArray();
            if($row['booking_id']){
                $roomsBooked = $this->BM->getBookedRooms($row['booking_id']);
                $invData = array();
                $invData['booking'] = $row;
                $invData['rooms'] = $roomsBooked;
                $invData['invoice_date'] = date('d-m-Y');
                // echo '<pre>';print_r($invData);die;

                $invoiceName = $bookingUid.'_'.uniqid().'.pdf';
                $mpdf = new \Mpdf\Mpdf();
                $html = view('invoice/invoice',$invData);
                // echo $html;die;
                $mpdf->WriteHTML($html);
                $mpdf->Output('./uploads/invoice/'.$invoiceName,'F');
                return array('status'=>true,'invoice_name'=>$invoiceName,'message'=>'');
            }
        }
        return array('status'=>false,'message'=>lang('App.details_not_found'));
    }
}

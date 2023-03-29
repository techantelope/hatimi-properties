<html>
    <head>
        <title></title>
        <style>
            @page{
                margin-top: 10px;
                margin-bottom: 1px;
                margin-left: 1px;
                margin-right: 1px;
            }
            *{
                font-family: arial;
            }
            table{
                width: 100%                
            }
            td{
                padding: 2px;
                font-size: 12px;
            }
            .brd{
                border-bottom: 1px solid grey;
                padding:10px 5px;
            }
        </style>
    </head>
    <body>
        <table cellspacing="0" style="border: 1px solid grey;padding: 20px;width: 95%;margin: 40px auto 40px auto;">
          
            
                    <tr>
                        <td style="border-bottom: 1px solid grey;padding: 25px  5px;">
                            <table>
                                <tr>
                                    <td>
                                        <?php 
                                        if(file_exists(FCPATH.'uploads/images/invoice_logo.png'))
                                        {
                                            echo "<img src='".FCPATH."uploads/images/invoice_logo.png' style='width:170px;'>";
                                        }
                                        else
                                        { 
                                            echo SITENAME; 
                                        } 
                                        ?>                                        
                                        <!-- <h1 style="color: green;font-size: 20px;margin-bottom: 0;">Great! Your Booking is Confirmed</h1> -->
                                    </td>
                                </tr>
                            </table>
                        </td>
                        <td style="border-bottom: 1px solid grey;padding: 25px  5px;">
                            <table>
                                <tr>
                                    <td colspan="2"><h1>Tax Invoice</h1></td>
                                </tr>
                                <tr>
                                    <td><b>Invoice No.</b></td>
                                    <td><?php echo $booking['booking_id'];?></td>
                                </tr>
                                <tr>
                                    <td><b>CIN</b></td>
                                    <td>U74999MH2017PTC299912</td>
                                </tr>
                                <tr>
                                    <td style="width: 50%;"><b>Address</b></td>
                                    <td><p>Fort,Mumbai 400001</p></td>
                                </tr>
                                <tr>
                                    <td><b>Contact No.</b></td>
                                    <td><p>Fort: +91  9820834976</p></td>
                                </tr>
                                <tr>
                                    <td><b>Matheran</b></td>
                                    <td><p>+91 9277204152</p></td>
                                </tr>
                                <tr>
                                    <td><b>GST No.</b></td>
                                    <td><p>27AAECH3151J1ZM</p></td>
                                </tr>
                                <tr>
                                    <td><b>HSN Code</b></td>
                                    <td><p>996311</p></td>
                                </tr>
                                <tr>
                                    <td><b>Describe of Service</b></td>
                                    <td><p>Guest House Service</p></td>
                                </tr>
                            </table>
                        </td>
                    </tr>
               

              
                    <tr style="vertical-align: top;" valign="top">
                        <td style="padding-bottom: 20px;vertical-align: top;">
                            <table style="vertical-align: top;">
                                <tr>
                                    <td colspan="2" style="padding:25px 5px ;"><b><u>Guest Information</u></b></td>
                                </tr>
                                <tr>
                                    <td style="width: 30%;"><b>ITS</b></td>
                                    <td><p><?php echo $booking['customer_its']; ?></p></td>
                                </tr>
                                <tr>
                                    <td><b>Name</b></td>
                                    <td><p><?php echo $booking['customer_fname'].' '.$booking['customer_mname'].' '.$booking['customer_lname']; ?></p></td>
                                </tr>
                                <tr>
                                    <td><b>Address</b></td>
                                    <td><p><?php echo $booking['customer_address']; ?></p></td>
                                </tr>
                                <tr>
                                    <td><b>Email ID</b></td>
                                    <td><p><?php echo $booking['customer_email']; ?></p></td>
                                </tr>
                                <tr>
                                    <td><b>Contact No</b></td>
                                    <td><p><?php echo $booking['customer_phone']; ?></p></td>
                                </tr>
                            </table>
                        </td>
                        <td style="padding-bottom: 20px;">
                            <table>
                                <tr>
                                    <td colspan="2" style="padding:25px 5px ;"><b><u>Booking Information</u></b></td>
                                </tr>
                                <tr>
                                    <td style="width: 30%;"><b>Property Name</b></td>
                                    <td><p><?php echo $booking['property_name']; ?></p></td>
                                </tr>
                                <tr>
                                    <td><b>Booking Date</b></td>
                                    <td><p><?php echo $invoice_date; ?></p></td>
                                </tr>
                                <tr>
                                    <td><b>Booking ID</b></td>
                                    <td><p><?php echo $booking['booking_uid']; ?></p></td>
                                </tr>
                                <tr>
                                    <td><b>No of Rooms</b></td>
                                    <td><p><?php echo count($rooms);?></p></td>
                                </tr>
                                <tr>
                                    <td><b>Check in Date</b></td>
                                    <td><p><?php echo date('d/m/Y',strtotime($booking['booking_checkin'])); ?> | <?php echo date('h:i A',strtotime($booking['booking_checkin'])); ?></p></td>
                                </tr>
                                <tr>
                                    <td><b>Check Out Date</b></td>
                                    <td><p><?php echo date('d/m/Y',strtotime($booking['booking_checkout'])); ?> | <?php echo date('h:i A',strtotime($booking['booking_checkout'])); ?></p></td>
                                </tr>
                                <tr>
                                    <td><b>Stay</b></td>
                                    <?php
                                    // this calculates the diff between two dates, which is the number of nights
                                    $booking['booking_checkout'] = str_replace('09:00:00','10:00:00',$booking['booking_checkout']);
                                    $date1 = strtotime($booking['booking_checkin']); 
                                    $date2 = strtotime($booking['booking_checkout']); 
                                    $diff = $date2 - $date1;
                                    // 1 day = 24 hours
                                    // 24 * 60 * 60 = 86400 seconds
                                    $numberOfNights = abs(round($diff / 86400));
                                    ?>
                                    <td><p><?php echo $numberOfNights;?> Night</p></td>
                                </tr>
                                <tr>
                                    <td><b>No of Persons</b></td>
                                    <td><p><?php echo $booking['room_adults']; ?> Adult | <?php if($booking['room_children']==''){echo '0';}else{ echo $booking['room_children'];} ?> Children</p></td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <tr>
                        <td colspan="2" style="border-top: 1px solid grey;padding: 20px 0;">
                            <table cellspacing="0">
                                <tr>
                                    <td class="brd"><b>Sr No</b></td>
                                    <td class="brd"><b>Room</b></td>
                                    <td class="brd" style="text-align: center;"><b>Charges/Night</b></td>
                                    <td class="brd" style="text-align: center;"><b>Extra Bed</b></td>
                                    <td class="brd" style="text-align: center;"><b>Extra Bed Charges</b></td>
                                    <td class="brd"><b>Stay</b></td>
                                    <td class="brd"><b>Amount</b></td>
                                </tr>
                                <?php
                                $totalCharges = 0;
                                if($rooms){
                                    $i=1;
                                    foreach ($rooms as $r) 
                                    {
                                        $roomCharges = $r['room_charges'];
                                        $r['total_extra_bed_charges'] = (int)$r['total_extra_bed_charges'];
                                        if($r['total_extra_bed_charges']){
                                            $roomCharges = $roomCharges+$r['total_extra_bed_charges'];
                                        }
                                        $totalRoomCharge = $numberOfNights*$roomCharges;
                                        $totalCharges = $totalCharges+$totalRoomCharge;
                                        ?>
                                        <tr>
                                            <td class="brd"><p><?php echo $i++;?></p></td>
                                            <td class="brd"><p><?php echo $r['room_number'].' ( '.$r['room_type'].' )'; ?></p></td>
                                            <td class="brd" style="text-align: center;"><p><?php echo $r['room_charges']; ?>/-</p></td>
                                            <td class="brd" style="text-align: center;"><p><?php echo $r['extra_beds']; ?></p></td>
                                            <td class="brd" style="text-align: center;"><p><?php echo $r['total_extra_bed_charges']; ?>/-</p></td>
                                            <td class="brd"><p><?php echo $numberOfNights;?> Night</p></td>
                                            <td class="brd"><p><?php echo $totalRoomCharge; ?>/-</p></td>
                                        </tr>
                                        <?php
                                    }
                                }    
                                ?>                                
                                <tr>
                                    <td ><p></p></td>
                                    <td ><p></p></td>
                                    <td ><p></p></td>
                                    <td ><p></p></td>
                                    <td ><p></p></td>
                                    <td style="padding: 7px;"><p>Amount</p></td>
                                    <td style="padding: 7px;"><p><?php echo $totalCharges;?>/-</p></td>
                                </tr>
                                <?php
                                if($booking['promocode_id']!='')
                                {   
                                    $floatDiscount = (float)$booking['discount'];
                                    $discountedPrice = ($totalCharges * $floatDiscount) / 100 ;
                                    $totalCharges = $totalCharges - $discountedPrice;
                                    ?>
                                    <tr>
                                        <td ><p></p></td>
                                        <td ><p></p></td>
                                        <td ><p></p></td>
                                        <td ><p></p></td>
                                        <td ><p></p></td>
                                        <td style="padding:10px;">Discount(<?php echo $booking['discount'];?>%)</td>
                                        <td  style="padding:10px;"> - <?php echo $discountedPrice;?>/-</td>
                                    </tr>
                                    <?php
                                }
                                ?>
                                <tr>
                                    <td ><p></p></td>
                                    <td ><p></p></td>
                                    <td ><p></p></td>
                                    <td ><p></p></td>
                                    <td ><p></p></td>
                                    <td style="padding: 7px;"><p>CGST @ 6%</p></td>
                                    <?php
                                    $gst = ($totalCharges*6)/100;
                                    ?>
                                    <td style="padding: 7px;"><p><?php echo $gst;?>/-</p></td>
                                </tr>
                                <tr>
                                    <td ><p></p></td>
                                    <td ><p></p></td>
                                    <td ><p></p></td>
                                    <td ><p></p></td>
                                    <td ><p></p></td>
                                    <td style="padding: 7px;"><p>SGST @ 6%</p></td>
                                    <td style="padding: 7px;"><p><?php echo $gst;?>/-</p></td>
                                </tr>
                                <tr>
                                    <td ><p></p></td>
                                    <td ><p></p></td>
                                    <td ><p></p></td>
                                    <td ><p></p></td>
                                    <td ><p></p></td>
                                    <td style="padding:10px;">Total Amount</td>
                                    <td  style="padding:10px;"><?php echo $gst+$gst+$totalCharges; ?>/-</td>
                                </tr>                                
                                <tr>
                                    <td ><p></p></td>
                                    <td ><p></p></td>
                                    <td ><p></p></td>
                                    <td ><p></p></td>
                                    <td ><p></p></td>
                                    <td style="padding:10px;background-color:rgb(177, 225, 255)"><b>Amount Paid</b></td>
                                    <td  style="padding:10px;background-color:rgb(177, 225, 255)"><b><?php echo $booking['booking_amount_paid']; ?>/-</b></td>
                                </tr>  
                            </table>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding-bottom: 20px;" colspan="2">
                            <table>
                                <tr>
                                    <td colspan="2" style="padding:25px 5px ;"><b><u>Payment Information</u></b></td>
                                </tr>
                                <tr>
                                    <td style="width: 30%;"><b>Name</b></td>
                                    <td><p><?php echo $booking['customer_fname'].' '.$booking['customer_mname'].' '.$booking['customer_lname']; ?></p></td>
                                </tr>
                                <tr>
                                    <td><b>Mode Of Payment</b></td>
                                    <td><p>Online</p></td>
                                </tr>
                                <tr>
                                    <td><b>Transaction ID</b></td>
                                    <td><p><?php if($booking['transaction_id']!=''){ echo $booking['transaction_id'];}else{ echo '-NA-';}?></p></td>
                                </tr>
                                
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="2">
                            <h1 style="color: rgb(56, 155, 56);font-size: 20px;margin-bottom: 0;text-align: center;">Thank you for Booking with us</h1>
                        </td>
                    </tr>
        </table>
    </body>
</html>
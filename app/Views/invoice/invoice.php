<html>
    <head>
        <style>

            *{
                font-family: Arial, Helvetica, sans-serif;
            }
            h2{
                font-size: 15px;
                margin: 0;
                font-weight: bold;
            }
            table{
                width: 100%;
                background-color: rgb(241, 248, 255);
                padding: 10px;
            }
            td{
                padding: 8px;
                font-size: 14px;
               
            }
            h3{
                margin-top: 0;
            }
            h4{
                margin: 0;
            }
            .brd-btm{
                border-bottom: 1px solid rgb(190, 190, 190);
            }
     </style>
        <title></title>
        
        <body>
            <table cellspacing="0" style="font-family: Arial, Helvetica, sans-serif;">
                <tr>
                    <td>
                        <?php if(file_exists(LOGOPATH)){
                            echo "<img src='".LOGOPATH."' width='100%'>";
                        }else{ ?>
                            <u>
                                <b style="font-size:25px;"><?php echo SITENAME; ?></b>
                            </u>
                        <?php } ?>
                    </td>
                    <td ><h4>Date : <?php echo $invoice_date; ?></h4></td>
                </tr>
                <tr>
                    <td class="brd-btm" ><h2>Name : <?php echo $booking['customer_fname'].' '.$booking['customer_mname'].' '.$booking['customer_lname'] ?></h2></td>
                   <td class="brd-btm"><h2>ITS : <?php echo $booking['customer_its']; ?></h2></td>
                </tr>
                <tr>
                    <td class="brd-btm"><b>Building Name</b></td>
                    <td class="brd-btm"><?php echo $booking['property_name']; ?></td>
                </tr>
                <?php
                $charges = 0;
                if($rooms){
                    foreach ($rooms as $r) {
                        $charges = $charges+$r['room_charges'];
                        $r['extra_bed_charges'] = (int)$r['extra_bed_charges'];
                        if($r['extra_bed_charges']){
                            $charges = $charges+$r['extra_bed_charges'];
                        }
                        ?>
                        <tr>
                            <td><b>Room No</b></td>
                            <td> <?php echo $r['room_number']; ?></td>
                        </tr>
                        <tr>
                            <td><b>Check In</b></td>
                            <td><?php echo date('d-m-Y',strtotime($r['booking_checkin'])); ?></td>
                        </tr>
                        <tr>
                            <td><b>Check Out</b></td>
                            <td><?php echo date('d-m-Y',strtotime($r['booking_checkout'])); ?></td>
                        </tr>
                        <tr>
                            <td><b>Numer of Person</b></td>
                            <td><?php echo $r['room_bedcapacity']; ?></td>
                        </tr>
                        <?php
                        if($r['extra_beds']){
                            ?>
                            <tr>
                                <td>Extra bed</td>
                                <td><?php echo $r['extra_bed_charges']; ?></td>
                            </tr>
                        <?php } ?>
                        <tr>
                            <td style="background:rgb(251, 253, 255)"><b>Charges</b></td>
                            <td style="background:rgb(251, 253, 255)"> <?php echo $r['room_charges']; ?>/-</td>
                        </tr>
                        <tr>
                            <td colspan="2"><hr></td>
                        </tr>
                        <?php
                    }
                }
                ?>
                
                <tr>
                    <td class="brd-btm"><b>GST(18%)</b></td>
                    <td class="brd-btm">
                        <?php
                        $gst = number_format(($charges*18)/100,2);
                        echo $gst;
                        ?>
                        /-
                    </td>
                </tr>
                <tr>
                    <td style="background:rgb(251, 253, 255)"><b>Total</b></td>
                    <td style="background:rgb(251, 253, 255)"><b><?php echo $gst+$charges; ?>/-</b></td>
                </tr>
                <tr>
                    <td colspan="2" align="center"><b style="text-align:center;padding-top:10px;display: block;">Thank you for coming</b></td>
                </tr>
            </table>
        </body>
    </head>
</html>
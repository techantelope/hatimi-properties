<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thank you for Booking</title>
</head>
<body>
    <table style="width: 550px;margin: auto;padding: 20px;">
        <tr>
            <td colspan="2">
                <?php 
                    echo "<img src='".BASEURL."/uploads/images/invoice_logo.png' style='width: 90px;display: block;margin: auto;'>";
                ?>
            </td>
        </tr>
        <tr>
            <td colspan="2">
                <h1 style="color: red;margin-top: 8px;">Your Booking has been cancelled</h1>
                <p style="color: rgb(25, 25, 25);">You Can Check your Booking Status on hatimipropeties.com by login with your ITS</p>
            </td>
        </tr>
        <tr>
            <td>Name <b>:</b></td>
            <td><?php echo $name;?></td>
        </tr>
        <tr>
            <td>Check In Date <b>:</b></td>
            <td><?php echo date('d/m/Y',strtotime($checkin)); ?> | <?php echo date('h:i A',strtotime($checkin)); ?></td>
        </tr>
        <tr>
            <td>Check Out Date</td>
            <td><?php echo date('d/m/Y',strtotime($checkout)); ?> | <?php echo date('h:i A',strtotime($checkout)); ?></td>
        </tr>
        <tr>
            <td>Booking-ID</td>
            <td><?php echo $booking_uid;?></td>
        </tr>
    </table>
</body>
</html>
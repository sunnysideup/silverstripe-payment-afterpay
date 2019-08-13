<?php
     require('init_api.php');

     // Environment
     $finalCheckoutTotal = $checkout_total;
     $afterpayDenied = false;
     $afterpayDown = false;

     if(isset($_GET['afterpay'])) {

         if ($_GET['afterpay'] == 'fail') {
             $afterpayDenied = true;
         } else if ($_GET['afterpay'] == 'down') {
             $afterpayDown = true;
         }

     }

?>
<!DOCTYPE html>
<html>
<body>

    <h1>Checkout - Choose Payment Option</h1>

    <p>
        <?php

            $installations = $_SESSION['api']->getPaymentInstallations($finalCheckoutTotal);

            if($installations != 0.00 && !$afterpayDenied && !$afterpayDown) {
                echo("Pay with 4 installations of $" . $installations . " using afterpay");
                echo("<p><a href='place_order_with_afterpay.php'>Use afteray</a></p>");
            } else if ($afterpayDenied) {
                echo("Afterpay denied you sorry");
            } else if ($afterpayDown) {
                echo("Afterpay is not working at the moment ... sorry");
            }

        ?>
    </p>

</body>
</html>

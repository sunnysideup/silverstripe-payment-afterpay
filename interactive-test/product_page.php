<?php
     require('init_api.php');

     // Environment
     $prices = [600.00, 1200.00];
?>
<!DOCTYPE html>
<html>
<body>

    <h1>Product Page</h1>

    <p>
        <?php

            foreach($prices as $price) {
                $installations = $_SESSION['api']->getPaymentInstallations($price);
                $outputString = "Pay $" . $price;

                if($installations != 0.00) {
                    $outputString .= " or pay 4 installations of $" . $installations . "<br />";
                }

                echo($outputString);
            }

        ?>
    </p>

    <p>

        <a href="checkout.php">Checkout Now</a>
    </p>

</body>
</html>

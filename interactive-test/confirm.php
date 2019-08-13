<?php
     require('init_api.php');
?>
<!DOCTYPE html>
<html>
<body>

    <h1>Confirm payment - Afterpay has approved you!</h1>

    <p>
        <?php echo("Total comes to $" . $checkout_total); ?><br />
        <a href="complete_afterpay_payment.php">Take my money afterpay</a><br />
        <a href="checkout.php">I don't want to pay!</a>
    </p>

</body>
</html>

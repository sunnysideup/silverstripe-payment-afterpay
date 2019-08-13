<?php

require('init_api.php');

try {
    $_SESSION['api']->createPayment();
    header('Location: '. $host . 'payment_complete.php');
} catch(Exception $e) {
    header('Location: '. $host . 'checkout.php?afterpay=down');
}

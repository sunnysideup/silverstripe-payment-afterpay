<?php

require('../vendor/autoload.php');
require('../src/Factory/MerchantApi.php');

session_start();

use Sunnysideup\Afterpay\Factory\MerchantApi;

if (!isset($_SESSION['api'])) {

    MerchantApi::inst()
        ->setIsTest(true)
        ->setMerchantId(32)
        ->setSecretKey('adasda')
        ->setIsServerAvailable(false)
        ->logIn();

    $_SESSION['api'] = MerchantApi::inst();

}

$checkout_total = 467.00;
$host = "http://afterpay.api.test/";

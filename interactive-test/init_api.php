<?php

require('../vendor/autoload.php');
require('../src/Factory/SilverstripeMerchantApi.php');

session_start();

use Sunnysideup\Afterpay\Factory\SilverstripeMerchantApi;

if (!isset($_SESSION['api'])) {

    SilverstripeMerchantApi::inst()
        ->setIsTest(true)
        ->setMerchantId(32)
        ->setSecretKey('adasda')
        ->setIsServerAvailable(false)
        ->logIn();

    $_SESSION['api'] = SilverstripeMerchantApi::inst();

}

$checkout_total = 467.00;
$host = "http://afterpay.api.test/";

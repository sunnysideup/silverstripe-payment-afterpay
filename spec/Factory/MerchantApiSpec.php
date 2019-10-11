<?php

namespace spec\Sunnysideup\Afterpay\Factory;

// Models //
use \CultureKings\Afterpay\Model\Merchant\Consumer;
use \CultureKings\Afterpay\Model\Merchant\MerchantOptions;
use \CultureKings\Afterpay\Model\Money;
use \CultureKings\Afterpay\Model\Merchant\OrderDetails;
use \CultureKings\Afterpay\Model\Merchant\Payment;

use Sunnysideup\Afterpay\Factory\MerchantApi;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class MerchantApiSpec extends ObjectBehavior
{

    function it_will_initialize()
    {
        MerchantApi::inst()
            ->setIsTest(true)
            ->setMerchantId("")
            ->setSecretKey("")
            ->setIsServerAvailable(false)
            ->logIn();
    }

    function it_will_get_config()
    {
        MerchantApi::inst()
            ->getConfig();
    }

    function it_will_check_payment() {

        $v = MerchantApi::inst()
            ->getPaymentInstallations(999.00);

        echo("Returns: $v \n");

    }

    function it_will_create_an_order() {

        $consumer = new Consumer();
        $consumer->setEmail('john.doe@culturekings.com.au');
        $consumer->setGivenNames('John');
        $consumer->setSurname('Doe');
        $consumer->setPhoneNumber('0534242323');

        $merchantOptions = new MerchantOptions();
        $merchantOptions->setRedirectConfirmUrl('https://www.merchant.com/confirm');
        $merchantOptions->setRedirectCancelUrl('https://www.merchant.com/cancel');

        $totalAmount = new Money();
        $totalAmount->setAmount(mt_rand(1, 300));
        $totalAmount->setCurrency('NZD');

        $orderDetails = new OrderDetails();
        $orderDetails->setConsumer($consumer);
        $orderDetails->setMerchant($merchantOptions);
        $orderDetails->setTotalAmount($totalAmount);

        MerchantApi::inst()
            ->createOrder($orderDetails);

    }

    function it_should_create_a_payment() {
        MerchantApi::inst()
            ->createPayment();
    }

}

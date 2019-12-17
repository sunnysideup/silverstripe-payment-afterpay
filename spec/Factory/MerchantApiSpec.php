<?php

namespace spec\Sunnysideup\Afterpay\Factory;
/**
 *
 * test class only!
 * @var
 */
use \CultureKings\Afterpay\Model\Merchant\Consumer;
use \CultureKings\Afterpay\Model\Merchant\MerchantOptions;
use \CultureKings\Afterpay\Model\Money;
use \CultureKings\Afterpay\Model\Merchant\OrderDetails;
use \CultureKings\Afterpay\Model\Merchant\Payment;

use Sunnysideup\Afterpay\Factory\SilverstripeMerchantApi;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class SilverstripeMerchantApiSpec extends ObjectBehavior
{

    function it_will_initialize()
    {
        SilverstripeMerchantApi::inst()
            ->setIsTest(true)
            ->setMerchantId("")
            ->setSecretKey("")
            ->setIsServerAvailable(false)
            ->logIn();
    }

    function it_will_get_config()
    {
        SilverstripeMerchantApi::inst()
            ->getConfig();
    }

    function it_will_check_payment() {

        $v = SilverstripeMerchantApi::inst()
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

        SilverstripeMerchantApi::inst()
            ->createOrder($orderDetails);

    }

    function it_should_create_a_payment() {
        SilverstripeMerchantApi::inst()
            ->createPayment();
    }

}

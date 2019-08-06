<?php

namespace spec\Sunnysideup\Afterpay\Factory;

use Sunnysideup\Afterpay\Factory\MerchantApi;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class MerchantApiSpec extends ObjectBehavior
{

    function it_will_get_config()
    {
        MerchantApi::inst()
            ->setIsTest(true)
            ->setMerchantId(32)
            ->setSecretKey('adasda')
            ->getConfig();
    }
}

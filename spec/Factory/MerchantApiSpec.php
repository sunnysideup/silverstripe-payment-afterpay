<?php

namespace spec\Sunnysideup\Afterpay\Factory;

use Sunnysideup\Afterpay\Factory\MerchantApi;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class MerchantApiSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType(MerchantApi::class);
    }

    function it_will_get_config()
    {
        print_r($this->getConfig());
    }
}

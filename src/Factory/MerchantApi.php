<?php

namespace Sunnysideup\Afterpay\Factory;

use \CultureKings\Afterpay\Model\Merchant\Authorization;
use \CultureKings\Afterpay\Factory\MerchantApi as AfterpayApi;

class MerchantApi {

    public function getConfig() {
        $authorization = new Authorization(
            "https://api-sandbox.afterpay.com/v1/",
            32,
            "abcdefgh"
        );

        return AfterpayApi::configuration($authorization)->get();
    }

}

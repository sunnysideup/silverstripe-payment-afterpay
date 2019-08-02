<?php

namespace Sunnysideup\Afterpay\Factory;

use \CultureKings\Afterpay\Model\Merchant\Authorization;
use \CultureKings\Afterpay\Factory\MerchantApi as AfterpayApi;

class MerchantApi
{

    private const CONNECTION_URL_TEST: "https://api-sandbox.afterpay.com/v1/";

    private const CONNECTION_URL_LIVE: "https://api-sandbox.afterpay.com/v1/";

    protected $isTest = false;

    private static $merchantId = 0;

    private static $secretKey = "";

    protected $minimum = 0.00;

    protected $maximum = 0.00;

    private static $singleton_cache = null;

    public static function inst(boolean $isTest = false, int $merchantId, string $secretKey)
    {
        if(self::$singleton_cache === null)
        {
            $this->isTest = $isTest;
            $this->merchantId = $merchantId;
            $this->secretKey = $secretKey;
            self::$singleton_cache = new MerchantApi("singleton");
        }

        return self::$singleton_cache;
    }

    public function __construct(string $initMethod = "instance") {

        if(self::$singleton_cache == "instance") {
            user_error("Please use the inst() static method to create me!");
        }

    }

    public function getConfig()
    {

        $authorization = new Authorization(
            ($this->isTest ? CONNECTION_URL_TEST : CONNECTION_URL_LIVE),
            $this->merchantId,
            $this->secretkey
        );

        /** @TODO Initialize the class variables with the result */
        return AfterpayApi::configuration($authorization)->get();
    }

    public function getPaymentInstallations(float $price) : float
    {
        return $price / 4;
    }

    public function canProcessPayment(float $price) : boolean
    {
        if ($price > $maximum)
    }

}

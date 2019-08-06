<?php

namespace Sunnysideup\Afterpay\Factory;

use \CultureKings\Afterpay\Model\Merchant\Authorization;
use \CultureKings\Afterpay\Factory\MerchantApi as AfterpayApi;

/**
 * An API which handles the main steps needed for a website to function with afterpay
 */
class MerchantApi
{

    private const CONNECTION_URL_TEST = "https://api-sandbox.afterpay.com/v1/";

    private const CONNECTION_URL_LIVE = "https://api-sandbox.afterpay.com/v1/";

    private $isTest = false;

    private $merchantId = 0;

    private $secretKey = "";

    protected $minimum = 0.00;

    protected $maximum = 0.00;

    private static $singleton_cache = null;

    public function __construct(string $initMethod = "instance")
    {

        if($initMethod != "singleton") {
            user_error("Please use the inst() static method to create me!");
        }

    }

    /**
     * Singleton instance pattern
     * @return self
     */
    public static function inst() : self
    {
        if(self::$singleton_cache === null)
        {
            self::$singleton_cache = new MerchantApi("singleton");
        }

        return self::$singleton_cache;
    }

    /**
     * Setter for merchant id
     * @param  int  $id The merchant id for authentication
     * @return self     Daisy chain
     */
    public function setMerchantId(int $id) : self
    {
        $this->merchantId = $id;

        return $this;
    }

    /**
     * Setter for is test
     * @param  bool $isTest Should the client be using the live api?
     * @return self         Daisy chain
     */
    public function setIsTest(bool $isTest) : self
    {
        $this->isTest = $isTest;

        return $this;
    }

    /**
     * Setter for secret key
     * @param  string $secretKey A unique key to authenticate the user
     * @return self              Daisy chain
     */
    public function setSecretKey(string $secretKey) : self
    {
        $this->secretKey = $secretKey;

        return $this;
    }

    public function getConfig()
    {
        $authorization = new Authorization(
            ($this->isTest ? $this::CONNECTION_URL_TEST : $this::CONNECTION_URL_LIVE),
            $this->merchantId,
            $this->secretKey
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
        //if ($price > $maximum)
    }

}

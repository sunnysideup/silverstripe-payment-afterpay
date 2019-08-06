<?php

namespace Sunnysideup\Afterpay\Factory;

use \CultureKings\Afterpay\Factory\MerchantApi as AfterpayApi;

use \CultureKings\Afterpay\Factory\SerializerFactory;

// Models for Data //
use \CultureKings\Afterpay\Model\Merchant\Authorization;
use \CultureKings\Afterpay\Model\Merchant\Configuration;
use \CultureKings\Afterpay\Model\Merchant\OrderDetails;
use \CultureKings\Afterpay\Model\Merchant\OrderToken;

/**
 * An API which handles the main steps needed for a website to function with afterpay
 * @author Tristan Mastrodicasa
 */
class MerchantApi
{
    // API connection //

    private const CONNECTION_URL_TEST = 'https://api-sandbox.afterpay.com/v1/';

    private const CONNECTION_URL_LIVE = 'https://api-sandbox.afterpay.com/v1/';

    private $isTest = false;

    private $serverAvailable = true;

    private $merchantId = 0;

    private $secretKey = '';

    private $authorization = null;

    // -------------- //

    // Afterpay config //

    private $minimumAllowed = [
        'amount' => 0.00,
        'currency' => 'NZD',
    ];

    private $maximumAllowed = [
        'amount' => 0.00,
        'currency' => 'NZD',
    ];

    // --------------- //

    private $orderToken = null;

    private static $singleton_cache = null;

    public function __construct(string $initMethod = 'instance')
    {
        if ($initMethod !== 'singleton') {
            user_error('Please use the inst() static method to create me!');
        }
    }

    /**
     * Singleton instance pattern
     * @return self
     */
    public static function inst(): self
    {
        if (self::$singleton_cache === null) {
            self::$singleton_cache = new self('singleton');
        }

        return self::$singleton_cache;
    }

    /**
     * Setter for merchant id
     * @param  int  $id The merchant id for authentication
     * @return self     Daisy chain
     */
    public function setMerchantId(int $id): self
    {
        $this->merchantId = $id;

        return $this;
    }

    /**
     * Setter for is test
     * @param  bool $isTest Should the client be using the live api?
     * @return self         Daisy chain
     */
    public function setIsTest(bool $isTest): self
    {
        $this->isTest = $isTest;

        return $this;
    }

    /**
     * Setter for secret key
     * @param  string $secretKey A unique key to authenticate the user
     * @return self              Daisy chain
     */
    public function setSecretKey(string $secretKey): self
    {
        $this->secretKey = $secretKey;

        return $this;
    }

    /**
     * Setter for server available
     * If no server exists then collect fake responses from a cache
     * @param  bool $available Are there any external APIs available
     * @return self            Daisy chain
     */
    public function setServerAvailable(bool $available): self
    {
        $this->serverAvailable = $available;

        return $this;
    }

    /**
     * Initialize the authorization feild with the set merchant id and secret key
     */
    public function logIn(): self
    {
        $this->authorization = new Authorization(
            ($this->isTest ? $this::CONNECTION_URL_TEST : $this::CONNECTION_URL_LIVE),
            $this->merchantId,
            $this->secretKey
        );

        return $this;
    }

    /**
     * Initialize the API with the configuration data from afterpay
     * Currently only the PAY_BY_INSTALLMENT configuration is collected**maybe
     */
    public function getConfig()
    {
        // Collect the configuration data //
        if ($this->serverAvailable) {
            $config = AfterpayApi::configuration($this->authorization)->get();
        } else {
            $json = file_get_contents(__DIR__ . '/../expectations/configuration_details.json');

            $config = SerializerFactory::getSerializer()->deserialize(
                (string) $json,
                sprintf('array<%s>', Configuration::class),
                'json'
            );
        }

        foreach ($config as $value) {
            /*if ($value['type'] === 'PAY_BY_INSTALLMENT') {
                $this->minimumAllowed = $value['minimumAmount'];
                $this->maximumAllowed = $value['maximumAmount'];
            }*/
        }
    }

    /**
     * Can the payment be processed (in range of the max and min price)
     * @param  float $price Price of product
     * @return bool         Can the payment be processed (true / false)
     */
    public function canProcessPayment(float $price): bool
    {
        if ($price > $this->maximumAllowed['amount']) {
            return false;
        }
        if ($price < $this->minimumAllowed['amount']) {
            return false;
        }
        return true;
    }

    /**
     * Get the payment installations for afterpay (return 0 if price is out of range)
     * @param  float $price Price of the product
     * @return float        (Price / 4) or 0 if fail
     */
    public function getPaymentInstallations(float $price): float
    {
        if ($this->canProcessPayment($price)) {
            return $price / 4;
        }
        return 0.00;
    }

    /**
     * Pass an OrderDetails object to this function and collect the OrderToken from afterpay
     * if succesful. This order helps afterpay assess the preapproval
     * https://github.com/culturekings/afterpay/blob/master/docs/merchant/api.md#create-order
     * https://docs.afterpay.com/nz-online-api-v1.html#orders
     * @param  OrderDetails $order An object holding all the information for the request
     * @return OrderToken          The token for the preapproval process
     */
    public function createOrder(OrderDetails $order)
    {

        // Create the order, collect the token //
        if ($this->serverAvailable) {
            $this->orderToken = AfterpayApi::orders($this->authorization)->create($order);
        } else {
            $json = file_get_contents(__DIR__ . '/../expectations/order_create_response.json');
            $this->orderToken = SerializerFactory::getSerializer()->deserialize(
                (string) $json,
                OrderToken::class,
                'json'
            );
        }
    }
}

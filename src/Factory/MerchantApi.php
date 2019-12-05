<?php

namespace Sunnysideup\Afterpay\Factory;

use CultureKings\Afterpay\Factory\MerchantApi as AfterpayApi;

use CultureKings\Afterpay\Factory\SerializerFactory;

// Models for Data //
use CultureKings\Afterpay\Model\Merchant\Authorization;
use CultureKings\Afterpay\Model\Merchant\Configuration;
use CultureKings\Afterpay\Model\Merchant\OrderDetails;
use CultureKings\Afterpay\Model\Merchant\OrderToken;
use CultureKings\Afterpay\Model\Merchant\Payment;


use Object;
use Director;

/**
 * An API which handles the main steps needed for a website to function with afterpay
 * @author Tristan Mastrodicasa
 */
class MerchantApi extends Object
{


    ############################
    # global settings
    ############################

    private const CONNECTION_URL_TEST = 'https://api-sandbox.afterpay.com/v1/';

    private const CONNECTION_URL_LIVE = 'https://api-sandbox.afterpay.com/v1/';

    private static $merchant_id = 0;

    private static $secret_key = '';

    private static $number_of_payments = 4;

    /**
     * see: afterpay/expectations as an example
     * @var string
     */

    private static $expectations_folder = 'vendor/sunnysideup/expectations';


    ############################
    # global instance settings
    ############################

    /**
     *
     * @var float
     */
    private $minPrice = 0;


    /**
     *
     * @var float
     */
    private $maxPrice = 0;


    /**
     *
     * @var bool
     */
    private $isTest = false;

    /**
     *
     * @var bool
     */
    private $isServerAvailable = null;



    ############################
    # internal variables
    ############################


    private $authorization = null;


    /**
     * Configuration information
     * @var Configuration[]
     */
    private $configurationInfo = null;

    /**
     * Order Token
     * @var OrderToken
     */
    private $orderToken = null;

    /**
     * Payment information
     * @var Payment
     */
    private $paymentInfo = null;




    ############################
    # instance
    ############################

    /**
     * this
     * @var MerchantApi|null
     */
    protected static $singleton_cache = null;

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
        self::$singleton_cache->setIsTest(Director::isLive() ? false : true);
        self::$singleton_cache->logIn();

        return self::$singleton_cache;
    }








    ############################
    # setters
    ############################

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
     * Setter for is server available
     * If no server exists then collect fake responses from a cache
     * @param  bool $available Are there any external APIs available
     * @return self            Daisy chain
     */
    public function setIsServerAvailable(bool $available): self
    {
        $this->isServerAvailable = $available;

        return $this;
    }



    /**
     * set the minimum and maximum price to use Afterpay
     * This can overrule settings from Afterpay server
     * and therefore make it faster ...
     * @param  float $minPrice
     * @param  float $maxPrice
     * @return self
     */
    public function setMinAndMaxPrice(float $minPrice, float $maxPrice): self
    {
        $this->minPrice = $minPrice;
        $this->maxPrice = $maxPrice;

        return $this;
    }








    ############################
    # getters
    ############################



    /**
     * Getter for is test
     * @return bool Are we using the live or sandbox API
     */
    public function getIsTest(): bool
    {
        return $this->isTest;
    }

    /**
     * Getter for is server available
     * @return bool Are any servers available? Otherwise use cache
     */
    public function getIsServerAvailable(): bool
    {
        return $this->isServerAvailable;
    }


    /**
     * Getter for payment info
     * @return Payment Object defining the payment details
     */
    public function getPaymentInfo(): Payment
    {
        return $this->paymentInfo;
    }




    /**
     * Can the payment be processed (in range of the max and min price)
     * @param  float $price Price of product
     * @return bool         Can the payment be processed (true / false)
     */
    public function canProcessPayment(float $price): bool
    {
        if($this->minPrice && $this->maxPrice) {
            $minPrice = $this->minPrice;
            $maxPrice = $this->maxPrice;
        } else {
            $this->retrieveConfig();
            foreach ($this->configurationInfo as $config) {
                switch ($config->getType()) {
                    case 'PAY_BY_INSTALLMENT':
                        $minPrice = $config->getMaximumAmount()->getAmount();
                        $maxPrice = $config->getMinimumAmount()->getAmount();
                        // code...
                        break;

                    default:
                        // code...
                        break;
                }
            }
        }
        if($minPrice && $maxPrice) {
            if ($price >= $minPrice && $price <= $maxPrice) {
                return true;
            }
        }

        return false;
    }

    public function getNumberOfPayments() : int
    {
        return $this->Config()->get('number_of_payments');
    }

    /**
     * Get the payment installations for afterpay (return 0 if price is out of range)
     * @param  float $price Price of the product
     * @return float        (Price / 4) or 0 if fail
     */
    public function getAmountPerPayment(float $price): float
    {
        if ($this->canProcessPayment($price)) {
            $numberOfPayments = $this->getNumberOfPayments();
            if($numberOfPayments) {
                return $price / $numberOfPayments;
            }
        }

        return $price;
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
        if ($this->isServerAvailable) {
            $this->orderToken = AfterpayApi::orders($this->authorization)->create($order);
        } else {
            $this->orderToken = $this->localExpecationFileToClass('order_create_response.json', OrderToken::class);
        }

        return $this->orderToken;
    }

    /**
     * Capture the payment after the order has been placed
     * @param  string $merchantReference Optional: Update the merchant reference
     */
    public function createPayment(string $merchantReference = '')
    {
        // $authorization = new \CultureKings\Afterpay\Model\Merchant\Authorization(
        //     \CultureKings\Afterpay\Model\Merchant\Authorization::SANDBOX_URI,
        //     YOUR_MERCHANT_ID,
        //     YOUR_SECRET_KEY
        // );
        //
        // $payment = \CultureKings\Afterpay\Factory\MerchantApi::payments($authorization)->capture(
        //     ORDER_TOKEN,
        //     MERCHANT_REFERENCE,
        //     WEBHOOK_EVENT_URL
        // );
        // Create the payment, collect the token //
        if ($this->isServerAvailable) {
            if ($this->orderToken !== null) {
                $this->paymentInfo = AfterpayApi::payments($this->authorization)->capture(
                    $this->orderToken,
                    $merchantReference
                );
            } else {
                user_error('No order token found, please create an order before processing a payment');
            }
        } else {
            $this->paymentInfo = $this->localExpecationFileToClass(
                'payments_get_response.json',
                Payment::class
            );
        }
    }







    ############################
    # internal do-ers
    ############################

    /**
     * Initialize the authorization field with the set merchant id and secret key
     */
    protected function ping(bool $pingAgain = false): self
    {
        if($this->isServerAvailable === null || $pingAgain) {
            $this->isServerAvailable = true;
        }

        return $this->isServerAvailable;
    }
    /**
     * Initialize the authorization field with the set merchant id and secret key
     */
    protected function logIn(bool $loginAgain = false): Authorization
    {
        if($this->authorization === null || $loginAgain) {
            $this->authorization = new Authorization(
                ($this->isTest ? $this::CONNECTION_URL_TEST : $this::CONNECTION_URL_LIVE),
                $this->Config()->get('merchant_id'),
                $this->Config()->get('secret_key')
            );
        }

        return $this->authorization;
    }

    /**
     * Initialize the API with the configuration data from afterpay
     * Currently only the PAY_BY_INSTALLMENT configuration is collected**maybe
     */
    protected function retrieveConfig(bool $getConfigAgain = false)
    {
        if($this->configurationInfo === null || $getConfigAgain) {
            if($this->findExpectationFile('configuration_details.json')) {
                //look for local config details (FASTER)
                $this->configurationInfo = $this->localExpecationFileToClass(
                    'configuration_details.json',
                    sprintf('array<%s>', Configuration::class)
                );
            } else {
                // Collect the configuration data //
                if ($this->isServerAvailable) {
                    $this->configurationInfo = AfterpayApi::configuration($this->authorization)->get();
                }
            }
        }

        return $this->configurationInfo;
    }







    ########################################
    # helpers
    ########################################

    /**
     *
     * @param  string $relativeFileName
     * @return string
     */
    protected function findExpectationFile(string $relativeFileName) : string
    {
        if($relativeFileName) {
            $folder = $this->Config()->get('expectations_folder');
            $absoluteFileName = Director::baseFolder() . '/' . $folder . '/' . $relativeFileName;
            if(file_exists($absoluteFileName)) {

                return $absoluteFileName;
            } else {
                user_error('bad file specified: '.$absoluteFileName);
            }
        }

        return '';
    }

    protected function localExpecationFileToClass($fileName, $className)
    {
        $absoluteFileName = $this->findExpectationFile($fileName);
        if($absoluteFileName) {
            $json = file_get_contents($absoluteFileName);
            if($json) {
                return SerializerFactory::getSerializer()->deserialize(
                    (string) $json,
                    $className,
                    'json'
                );
            }
        }
        user_error('Could not create expectation file.');

        return new $className();
    }
}

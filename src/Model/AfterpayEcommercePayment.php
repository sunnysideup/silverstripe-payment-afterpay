<?php

namespace Sunnysideup\Afterpay\Model;


use Sunnysideup\Afterpay\Factory\MerchantApi;
use Sunnysideup\Afterpay\Api\OrderToAfterpayConverter;

use ReadonlyField;
use FieldList;
use LiteralField;
use SiteTree;
use Requirements;
use EcommercePayment;
use EcommercePayment_Result;
use EcommercePayment_Processing;
use EcommercePayment_Failure;
use EcommerceDBConfig;
use OrderForm;
use ContentController;
use ShoppingCart;

/**
 *@author nicolaas[at]sunnysideup.co.nz
 *@description: OrderNumber and PaymentID
 *
 *
 **/

class AfterpayEcommercePayment extends EcommercePayment
{

    private static $db = array(
        'AfterpayResponse' => 'Text',
        'AfterpayToken' => 'Text',
        'AfterpayConfirmationToken' => 'Text',
        'DebugMessage' => 'HTMLText',
    );

    private static $table_name = 'AfterpayEcommercePayment';

    private static $logo = '/themes/base/images/AP-RGB-sm.svg';

    private const LIVE_URL = 'https://portal.afterpay.com/afterpay.js';

    private const DEV_URL = 'https://portal.sandbox.afterpay.com/afterpay.js';


    // DPS Information

    private static $privacy_link = 'https://www.afterpay.com/terms';

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        $fields->replaceField("AfterpayResponse", new ReadonlyField("AfterpayResponse"));
        $fields->replaceField("AfterpayToken", new ReadonlyField("AfterpayToken"));
        $fields->replaceField("DebugMessage", new ReadonlyField("DebugMessage", "Debug info"));

        return $fields;
    }

    public function getPaymentFormFields()
    {
        $logo = '<img src="'.$this->Config()->logo.'" alt="Credit card payments powered by Afterpay" style="height: 100px;"/>';
        $privacyLink = '<a href="' . $this->config()->get("privacy_link"). '" target="_blank" title="Read Afterpay\'s privacy policy">' . $logo . '</a><br/>';
        $api = $this->myAfterpayApi();
        $afterpayDetails = $api->getNumberOfPayments().' payments of '.$api->getAmountPerPaymentForCurrentOrder()->Nice().' each.';
        $fields = new FieldList([
            new LiteralField('AfterpayDetails', '<p style="text-align: center">'.$afterpayDetails.'</p>'),
            new LiteralField('AfterpayLogo', $privacyLink),
        ]);

        return $fields;
    }

    public function getPaymentFormRequirements()
    {
        return [];
    }


    /**
     * @param array $data The form request data - see OrderForm
     * @param OrderForm $form The form object submitted on
     *
     * @return EcommercePayment_Result
     */
    public function processPayment($data, $form)
    {
        $order = $this->Order();
        $token = $this->getTokenFromAfterpay($order);
        return $this->executeURL($token);
    }

    public function executeURL(string $token)
    {
        if ($token) {
            /**
            * build redirection page
            **/
            $page = new SiteTree();
            $page->Title = 'Redirection to Afterpay...';
            $page->Logo = '<img src="' . $this->config()->get("logo") . '" alt="Payments powered by Afterpay"/>';
            $controller = new ContentController($page);

            if($this->myAfterpayApi()->getIsTest()) {
                $requirement = self::DEV_URL;
            } else {
                $requirement = self::LIVE_URL;
            }
            Requirements::clear();
            Requirements::insertHeadTags('<script type="text/javascript" src="' . $requirement . '"></script>');
            Requirements::customScript('window.onload = function() { AfterPay.initialize({countryCode: "NZ"}); AfterPay.redirect({token: "'.$token.'"}); };');

            return EcommercePayment_Processing::create($controller->renderWith('PaymentProcessingPage'));
        } else {
            $page = new SiteTree();
            $page->Title = 'Sorry, Afterpay can not be contacted at the moment ...';
            $page->Logo = 'Sorry, an error has occured in contacting the Payment Processing Provider, please try again in a few minutes...';
            $controller = new ContentController($page);

            Requirements::clear();

            return EcommercePayment_Failure::create($controller->renderWith('PaymentProcessingPage'));
        }
    }


    public function getTokenFromAfterpay($order) : string
    {
        if(empty($this->AfterpayToken)) {

            $obj = OrderToAfterpayConverter::create($order);
            $data = $obj->convert();

            $api = $this->myAfterpayApi();
            $tokenObject = $api->createOrder($data);

            $tokenString = $tokenObject->getToken();

            $this->AfterpayResponse = serialize($tokenObject);
            $this->AfterpayToken = $tokenString;

            $this->write();
        }

        return $this->AfterpayToken;
    }

    protected function myAfterpayApi()
    {
        return MerchantApi::inst()
            ->setMinAndMaxPrice(
                $this->EcomConfig()->AfterpayMinValue,
                $this->EcomConfig()->AfterpayMaxValue
            )
            ->setIsServerAvailable(true);
    }

    protected function EcomConfig()
    {
        return EcommerceDBConfig::current_ecommerce_db_config();
    }

}

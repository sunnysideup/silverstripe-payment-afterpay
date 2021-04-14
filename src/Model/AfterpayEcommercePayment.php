<?php

namespace Sunnysideup\Afterpay\Model;

use CultureKings\Afterpay\Model\Merchant\OrderToken;
use SilverStripe\CMS\Controllers\ContentController;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Control\Director;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\LiteralField;
use SilverStripe\Forms\ReadonlyField;
use SilverStripe\View\Requirements;
use Sunnysideup\Afterpay\Api\OrderToAfterpayConverter;
use Sunnysideup\Afterpay\Factory\SilverstripeMerchantApi;
use Sunnysideup\Ecommerce\Config\EcommerceConfig;
use Sunnysideup\Ecommerce\Forms\OrderForm;
use Sunnysideup\Ecommerce\Model\Money\EcommercePayment;
use Sunnysideup\Ecommerce\Model\Order;
use Sunnysideup\Ecommerce\Money\Payment\PaymentResults\EcommercePaymentFailure;
use Sunnysideup\Ecommerce\Money\Payment\PaymentResults\EcommercePaymentProcessing;

/**
 *@author nicolaas[at]sunnysideup.co.nz
 *@description: OrderNumber and PaymentID
 */
class AfterpayEcommercePayment extends EcommercePayment
{
    /**
     * @var string
     */
    private const LIVE_URL = 'https://portal.afterpay.com/afterpay.js';

    /**
     * @var string
     */
    private const DEV_URL = 'https://portal.sandbox.afterpay.com/afterpay.js';

    private static $table_name = 'AfterpayEcommercePayment';

    private static $db = [
        'AfterpayResponse' => 'Text',
        'AfterpayToken' => 'Text',
        'AfterpayConfirmationToken' => 'Text',
        'DebugMessage' => 'HTMLText',
    ];

    private static $logo = '/themes/base/images/AP-RGB-sm.svg';

    // DPS Information

    private static $privacy_link = 'https://www.afterpay.com/terms';

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        $fields->replaceField('AfterpayResponse', new ReadonlyField('AfterpayResponse'));
        $fields->replaceField('AfterpayToken', new ReadonlyField('AfterpayToken'));
        $fields->replaceField('DebugMessage', new ReadonlyField('DebugMessage', 'Debug info'));

        return $fields;
    }

    public function getPaymentFormFields($amount = 0, ?Order $order = null): FieldList
    {
        $logo = '<img src="' . $this->Config()->logo . '" alt="Payments powered by Afterpay" />';

        $api = $this->myAfterpayApi();
        $html = '
            <p>
                Afterpay
                allows ' . $api->getNumberOfPayments() . ' interest free payments of ' . $api->getAmountPerPaymentForCurrentOrder($order)->Nice() . ' each.
                ' . $logo . '
                <a href="' . $this->config()->get('privacy_link') . '" target="_blank">Learn More</a>
            </p>';

        return new FieldList([
            new LiteralField('AfterpayDetails', $html),
        ]);
    }

    public function getPaymentFormRequirements(): array
    {
        return [];
    }

    /**
     * @param array     $data The form request data - see OrderForm
     * @param OrderForm $form The form object submitted on
     *
     * @return \Sunnysideup\Ecommerce\Money\Payment\EcommercePaymentResult
     */
    public function processPayment($data, OrderForm $form)
    {
        $order = $this->Order();
        $token = $this->getTokenFromAfterpay($order);

        return $this->executeURL($token);
    }

    public function executeURL(string $token)
    {
        if ($token) {
            /**
             * build redirection page.
             */
            $page = new SiteTree();
            $page->Title = 'Redirection to Afterpay...';
            $page->Logo = '<img src="' . $this->config()->get('logo') . '" alt="Payments powered by Afterpay"/>';
            $controller = new ContentController($page);

            $requirement = Director::isLive() ? self::LIVE_URL : self::DEV_URL;
            Requirements::clear();
            Requirements::insertHeadTags('<script type="text/javascript" src="' . $requirement . '"></script>');
            Requirements::customScript('window.onload = function() { AfterPay.initialize({countryCode: "NZ"}); AfterPay.redirect({token: "' . $token . '"}); };');

            return EcommercePaymentProcessing::create($controller->renderWith('Sunnysideup\Ecommerce\PaymentProcessingPage'));
        }
        $page = new SiteTree();
        $page->Title = 'Sorry, Afterpay can not be contacted at the moment ...';
        $page->Logo = 'Sorry, an error has occured in contacting the Payment Processing Provider, please try again in a few minutes...';
        $controller = new ContentController($page);

        Requirements::clear();

        return EcommercePaymentFailure::create($controller->renderWith('Sunnysideup\Ecommerce\PaymentProcessingPage'));
    }

    public function getTokenFromAfterpay($order): string
    {
        if (empty($this->AfterpayToken)) {
            $obj = OrderToAfterpayConverter::create($order);
            $data = $obj->convert();

            $api = $this->myAfterpayApi();
            $tokenObject = $api->createOrder($data);
            $this->AfterpayResponse = serialize($tokenObject);
            if ($tokenObject instanceof OrderToken) {
                $tokenString = $tokenObject->getToken();
                $this->AfterpayToken = $tokenString;
            } else {
                $this->AfterpayToken = '';
            }

            $this->write();
        }

        return $this->AfterpayToken;
    }

    protected function myAfterpayApi()
    {
        return SilverstripeMerchantApi::inst()
            ->setMinAndMaxPrice(
                EcommerceConfig::inst()->AfterpayMinValue,
                EcommerceConfig::inst()->AfterpayMaxValue
            )
            ->setIsServerAvailable(true);
    }
}

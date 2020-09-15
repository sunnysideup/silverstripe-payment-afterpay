<?php

namespace Sunnysideup\Afterpay\Control;

use CultureKings\Afterpay\Model\Merchant\Payment;
use SilverStripe\Control\Controller;

use SilverStripe\Control\Director;

use Sunnysideup\Afterpay\Factory\SilverstripeMerchantApi;
use Sunnysideup\Afterpay\Model\AfterpayEcommercePayment;
use Sunnysideup\Ecommerce\Model\Config\EcommerceDBConfig;
use Sunnysideup\Ecommerce\Model\Order;

class AfterpayEcommercePaymentController extends Controller
{
    private static $allowed_actions = [
        'confirm' => true,
    ];

    public function index()
    {
        return $this->redirect('/');
    }

    public function allowedActions($limitToClass = null)
    {
        return self::$allowed_actions;
    }

    public function confirm($request)
    {
        $orderID = intval($request->param('ID'));
        $order = Order::get()->byID($orderID);
        $orderToken = $request->getVar('orderToken');
        $success = $request->getVar('status') === 'SUCCESS' ? true : false;
        $payment = AfterpayEcommercePayment::get()->filter(
            [
                'OrderID' => $orderID,
                'AfterpayToken' => $orderToken,
            ]
        )->first();
        if ($payment) {
            $payment->Status = 'Failure';
            if ($success) {
                if ($order) {
                    $api = $this->myAfterpayApi();
                    $response = $api->createPayment($orderToken, $order->ID);
                    if ($response instanceof Payment) {
                        $payment->AfterpayConfirmationToken = serialize($response);
                        if ($response->getStatus() === 'APPROVED') {
                            $payment->Status = 'Success';
                        }
                    }
                }
            } else {
                $payment->Status = 'Failure';
            }
            $payment->write();
        }
        if ($order) {
            return $this->redirect($order->Link());
        }

        return $this->redirect('/404-can-not-find-order');
    }

    public function Link($action = null): string
    {
        return '/afterpaypayment/';
    }

    public static function create_link($order): string
    {
        return Director::absoluteURL('afterpaypayment/confirm/' . $order->ID . '/');
    }

    public function ShowAfterpay($total): bool
    {
        return $this->myAfterpayApi()->canProcessPayment(floatval($total));
    }

    protected function capturePayment()
    {
    }

    protected function myAfterpayApi()
    {
        return SilverstripeMerchantApi::inst()
            ->setMinAndMaxPrice(
                EcommerceDBConfig::current_ecommerce_db_config()->AfterpayMinValue,
                EcommerceDBConfig::current_ecommerce_db_config()->AfterpayMaxValue
            )
            ->setIsServerAvailable(true);
    }

    // /**
    //  * @return EcommerceDBConfig
    //  */
    protected function EcomConfig()
    {
        return EcommerceDBConfig::current_ecommerce_db_config();
    }
}

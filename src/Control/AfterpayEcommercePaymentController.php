<?php

namespace Sunnysideup\Afterpay\Control;

use CultureKings\Afterpay\Model\Merchant\Payment;
use SilverStripe\Control\Controller;
use SilverStripe\Control\Director;
use Sunnysideup\Afterpay\Factory\SilverstripeMerchantApi;
use Sunnysideup\Afterpay\Model\AfterpayEcommercePayment;
use Sunnysideup\Ecommerce\Config\EcommerceConfig;
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
        $orderID = (int) $request->param('ID');
        $order = Order::get_order_cached((int) $orderID);
        $orderToken = $request->getVar('orderToken');
        $success = 'SUCCESS' === $request->getVar('status');
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
                        if ('APPROVED' === $response->getStatus()) {
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
                EcommerceConfig::inst()->AfterpayMinValue,
                EcommerceConfig::inst()->AfterpayMaxValue
            )
            ->setIsServerAvailable(true)
        ;
    }
}

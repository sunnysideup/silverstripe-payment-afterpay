<?php

namespace Sunnysideup\Afterpay\Control;

use Controller;
use Director;
use Order;
use Sunnysideup\Afterpay\Model\AfterpayEcommercePayment;

class AfterpayEcommercePaymentController extends Controller
{
    private static $allowed_actions = [
        'confirm' => true
    ];

    public function index()
    {
        return $this->redirect('/');
    }

    public function allowedActions($limitToClass = NULL)
    {
        return self::$allowed_actions;
    }

    public function confirm($request)
    {
        $orderID = intval($request->param('ID'));
        $orderToken = $request->getVar('orderToken');
        $success = $request->getVar('status') === 'SUCCESS' ? true : false;
        $payment = AfterpayEcommercePayment::get()->filter(
                [
                    'OrderID' => $orderID,
                    'AfterpayToken' => $orderToken
                ]
            )->first();
        if($payment) {
            if($success) {
                $payment->Status = 'Success';
            } else {
                $payment->Status = 'Failure';
            }
            $payment->write();
            $order = Order::get()->byID($orderID);

            return $this->redirect($order->Link());
        }

        return $this->redirect('404-can-not find payment');

    }

    public function Link($action = null) :string
    {
        return '/afterpaypayment/';
    }

    public static function create_link($order) : string
    {
        return Director::absoluteURL('afterpaypayment/confirm/'.$order->ID.'/');
    }


    protected function capturePayment()
    {

    }

}

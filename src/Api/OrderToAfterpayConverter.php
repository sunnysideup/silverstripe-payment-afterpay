<?php

namespace Sunnysideup\Afterpay\Api;

use CultureKings\Afterpay\Model\Item;
use CultureKings\Afterpay\Model\Merchant\Consumer;
use CultureKings\Afterpay\Model\Merchant\Contact;
use CultureKings\Afterpay\Model\Merchant\Discount;
use CultureKings\Afterpay\Model\Merchant\MerchantOptions;
use CultureKings\Afterpay\Model\Merchant\OrderDetails;
use CultureKings\Afterpay\Model\Merchant\ShippingCourier;
use CultureKings\Afterpay\Model\Money;
use DateTime;
use Sunnysideup\Afterpay\Control\AfterpayEcommercePaymentController;
use Sunnysideup\Ecommerce\Api\OrderConverter;

class OrderToAfterpayConverter extends OrderConverter
{
    public function convert()
    {
        $orderDetails = new OrderDetails();

        $orderDetails->setMerchantReference($this->order->ID);

        // the consumer //
        $consumer = new Consumer();
        $consumer->setEmail($this->billingAddress->Email);
        $consumer->setGivenNames($this->billingAddress->FirstName);
        $consumer->setSurname($this->billingAddress->Surname);
        $consumer->setPhoneNumber($this->billingAddress->Phone);
        //add to order details
        $orderDetails->setConsumer($consumer);

        // who is billing //
        $billingAddressContact = new Contact();
        $billingName = $this->implodeAndTrim(
            [
                $this->billingAddress->FirstName,
                $this->billingAddress->Surname,
            ]
        );
        if (! $billingName) {
            $billingName = 'not set';
        }
        $billingLine1 = $this->implodeAndTrim(
            [
                $this->billingAddress->Address,
                $this->billingAddress->Address2,
            ]
        );
        if (! $billingLine1) {
            $billingLine1 = 'not set';
        }
        $billingAddressContact->setName($billingName);
        $billingAddressContact->setLine1($billingLine1);
        $billingAddressContact->setState($this->billingAddress->City ?? 'not set');
        $billingAddressContact->setPostcode($this->billingAddress->PostalCode ?? 'not set');
        $billingAddressContact->setCountryCode($this->billingAddress->Country ?? 'not set');
        $billingAddressContact->setPhoneNumber($this->billingAddress->Phone ?? 'not set');
        //add to order details
        $orderDetails->setBilling($billingAddressContact);

        // who it is being shipped to //
        $shippingAddressContact = new Contact();
        $shippingName = $this->implodeAndTrim(
            [
                $this->shippingAddress->FirstName,
                $this->shippingAddress->Surname,
            ]
        );
        if (! $shippingName) {
            $shippingName = 'not set';
        }
        $shippingLine1 = $this->implodeAndTrim(
            [
                $this->shippingAddress->Address,
                $this->shippingAddress->Address2,
            ]
        );
        if (! $shippingLine1) {
            $shippingLine1 = 'not set';
        }
        $shippingAddressContact->setName($shippingName);
        $shippingAddressContact->setLine1($shippingLine1);
        $shippingAddressContact->setState($this->shippingAddress->City ?? 'not set');
        $shippingAddressContact->setPostcode($this->shippingAddress->PostalCode ?? 'not set');
        $shippingAddressContact->setCountryCode($this->shippingAddress->Country ?? 'not set');
        $shippingAddressContact->setPhoneNumber($this->shippingAddress->Phone ?? 'not set');
        //add to order details
        $orderDetails->setShipping($shippingAddressContact);

        // where to bring the user on payment fail or success (test info) //
        $merchantOptions = new MerchantOptions();
        $absoluteURLForOrder = AfterpayEcommercePaymentController::create_link($this->order);
        $merchantOptions->setRedirectConfirmUrl($absoluteURLForOrder);
        $merchantOptions->setRedirectCancelUrl($absoluteURLForOrder);

        //add to order details
        $orderDetails->setMerchant($merchantOptions);

        // Set the total amount for the order //
        $totalAmount = new Money();
        $totalAmount->setAmount($this->cleanupCurrencies($this->order->Total));
        $totalAmount->setCurrency($this->currencyCode);
        //add to order details
        $orderDetails->setTotalAmount($totalAmount);

        // Add the list of purchased items //
        $itemsList = [];

        foreach ($this->orderItems as $item) {
            $i = new Item();
            $i->setName($item->getTitle());
            $i->setSKU($item->getInternalItemID());
            $i->setQuantity($item->Quantity);

            $price = new Money();
            $price->setAmount($this->cleanupCurrencies($item->UnitPriceAsMoney()->Amount));
            // $price->setCurrency($item->UnitPriceAsMoney()->Currency);
            $price->setCurrency($this->currencyCode);

            $i->setPrice($price);

            $itemsList[] = $i;
        }

        $orderDetails->setItems($itemsList);

        // set discount amount //
        $discount = new Discount();
        $discount->setDisplayName('discount');

        $discountAmount = new Money();
        $discountAmount->setAmount($this->cleanupCurrencies($this->getAmountForModifierType('Discount')));
        $discountAmount->setCurrency($this->currencyCode);
        //set amount in Discount
        $discount->setAmount($discountAmount);
        //set discount in order details
        $orderDetails->setDiscounts([$discount]);

        // Update shipping amount //
        $shippingCost = new Money();
        $shippingCost->setAmount($this->cleanupCurrencies($this->getAmountForModifierType('Delivery')));
        $shippingCost->setCurrency($this->currencyCode);
        //set discount in order details
        $orderDetails->setShippingAmount($shippingCost);

        // Courier details (test details) //
        if ($this->hasCourier()) {
            $courier = new ShippingCourier();
            $tomorrowTS = strtotime('tomorrow');
            $dateTime = new DateTime(date('Y-m-d', $tomorrowTS));
            $courier->setShippedAt($dateTime);
            $courier->setName('tba');
            $courier->setTracking('tba');
            $courier->setPriority('tba');
        }

        // Update tax amount //
        $tax = new Money();
        $tax->setAmount($this->cleanupCurrencies($this->getAmountForModifierType('Tax')));
        $tax->setCurrency($this->currencyCode);
        //set discount in order details
        $orderDetails->setTaxAmount($tax);

        return $orderDetails;
    }

    public function hasCourier()
    {
        return false;
    }

    protected function cleanupCurrencies($value)
    {
        $value = str_replace(',', '', (string) $value);

        return floatval($value);
    }
}

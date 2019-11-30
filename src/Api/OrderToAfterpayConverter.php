<?php

namespace Sunnysideup\Afterpay\Api;

use CultureKings\Afterpay\Model\Merchant\Consumer;
use CultureKings\Afterpay\Model\Merchant\MerchantOptions;
use CultureKings\Afterpay\Model\Merchant\OrderDetails;
use CultureKings\Afterpay\Model\Merchant\Contact;
use CultureKings\Afterpay\Model\Merchant\ShippingCourier;
use CultureKings\Afterpay\Model\Merchant\Discount;
use CultureKings\Afterpay\Model\Item;
use CultureKings\Afterpay\Model\Money;

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
        $billingAddressContact->setName($this->billingAddress->FirstName.' '.$this->billingAddress->Surname);
        $billingAddressContact->setLine1($this->billingAddress->Address.' '.$this->billingAddress->Address2);
        $billingAddressContact->setState($this->billingAddress->City);
        $billingAddressContact->setPostcode($this->billingAddress->PostalCode);
        $billingAddressContact->setCountryCode($this->billingAddress->Country);
        $billingAddressContact->setPhoneNumber($this->billingAddress->Phone);
        //add to order details
        $orderDetails->setBilling($billingAddressContact);

        // who it is being shipped to //
        $shippingAddressContact = new Contact();
        $shippingAddressContact->setName($this->shippingAddress->FirstName.' '.$this->shippingAddress->Surname);
        $shippingAddressContact->setLine1($this->shippingAddress->Address.' '.$this->shippingAddress->Address2);
        $shippingAddressContact->setState($this->shippingAddress->City);
        $shippingAddressContact->setPostcode($this->shippingAddress->PostalCode);
        $shippingAddressContact->setCountryCode($this->shippingAddress->Country);
        $shippingAddressContact->setPhoneNumber($this->shippingAddress->Phone);
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

        foreach($this->orderItems as $item) {
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
        $this->shippingAddress = new Money();
        $this->shippingAddress->setAmount($this->cleanupCurrencies($this->getAmountForModifierType('Delivery')));
        $this->shippingAddress->setCurrency($this->currencyCode);
        //set discount in order details
        $orderDetails->setShippingAmount($this->shippingAddress);

        // Courier details (test details) //
        if($this->hasCourier()) {
            $courier = new ShippingCourier();
            $tomorrowTS = strtotime('tomorrow');
            $dateTime = new DateTime(Date('Y-m-d', $tomorrowTS));
            $courier->setShippedAt($dateTime);
            $courier->setName("CourierPost");
            $courier->setTracking("AA999999999AA");
            $courier->setPriority("STANDARD");
        }

        // Update tax amount //
        $tax = new Money();
        $tax->setAmount($this->cleanupCurrencies($this->getAmountForModifierType('Tax')));
        $tax->setCurrency($this->currencyCode);
        //set discount in order details
        $orderDetails->setTaxAmount($tax);

        return $orderDetails;
    }

    protected function cleanupCurrencies($value)
    {
        $value = str_replace(',', '', $value);

        return floatval($value);
    }


    public function hasCourier()
    {
        return false;
    }

}

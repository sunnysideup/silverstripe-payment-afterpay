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
        $orderDetails->setConsumer($consumer);

        // who is billing //
        $billingAddressContact = new Contact();
        $billingAddressContact->setName($this->billingAddress->FirstName.' '.$this->billingAddress->Surname);
        $billingAddressContact->setLine1($this->billingAddress->Address.' '.$this->billingAddress->Address2);
        $billingAddressContact->setState($this->billingAddress->City);
        $billingAddressContact->setPostcode($this->billingAddress->PostalCode);
        $billingAddressContact->setCountryCode($this->billingAddress->Country);
        $billingAddressContact->setPhoneNumber($this->billingAddress->Phone);
        $orderDetails->setBilling($billingAddressContact);

        // who it is being shipped to //
        $shippingAddressContact = new Contact();
        $shippingAddressContact->setName($this->shippingAddress->FirstName.' '.$this->shippingAddress->Surname);
        $shippingAddressContact->setLine1($this->shippingAddress->Address.' '.$this->shippingAddress->Address2);
        $shippingAddressContact->setState($this->shippingAddress->City);
        $shippingAddressContact->setPostcode($this->shippingAddress->PostalCode);
        $shippingAddressContact->setCountryCode($this->shippingAddress->Country);
        $shippingAddressContact->setPhoneNumber($this->shippingAddress->Phone);
        $orderDetails->setShipping($shippingAddressContact);


        // where to bring the user on payment fail or success (test info) //
        $merchantOptions = new MerchantOptions();
        $merchantOptions->setRedirectConfirmUrl($this->order->Link());
        $merchantOptions->setRedirectCancelUrl($this->order->Link());
        $orderDetails->setMerchant($merchantOptions);

        // Set the total amount for the order //
        $totalAmount = new Money();
        $totalAmount->setAmount($this->order->Total);
        $totalAmount->setCurrency($this->currencyCode);
        $orderDetails->setAmount($totalAmount);

        // Set the default amount for shipping //
        $this->shippingAddressAmount = new Money();
        $this->shippingAddressAmount->setAmount(0);
        $this->shippingAddressAmount->setCurrency($this->currencyCode);
        $this->shippingAddressAmount->setAmount($totalAmount);

        // Set the default amount to be paid in tax //
        $totalAmount = new Money();
        $totalAmount->setAmount(0);
        $totalAmount->setCurrency($this->currencyCode);
        $orderDetails->setTaxAmount($totalAmount);

        // Add the list of purchased items //
        $itemsList = [];

        foreach($this->orderItems as $item) {
            $i = new Item();
            $i->setName($item->getTitle());
            $i->setSKU($item->getInternalItemID());
            $i->setQuantity($item->Quantity);

            $price = new Money();
            $price->setAmount($item->UnitPriceAsMoney()->Amount);
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
        $discountAmount->setAmount($this->getAmountForModifierType('Discount'));
        $discountAmount->setCurrency($this->currencyCode);

        $discount->setAmount($discountAmount);

        $orderDetails->setDiscount($discount);

        // Update shipping amount //
        $this->shippingAddress = new Money();
        $this->shippingAddress->setAmount($orderDetails->getShippingAmount()->getAmount() + $this->getAmountForModifierType('Delivery'));
        $this->shippingAddress->setCurrency($this->currencyCode);
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
        $tax->setAmount($orderDetails->getTaxAmount()->getAmount() +$this->getAmountForModifierType('Tax'));
        $tax->setCurrency($this->currencyCode);
        $orderDetails->setTaxAmount($tax);

        return $orderDetails;
    }


    public function hasCourier()
    {
        return false;
    }

}

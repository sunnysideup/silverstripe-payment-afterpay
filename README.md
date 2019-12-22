# silverstripe-afterpay


Provides a basic implementation of afterpay as a payment method for
`sunnysideup/ecommerce` - using https://github.com/culturekings/afterpay as a base.

# installation

Use composer

```
composer require sunnysideup/afterpay
```

# setup

Add the following code as outlined below:

1. set up credentials

`mysite/_config/afterpay.yml`:

```yml

---
Name: Afterpay
---

Sunnysideup\Afterpay\Factory\SilverstripeMerchantApi:
  merchant_name: 'Photowarehouse'
  merchant_id: yyy
  secret_key: 'xxx'
  expectations_folder: 'mysite/afterpay/configurations'
  number_of_payments: 4
```

2. set afterpay as a payment option

`mysite/_config/ecommerce.yml`:

```yml

EcommercePayment:
  supported_methods:
    # ...
    Sunnysideup\Afterpay\Model\AfterpayEcommercePayment: "Afterpay"
```

3. add functionality to product:

`mysite/src/Model/MyProduct.php`:


```php
use Sunnysideup\Afterpay\Factory\SilverstripeMerchantApi;

class MyProduct extends Product
{
    protected $AfterpayMinValue = 100;

    protected $AfterpayMaxValue = 100;

    protected $ShowAfterpayOption = true;

    public function showAfterpay() : bool
    {
        return $this->ShowAfterpayOption() ? true : false;
    }


    protected function myAfterpayApi() : SilverstripeMerchantApi
    {
        return SilverstripeMerchantApi::inst()
            ->setMinAndMaxPrice(
                (float) $this->AfterpayMinValue,
                (float) $this->AfterpayMaxValue
            )
            ->setIsServerAvailable($this->ShowAfterpayOption);
    }


    public function hasAfterpay() : bool
    {
        return $this->myAfterpayApi()->canProcessPayment($this->CalculatedPrice());
    }

    public function getAfterpayNumberOfPayments() : int
    {
        return $this->myAfterpayApi()
            ->getNumberOfPayments();
    }

    public function getAfterpayNumberOfPaymentsInWeeks() : int
    {
        return $this->getAfterpayNumberOfPayments() * 2;
    }


    public function getAfterpayAmountPerPayment() :float
    {
        return $this->myAfterpayApi()
            ->getAmountPerPayment($this->CalculatedPrice());
    }

    public function getAfterpayAmountPerPaymentAsMoney()
    {
        return EcommerceCurrency::get_money_object_from_order_currency(
            $this->getAfterpayAmountPerPayment()
        );
    }
    public function getAfterpayAmountPerPaymentAsCurrency()
    {
        return DBField::create_field('Currency', $this->getAfterpayAmountPerPayment());
    }


    /**
     * Should the after pay option appear for a product
     */
    public function ShowAfterpayOption() : bool
    {
        return $this->hasAfterpay();
    }

}

```

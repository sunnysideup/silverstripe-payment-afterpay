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

`app/_config/afterpay.yml`:

```yml

---
Name: Afterpay
---

Sunnysideup\Afterpay\Factory\SilverstripeMerchantApi:
  merchant_name: 'my merchant name'
  merchant_id: yyy
  secret_key: 'xxx'
  expectations_folder: 'app/afterpay/configurations'
  number_of_payments: 4
```

2. set afterpay as a payment option

`app/_config/ecommerce.yml`:

```yml

EcommercePayment:
  supported_methods:
    # ...
    Sunnysideup\Afterpay\Model\AfterpayEcommercePayment: "Afterpay"
```

3. add fields to EcomConfig (via data extension or otherwise)

```php
    private static $db = [
        'ShowAfterpayOption' => 'Boolean',
        'AfterpayMinValue' => 'Int',
        'AfterpayMaxValue' => 'Int',
    ]
```

4. add functionality to product:

`app/src/Model/MyProduct.php`:


```php
use Sunnysideup\Afterpay\Factory\SilverstripeMerchantApi;

class MyProduct extends Product
{
    public function ShowAfterpay() : bool
    {
        return return $this->myAfterpayApi()->canProcessPayment($this->CalculatedPrice());
    }

    protected function myAfterpayApi() : SilverstripeMerchantApi
    {
        return SilverstripeMerchantApi::inst()
            ->setMinAndMaxPrice(
                (float) $this->EcomConfig()->AfterpayMinValue,
                (float) $this->EcomConfig()->AfterpayMaxValue
            )
            ->setIsServerAvailable($this->EcomConfig()->ShowAfterpayOption);
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

    public function getAfterpayAmountPerPaymentAsMoney() : Money
    {
        return EcommerceCurrency::get_money_object_from_order_currency(
            $this->getAfterpayAmountPerPayment()
        );
    }

    public function getAfterpayAmountPerPaymentAsCurrency(): Currency
    {
        return DBField::create_field('Currency', $this->getAfterpayAmountPerPayment());
    }


}

```

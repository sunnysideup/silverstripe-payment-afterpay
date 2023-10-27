`app/_config/payment.yml`

```yml
---
Name: app_payment
---
Sunnysideup\Ecommerce\Model\Money\EcommercePayment:
    supported_methods:
        Sunnysideup\Afterpay\Model\AfterpayEcommercePayment: Afterpay
        MyOrganisation\App\Ecommerce\Model\Payment\AfterPayInStore: AfterPay In Store
---
Name: app_afterpay_dev
---
Sunnysideup\Afterpay\Factory\SilverstripeMerchantApi:
    merchant_name: 'My Org'
    merchant_id: 123
    secret_key: 'abc'
    expectations_folder: 'app/afterpay/configurations'
    number_of_payments: 4
```

`app/src/Ecommerce/Api/EcommercePaymentSupportedMethodsProvider.php`

```php
<?php

namespace MyOrganisation\App\Ecommerce\Api;
use MyOrganisation\App\Ecommerce\Model\Payment\AfterPayInStore;
use SilverStripe\Control\Director;
use SilverStripe\Security\Permission;
use Sunnysideup\Afterpay\Api\OrderToAfterpayConverter;
use Sunnysideup\Afterpay\Factory\SilverstripeMerchantApi;
use Sunnysideup\Afterpay\Model\AfterpayEcommercePayment;
use Sunnysideup\Afterpay\Api\OrderToAfterpayConverter;
use Sunnysideup\Afterpay\Factory\SilverstripeMerchantApi;
use Sunnysideup\Afterpay\Model\AfterpayEcommercePayment;
use Sunnysideup\Ecommerce\Config\EcommerceConfig;
use Sunnysideup\Ecommerce\Model\Order;
use Sunnysideup\Ecommerce\Money\EcommercePaymentSupportedMethodsProvider;
use Sunnysideup\EcommerceSecurity\Model\Process\OrderStatusLogWhitelistCustomer;
use Sunnysideup\PaymentDirectcredit\DirectCreditPayment;
use Sunnysideup\PaymentDps\DpsPxPayPayment;
use Sunnysideup\PaymentDps\DpsPxPayPaymentRandomAmount;
use Sunnysideup\PaymentDps\Model\Process\OrderStepAmountConfirmed;
use Sunnysideup\PaymentHirePurchase\HirePurchasePayment;
use Sunnysideup\PaymentInstore\InStorePayment;
class EcommercePaymentSupportedMethodsProvider extends EcommercePaymentSupportedMethodsProvider
{
    public function SupportedMethods(?Order $order = null): array
    {
        $order = $this->orderToUse($order);
        $options = parent::SupportedMethods($order);
        $dbConfig = EcommerceConfig::inst();
        $hasAfterpay = false;
        if ($this->ShowAfterpay($order->getTotal())) {
            $hasAfterpay = true;
        }
        if (true !== $hasAfterpay) {
            $options[AfterpayEcommercePayment::class] = false;
        }
        if (isset($_GET['testafterpay']) && (Permission::check('ADMIN') || (Director::isDev() && $hasAfterpay))) {
            $this->testAfterpay();
        }
        return $options;
    }

    public function ShowAfterpay($total): bool
    {
        return $this->myAfterpayApi()->canProcessPayment($total);
    }

    protected function testAfterpay()
    {
        $obj = new OrderToAfterpayConverter();
        $data = $obj->convert();
        echo '<hr /><pre>';
        print_r($data);
        echo '</pre><hr />';
        $api = $this->myAfterpayApi();
        $tokenObject = $api->createOrder($data);
        echo '<hr />';
        print_r($tokenObject);
        echo '<hr />';
        die('END');
    }

    protected function myAfterpayApi()
    {
        return SilverstripeMerchantApi::inst()
            ->setMinAndMaxPrice(
                EcommerceConfig::inst()->AfterpayMinValue,
                EcommerceConfig::inst()->AfterpayMaxValue
            )
            ->setIsServerAvailable(EcommerceConfig::inst()->ShowAfterpayOption)
        ;
    }
}
```

`app/src/Ecommerce/Model/Payment/AfterPayInStore.php`

```php

<?php
namespace MyOrganisation\App\Ecommerce\Model\Payment;
use SilverStripe\Core\Config\Config;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\HiddenField;
use SilverStripe\Forms\LiteralField;
use Sunnysideup\Afterpay\Factory\SilverstripeMerchantApi;
use Sunnysideup\Ecommerce\Config\EcommerceConfig;
use Sunnysideup\Ecommerce\Model\Money\EcommercePayment;
use Sunnysideup\Ecommerce\Model\Order;
use Sunnysideup\Ecommerce\Money\Payment\PaymentResults\EcommercePaymentSuccess;
/**
 * Payment object representing a DirectCredit payment.
 *
 */
class AfterPayInStore extends EcommercePayment
{
    private static $logo = '/_resources/vendor/sunnysideup/afterpay/client/images/afterpay-logo-black.png';
    /**
     * Message shown before payment is made.
     *
     * @var string
     */
    private static $before_payment_message = '';
    /**
     * Message shown after payment is made.
     *
     * @var string
     */
    private static $after_payment_message = '';
    /**
     * Default Status for Payment.
     *
     * @var string
     */
    private static $default_status = EcommercePayment::PENDING_STATUS;
    /**
     * Process the DirectCredit payment method.
     *
     * @param mixed $data
     */
    public function processPayment($data, Form $form)
    {
        $this->Status = Config::inst()->get(AfterPayInStore::class, 'default_status');
        $this->Message = Config::inst()->get(AfterPayInStore::class, 'after_payment_message');
        $this->write();
        return EcommercePaymentSuccess::create();
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
    protected function myAfterpayApi()
    {
        return SilverstripeMerchantApi::inst()
            ->setMinAndMaxPrice(
                EcommerceConfig::inst()->AfterpayMinValue,
                EcommerceConfig::inst()->AfterpayMaxValue
            )
            ->setIsServerAvailable(EcommerceConfig::inst()->ShowAfterpayOption)
        ;
    }
}
```

`app/src/Ecommerce/Model/MyProduct.php`

```php

use MyOrganisation\App\Ecommerce\Traits\Products\AfterpayPaymentProductTrait;

class MyProduct extends Product
{
    use AfterpayPaymentProductTrait;
}

```

`app/src/Ecommerce/Traits/Products/PaymentProductTrait.php`

```php

<?php
namespace MyOrganisation\App\Ecommerce\Traits\Products;
use SilverStripe\ORM\FieldType\DBField;
use Sunnysideup\Afterpay\Factory\SilverstripeMerchantApi;
use Sunnysideup\Ecommerce\Config\EcommerceConfig;
use Sunnysideup\Ecommerce\Model\Money\EcommerceCurrency;
use Sunnysideup\Ecommerce\Pages\Product;
trait AfterpayPaymentProductTrait
{
    public function hasFinanceOptions(): bool
    {
        return $this->ShowAfterpayOption();
    }
    public function getAfterpayNumberOfPayments(): int
    {
        return $this->myAfterpayApi()
            ->getNumberOfPayments()
        ;
    }
    public function getAfterpayAmountPerPayment(): float
    {
        return $this->myAfterpayApi()
            ->getAmountPerPayment($this->CalculatedPrice())
        ;
    }
    public function getAfterpayAmountPerPaymentAsCurrency()
    {
        return DBField::create_field('Currency', $this->getAfterpayAmountPerPayment());
        return $this->ShowAutoPaymentOptions();
    }
    /**
     * Should the after pay option appear for a product.
     */
    public function ShowAfterpayOption(): bool
    {
        if (MyProduct::class === $this->ClassName) {
            if ($this->HasPrice()) {
                return $this->myAfterpayApi()->canProcessPayment($this->CalculatedPrice());
            }
        }
        return false;
    }
    public function ShowAutoPaymentOptions()
    {
        if (MyProduct::class === $this->ClassName) {
            if ($this->HasPrice()) {
                if (EcommerceConfig::inst()->ShowHirePurchaseCalculator) {
                    if ($this->Price >= EcommerceConfig::inst()->HirePurchaseMinValue) {
                        return true;
                    }
                }
            }
        }
        return false;
    }
    protected function myAfterpayApi(): SilverstripeMerchantApi
    {
        return SilverstripeMerchantApi::inst()
            ->setMinAndMaxPrice(
                (float) EcommerceConfig::inst()->AfterpayMinValue,
                (float) EcommerceConfig::inst()->AfterpayMaxValue
            )
            ->setIsServerAvailable(EcommerceConfig::inst()->ShowAfterpayOption)
        ;
    }
}
```

`themes/base/templates/Includes/AfterpayPaymentOptions.ss`

```html
<% if $ShowAfterpayOption %>
<div class="weeklypayment-wrap afterpay" id="afterpay-wrap-$ID">
    <div class="weeklypayment-row">
        <div class="weeklypayment-col-one">
            <span class="per-week">Buy Now</span>
            <strong class="bigPrice">pay $getAfterpayNumberOfPayments x</strong>
            <span class="per-week">interest free</span>
        </div>
        <div class="weeklypayment-col-one">
            <span class="per-week"
                >$getAfterpayNumberOfPayments payments of</span
            >
            <strong class="bigPrice">
                $getAfterpayAmountPerPaymentAsCurrency.Nice
            </strong>
            <span class="per-week">paid fortnightly</span>
        </div>
        <div class="weeklypayment-col-three">
            <a href="https://www.afterpay.com/terms/" class="externalLink">
                <img
                    src="/_resources/themes/mytheme/images/afterpay-logo-black.png"
                    alt="After Pay Logo"
                />
            </a>
        </div>
    </div>
</div>
<% end_if %>
```

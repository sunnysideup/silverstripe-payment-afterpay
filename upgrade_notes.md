2020-06-26 10:46

# running php upgrade upgrade see: https://github.com/silverstripe/silverstripe-upgrader
cd /var/www/upgrades/afterpay
php /var/www/upgrader/vendor/silverstripe/upgrader/bin/upgrade-code upgrade /var/www/upgrades/afterpay/afterpay  --root-dir=/var/www/upgrades/afterpay --write -vvv
Writing changes for 4 files
Running upgrades on "/var/www/upgrades/afterpay/afterpay"
[2020-06-26 10:46:56] Applying UpdateConfigClasses to routes.yml...
[2020-06-26 10:46:56] Applying RenameClasses to SilverstripeMerchantApi.php...
[2020-06-26 10:46:56] Applying ClassToTraitRule to SilverstripeMerchantApi.php...
[2020-06-26 10:46:56] Applying RenameClasses to AfterpayEcommercePaymentController.php...
[2020-06-26 10:46:56] Applying ClassToTraitRule to AfterpayEcommercePaymentController.php...
[2020-06-26 10:46:56] Applying RenameClasses to OrderToAfterpayConverter.php...
[2020-06-26 10:46:56] Applying ClassToTraitRule to OrderToAfterpayConverter.php...
[2020-06-26 10:46:56] Applying RenameClasses to AfterpayEcommercePayment.php...
[2020-06-26 10:46:56] Applying ClassToTraitRule to AfterpayEcommercePayment.php...
[2020-06-26 10:46:56] Applying RenameClasses to _config.php...
[2020-06-26 10:46:56] Applying ClassToTraitRule to _config.php...
modified:	src/Factory/SilverstripeMerchantApi.php
@@ -12,12 +12,17 @@
 use CultureKings\Afterpay\Service\Merchant\Payments;
 use GuzzleHttp\Client;
 use GuzzleHttp\ClientInterface;
-use ViewableData;
-use Director;
-use ShoppingCart;
-use DBField;
-use Order;
+
+
+
+
+
 use Currency;
+use SilverStripe\Control\Director;
+use Sunnysideup\Ecommerce\Api\ShoppingCart;
+use SilverStripe\ORM\FieldType\DBField;
+use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Extensible;
use SilverStripe\Core\Injector\Injectable;
+

 /**
  * An API which handles the main steps needed for a website to function with afterpay

Warnings for src/Factory/SilverstripeMerchantApi.php:
 - src/Factory/SilverstripeMerchantApi.php:203 PhpParser\Node\NullableType
 - WARNING: New class instantiated by a dynamic value on line 203

 - src/Factory/SilverstripeMerchantApi.php:421 PhpParser\Node\Expr\Variable
 - WARNING: New class instantiated by a dynamic value on line 421

 - src/Factory/SilverstripeMerchantApi.php:421 PhpParser\Node\Expr\Variable
 - WARNING: New class instantiated by a dynamic value on line 421

 - src/Factory/SilverstripeMerchantApi.php:497 PhpParser\Node\Expr\Variable
 - WARNING: New class instantiated by a dynamic value on line 497

modified:	src/Control/AfterpayEcommercePaymentController.php
@@ -2,13 +2,18 @@

 namespace Sunnysideup\Afterpay\Control;

-use Controller;
-use Order;
+
+
 use AfterpayEcommercePayment;
 use Payment;
-use Director;
+
 use SilverstripeMerchantApi;
-use EcommerceDBConfig;
+
+use Sunnysideup\Ecommerce\Model\Order;
+use SilverStripe\Control\Director;
+use Sunnysideup\Ecommerce\Model\Config\EcommerceDBConfig;
+use SilverStripe\Control\Controller;
+


 class AfterpayEcommercePaymentController extends Controller

modified:	src/Api/OrderToAfterpayConverter.php
@@ -2,17 +2,20 @@

 namespace Sunnysideup\Afterpay\Api;

-use OrderConverter;
+
 use OrderDetails;
 use Consumer;
 use Contact;
 use MerchantOptions;
-use AfterpayEcommercePaymentController;
+
 use Money;
 use Item;
 use Discount;
 use ShippingCourier;
 use DateTime;
+use Sunnysideup\Afterpay\Control\AfterpayEcommercePaymentController;
+use Sunnysideup\Ecommerce\Api\OrderConverter;
+


 class OrderToAfterpayConverter extends OrderConverter

modified:	src/Model/AfterpayEcommercePayment.php
@@ -6,21 +6,31 @@
 use Sunnysideup\Afterpay\Factory\SilverstripeMerchantApi;
 use Sunnysideup\Afterpay\Api\OrderToAfterpayConverter;

-use ReadonlyField;
-use FieldList;
-use LiteralField;
-use SiteTree;
-use Requirements;
-use EcommercePayment;
-use EcommercePaymentResult;
-use EcommercePaymentProcessing;
-use EcommercePaymentFailure;
-use EcommerceDBConfig;
-use OrderForm;
-use ContentController;
-use ShoppingCart;
-use Director;
+
+
+
+
+
+
+
+
+
+
+
+
+
+
 use CultureKings\Afterpay\Model\Merchant\OrderToken;
+use SilverStripe\Forms\ReadonlyField;
+use SilverStripe\Forms\LiteralField;
+use SilverStripe\Forms\FieldList;
+use SilverStripe\CMS\Model\SiteTree;
+use SilverStripe\CMS\Controllers\ContentController;
+use SilverStripe\Control\Director;
+use SilverStripe\View\Requirements;
+use Sunnysideup\Ecommerce\Model\Config\EcommerceDBConfig;
+use Sunnysideup\Ecommerce\Model\Money\EcommercePayment;
+

 /**
  *@author nicolaas[at]sunnysideup.co.nz

Writing changes for 4 files
✔✔✔
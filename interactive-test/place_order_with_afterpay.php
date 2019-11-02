<?php
    require('init_api.php');

    use \CultureKings\Afterpay\Model\Merchant\Consumer;
    use \CultureKings\Afterpay\Model\Merchant\MerchantOptions;
    use \CultureKings\Afterpay\Model\Money;
    use \CultureKings\Afterpay\Model\Merchant\OrderDetails;

    $consumer = new Consumer();
    $consumer->setEmail('john.doe@culturekings.com.au');
    $consumer->setGivenNames('John');
    $consumer->setSurname('Doe');
    $consumer->setPhoneNumber('0534242323');

    $merchantOptions = new MerchantOptions();
    $merchantOptions->setRedirectConfirmUrl($host . 'confirm.php');
    $merchantOptions->setRedirectCancelUrl($host . 'checkout.php?afterpay=fail');

    $totalAmount = new Money();
    $totalAmount->setAmount(mt_rand(1, 300));
    $totalAmount->setCurrency('NZD');

    $orderDetails = new OrderDetails();
    $orderDetails->setConsumer($consumer);
    $orderDetails->setMerchant($merchantOptions);
    $orderDetails->setTotalAmount($totalAmount);

    try {
        $_SESSION['api']->createOrder($orderDetails);
    } catch(Exception $e) {
        header('Location: '. $host . 'checkout.php?afterpay=down');
    }

    // If no server available (getting repsonses from cache) automatically redirect with confirmation //
    if(!$_SESSION['api']->getIsServerAvailable()) {
        header('Location: '. $host . 'confirm.php');
    }
?>
<!--<!DOCTYPE html>
<html>
<head>
    <script src="https://portal.sandbox.afterpay.com/afterpay.js" async></script>
</head>
<body>

    Your HTML here
    <script>
    window.onload = function() {
        Afterpay.initialize({countryCode: "NZ"});
        Afterpay.redirect({token: "123456"});
    };
    </script>

</body>
</html>-->

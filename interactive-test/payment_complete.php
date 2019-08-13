<?php
     require('init_api.php');
?>
<!DOCTYPE html>
<html>
<body>

    <h1>Payment Complete!</h1>

    <p>
        <?php echo("Total comes to $" . $checkout_total); ?><br />
        Have fun with your new deck chair<br />
        <a href="index.php">Let's do that again</a>
    </p>

</body>
</html>

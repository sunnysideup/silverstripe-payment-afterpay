## 1. On daily update / checkout

Collect the afterpay configuration (https://docs.afterpay.com/nz-online-api-v1.html#configuration)

## 2. On product Page

If product can be paid for with afterpay (based on the maximum and minimum amounts from the configuration data)
- then display the option with text like "$104.95 Or 4 payments of $26.24 with afterpay logo"

## 3. When the user has to choose a payment option

Check if the final price is between the minimum and maximum ranges for AfterPay (provided in step 1)
- if the price is in the range then allow afterpay as a payment option

## 4. When the checkout afterpay option is clicked in payment

Move the user to a "redirect page" which ->
    - Creates an order with afterpay and collect the response token (https://docs.afterpay.com/nz-online-api-v1.html#create-order)
    - if the order creation is successful redirect the user to afterpay (move to step 5)
    - Otherwise return to payment method and say afterpay is not working

## 5. Afterpay will redirect to your redirectConfirmUrl or redirectCancelUrl

Redirect cancel will move the user back to payment options with a "pre-approval fail / cancel thingy"
Redirect confirm will move the user to a "do you want to place order?" (move to step 6 if yes, back to choose payment if no)

## 6. Place order

When the place order button is pressed, redirect the user to a php page which sends a create payment request to afterpay (https://docs.afterpay.com/nz-online-api-v1.html#capture-payment)
 - If the response is declined (move user back to payment method)
 - If the response is accepted move to order completed


#### QUESTIONS FOR CLIENT:
- what products are on afterpay
- where is our login
- how often will min / max change
- Is only PAY_BY_INSTALLMENT going to be implmented
- How many currencies are being used

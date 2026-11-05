# Failed purchase with Stripe in TEST mode: the customer uses the Stripe test card that is always declined.
# cart -> payment.php -> Dolibarr payment page -> Stripe refuses the card -> the site must not accept the payment:
# payment failed page, and the order must not appear in the history.
# Creates (or reuses) a pending order of the test account.
#
# hurl cannot run the Stripe.js card field, so the card step is replayed (see 04_pay_stripe_paid_WRITE.hurl).
# The Stripe TEST payment method pm_card_chargeDeclined is the card that is always declined.

# Why [BasicAuth] on every request to the site: this file runs without --user (see run.sh), because the basic auth
# of the site must not be sent to the third party hosts called below (api.stripe.com). So it is set only on the requests to the site.

# ---- Login and cart
GET {{base_url}}/login-register.php
[BasicAuth]
{{basic_user}}: {{basic_password}}
HTTP 200

POST {{base_url}}/login-register.php
[BasicAuth]
{{basic_user}}: {{basic_password}}
[FormParams]
action: login
back:
email: {{customer_email}}
passwd: {{customer_password}}
HTTP 302

# The login reloads the pending order of the account into the cart, so the cart may already have lines
POST {{base_url}}/_service_page_ajax.php
[BasicAuth]
{{basic_user}}: {{basic_password}}
[FormParams]
product_id: {{paid_product_id}}
product_quantity: 1
product_thumb: test.png
HTTP 200
[Asserts]
jsonpath "$.items" >= 1

# ---- Payment step: creates the order, gives the payment URL (iframe src or link href)
GET {{base_url}}/payment.php
[BasicAuth]
{{basic_user}}: {{basic_password}}
HTTP 200
[Captures]
payurl: xpath "string(//div[@id='HOOK_PAYMENT']/iframe/@src | //a[@class='payment-button-link']/@href)"
orderref: xpath "string(//div[@id='HOOK_PAYMENT']/iframe/@src | //a[@class='payment-button-link']/@href)" regex "ref=(CO[0-9-]+)"
[Asserts]
variable "payurl" contains "paymentmethod=stripe"

# ---- Dolibarr payment page: summary of the payment and the "Pay with Stripe" button
GET {{payurl}}
[BasicAuth]
{{basic_user}}: {{basic_password}}
HTTP 200
[Captures]
token: xpath "string(//form[@id='dolpaymentform']/input[@name='token']/@value)"
securekey: xpath "string(//form[@id='dolpaymentform']/input[@name='securekey']/@value)"
creditor: xpath "string(//form[@id='dolpaymentform']/input[@name='creditor']/@value)"
dolid: xpath "string(//form[@id='dolpaymentform']//input[@name='dol_id']/@value)"
amount: xpath "string(//form[@id='dolpaymentform']//input[@name='amount']/@value)"
fulltag: xpath "string(//form[@id='dolpaymentform']//input[@name='fulltag']/@value)"
thirdpartyid: xpath "string(//form[@id='dolpaymentform']//input[@name='thirdparty_id']/@value)"
desc: xpath "string(//form[@id='dolpaymentform']//input[@name='desc']/@value)"
[Asserts]
body contains "Payment of order {{orderref}}"
body contains "id=\"dopayment_stripe\""
variable "token" != ""
variable "securekey" != ""

# ---- Click on "Pay with Stripe": the page now shows the card form (Stripe.js) with the PaymentIntent
POST {{base_url}}/public/payment/newpayment.php
[BasicAuth]
{{basic_user}}: {{basic_password}}
[FormParams]
token: {{token}}
action: dopayment
tag:
suffix:
securekey: {{securekey}}
e: 1
lang: en_US
ws: marketplace
creditor: {{creditor}}
s: order
ref: {{orderref}}
dol_id: {{dolid}}
amount: {{amount}}
newamount: {{amount}}
currency: EUR
fulltag: {{fulltag}}
thirdparty_id: {{thirdpartyid}}
desc: {{desc}}
dopayment_stripe: Pay with Stripe
noidempotency: 1
HTTP 200
[Captures]
chargetoken: xpath "string(//form[@id='payment-form']/input[@name='token']/@value)"
chargefulltag: xpath "string(//form[@id='payment-form']/input[@name='fulltag']/@value)"
chargekey: xpath "string(//form[@id='payment-form']/input[@name='securekey']/@value)"
chargethirdparty: xpath "string(//form[@id='payment-form']/input[@name='thirdparty_id']/@value)"
pi: xpath "string(//form[@id='payment-form']//input[@name='paymentintent_id']/@value)"
secret: xpath "string(//button[@id='buttontopay']/@data-secret)"
pk: regex /Stripe[(]'(pk_test_[A-Za-z0-9]+)'[)]/
[Asserts]
variable "pi" startsWith "pi_"
variable "secret" startsWith "pi_"
variable "pk" startsWith "pk_test_"
body contains "id=\"payment-form\""
body contains "js.stripe.com/v3"
body not contains "pk_live_"

# ---- Stripe refuses the card (same call as Stripe.js): in the browser the page shows the error and stops here
POST https://api.stripe.com/v1/payment_intents/{{pi}}/confirm
[FormParams]
key: {{pk}}
client_secret: {{secret}}
payment_method: pm_card_chargeDeclined
use_stripe_sdk: true
HTTP 402
[Asserts]
jsonpath "$.error.code" == "card_declined"
jsonpath "$.error.payment_intent.status" == "requires_payment_method"

# ---- The payment form is sent to the site anyway (a customer forging the request): the site checks the
# PaymentIntent on the Stripe side, it is not paid, so the site must show the payment failed page
POST {{base_url}}/public/payment/newpayment.php
[BasicAuth]
{{basic_user}}: {{basic_password}}
[Options]
location: true
[FormParams]
token: {{chargetoken}}
dopayment_stripe: 1
action: charge
tag:
s: order
ref: {{orderref}}
fulltag: {{chargefulltag}}
suffix:
securekey: {{chargekey}}
e: 1
amount: {{amount}}
currency: EUR
email:
thirdparty_id: {{chargethirdparty}}
lang: en_US
paymentintent_id: {{pi}}
HTTP 200
[Asserts]
url contains "paymentko.php"
body contains "Payment failed"

# ---- The unpaid order is not in the history
GET {{base_url}}/order-history.php
[BasicAuth]
{{basic_user}}: {{basic_password}}
HTTP 200
[Asserts]
body not contains "{{orderref}}"

# Failed purchase with Stancer in TEST mode: the customer clicks "Decline" on the 3D Secure test page.
# The site must show the payment failed page and the order must not appear in the history.
# Creates (or reuses) a pending order of the test account.
#
# Same steps as 04_pay_stancer_paid_WRITE.hurl until the 3D Secure page, where the customer declines.
# hurl cannot run JavaScript: the Stancer and 3D Secure pages are replayed with the same HTTP calls as the browser.
#
# Why [BasicAuth] on every request to the site: this file runs without --user (see run.sh), because the basic auth
# of the site must not be sent to the third party hosts called below. So it is set only on the requests to the site.

# ---- Login, cart, payment step
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

# payment.php creates (or reuses) the pending order and gives the payment URL (iframe src or link href) and the order ref
GET {{base_url}}/payment.php
[BasicAuth]
{{basic_user}}: {{basic_password}}
HTTP 200
[Captures]
payurl: xpath "string(//div[@id='HOOK_PAYMENT']/iframe/@src | //a[@class='payment-button-link']/@href)"
orderref: xpath "string(//div[@id='HOOK_PAYMENT']/iframe/@src | //a[@class='payment-button-link']/@href)" regex "ref=(CO[0-9-]+)"

# The Dolibarr payment page creates the Stancer payment intent (TEST mode) and redirects to it: test_pi_<24 characters>
GET {{payurl}}
[BasicAuth]
{{basic_user}}: {{basic_password}}
HTTP 302
[Captures]
piid: header "Location" regex "/test_pi_(\\w{24})"
[Asserts]
header "Location" matches "^https://payment\\.stancer\\.com/test_pi_"

# ---- Card accepted by Stancer, then 3D Secure
# Card details sent to Stancer (same call as the Stancer page JavaScript). Stancer answers the URL of the 3D Secure page
POST https://payment.stancer.com/test_pi_{{piid}}/card
{
  "number": "4242424242424242",
  "exp_month": 12,
  "exp_year": 2032,
  "cvc": "123",
  "name": "Test Hurl",
  "preferred_network": "visa"
}
HTTP 200
[Captures]
url3ds: jsonpath "$['3ds']"

# 3D Secure test page of the bank simulator: gives the hidden fields (MD, PaReq, TermUrl) to send to the next step
GET {{url3ds}}
HTTP 200
[Captures]
md: xpath "string(//input[@name='MD']/@value)"
pareq: xpath "string(//input[@name='PaReq']/@value)"
termurl: xpath "string(//input[@name='TermUrl']/@value)"

# Authentication step: the simulator shows its validation page with the Accept and Decline buttons
POST https://3ds.iliad78.net/v1/acs
[FormParams]
PaReq: {{pareq}}
TermUrl: {{termurl}}
MD: {{md}}
HTTP 200
[Captures]
pareq2: xpath "string(//input[@name='PaReq']/@value)"
md2: xpath "string(//input[@name='MD']/@value)"

# The customer declines the authentication (accept=nok is the "Decline" button)
POST https://3ds.iliad78.net/v1/acs_post
[FormParams]
accept: nok
MD: {{md2}}
PaReq: {{pareq2}}
HTTP 200
[Captures]
pares: xpath "string(//input[@name='PaRes']/@value)"
md3: xpath "string(//input[@name='MD']/@value)"

# Result of the authentication, given back to the merchant side (PaRes)
POST https://3ds.iliad78.net/v1/return
[FormParams]
MD: {{md3}}
PaRes: {{pares}}
HTTP 200
[Captures]
payid: xpath "string(//input[@name='payment']/@value)"
payerror: xpath "string(//input[@name='error']/@value)"

# Stancer refuses the payment
POST https://payment.stancer.com/test_pi_{{piid}}/3ds
[FormParams]
error: {{payerror}}
payment: {{payid}}
HTTP 200
[Asserts]
xpath "string(//body/@data-status)" == "fail.authenticate"
xpath "string(//body/@data-response)" == "declined"

# The Stancer page then gives the return URL to the site (in the data-payment-intent attribute): the payment is NOT paid
GET https://payment.stancer.com/test_pi_{{piid}}
HTTP 200
[Captures]
returnurl: xpath "string(//body/@data-payment-intent)" jsonpath "$.return_url"
[Asserts]
xpath "string(//body/@data-status)" == "unpaid"

# ---- Return on the site: the payment failed page, nothing is debited
# The return URL can be on another host than the site (ex: the Dolibarr back-office host), which redirects to the site:
# location-trusted keeps the basic auth along the redirections (without it the last one answers 401)
GET {{returnurl}}
[BasicAuth]
{{basic_user}}: {{basic_password}}
[Options]
location-trusted: true
HTTP 200
[Asserts]
url contains "paymentko.php"
body contains "Payment failed"

# ---- The unpaid order is not in the history (only paid orders are listed)
GET {{base_url}}/order-history.php
[BasicAuth]
{{basic_user}}: {{basic_password}}
HTTP 200
[Asserts]
body not contains "{{orderref}}"

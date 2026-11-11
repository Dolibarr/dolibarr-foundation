# Full purchase with Stancer in TEST mode and the test card 4242 4242 4242 4242 (iframe or link, same payment URL).
# cart -> payment.php (creates the order) -> Stancer card + 3D Secure test page (Accept) -> return on the site
# -> paymentok -> order details -> order history -> download of the purchased module.
# Creates a real order, invoice and payment on the test instance, at every run.
#
# hurl cannot run JavaScript: the Stancer and 3D Secure pages are replayed with the same HTTP calls as the browser.
#
# Why [BasicAuth] on every request to the site: this file runs without --user (see run.sh), because the basic auth
# of the site must not be sent to the third party hosts called below. So it is set only on the requests to the site.

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
variable "payurl" contains "paymentmethod=stancerdolicloud"

# ---- Dolibarr payment page -> Stancer payment intent (TEST mode)
GET {{payurl}}
[BasicAuth]
{{basic_user}}: {{basic_password}}
HTTP 302
[Captures]
piid: header "Location" regex "/test_pi_(\\w{24})"
[Asserts]
header "Location" matches "^https://payment\\.stancer\\.com/test_pi_"

# ---- Card details sent to Stancer (same call as the Stancer payment page JavaScript)
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
[Asserts]
jsonpath "$['3ds']" startsWith "https://3ds.iliad78.net/"

# ---- 3D Secure test flow
GET {{url3ds}}
HTTP 200
[Captures]
md: xpath "string(//input[@name='MD']/@value)"
pareq: xpath "string(//input[@name='PaReq']/@value)"
termurl: xpath "string(//input[@name='TermUrl']/@value)"
[Asserts]
variable "pareq" != ""

POST https://3ds.iliad78.net/v1/acs
[FormParams]
PaReq: {{pareq}}
TermUrl: {{termurl}}
MD: {{md}}
HTTP 200
[Captures]
pareq2: xpath "string(//input[@name='PaReq']/@value)"
md2: xpath "string(//input[@name='MD']/@value)"
[Asserts]
body contains "Validation test page"
body contains "xxxx xxxx xxxx 4242"

# The validation test page has two buttons: accept=ok (Accept) and accept=nok (Decline)
POST https://3ds.iliad78.net/v1/acs_post
[FormParams]
accept: ok
MD: {{md2}}
PaReq: {{pareq2}}
HTTP 200
[Captures]
pares: xpath "string(//input[@name='PaRes']/@value)"
md3: xpath "string(//input[@name='MD']/@value)"
[Asserts]
variable "pares" != ""

POST https://3ds.iliad78.net/v1/return
[FormParams]
MD: {{md3}}
PaRes: {{pares}}
HTTP 200
[Captures]
payid: xpath "string(//input[@name='payment']/@value)"
payerror: xpath "string(//input[@name='error']/@value)"
[Asserts]
variable "payid" startsWith "paym_"

# 3D Secure result sent back to Stancer: the payment is paid
POST https://payment.stancer.com/test_pi_{{piid}}/3ds
[FormParams]
error: {{payerror}}
payment: {{payid}}
HTTP 200
[Asserts]
xpath "string(//body/@data-status)" == "ok.paid"

# ---- Return on the site (what the browser does after the Stancer page): follow the redirections
GET https://payment.stancer.com/test_pi_{{piid}}
HTTP 200
[Captures]
returnurl: xpath "string(//body/@data-payment-intent)" jsonpath "$.return_url"
[Asserts]
xpath "string(//body/@data-status)" == "paid"
variable "returnurl" contains "action=returnDoPaymentStancer"

# The return URL can be on another host than the site (ex: the Dolibarr back-office host), which redirects to the site:
# location-trusted keeps the basic auth along the redirections (without it the last one answers 401)
GET {{returnurl}}
[BasicAuth]
{{basic_user}}: {{basic_password}}
[Options]
location-trusted: true
HTTP 200
[Asserts]
url contains "order-details.php?ref={{orderref}}"
body contains "Payment accepted"
body contains "Invoice Reference"
[Captures]
downloadurl: xpath "string((//a[contains(@href,'_service_download.php')])[1]/@href)"

# ---- The cart is empty after the payment
POST {{base_url}}/_service_page_ajax.php
[BasicAuth]
{{basic_user}}: {{basic_password}}
[FormParams]
load_cart: 1
HTTP 200
[Asserts]
body contains "cart_block_no_products"

# ---- The paid order is in the history
GET {{base_url}}/order-history.php
[BasicAuth]
{{basic_user}}: {{basic_password}}
HTTP 200
[Asserts]
body contains "{{orderref}}"
body not contains "No order founded"

# ---- The purchased module can be downloaded (zip archive)
GET {{base_url}}/{{downloadurl}}
[BasicAuth]
{{basic_user}}: {{basic_password}}
HTTP 200
[Asserts]
header "Content-Disposition" contains "attachment"
header "Content-Disposition" contains ".zip"
bytes startsWith hex,504b0304;

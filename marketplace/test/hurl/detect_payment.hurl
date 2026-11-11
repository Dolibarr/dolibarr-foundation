# Used by run.sh (not a test): finds which payment gateway the instance uses.
# It does what a customer does: login, product in the cart, payment step. The answer of the last
# request (payment.php) is printed and run.sh reads paymentmethod=... in the payment URL
# (stancerdolicloud, stripe, ...).
# Like the payment tests, it creates (or reuses) the pending order of the test account.

GET {{base_url}}/login-register.php
HTTP 200

POST {{base_url}}/login-register.php
[FormParams]
action: login
back:
email: {{customer_email}}
passwd: {{customer_password}}
HTTP 302

POST {{base_url}}/_service_page_ajax.php
[FormParams]
product_id: {{paid_product_id}}
product_quantity: 1
product_thumb: test.png
HTTP 200

GET {{base_url}}/payment.php
HTTP 200

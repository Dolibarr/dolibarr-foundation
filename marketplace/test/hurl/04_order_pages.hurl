# Order page (cart content and link to the payment step) and payment return pages. Read only.
# Session only: order.php does not create anything. The order is created later by payment.php

# ---- A visitor puts a product in the cart (the cart is in the session of the visitor)
POST {{base_url}}/_service_page_ajax.php
[FormParams]
product_id: {{cart_product_id_1}}
product_quantity: 1
product_thumb: test.png
HTTP 200
[Asserts]
jsonpath "$.items" == 1

# ---- Order page: shows the product of the cart and the link to the payment step
GET {{base_url}}/order.php
HTTP 200
[Asserts]
body contains "product_img_{{cart_product_id_1}}"
body contains "payment.php"

# ---- Payment return pages for a visitor without session
# Payment OK needs a logged in customer: redirected to the login page
GET {{base_url}}/paymentok.php
HTTP 302
[Asserts]
header "Location" contains "login-register.php"

# Payment KO page is displayed (with an error message because of the missing parameters)
GET {{base_url}}/paymentko.php
HTTP 200

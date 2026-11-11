# Cart edge cases: empty cart, invalid inputs that add nothing, then removing products until the cart is empty.
# Session only, nothing is created on the server.
# The cart endpoint of the template is _service_page_ajax.php (shopping_cart_url in the template JS).

# ---- Empty cart
# Cart block of the header
POST {{base_url}}/_service_page_ajax.php
[FormParams]
load_cart: 1
HTTP 200
[Asserts]
body contains "cart_block_no_products"

# Popup of the products: nothing is returned when the cart is empty
POST {{base_url}}/_service_page_ajax.php
[FormParams]
load_popup_products_html: 1
HTTP 200
[Asserts]
body == ""

# Removing a product that is not in an empty cart does not fail
GET {{base_url}}/_service_page_ajax.php?remove_code={{cart_product_id_1}}
HTTP 200

# ---- Invalid inputs add nothing: the endpoint answers 200 with an empty body

# Unknown product
POST {{base_url}}/_service_page_ajax.php
[FormParams]
product_id: 99999999
product_quantity: 1
HTTP 200
[Asserts]
body == ""

# Quantity 0
POST {{base_url}}/_service_page_ajax.php
[FormParams]
product_id: {{cart_product_id_1}}
product_quantity: 0
HTTP 200
[Asserts]
body == ""

# Negative quantity
POST {{base_url}}/_service_page_ajax.php
[FormParams]
product_id: {{cart_product_id_1}}
product_quantity: -3
HTTP 200
[Asserts]
body == ""

# Missing quantity
POST {{base_url}}/_service_page_ajax.php
[FormParams]
product_id: {{cart_product_id_1}}
HTTP 200
[Asserts]
body == ""

# Non numeric product id
POST {{base_url}}/_service_page_ajax.php
[FormParams]
product_id: abc
product_quantity: 1
HTTP 200
[Asserts]
body == ""

# After all these attempts the cart is still empty
POST {{base_url}}/_service_page_ajax.php
[FormParams]
load_cart: 1
HTTP 200
[Asserts]
body contains "cart_block_no_products"

# ---- Remove products

# Prepare a cart with two products
POST {{base_url}}/_service_page_ajax.php
[FormParams]
product_id: {{cart_product_id_1}}
product_quantity: 1
product_thumb: test.png
HTTP 200
[Asserts]
jsonpath "$.items" == 1

POST {{base_url}}/_service_page_ajax.php
[FormParams]
product_id: {{cart_product_id_2}}
product_quantity: 1
product_thumb: test2.png
HTTP 200
[Asserts]
jsonpath "$.items" == 2

# Remove the first product: 1 item left, and it is the second one
GET {{base_url}}/_service_page_ajax.php?remove_code={{cart_product_id_1}}
HTTP 200
[Asserts]
jsonpath "$.items" == 1

POST {{base_url}}/_service_page_ajax.php
[FormParams]
load_cart: 1
HTTP 200
[Asserts]
body not contains "data-code=\"{{cart_product_id_1}}\""
body contains "data-code=\"{{cart_product_id_2}}\""

# Removing a product that is not in the cart changes nothing
GET {{base_url}}/_service_page_ajax.php?remove_code=99999999
HTTP 200
[Asserts]
jsonpath "$.items" == 1

# Remove the last product: the cart is empty
GET {{base_url}}/_service_page_ajax.php?remove_code={{cart_product_id_2}}
HTTP 200
[Asserts]
jsonpath "$.items" == 0

POST {{base_url}}/_service_page_ajax.php
[FormParams]
load_cart: 1
HTTP 200
[Asserts]
body contains "cart_block_no_products"

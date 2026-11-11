# Add products to the cart. Session only, nothing is created on the server.

# Add a first product (quantity 2): JSON answer with the number of items
POST {{base_url}}/_service_page_ajax.php
[FormParams]
product_id: {{cart_product_id_1}}
product_quantity: 2
product_thumb: test.png
HTTP 200
[Asserts]
header "Content-Type" contains "application/json"
jsonpath "$.items" == 1
jsonpath "$.popup_product_html" contains "layer_cart_product_quantity"

# The cart block lists the product with its quantity
POST {{base_url}}/_service_page_ajax.php
[FormParams]
load_cart: 1
HTTP 200
[Asserts]
body contains "data-code=\"{{cart_product_id_1}}\""
body contains "quantity\">2<"

# The popup summarizes the cart
POST {{base_url}}/_service_page_ajax.php
[FormParams]
load_popup_products_html: 1
HTTP 200
[Asserts]
body contains "ajax_block_products_total"
body contains "href=\"order.php\""

# Add a second product: 2 items
POST {{base_url}}/_service_page_ajax.php
[FormParams]
product_id: {{cart_product_id_2}}
product_quantity: 1
product_thumb: test2.png
HTTP 200
[Asserts]
jsonpath "$.items" == 2

# Add the first product again with another quantity: it replaces the line, still 2 items
POST {{base_url}}/_service_page_ajax.php
[FormParams]
product_id: {{cart_product_id_1}}
product_quantity: 5
product_thumb: test.png
HTTP 200
[Asserts]
jsonpath "$.items" == 2

POST {{base_url}}/_service_page_ajax.php
[FormParams]
load_cart: 1
HTTP 200
[Asserts]
body contains "quantity\">5<"
body not contains "quantity\">2<"

# Private pages (customer and vendor) redirect a visitor without session to the login page.

GET {{base_url}}/account-information.php
HTTP 302
[Asserts]
header "Location" contains "login-register.php"

GET {{base_url}}/myaccount.php
HTTP 302
[Asserts]
header "Location" contains "login-register.php"

GET {{base_url}}/order-history.php
HTTP 302
[Asserts]
header "Location" contains "login-register.php"

GET {{base_url}}/order-details.php
HTTP 302
[Asserts]
header "Location" contains "login-register.php"

# The payment page keeps a "back" parameter to come back after the login
GET {{base_url}}/payment.php
HTTP 302
[Asserts]
header "Location" contains "login-register.php"
header "Location" contains "back="

GET {{base_url}}/edit-module-product.php
HTTP 302
[Asserts]
header "Location" contains "login-register.php"

GET {{base_url}}/product-statistics.php
HTTP 302
[Asserts]
header "Location" contains "login-register.php"

GET {{base_url}}/manage-products.php
HTTP 302
[Asserts]
header "Location" contains "login-register.php"

# Edit form of an existing module
GET {{base_url}}/edit-module-product.php?id={{paid_product_id}}
HTTP 302
[Asserts]
header "Location" contains "login-register.php"

# A visitor cannot submit the vendor form either (nothing is created)
POST {{base_url}}/edit-module-product.php
[MultipartFormData]
action: new-module
name_en: Hurl anonymous module
HTTP 302
[Asserts]
header "Location" contains "login-register.php"

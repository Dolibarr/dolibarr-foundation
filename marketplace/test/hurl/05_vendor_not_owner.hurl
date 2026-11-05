# A vendor cannot open the edit form of a module that belongs to someone else.
# The paid test module (paid_product_id) is not owned by the customer test account.

GET {{base_url}}/login-register.php
HTTP 200

POST {{base_url}}/login-register.php
[FormParams]
action: login
back:
email: {{customer_email}}
passwd: {{customer_password}}
HTTP 302

# Module of another vendor: redirected to the list of the vendor's own modules
GET {{base_url}}/edit-module-product.php?id={{paid_product_id}}
HTTP 302
[Asserts]
header "Location" contains "manage-products.php"

# Same with a submit: the other vendor's module is not modified
POST {{base_url}}/edit-module-product.php?id={{paid_product_id}}
[MultipartFormData]
action: new-module
name_en: Hurl takeover attempt
HTTP 302
[Asserts]
header "Location" contains "manage-products.php"

# The list of the vendor's modules is displayed (empty for the test account)
GET {{base_url}}/manage-products.php
HTTP 200
[Asserts]
body contains "List of products/services"
body not contains "Hurl takeover attempt"

# Unknown module id: no form is displayed (see A_VERIFIER.md #16 about the 200 status)
GET {{base_url}}/edit-module-product.php?id=99999999
HTTP 200
[Asserts]
body not contains "product-creation-form"
body contains "Product not found"

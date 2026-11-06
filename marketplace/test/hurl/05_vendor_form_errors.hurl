# A logged in vendor opens the "new module" form and sends it incomplete.
# The site must refuse it with the list of errors. Nothing is created (the checks stop before addProduct).
# Uses the customer test account: any account of the site can open the vendor pages.

GET {{base_url}}/login-register.php
HTTP 200

POST {{base_url}}/login-register.php
[FormParams]
action: login
back:
email: {{customer_email}}
passwd: {{customer_password}}
HTTP 302

# ---- The empty form is displayed with its fields
GET {{base_url}}/edit-module-product.php
HTTP 200
[Asserts]
body contains "product-creation-form"
body contains "name_en"
body contains "short_description_en"
body contains "virtual_product_file"
body contains "image_cover"
body contains "sale_price"
body contains "version_min"

# ---- Everything missing: English fields, category, zip file, cover image
POST {{base_url}}/edit-module-product.php
[MultipartFormData]
action: new-module
HTTP 200
[Asserts]
body contains "alert-danger"
body contains "There are 4 errors"
body contains "All English fields are required."
body contains "You must choose a category."
body contains "<b>Cover image</b> is required."

# ---- English texts given but the short description is too long (max 400 characters)
POST {{base_url}}/edit-module-product.php
[MultipartFormData]
action: new-module
name_en: Hurl test module
short_description_en: aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa
description_en: Description of the Hurl test module
HTTP 200
[Asserts]
body contains "alert-danger"
body contains "400"

# ---- Zip with a wrong file name and a cover that is not an image: both refused, nothing is created
POST {{base_url}}/edit-module-product.php
[MultipartFormData]
action: new-module
name_en: Hurl test module
short_description_en: Refused module
description_en: Refused module
categories[]: 25
version: 1.0.0
version_min: 54
version_max: 59
sale_price: 0
virtual_product_file: file,fixtures/bad_name.zip; application/zip
image_cover: file,fixtures/bad_name.zip; application/zip
HTTP 200
[Asserts]
body contains "alert-danger"
body contains "Package file name must match"

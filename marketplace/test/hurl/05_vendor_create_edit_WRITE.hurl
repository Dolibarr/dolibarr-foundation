# A vendor submits a new module (zip + cover image), sees it "To approve" in his list,
# then edits it. Creates one module (status "To approve") on the test instance at every run.
# The zip must be named module_xxx-x.y.z.zip. Fixtures are in fixtures/ (fake module, not installable).
# Account: customer test account (any account of the site can submit modules).
# categories[] 25, version_min 54 and version_max 59 are ids of this site (category and Dolibarr versions).

GET {{base_url}}/login-register.php
HTTP 200

POST {{base_url}}/login-register.php
[FormParams]
action: login
back:
email: {{customer_email}}
passwd: {{customer_password}}
HTTP 302

# ---- Creation: redirect to the list of the vendor's modules
POST {{base_url}}/edit-module-product.php
[MultipartFormData]
action: new-module
name_en: Hurl test module
short_description_en: Fake module created by the Hurl tests
description_en: <p>Fake module created by the Hurl tests, to delete.</p>
keywords: hurl,test
categories[]: 25
version: 1.0.0
version_min: 54
version_max: 59
phpmin: 7.4
phpmax: 8.3
validityduration: 730
status: 2
sale_price: 0
contact_support: hurl@example.com
dolibarr_terms: 1
module_wiki: 1
virtual_product_file: file,fixtures/module_hurltest-1.0.0.zip; application/zip
image_cover: file,fixtures/cover.png; image/png
image_1: file,fixtures/image_1.png; image/png
HTTP 302
[Asserts]
header "Location" contains "manage-products.php"

# ---- The module is in the list, waiting for the validation by the team
GET {{base_url}}/manage-products.php
HTTP 200
[Captures]
moduleid: regex /edit-module-product[.]php[?]id=([0-9]+)/
[Asserts]
body contains "Hurl test module 1.0.0"
body contains "To approve"
body not contains "Error, No product to display"

# ---- Edit form is prefilled with the saved values
GET {{base_url}}/edit-module-product.php?id={{moduleid}}
HTTP 200
[Asserts]
body contains "product-creation-form"
body contains "Hurl test module"
body contains "hurl@example.com"

# ---- Edit: new name, no new file (files are only required at creation)
POST {{base_url}}/edit-module-product.php?id={{moduleid}}
[MultipartFormData]
action: new-module
name_en: Hurl test module edited
short_description_en: Fake module edited by the Hurl tests
description_en: <p>Edited by the Hurl tests, to delete.</p>
keywords: hurl,test
categories[]: 25
version: 1.0.1
version_min: 54
version_max: 59
phpmin: 7.4
phpmax: 8.3
validityduration: 730
status: 2
sale_price: 0
contact_support: hurl@example.com
dolibarr_terms: 1
module_wiki: 1
HTTP 302
[Asserts]
header "Location" contains "manage-products.php"

GET {{base_url}}/manage-products.php
HTTP 200
[Asserts]
body contains "Hurl test module edited"
body contains "1.0.1"

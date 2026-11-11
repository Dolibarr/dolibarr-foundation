# Public pages answer 200 for a visitor without session, unknown pages answer 404.

GET {{base_url}}/
HTTP 200
[Asserts]
xpath "string(//title)" contains "Dolistore"

GET {{base_url}}/index.php
HTTP 200
[Asserts]
xpath "string(//title)" contains "Dolistore"

GET {{base_url}}/contact-us.php
HTTP 200
[Asserts]
xpath "string(//title)" == "Contact"

GET {{base_url}}/about-us.php
HTTP 200
[Asserts]
xpath "string(//title)" contains "About us"

GET {{base_url}}/about-payments.php
HTTP 200
[Asserts]
xpath "string(//title)" == "About payments"

GET {{base_url}}/forgot-password.php
HTTP 200
[Asserts]
xpath "string(//title)" == "Forgot Password"
body contains "name=\"email\""

GET {{base_url}}/login-register.php
HTTP 200
[Asserts]
xpath "string(//title)" == "Login/Register"
body contains "id=\"login_form\""

GET {{base_url}}/product.php
HTTP 200
[Asserts]
xpath "string(//title)" contains "PRODUCT"

# The order page is reachable without a session
GET {{base_url}}/order.php
HTTP 200

# ---- Unknown pages answer 404
GET {{base_url}}/this-page-does-not-exist.php
HTTP 404

GET {{base_url}}/this-page-does-not-exist
HTTP 404

GET {{base_url}}/register.php
HTTP 404

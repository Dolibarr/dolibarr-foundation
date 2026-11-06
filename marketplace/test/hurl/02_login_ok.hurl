# Login of the customer test account on the template website (login-register.php, no CSRF token).

# Login page
GET {{base_url}}/login-register.php
HTTP 200
[Asserts]
body contains "name=\"passwd\""
body contains "name=\"action\" value=\"login\""

# Success redirects to the account page (or to the payment page if back is set)
POST {{base_url}}/login-register.php
[FormParams]
action: login
back:
email: {{customer_email}}
passwd: {{customer_password}}
HTTP 302
[Asserts]
header "Location" matches "myaccount|payment"

# Private pages are now displayed (no login form)
GET {{base_url}}/account-information.php
HTTP 200
[Asserts]
body not contains "id=\"login_form\""

GET {{base_url}}/order-history.php
HTTP 200
[Asserts]
xpath "string(//title)" contains "Order History"
body not contains "id=\"login_form\""

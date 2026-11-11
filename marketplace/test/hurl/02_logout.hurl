# Logout destroys the customer session.

GET {{base_url}}/login-register.php
HTTP 200

POST {{base_url}}/login-register.php
[FormParams]
action: login
back:
email: {{customer_email}}
passwd: {{customer_password}}
HTTP 302
[Asserts]
header "Location" matches "myaccount|payment"

# Logged in: the private page is displayed
GET {{base_url}}/account-information.php
HTTP 200
[Asserts]
body not contains "id=\"login_form\""

# Logout link of the template (header)
GET {{base_url}}/myaccount.php?action=logout
HTTP 302
[Asserts]
header "Location" contains "index.php"

# Logged out: the private page redirects to the login again
GET {{base_url}}/account-information.php
HTTP 302
[Asserts]
header "Location" contains "login-register.php"

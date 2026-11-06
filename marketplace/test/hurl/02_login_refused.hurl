# Invalid logins are refused (the login page is displayed again with an error).

# Existing account, wrong password
POST {{base_url}}/login-register.php
[FormParams]
action: login
back:
email: {{customer_email}}
passwd: this-is-a-wrong-password
HTTP 200
[Asserts]
body contains "alert alert-danger"
body contains "id=\"login_form\""

# Unknown account
POST {{base_url}}/login-register.php
[FormParams]
action: login
back:
email: nobody-unknown@example.com
passwd: whatever
HTTP 200
[Asserts]
body contains "alert alert-danger"

# Empty fields
POST {{base_url}}/login-register.php
[FormParams]
action: login
back:
email:
passwd:
HTTP 200
[Asserts]
body contains "alert alert-danger"

# After the failed attempts the visitor is still not logged in
GET {{base_url}}/account-information.php
HTTP 302
[Asserts]
header "Location" contains "login-register.php"

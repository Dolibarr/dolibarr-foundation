# Registration, error cases only.
# The success case is NOT tested: it creates a thirdparty and sends a welcome email
# (and may require a reCAPTCHA). Both cases below are refused before any creation.

# Invalid email
POST {{base_url}}/login-register.php
[FormParams]
action: register
back:
firstname: Test
lastname: Test
email: not-an-email
passwd: Abcdef123456!
address: 1 test street
city: Paris
postalcode: 75001
country_id: 1
HTTP 200
[Asserts]
body contains "alert alert-danger"
body contains "Email address is not valid"

# Email already registered
POST {{base_url}}/login-register.php
[FormParams]
action: register
back:
firstname: Test
lastname: Test
email: {{customer_email}}
passwd: Abcdef123456!
address: 1 test street
city: Paris
postalcode: 75001
country_id: 1
HTTP 200
[Asserts]
body contains "alert alert-danger"
body contains "This email is already registered."

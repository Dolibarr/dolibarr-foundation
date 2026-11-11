# Forgot password, error cases only.
# The success case (valid account) is NOT tested: it changes the temporary password
# of the account and sends a real email.

# Invalid email format
POST {{base_url}}/forgot-password.php
[FormParams]
action: resetpassword
email: not-an-email
HTTP 200
[Asserts]
body contains "alert alert-danger"

# Unknown email
POST {{base_url}}/forgot-password.php
[FormParams]
action: resetpassword
email: nobody-unknown@example.com
HTTP 200
[Asserts]
body contains "alert alert-danger"
body contains "This email does not exist"

# Invalid reset token
GET {{base_url}}/forgot-password.php?token=invalidtoken
HTTP 200
[Asserts]
body contains "Wrong or expired token"

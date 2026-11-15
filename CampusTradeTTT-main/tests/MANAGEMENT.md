# Profile and account management tests

`test_management.py` tests users managing their own accounts. It covers viewing
and editing personal details, trimming whitespace, Student/Alumni status,
payment preferences, leaving without saving, session restrictions, password
changes, reset requests, reset validation, token reuse/expiry, and account
isolation. It does not contain admin dashboard or book management scenarios.

`CT-MGMT-001` through `CT-MGMT-015` are local scenario identifiers; replace them
with test plan IDs if needed. Pytest parameterization runs each dataset as a
separate test with its own setup and cleanup. The complete suite has 51 cases
for one browser.

## Setup

Use a dedicated test database initialized with `SQL/campustrade.sql`. Configure
the running PHP application to use that same database. The tests create a
Student and an Alumni account per case, with unique emails and profile rows.
They clean up those exact rows on success or failure and never use existing
account credentials. An interrupted process may leave emails starting with
`mgmt-`.

From the project directory in PowerShell:

```powershell
# Only needed when setting up a new Python environment:
python -m pip install -r tests/requirements.txt
python -m playwright install chromium

# The existing project virtual environment can also be used.
$env:CAMPUS_TRADE_TEST_URL = 'http://localhost:8080'
$env:TEST_DB_NAME = 'campustrade_testing'
$env:TEST_DB_HOST = '127.0.0.1'
$env:TEST_DB_PORT = '3306'
$env:TEST_DB_USER = 'root'
$env:TEST_DB_PASSWORD = ''
.\.venv\Scripts\python.exe -m pytest tests/test_management.py -v -p no:cacheprovider
```

The URL must point to the app directory, including any XAMPP path prefix.
`TEST_DB_*` configures Python's connection and does not reconfigure PHP.
The tests intentionally do not load the application's normal `.env` or select
random users from the existing login helper. Missing `TEST_DB_NAME` fails with
setup guidance. PHP's Composer dependencies must also be installed for the
seller controller to load.

Collect scenarios without starting the server or connecting to MySQL:

```powershell
.\.venv\Scripts\python.exe -m pytest tests/test_management.py --collect-only -q -p no:cacheprovider
```

## Current application gaps

These are assertions of the intended user behavior, and known bugs remain
visible as failures rather than skips or expected failures:

- `Seller_Controller.php` does not save `preferred_pay` and does not populate
  `$vPay` when opening the edit form. Non-default payment persistence fails.
- The form offers `Other`, but the database payment enum does not support it.
  `Cash App` is the UI value and `CashApp` is its database representation.
- `ResetPassword.php` does not check `reset_expires_at`. The expired-token
  scenario requires rejection and no password change.

Reset requests exercise the app's current demo flow, which displays a reset
link and does not send email. The link's token is verified without following
its hardcoded host. Other reset scenarios seed tokens directly, avoiding an
email service dependency. Validation cases POST directly to verify server
checks even when the browser would block empty fields.

Successful profile image uploads are outside this suite: they require a
dedicated test storage bucket and object cleanup. The tests make no uploads.
Blank-field rejection for personal profile fields is also not assumed, since
the current edit form does not mark those fields required.

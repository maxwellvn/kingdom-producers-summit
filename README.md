# Loveworld Kingdom Producers Summit

PHP and MySQL site for summit registration, QR access passes, attendance scanning, and administration.

## Coolify deployment

Create a new Coolify resource from this repository and select **Docker Compose**. Point the public domain to the `app` service on port `80`, then configure these environment variables in Coolify:

- `APP_URL` — the complete public HTTPS URL
- `APP_KEY` — generate with `php -r "echo bin2hex(random_bytes(32));"`
- `DB_NAME` — optional; defaults to `lw_producers_summit`
- `DB_USER` — optional; defaults to `producers`
- `DB_PASS` — a strong database password
- `DB_ROOT_PASS` — a different strong root password
- `ADMIN_EMAIL` — administrator login email
- `ADMIN_PASSWORD_HASH` — generate with `php -r "echo password_hash('your-password', PASSWORD_DEFAULT);"`
- `MAIL_FROM` — optional; defaults to `lkps@loveworldconsulate.org`
- `MAIL_FROM_NAME` — optional sender name
- `MAIL_HOST`, `MAIL_PORT`, `MAIL_ENCRYPTION` — SMTP connection settings
- `MAIL_USERNAME`, `MAIL_PASSWORD` — authenticated mailbox credentials
- `PAYPAL_ENABLED` — `true` to enable onsite payments
- `PAYPAL_CLIENT_ID`, `PAYPAL_SECRET` — PayPal REST app credentials, set privately in Coolify (required for checkout)
- `PAYPAL_MODE` — `sandbox` while testing, `live` for real payments
- `PAYPAL_WEBHOOK_ID` — id of the webhook you create in the PayPal dashboard pointing to `/paypal/webhook`; it listens for `PAYMENT.CAPTURE.COMPLETED`

Redeploy after changing payment settings so the app container receives them. For local testing use sandbox credentials from the PayPal developer dashboard (sandbox buyer accounts let you pay without real money); never commit keys. Without credentials, registrations are saved but checkout cannot start.

The application container waits for MySQL and runs outstanding migrations whenever it starts. Database data is retained in the `producers_db` volume.

Camera scanning requires HTTPS in production. Allow camera permission when prompted, and make sure no proxy overrides the application’s `Permissions-Policy: camera=(self)` header.

## Local XAMPP setup

Copy `.env.example` to `.env`, replace the placeholder values, and run:

```sh
php database/migrate.php
```

Serve the project through Apache with `mod_rewrite` and `mod_headers` enabled.

# eSampark · OPTCL Operations Portal

For server upload, migration, and midnight SAP cron steps, see [DEPLOYMENT_FTP.md](DEPLOYMENT_FTP.md).

Laravel administration portal for employee records, birthdays, estimated retirements, holidays, quotes, cyber tips, and notifications.

## Features

- Responsive blue/cyan OPTCL interface with collapsible sidebar, profile/signout footer, and shared application header/footer.
- Dashboard metrics, upcoming birthdays, and retirements estimated at age 60 on the last day of the birth month (birthdays on the 1st retire at the previous month end).
- Notification bell with unread counts refreshed every 30 seconds.
- Pagination for employees, holidays, quotes, cyber tips, and notifications (25 records per page).
- Read-only employee profiles with personal, contact, employment, and reporting information.
- Profile photo upload/replacement, preview, and initials fallback. JPG/PNG/WebP, up to 2 MB and 4096 × 4096 pixels.

## Requirements

- PHP compatible with the committed Composer lock file. One locked dependency requires PHP 8.4.1 or newer; PHP 8.0 is incompatible. Selected application paths were also verified locally on PHP 8.3.
- Composer, MySQL/MariaDB, and PHP extensions including PDO MySQL, GD, mbstring, XML, and fileinfo.
- An existing OPTCL database containing the domain tables. The bundled migrations are Laravel defaults and do **not** create the full domain schema.

## Local setup

```sh
composer install
cp .env.example .env
php artisan key:generate
```

Configure the database credentials in `.env`, then set `ADMIN_EMAIL` and `ADMIN_PASSWORD_HASH`. Generate a bcrypt hash using Laravel's `Hash::make()` (for example in `php artisan tinker`) and paste it into `.env`. Never commit passwords, hashes, or `.env`.

```sh
php artisan serve --host=127.0.0.1 --port=8000
```

Open http://127.0.0.1:8000/login and sign in with your configured credentials. The active views use local CSS/JavaScript directly; a Vite server is not required. Vite scripts remain available for future frontend assets.

## Data and storage

Birthdays lists today’s birthdays from `emp_details` in India time. Retirements lists all upcoming retirements starting today, ordered by retirement date, using the same list as the dashboard preview. Both pages are paginated. Retirement dates use the last day of the month in which an employee turns 60; birthdays on the 1st use the last day of the previous month. Dashboard milestone counts use the same filters.

The application reads `emp_details`, `bday_wishes`, `retirement_wishes`, `optcl_holidays`, `optcl_quotes`, `cyber_alerts`, and `notifications`. Database exports and employee data are excluded from source control.

Uploaded photos are stored privately under `storage/app/employee-photos` and served through authenticated employee photo routes. Keep storage writable and back up photos separately. No public storage symlink is required for employee photos.

Holidays, Quotes, and Cyber Tips each provide **Export CSV template** and **Upload CSV**. Fill in the downloaded header-only template and save as UTF-8 CSV (up to 2 MB and 1,000 data rows). Dates use `YYYY-MM-DD`; each page explains its required fields and allowed values. Holiday day of week and year are derived from its date. IDs are generated automatically. Uploads append new records, so uploading the same data again creates duplicates. All rows are validated before insertion and inserts run in a transaction; an invalid file or a database error leaves no partial import. The three target tables must support transactions (the current OPTCL tables use InnoDB).

The shared administrator login is intended for the current local portal; role-based user accounts and login rate limiting are not implemented. Configure production hosting separately with HTTPS, `APP_DEBUG=false`, and the web root pointed at `public/`.

## SAP employee sync

Click the profile avatar at the bottom of the sidebar to open **Admin profile**. Set the SAP user ID and password under **SAP employee sync**. Credentials are encrypted in `sap_employee_sync_settings` using `APP_KEY`; keep that key in your deployment secrets and backups. The password is never displayed. Leave the password blank when saving to keep it; changing the user ID requires entering a password. The **Sync** button next to the Employees section heading runs the same update immediately and displays the result, preserving your list filters.

Apply the integration migration against the existing OPTCL database:

```sh
php artisan migrate --path=database/migrations/2026_11_06_000000_create_sap_employee_sync_settings_table.php --force
```

The sync calls the fixed HTTPS SAP OData endpoint configured in `config/sap.php`. It follows same-endpoint pagination, validates the complete response, and upserts `emp_erp_data` by employee number in a transaction. `emp_details` is a view and reflects these changes automatically. Personal contact tables, profile photos, local grade/reporting IDs, and employees absent from the SAP response are preserved. SAP supplies `emp_cat` as employee type, `emp_grpt` as department, `emp_pop` as place of posting, and `emp_ddo_stext` as GRE/office. Missing SAP dates are stored as null; the migration allows null joining/posting dates on the source table. An empty, incomplete, invalid, or failed response leaves employee data unchanged. Sync status appears in the admin profile.

```sh
php artisan sap:sync-employees
php artisan schedule:list
```

Laravel schedules the sync at **00:00 Asia/Kolkata**. On each host that runs this app, install one cron entry using that host's absolute PHP and application paths:

```cron
* * * * * cd /absolute/path/to/app && /absolute/path/to/php artisan schedule:run >> /absolute/path/to/app/storage/logs/scheduler.log 2>&1
```

The scheduler and command prevent overlapping runs. The host must be awake, connected to SAP, and running MySQL at midnight. Deployment to another host requires installing its scheduler separately. Do not run `key:generate` on an existing installation without re-entering the SAP credentials afterward.

## Announcements

The **Announcement** sidebar item below Notifications and all management routes are restricted to the admin session. Create or edit announcements with a title, description, schedule date, expiry date and Active/Inactive status. **Save as draft** keeps an item private; **Publish** makes an active item eligible for the dashboard. Dates use Asia/Kolkata: visibility starts at midnight on the schedule date and lasts through the expiry date. Scheduled visibility is evaluated when the dashboard loads, without a separate cron job. Saving a published item as a draft or setting it inactive removes it from the dashboard.

Published announcements appear below the dashboard welcome banner for every dashboard viewer. Multiple items have Next/Previous controls and an item counter. The existing portal currently provides only an administrator sign-in; announcement management does not add a new employee sign-in system.

The employee app can read the same published items through **GET `/api/announcements`** and **GET `/api/announcements/{id}`**. Send `Accept: application/json` and `Authorization: ****** Sanctum token>`. API authentication uses the configured Laravel `User` model and Sanctum tokens, independently of the portal admin session. Provision employee accounts/tokens through your trusted authentication integration; this feature does not add a public token-issuing endpoint. The user and personal-access-token tables must exist on the deployment. The employee app must call this API using a token recognized by this backend; tokens issued by another system need an authentication integration first.

List responses contain `data`, `links` and `meta`. Each item includes `id`, `title`, plain-text `description`, `schedule_date`, `expiry_date` and UTC `published_at`. `meta.timezone` is `Asia/Kolkata`. The list accepts `page` and `per_page` (default 25, maximum 100), ordered newest publication first. Follow `links.next` to retrieve more items. Recheck on app launch/foreground; responses are not cacheable. The app should render descriptions as plain text and avoid HTML injection from untrusted announcement content.

## Project status

This repository is a Laravel application tailored for the OPTCL environment, with the production deployment flows documented in [DEPLOYMENT_FTP.md](DEPLOYMENT_FTP.md).

The app includes admin authentication, employee directory browsing, SAP sync, holiday/quote/cyber-tip CSV import flows, announcements, dashboard metrics, and notification features.

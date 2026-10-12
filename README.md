# DIORWA RealEstate (completed version)

PHP 8.1+ (with mbstring and PDO MySQL) and MySQL/MariaDB. Works on XAMPP out of the box.

## Setup (XAMPP)
1. Copy the `DIORWA` folder into `C:\xampp\htdocs\`.
2. Start Apache and MySQL in the XAMPP control panel.
3. Open phpMyAdmin, go to Import, choose `database/schema.sql`, click Import. It creates the `diorwa_realestate` database, all tables, an admin account and 28 sample listings.
4. Open http://localhost/DIORWA/index.php
5. Edit `php/config.php`: set your real WhatsApp number, phone, email and the USD to RWF rate.

**Admin login:** `admin@diorwa.rw` / `Admin@12345`. Change this password straight away: log in, open Dashboard, Account settings, Change password.

## What was added
| Area | What it does |
|---|---|
| Pages | Every `.html` page is now `.php`, sharing one header and footer. Contact and login were bare forms and are now full pages. |
| Properties, Buy, Rent | Live from the database with search, city, type, price, bedrooms, sort and pagination. |
| Property detail | Photo, specs, features, map, similar listings, inquiry form, viewing booking, WhatsApp button, share. |
| Accounts | Register (validated, duplicate-safe), login (hashed passwords, lockout after 5 tries), dashboard, profile, change password. |
| Listing tools | Owners and agents add and edit listings with a safe image upload. Listings wait for admin approval. |
| Admin panel | Approve/reject listings, verify titles, feature listings, read messages and inquiries, confirm viewings, change roles. |
| Security | Prepared statements everywhere (the old contact form was open to SQL injection), CSRF tokens, session regeneration, upload checks, spam trap, `.htaccess` guards. |

## Innovations
1. **Find My Match** (`match.php`): ranks listings against budget, city, type, bedrooms and priorities, and shows why each one fits. The rules are visible, not a black box.
2. **Price check**: each listing's price per m² is compared with similar listings (same city, otherwise all of Rwanda) and labelled Below market, Fair price or Above market.
3. **Title check**: owners can add the Rwanda parcel UPI. Only an admin can mark a title verified, and only if a UPI exists. A change of UPI clears the badge.
4. **USD / RWF toggle** on every price, remembered in the browser.
5. **Mortgage calculator** on every sale listing.
6. **Compare** up to three properties side by side.
7. **Saved properties** and **online viewing booking** with status tracking.
8. **WhatsApp** button on every page and a pre-filled message on each listing.

## Version 2 additions
- **Languages:** EN / RW / FR switch in the header. Text is translated from `php/i18n_data.php` and `php/i18n_extra.php`. To translate more, add a line with the exact English text. The Kinyarwanda and French are a first draft: have a native speaker review them. The admin panel stays in English.
- **Mobile Money viewing fee:** after booking a viewing, visitors can pay a fee by MTN MoMo or Airtel Money to secure the slot. Admins see paid/unpaid on each viewing and a Payments tab.
  - It starts in **demo mode**: no real money moves and you approve the request yourself.
  - Fee amount, mode and MTN credentials are at the bottom of `php/config.php`. Set `VIEWING_FEE_RWF` to 0 to switch the fee off.
  - **Live MTN payments** need an MTN MoMo developer account (Collection product), then `PAYMENT_MODE = 'mtn'` with your keys. The MTN code follows MTN's published API but has only been tested in demo mode, so test it in MTN's sandbox before going live. Airtel Money is demo-only for now.
- **Bank transfer:** a third way to pay the viewing fee. The visitor sees your bank details and a payment reference (`DIORWA-V<viewing number>`), pays from their bank, then enters the transfer reference from their receipt. An admin checks the money arrived and presses **Confirm received** in Admin > Payments, which marks the viewing paid. Customers cannot mark a bank payment as paid themselves.
  - Put your real bank details at the bottom of `php/config.php` (`BANK_NAME`, `BANK_ACCOUNT_NAME`, `BANK_ACCOUNT_NUMBER`, `BANK_BRANCH`). In live mode the bank option stays hidden until you replace the sample account number, and in demo mode it shows sample details.
  - If your payments table was created before this version, import `database/upgrade_v3.sql` once (select the `diorwa_realestate` database first).
- If you already imported the first `schema.sql`, run `database/upgrade_v2.sql` once. New installs only need `schema.sql`.

## Things to know
- Map coordinates are approximate city centres, so the map shows an area circle, not an exact address.
- The sample listings come from your original pages. None is marked as title verified.
- The RWF rate is a fixed number in `php/config.php` and needs updating by hand.
- Mobile menu, maps and fonts load from CDNs, so the site needs internet for those parts.
- Before going live: use HTTPS, set a MySQL password and put it in `php/config.php`, and change the admin password.

## Ideas for next
Kinyarwanda and French language switch, Mobile Money payment of viewing or reservation fees, email/SMS notifications when a viewing is confirmed, multiple photos per listing, saved searches with alerts.


## Configuration and GitHub
- Defaults live in `php/config.php`. Put real values (database password, bank account, phone, payment mode) in `php/config.local.php`, copied from `php/config.local.example.php`. That file is git-ignored, so secrets never go to GitHub.
- GitHub stores the code only. GitHub Pages cannot run PHP or MySQL, so the live site needs PHP hosting. `.github/workflows/deploy.yml` can upload the site to your host by FTP on every push (add the FTP secrets in the repository settings).
- The sample `schema.sql` creates an admin account with a published default password. Change it immediately on any real install.

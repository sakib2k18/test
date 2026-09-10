# KUET Bus Service — University Bus Portal

A complete **PHP + MySQL** web application for the bus service of
**Khulna University of Engineering & Technology (KUET)**.

It is a public transport portal (fleet, routes, stoppages, timetable, notices)
plus a private admin panel where **one** administrator manages everything
through real database CRUD.

Built with plain **PHP 8, MySQL/MariaDB, HTML5, CSS3 and vanilla JavaScript** —
no Laravel, no React, no Bootstrap, no build step.

---

## 1. Requirements

| Software | Version used | Notes |
|---|---|---|
| XAMPP | 8.2.x | Apache + MySQL/MariaDB |
| PHP | 8.2 (works on 7.4+) | needs `pdo_mysql`, `mbstring`, `fileinfo` |
| MySQL / MariaDB | 10.4 | **must listen on port 4306** |
| Browser | Chrome / Edge / Firefox | any modern browser |

> The three PHP extensions are enabled by default in XAMPP. `GD` is **not**
> required.

---

## 2. Installation (step by step)

### Step 1 — Copy the project

Put the project folder inside your XAMPP web root:

```text
C:\xampp\htdocs\test\
```

The folder name is up to you — the site detects its own URL automatically.
If you rename it (e.g. to `university-bus-service`), see the note in
section 9 about the `.htaccess` error pages.

### Step 2 — Start XAMPP

Open the XAMPP Control Panel and **Start**:

* **Apache**
* **MySQL**

### Step 3 — Make sure MySQL uses port 4306

This project is configured for **MySQL on port 4306**, not the default 3306.

In the XAMPP Control Panel: `MySQL` → `Config` → `my.ini`, and confirm:

```ini
[mysqld]
port=4306
```

(If your MySQL runs on a different port, change `DB_PORT` in
`config/config.php` — that is the only place the port is written.)

### Step 4 — Import the database

**With phpMyAdmin**

1. Open <http://localhost/phpmyadmin>
2. Click the **Import** tab
3. Choose the file `database/university_bus_service.sql`
4. Click **Go**

The script creates the database, all tables, all relationships, the indexes
and the sample data. You do **not** need to create the database first.

**Or from the command line**

```bash
C:\xampp\mysql\bin\mysql.exe -u root -h 127.0.0.1 -P 4306 < database/university_bus_service.sql
```

### Step 5 — Open the site

```text
http://localhost/test/
```

The homepage should already be full of buses, routes and notices.

---

## 3. Database configuration

Everything lives in **one file**: `config/config.php`

```php
define('DB_HOST', '127.0.0.1');
define('DB_PORT', '4306');                     // NOT 3306
define('DB_NAME', 'university_bus_service');
define('DB_USER', 'root');
define('DB_PASS', '');                         // XAMPP default: empty
```

The same file also holds the university name, site title, transport office
contact details and the upload folder, so the project can be re-branded from
one place.

---

## 4. Login details

### Administrator (there is only one)

| | |
|---|---|
| Email | `admin@kuet.ac.bd` |
| Password | `admin@123` |
| Panel | <http://localhost/test/admin/> |

### Sample student account

| | |
|---|---|
| Email | `rakib@stud.kuet.ac.bd` |
| Password | `user@123` |

> Passwords are stored only as **bcrypt hashes** (`password_hash`). The plain
> passwords above exist nowhere in the PHP source — they are only listed here
> and on the login page as demo hints.
>
> The admin can change their password from **Admin → My Account**.
> There is deliberately **no admin registration page**, and the sign-up form
> can only ever create a `user` account.

---

## 5. Where to put bus photos

```text
assets/uploads/buses/
```

Two ways to add a photo:

1. **Recommended —** log in as admin, open **Buses → Edit** (or **Add new
   bus**) and use the *Bus photo* field. The file is validated, renamed to a
   safe unique name and stored automatically.
2. **Manually —** copy an image into `assets/uploads/buses/` and then write
   its exact filename into the `image` column of the `buses` table.
   The seed data already does this for buses 01–08 (`bus-01.jpg` … `bus-08.jpg`
   ship with the project), so after importing the SQL those buses show real
   photos immediately.

Rules enforced by the server:

* allowed types: **JPG, JPEG, PNG, WEBP**
* maximum size: **2 MB**
* the real content of the file is inspected (`getimagesize` + MIME check), so
  a script renamed to `.png` is rejected
* the original filename is never reused — a name like
  `bus_20260902_001209_c10fe721.png` is generated instead

If a bus has no photo, an elegant SVG placeholder is shown instead — the site
never looks broken:

```text
assets/images/placeholders/bus-placeholder.svg
```

---

## 6. Pages

### Public

| URL | Page |
|---|---|
| `/index.php` | Homepage — hero, statistics, featured buses, popular routes, today's trips, latest notices |
| `/about.php` | About the service, mission, vision, safety, facilities |
| `/buses.php` | Fleet listing with search + type/status filters + pagination |
| `/bus-details.php?id=1` | One bus: photo, specs, facilities, driver, routes, timetable |
| `/routes.php` | Route listing with search + status filter |
| `/route-details.php?id=1` | One route: stoppage timeline, buses, timetable |
| `/schedules.php` | Full timetable, filter by route / bus / day / time of day |
| `/announcements.php` | Notice board with search + priority filter |
| `/announcement-details.php?id=1` | One notice |
| `/contact.php` | Office details + working contact form |
| `/login.php` | Log in |
| `/register.php` | Sign up |
| `/logout.php` | Log out |
| `/404.php`, `/403.php` | Friendly error pages |

### Admin (`/admin/`, administrator only)

| URL | Page |
|---|---|
| `/admin/index.php` | Dashboard: live counters, trips-per-weekday chart, activity log |
| `/admin/buses/` | Buses — list, create, view, edit, delete |
| `/admin/routes/` | Routes — list, create, view, edit, delete |
| `/admin/routes/view.php?id=1` | Manage the stoppages of a route (add / edit / reorder / delete) |
| `/admin/schedules/` | Schedules — list, create, edit, delete |
| `/admin/announcements/` | Announcements — list, create, edit, publish/unpublish, delete |
| `/admin/users/index.php` | Registered accounts (admin account is protected) |
| `/admin/messages/index.php` | Contact-form messages — read / unread / delete |
| `/admin/profile.php` | Change admin details and password |

---

## 7. Project structure

```text
test/
├── index.php                  Homepage
├── about.php  buses.php  bus-details.php
├── routes.php  route-details.php  schedules.php
├── announcements.php  announcement-details.php  contact.php
├── login.php  register.php  logout.php
├── 404.php  403.php           Error pages
├── .htaccess                  Error documents, no directory listing
│
├── config/
│   ├── config.php             ALL settings: university name, DB, port 4306, uploads
│   └── database.php           PDO connection + friendly connection-error page
│
├── includes/
│   ├── init.php               Bootstrap required by every page
│   ├── functions.php          Helpers: escaping, CSRF, validation, upload, icons
│   ├── auth.php               Sessions, requireLogin(), requireAdmin()
│   ├── header.php / footer.php            Public layout
│   └── admin_header.php / admin_footer.php  Admin layout (sidebar)
│
├── admin/
│   ├── index.php              Dashboard
│   ├── profile.php            Admin account
│   ├── buses/       index, create, edit, view, delete (+ _form, _validate)
│   ├── routes/      index, create, edit, view, delete (+ _form, _validate)
│   ├── stops/       create, edit, delete, move        (route stoppages)
│   ├── schedules/   index, create, edit, delete       (+ _form, _validate)
│   ├── announcements/ index, create, edit, delete, toggle
│   ├── users/       index, delete
│   └── messages/    index
│
├── assets/
│   ├── css/
│   │   ├── style.css          Design system + all components
│   │   ├── responsive.css     Every media query (1920px down to 320px)
│   │   └── admin.css          Admin sidebar, tiles, chart
│   ├── js/
│   │   ├── main.js            Nav drawer, toasts, modal, previews, filters
│   │   ├── validation.js      Client-side form validation rules
│   │   └── admin.js           Sidebar drawer, chart, table filter
│   ├── images/                 Site photos (hero, about, interiors) + placeholders/
│   └── uploads/buses/          >>> PUT BUS PHOTOS HERE <<< (bus-01.jpg … bus-08.jpg)
│
├── database/
│   └── university_bus_service.sql   Schema + relationships + sample data
│
└── README.md
```

---

## 8. Database design

Nine tables, all InnoDB with proper keys:

| Table | Purpose | Key relationships |
|---|---|---|
| `users` | accounts (`user` / `admin`) | unique email |
| `buses` | the fleet | unique registration number |
| `routes` | corridors | unique route code |
| `route_stops` | stoppages of a route | `route_id` → `routes` **ON DELETE CASCADE** |
| `schedules` | one bus + one route + a time | `bus_id` → `buses`, `route_id` → `routes`, both **ON DELETE RESTRICT** |
| `announcements` | notice board | indexed on status + date |
| `facilities` | on-board facilities | unique name |
| `bus_facilities` | which bus has which facility | composite PK, both FKs **CASCADE** |
| `contact_messages` | contact-form submissions | — |
| `activity_logs` | admin action history | `user_id` → `users` **ON DELETE SET NULL** |

**Relationships used**

* one route → many stops
* one route → many schedules
* one bus → many schedules
* one schedule → exactly one bus and one route
* many buses ↔ many facilities

**How deletion protects the data**

* Deleting a **route** removes its stoppages automatically (CASCADE).
* A **bus or route that still has schedules cannot be deleted** — the app
  checks first and shows a clear warning ("This bus is currently assigned to
  2 active schedules…") instead of a raw SQL error. Set it to *Inactive* /
  *Maintenance* instead, or delete the trips first.
* Deleting a **user** keeps their entries in the activity log (SET NULL).
* Deleting a **bus** also deletes its uploaded photo from disk.

---

## 9. Features implemented

**Public site**

* Responsive homepage with hero, live statistics, featured buses, popular
  routes, today's trips, latest notices, feature grid and call-to-action
* Fleet listing with database search (bus number / registration / model),
  type filter, status filter and pagination
* Bus details: large photo, specifications, facilities, driver, assigned
  routes and full timetable
* Route listing and route details with a visual **stoppage timeline**
* Timetable page filterable by route, bus, weekday and time of day
* Notice board with search, priority filter and detail pages
* Contact page with a form that really stores messages in MySQL
* Custom 404 and 403 pages, plus empty states everywhere

**Authentication & authorisation**

* Registration, login, logout using PHP sessions
* `password_hash()` / `password_verify()` — no plain-text passwords
* Duplicate email prevented in PHP **and** by a UNIQUE index
* Exactly one admin; the role is hard-coded to `user` on sign-up, so posting
  `role=admin` to the form changes nothing
* `requireLogin()` and `requireAdmin()` guard every private page **on the
  server** — a logged-in normal user gets a real 403 page, and posting
  directly to a delete endpoint is refused too

**Admin panel**

* Dashboard with counters computed by MySQL and a CSS bar chart of trips per
  weekday
* Full CRUD for buses, routes, route stops, schedules and announcements
* Stoppage reordering (move up / down) inside a database transaction
* One-click publish / unpublish for announcements
* Users list (admin account protected from deletion)
* Contact messages: read / unread / delete
* Change own details and password
* Activity log of every admin action, shown on the dashboard

**Validation**

* **Client-side** (`assets/js/validation.js`): required, email, phone,
  password strength, confirm-password, min/max length, numeric ranges, time
  format, file type and size, checkbox groups — with friendly inline messages
* **Server-side** (PHP, on every single form): the same rules again, plus
  duplicate email / registration number / route code checks, ENUM whitelisting,
  arrival-after-departure checks and safe file-upload validation
* The site is fully usable with JavaScript switched off

**Security**

* PDO with **prepared statements everywhere** — no string-concatenated SQL
* CSRF token on every POST form
* `htmlspecialchars()` on all output (XSS protected)
* Session ID regenerated on login; session destroyed on logout
* Upload folder has its own `.htaccess` that disables script execution
* Raw database errors are logged, never shown to visitors

**Design & UX**

* Hand-written CSS design system with CSS variables (deep navy + amber accent)
* Sticky navbar, off-canvas mobile drawer, admin sidebar drawer
* Cards, badges, chips, timeline, toasts, confirmation modal, loading states,
  empty states, character counters, image preview, password show/hide
* Reveal-on-scroll animation that respects `prefers-reduced-motion`
* Fully responsive: verified with no horizontal overflow at
  **320, 375, 425, 768, 1024, 1366 and 1920 px**
* Tables turn into readable cards on phones (using `data-label` attributes)
* Accessible: semantic HTML, form labels, alt text, ARIA attributes, visible
  focus rings, skip-to-content link, keyboard-friendly dialogs

---

## 10. Notes & troubleshooting

**"Cannot connect to the database"**
MySQL is not running, or it is not on port 4306. Check the XAMPP panel and
`DB_PORT` in `config/config.php`.

**Apache is on a different port**
If Apache uses 8080, open <http://localhost:8080/test/> instead. Nothing in
the code needs to change — the base URL is detected automatically.

**You renamed the project folder**
Everything keeps working except the two `ErrorDocument` lines in the root
`.htaccess`, which contain the folder name. Change `/test/` there to match
your new folder name.

**Changing the university name / branding**
Edit the constants at the top of `config/config.php` — `UNIVERSITY_NAME`,
`UNIVERSITY_SHORT`, `SITE_NAME`, `SITE_TAGLINE` and the office contact
details. They are used across every page.

**Development errors**
Set `APP_DEBUG` to `true` in `config/config.php` to display errors on screen.
Errors are always written to `error_log.txt` in the project root.

---

*University project — PHP, MySQL, HTML, CSS and JavaScript on XAMPP.*

# NagrikX

**Report a city problem with a photo. Pin the exact spot on a map. Track it until it is fixed. Earn points for helping your city.**

NagrikX is a website where citizens report everyday city problems such as potholes, garbage, broken streetlights and water leaks. Admins review each report and update its status, so the citizen can see what is happening. When a problem is marked "Resolved", the citizen can confirm whether it is really fixed.

NagrikX is a student project made for the Tutedude Buildathon (#BuildForMyCity). It is **not** an official government service and is not connected to any government department.

**Live demo:** https://cityfix-srijal.fwh.is/cityfix/

> The live site is on free hosting that has browser protection. It opens normally in a phone or laptop browser, but automated tools (chatbots, scrapers, link checkers) may be blocked. Please open it in a normal browser. Screenshots and a demo video are linked below in case the site is busy.

---

## Table of contents

1. [The problem](#the-problem)
2. [What NagrikX does](#what-nagrikx-does)
3. [Features](#features)
4. [How each part works](#how-each-part-works)
5. [Safety and privacy](#safety-and-privacy)
6. [Built with](#built-with)
7. [Project files](#project-files)
8. [Database setup](#database-setup)
9. [Run it on your computer](#run-it-on-your-computer)
10. [Put it online (free hosting)](#put-it-online-free-hosting)
11. [Known limits](#known-limits)
12. [Planned next](#planned-next-not-built-yet)
13. [Data note](#data-note)
14. [Team](#team)

---

## The problem

In many cities, people see problems every day: a pothole on the road, garbage piling up, a streetlight that stays dark. Most people do not report them, because it is not clear where to complain, and nobody tells them what happened afterwards. Complaints also arrive without a clear category or exact location, so they are hard to sort and find.

## What NagrikX does

1. A citizen creates an account and logs in.
2. They report a problem: category, description, location and a photo.
3. They can use the map to pin the exact spot, or tap **Use my current location**.
4. If they are not sure of the category, AI looks at the photo and suggests it.
5. An admin sees all complaints, searches and filters them, and changes the status: **Pending**, **In Progress** or **Resolved**.
6. The citizen sees the status in their own complaints page.
7. When the admin marks a complaint **Resolved**, the citizen is asked: **"Is it really fixed?"** They can answer **Yes** or **No**. If **No**, the complaint goes back to **In Progress** and the admin sees it flagged as disputed.
8. When the admin confirms a report is genuine, the citizen earns **10 points**. The best citizens appear on a leaderboard.

## Features

### For citizens
- Register and log in safely (passwords are stored in a protected form)
- Report a problem with a photo (JPG, PNG or WEBP, up to 2 MB)
- Choose the category yourself, or let the AI detect it from the photo
- Pin the exact location on a map (tap the map, drag the pin, or use GPS)
- See all your complaints and their status, with a link to the pinned spot on a map
- Confirm whether a resolved problem is really fixed (**Yes** / **No**)
- Dashboard with counts of total, pending, in progress and resolved complaints
- Profile page with your points
- Leaderboard of top citizens (first names only, for privacy)
- Contact form to message the admin

### For admins
- Admin dashboard with every complaint
- Search, and filter by status or category
- Filter **Only disputed** complaints (the citizen said "not fixed")
- Change a complaint's status
- Verify a genuine report, which rewards the citizen with points (once per complaint)
- See which complaints had their category detected by AI
- See the citizen's feedback on resolved complaints
- Open the pinned location of a complaint on a map
- Read messages from the contact form

### Categories
Road Damage, Garbage, Streetlights, Water Leakage.

## How each part works

### 1. AI category detection
- The citizen chooses **"Not sure? Let AI detect it from my photo"** and uploads a photo.
- The website shrinks the photo (to about 800 pixels wide) and sends it to **Google Gemini** (`gemini-3.1-flash-lite`) through a small PHP function in `report.php`.
- The AI is asked to answer with one word: road damage, garbage, streetlight, water leak, or unsure.
- If the answer is clear, the category is filled in and the complaint is marked "detected by AI".
- If the AI is **unsure**, busy or unavailable, the citizen is asked to choose the category themselves. The site never depends on the AI to work.
- If the first model is busy, the site retries and can fall back to a second model (set in `ai_config.php`).
- The AI is a helper and **can make mistakes**.
- The API key is stored only on the server in `ai_config.php`. It is never sent to the browser and is not in this repository.

> **Earlier version:** the first version of this project used the pretrained CLIP model running as a separate Python (Flask) service on a laptop. The public online version uses Google Gemini instead, because CLIP cannot be hosted for free. No model was trained by us.

### 2. Map and location
- Uses **OpenStreetMap** tiles and the **Leaflet** map library (free, no API key).
- The citizen can tap the map, drag the pin, or tap **Use my current location** (browser GPS).
- The address box is filled automatically using the free **Nominatim** reverse-geocoding service, and the citizen can edit it.
- The exact latitude and longitude are saved with the complaint. Citizens and admins see a link: **View pinned spot on map**.
- GPS only works on HTTPS, and the browser asks for permission first. Location is never requested without the citizen tapping the button.

### 3. Status tracking
- The admin sets each complaint to **Pending**, **In Progress** or **Resolved**.
- The citizen sees the current status on **My Complaints**.

### 4. Citizen confirmation of "Resolved"
- When a complaint is **Resolved**, the citizen sees two buttons: **Yes, it is fixed** and **No, still not fixed**, plus an optional note.
- **Yes** marks it as confirmed by the citizen.
- **No** moves the complaint back to **In Progress**, saves the note, and flags it as disputed so the admin can find it with the **Only disputed** filter.
- A citizen can only answer for their own complaints, and can reopen a complaint at most **2 times**.
- When the admin marks it **Resolved** again, the citizen is asked again.

### 5. Points and leaderboard
- The admin clicks **Verify genuine report**. The citizen gets **10 points**, only once per complaint.
- Points show on the citizen's Dashboard, Profile and the Leaderboard (top 10, first names only).
- Points depend only on the admin verifying that the report is genuine, not on the citizen's confirmation.

## Safety and privacy

- Passwords are hashed, never stored as plain text
- Only logged-in citizens can open citizen pages, and only admins can open the admin page
- Forms are protected against forged requests (CSRF tokens)
- Photo uploads accept images only (JPG, PNG, WEBP), with a 2 MB limit and random file names
- Database changes for complaints use prepared statements
- The leaderboard shows first names only
- The AI API key is kept on the server and excluded from GitHub
- **Photos sent to the AI:** when a citizen uses "Let AI detect", the photo is sent to Google's Gemini API. On Google's free tier, Google may use the content to improve its products. The report page tells users not to upload faces or private information
- **Location:** the pinned location is saved with the complaint and shown to the admin. The browser asks permission before sharing GPS

## Built with

- PHP and MySQL (runs on XAMPP locally, and on free InfinityFree hosting online)
- HTML, CSS and a little JavaScript
- Google Gemini API (`gemini-3.1-flash-lite`) for photo category detection, called from PHP with cURL
- OpenStreetMap, Leaflet and Nominatim for maps and addresses
- AI assistant: Claude by Anthropic helped write and debug parts of the code

## Project files

| File | Purpose |
|---|---|
| `index.php`, `about.php`, `contact.php` | Public pages |
| `register.php`, `login.php`, `logout.php` | Accounts |
| `dashboard.php`, `profile.php`, `my_complaints.php` | Citizen pages (points, status, Yes/No confirmation, map links) |
| `report.php` | Report a problem: photo, map pin, AI category detection |
| `admin.php`, `admin_messages.php` | Admin pages (status, verify and points, disputed filter, messages) |
| `leaderboard.php` | Top 10 citizens by points |
| `db.php` | Connects the website to the database (**not in this repo**, see below) |
| `ai_config.php` | Gemini API key and model names (**not in this repo**, see below) |
| `cityfix.sql` | Database blueprint |
| `update_points.sql` | Adds points and AI columns |
| `css/`, `uploads/` | Styles and uploaded photos |

## Database setup

1. Create a database (named `cityfix` locally).
2. Import `cityfix.sql`.
3. Run `update_points.sql` (adds `points`, `verified`, `auto_category`).
4. Run these lines once (adds the confirmation and map columns):

```sql
ALTER TABLE complaints ADD COLUMN IF NOT EXISTS citizen_confirmation VARCHAR(10) NOT NULL DEFAULT 'none';
ALTER TABLE complaints ADD COLUMN IF NOT EXISTS citizen_note VARCHAR(255) NULL;
ALTER TABLE complaints ADD COLUMN IF NOT EXISTS reopen_count INT NOT NULL DEFAULT 0;
ALTER TABLE complaints ADD COLUMN IF NOT EXISTS latitude DECIMAL(10,7) NULL;
ALTER TABLE complaints ADD COLUMN IF NOT EXISTS longitude DECIMAL(10,7) NULL;
```

> On hosts like InfinityFree the database has a prefixed name (for example `if0_12345678_cityfix`). Remove any `CREATE DATABASE` or `USE` lines from the SQL files before importing there.

## Run it on your computer

1. Install [XAMPP](https://www.apachefriends.org/).
2. Copy the project folder into `C:\xampp\htdocs\cityfix\`.
3. Start **Apache** and **MySQL** in the XAMPP Control Panel.
4. Open `http://localhost/phpmyadmin`, create the database, and follow [Database setup](#database-setup).
5. Create `db.php` (it is not in this repo):

```php
<?php
$host = "localhost";
$user = "root";
$pass = "";
$db   = "cityfix";
// ...then the same connection code your project uses
```

6. For AI detection, create `ai_config.php` (also not in this repo):

```php
<?php
define('GEMINI_API_KEY', 'your-key-from-aistudio.google.com/apikey');
define('GEMINI_MODEL', 'gemini-3.1-flash-lite');
define('GEMINI_MODEL_FALLBACK', 'gemini-3.5-flash-lite'); // optional backup
```

7. Open `http://localhost/cityfix/`.
8. PHP `curl` must be enabled (in `php.ini`, remove the `;` before `extension=curl`, then restart Apache).

**Make an account an admin (for testing):** register a normal account, then in phpMyAdmin run this with your own email, and log in again:

```sql
UPDATE users SET role = 'admin' WHERE email = 'your-email@example.com';
```

## Put it online (free hosting)

This is how the live demo is set up:

1. Create a free account on [InfinityFree](https://www.infinityfree.com) and a free subdomain.
2. Create a MySQL database. Note the host, database name, username and password.
3. Import `cityfix.sql` and run the other SQL from [Database setup](#database-setup) in phpMyAdmin.
4. Edit `db.php` with the host's database details (the host name is not `localhost`).
5. Upload the project with File Manager into `htdocs/cityfix/`.
6. Create `ai_config.php` on the server with your Gemini key.
7. Turn on the free SSL certificate (HTTPS), which GPS needs.
8. Delete test files (`test.php`, debug files) and **never** upload `cityfix.sql` to a public folder.

## Known limits

- The free AI tier has usage limits and can be busy, so detection may sometimes fail. The citizen can always choose the category manually.
- The AI can be wrong. It is a helper, not a decision-maker.
- Free hosting is slow at times, and may block automated tools.
- GPS accuracy depends on the device. Laptops are often less exact than phones.
- Uploaded photos may contain hidden GPS data (EXIF). Removing it is planned.

## Planned next (not built yet)

- Automatic routing of each complaint to a department
- A map of all reported problems for admins
- More categories (for example waterlogging)
- Removing hidden GPS data from uploaded photos
- Notifications when a status changes

## Data note

Any complaints, users or photos shown in screenshots or demos are **demo data**, unless stated otherwise. We do not claim any real-world results. Please do not upload real people's personal information, faces or private photos to the demo.

**Do not commit** `db.php`, `ai_config.php` or the `uploads/` folder to a public repository. Suggested `.gitignore`:

```
db.php
ai_config.php
uploads/*
!uploads/.gitkeep
```

## Team

| Name | Role |
|---|---|
| Srijal Shiwani | Backend Developer / UI-UX Designer |
| Mohammed Musthaq | Frontend Developer / Cybersecurity Developer |

---

*NagrikX is a student project and is not an official government service.*

# CityFix - How to run it on your laptop

CityFix lets citizens report city problems (potholes, garbage, streetlights, water leaks) with a photo.
Admins check the reports and give points to citizens whose reports are genuine.
An AI can guess the problem type from the photo.

The project has **3 parts**. All 3 must be running to use everything:

| Part | What it does | Where it lives |
|---|---|---|
| Apache (XAMPP) | Shows the website | `C:\xampp\htdocs\cityfix\` |
| MySQL (XAMPP) | Stores users and complaints | Inside XAMPP, database name `cityfix` |
| AI service (Python) | Guesses the category from a photo | The `ai_service` folder |

---

## A. Every time you want to open the app (daily steps)

**Step 1 - Start XAMPP**
1. Open the **XAMPP Control Panel**.
2. Click **Start** next to **Apache**.
3. Click **Start** next to **MySQL**.
4. Both names should turn green.

**Step 2 - Start the AI (only needed for the "Let AI detect" option)**
1. Open the `ai_service` folder in File Explorer.
2. Click the address bar at the top, type `cmd`, and press **Enter**. A black window opens.
3. Type this and press **Enter**:

```
python app.py
```

4. Wait until the window shows: `Model ready. Waiting for photos on http://127.0.0.1:5001`
5. **Leave this black window open.** Closing it stops the AI.

**Step 3 - Open the website**

Open your browser and go to:

```
http://localhost/cityfix/
```

Useful pages:

| Page | Address |
|---|---|
| Home | http://localhost/cityfix/ |
| Login | http://localhost/cityfix/login.php |
| Report a problem | http://localhost/cityfix/report.php |
| My complaints | http://localhost/cityfix/my_complaints.php |
| Leaderboard | http://localhost/cityfix/leaderboard.php |
| Admin (admin account only) | http://localhost/cityfix/admin.php |
| AI check | http://127.0.0.1:5001/health (should show `{"status":"ok"}`) |
| Database (phpMyAdmin) | http://localhost/phpmyadmin |

**Step 4 - When you finish**
1. Close the black AI window (or press **Ctrl+C** in it).
2. In XAMPP, click **Stop** next to Apache and MySQL.

---

## B. How to use the main features

**Report a problem (citizen)**
1. Register, then log in.
2. Click Report a Problem.
3. Pick a category, or pick **Not sure? Let AI detect it from my photo** and upload a clear photo.
4. Add a description and a location, then submit.

**Give points (admin)**
1. Log in with an admin account and open the admin page.
2. Find the complaint and click **Verify genuine report**.
3. The citizen gets 10 points, only once per complaint.
4. Points show on the citizen's Dashboard, Profile and the Leaderboard.

**Make an account an admin (for testing on your own laptop)**
1. Register a normal account.
2. Open phpMyAdmin, click the `cityfix` database, open the **SQL** tab.
3. Run this (put your own email), then log out and log in again:

```
UPDATE users SET role = 'admin' WHERE email = 'your-email@example.com';
```

---

## C. First-time setup (only once on a new laptop)

1. Install **XAMPP** and **Python**.
2. Copy the `cityfix` website folder (the one with `index.php`) into `C:\xampp\htdocs\`.
3. Start Apache and MySQL in XAMPP.
4. Open http://localhost/phpmyadmin and click **Databases**. Create a database named `cityfix` (small letters).
5. Click the `cityfix` database, open **Import**, choose `cityfix.sql`, and click **Import**.
6. Open the **SQL** tab, paste everything from `update_points.sql`, and click **Go**. (This adds the points and AI columns.)
7. Open a black window inside the `ai_service` folder and run:

```
pip install -r requirements.txt
```

   If it says torch is missing, run this on one line:

```
pip install torch --index-url https://download.pytorch.org/whl/cpu
```

8. Run `python app.py`. The first time, it downloads about 600 MB. This is normal.

---

## D. If something goes wrong

| What you see | What to do |
|---|---|
| Website does not open | Check Apache is green in XAMPP. Check the folder is `C:\xampp\htdocs\cityfix\index.php`. |
| "Connection failed" or database error | Check MySQL is green. Check the database is named `cityfix`. |
| Database error on admin or profile page | You skipped step C6. Run `update_points.sql`. |
| "The AI service is not running" | Start the AI (Step 2) and wait for "Model ready". |
| "The AI is not sure what this photo shows" | Upload a clearer, closer photo, or choose the category yourself. |
| "PHP curl is not enabled" | In XAMPP click Config next to Apache, open php.ini, remove the `;` before `extension=curl`, save, and restart Apache. |
| MySQL will not start | Another MySQL may be running. Stop it in Windows Services, then start MySQL in XAMPP again. |
| Changes in a PHP file do not show | Press **Ctrl+F5** in the browser. |

---

## E. Files in the project

| File | Purpose |
|---|---|
| `index.php`, `about.php`, `contact.php` | Public pages |
| `register.php`, `login.php`, `logout.php` | Accounts |
| `dashboard.php`, `profile.php`, `my_complaints.php` | Citizen pages (profile and dashboard show points) |
| `report.php` | Report a problem, including AI category detection |
| `admin.php`, `admin_messages.php` | Admin pages (admin.php also verifies reports and gives points) |
| `leaderboard.php` | Top 10 citizens by points |
| `db.php` | Connects the website to the database |
| `cityfix.sql` | Database blueprint |
| `update_points.sql` | Adds points and AI columns (run once) |
| `ai_service/app.py` | The AI program (uses the free CLIP model) |
| `ai_service/requirements.txt` | Python packages the AI needs |

---

## F. Notes for judges and teammates

- The AI uses the pretrained CLIP model from OpenAI, loaded through the `transformers` library. No training data was collected.
- The AI runs on the laptop. It is a separate program from the website.
- Demo and test data must be clearly labelled as demo data.
- Do not upload real people's emails, passwords or photos to a public repository.

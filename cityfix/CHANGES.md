# New features: points for verified reports + AI category detection

## Setup (do in this order)
1. Copy these files into `C:\xampp\htdocs\cityfix\` and choose "Replace":
   `report.php`, `admin.php`, `dashboard.php`, `profile.php`, and the new `leaderboard.php`.
2. In phpMyAdmin, click the `cityfix` database, open the **SQL** tab, paste the contents of
   `update_points.sql`, and click **Go**. (Run it once.)
3. Test points: log in as admin, open Admin, click "Verify genuine report" on a complaint.
   The citizen gets 10 points (only once per complaint). They see it on Dashboard, Profile and Leaderboard.
4. AI detection (optional, needs internet the first time):
   - Open a terminal in the `ai_service` folder.
   - `pip install torch --index-url https://download.pytorch.org/whl/cpu`
   - `pip install -r requirements.txt`
   - `python app.py`  (keep this window open; first run downloads about 600 MB)
   - On the report page choose "Not sure? Let AI detect it from my photo" and upload a photo.

## What changed
- `report.php`: new dropdown option for AI detection; asks the AI service; if it is off or unsure, the user is asked to choose manually.
- `admin.php`: "Verify genuine report" button (CSRF protected, one reward per complaint), shows "detected by AI". Table name `Users` changed to `users` so it also works on Linux hosting.
- `dashboard.php`, `profile.php`: show the citizen's points.
- `leaderboard.php`: top 10 citizens (first names only).
- `ai_service/`: small Python program using the pretrained CLIP model.

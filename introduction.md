# CityFix

**Report a city problem with a photo. Track it until it is fixed. Earn points for helping your city.**

CityFix is a website where citizens report everyday city problems such as potholes, garbage, broken streetlights and water leaks. Admins review each report and update its status, so the citizen can see what is happening.

> CityFix is a student project made for the Tutedude Buildathon (#BuildForMyCity). It is not an official government service and is not connected to any government department.

---

## The problem

In many cities, people see problems every day: a pothole on the road, garbage piling up, a streetlight that stays dark. Most people do not report them, because it is not clear where to complain, and nobody tells them what happened afterwards. Complaints also arrive without a clear category, so they are hard to sort.

## What CityFix does

1. A citizen creates an account and logs in.
2. They report a problem: category, description, location and a photo.
3. If they are not sure of the category, **AI looks at the photo and suggests it**.
4. An admin sees all complaints, searches and filters them, and changes the status: **Pending, In Progress or Resolved**.
5. The citizen sees the status in their own complaints page.
6. When the admin confirms a report is genuine, the citizen earns **10 points**. The best citizens appear on a **leaderboard**.

## What you get in the app

**For citizens**
- Register and log in safely (passwords are stored in a protected form)
- Report a problem with a photo (JPG, PNG or WEBP)
- Choose the category yourself, or let the AI detect it from the photo
- See all your complaints and their status
- Dashboard with counts of total, pending, in progress and resolved complaints
- Profile page with your points
- Leaderboard of top citizens (first names only, for privacy)
- Contact form to message the admin

**For admins**
- Admin dashboard with every complaint
- Search, and filter by status or category
- Change a complaint's status
- Verify a genuine report, which rewards the citizen with points
- See which complaints had their category detected by AI
- Read messages from the contact form

## Categories

Road Damage, Garbage, Streetlights, Water Leakage.

## How the AI works

The AI is a pretrained model called CLIP. We did not train it. It compares your photo with short descriptions such as "a photo of a pothole on a road" and picks the closest match. If it is not sure, it says so and asks the citizen to choose the category. It is a helper, and it can make mistakes.

## Safety and privacy

- Passwords are hashed, never stored as plain text
- Only citizens and admins have access to their own pages, and only admins can open the admin page
- Forms are protected against forged requests
- Photo uploads accept images only, with a size limit and random file names
- The leaderboard shows first names only

## Planned next (not built yet)

- Automatic routing of each complaint to a department
- Pin the location on a map
- Map of all reported problems
- More categories (for example waterlogging)
- Removing hidden GPS data from uploaded photos

## Built with

- PHP and MySQL (runs on XAMPP)
- HTML and CSS
- Python with Flask for the AI service
- CLIP model (openai/clip-vit-base-patch32) through the `transformers` library
- AI assistant: Claude by Anthropic helped write and debug parts of the code

## Data note

Any complaints, users or photos shown in screenshots or demos are **demo data**, unless stated otherwise. We do not claim any real-world results.

## Run it on your computer

See **[HOW_TO_RUN.md](HOW_TO_RUN.md)** for simple step-by-step instructions.

## Team

Add your team's names and roles here.

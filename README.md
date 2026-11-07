# CampusRide

CampusRide is a PHP + MySQL student ride-share application for university commuters.

## Tech Stack
- PHP (Core, native)
- MySQL (InnoDB, prepared statements)
- HTML/CSS/Vanilla JavaScript
- Apache2 on Linux

## Setup
1. Create the database and tables:
   ```bash
   mysql -u root -p < database/campusride_db.sql
   ```
2. Configure DB credentials using environment variables (optional):
   - `DB_HOST` (default `127.0.0.1`)
   - `DB_USER` (default `root`)
   - `DB_PASS` (default empty)
   - `DB_NAME` (default `campusride_db`)
3. Place the project in Apache document root and open:
   - `http://localhost/CampusRide/`

## Core Features
- Student registration/login with secure password hashing and verification
- Session-based authentication with protected pages
- Vehicle registration for riders
- Ride publishing with seat/capacity validation
- Ride search with pickup, destination, and date filters
- Seat request flow with duplicate/self-request prevention
- Rider request management with transactional accept/reject handling
- My Rides and My Requests dashboards

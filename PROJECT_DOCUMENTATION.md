# Community Blood Donor Finder - Project Documentation

## 📄 Project Overview
**Community Blood Donor Finder** (also known as **Blood SOS**) is a real-time emergency blood donation platform designed to bridge the gap between people in urgent need of blood and potential donors within a community (e.g., a university campus or local area). The system leverages GPS tracking, real-time alerts, and automated notifications to ensure rapid response during emergencies.

---

## 🚀 Key Features

### 👤 User (Donor/Requester) Module
- **One-Tap SOS Alert:** Trigger an emergency blood request with GPS location in a single click.
- **Real-Time Donor Tracking:** Requesters can track the live location, distance, and ETA of donors who accept their SOS.
- **Radar Dashboard:** View live nearby emergency requests on an interactive map.
- **Smart Activation:** Account activation using pre-registered university data (Register Number + DOB).
- **Gamification:** Earn points for donations and view the top donor leaderboard.
- **Digital Certificates:** Automatically generated donation certificates for confirmed donors.
- **Notifications:** Multi-channel alerts via SMS, WhatsApp, and Email.

### 🛠️ Admin Module
- **Centralized Dashboard:** Real-time monitoring of all SOS alerts and system statistics.
- **Student Registry Management:** Bulk upload students via CSV and manage records.
- **Hospital Management:** Register nearby hospitals to display on the donor dashboard.
- **Blood Stock Tracking:** Monitor blood inventory levels across connected hospitals.
- **Donation Camp Scheduler:** Schedule and manage community blood donation camps.
- **Donation Confirmation:** Verify and confirm donations to award points and issue certificates.

---

## 🏗️ System Architecture

### Frontend
- **Design:** Modern, cinematic UI with fluid background animations and glassmorphism.
- **Interactivity:** Leaflet.js for interactive maps and real-time tracking.
- **Responsiveness:** Fully responsive design works on mobile, tablet, and desktop.

### Backend
- **Core:** PHP (7.4+) for business logic and API endpoints.
- **Messaging:** Integration with Twilio for SMS and WhatsApp alerts.
- **Database:** MySQL for structured data storage and relational management.

---

## 🗄️ Database Design
The system uses the `blood_sos_system` database with the following primary tables:

1. **`users`**: Stores donor, requester, and admin credentials, locations, and profiles.
2. **`preloaded_students`**: A verification table containing data for eligible students to activate accounts.
3. **`sos_alerts`**: Tracks emergency requests including blood group required and GPS coordinates.
4. **`sos_responses`**: Manages the link between specific alerts and donors who accepted them.
5. **`tracking`**: Stores real-time GPS coordinates for active emergency responses.
6. **`hospitals`**: Information about local hospitals for proximity-based features.
7. **`blood_inventory`**: Tracks stock levels (A+, B+, O+, etc.) per hospital.

---

## 🛠️ Technology Stack
- **Languages:** HTML5, CSS3, JavaScript (ES6+), PHP 7.4+
- **Database:** MySQL
- **Maps:** Leaflet.js (OpenStreetMap)
- **Styling:** Vanilla CSS3 (Custom Design System)
- **External APIs:** Twilio (SMS/WhatsApp), Google Fonts

---

## ⚙️ Installation & Setup

### Local Setup (XAMPP/WAMP)
1. Clone the repository into `htdocs/community`.
2. Import `database.sql` into PHPMyAdmin.
3. Configure database credentials in `backend/db_connect.php`.
4. Access the user portal at `http://localhost/community/`.
5. Access the admin portal at `http://localhost/community/admin/`.

### Production Deployment (Railway.app)
1. Push the code to a Private/Public GitHub repository.
2. Connect the repository to Railway.app.
3. Provision a MySQL instance on Railway and import `database.sql`.
4. Set Environment Variables (DB_HOST, DB_USER, DB_PASSWORD, DB_NAME).
5. Deploy and access via the generated Railway URL.

---

## 📱 Future Enhancements
- **A-Powered Matching:** Predictive analysis to find the most likely donors based on availability history.
- **Mobile App:** Native Android/iOS applications for better background tracking.
- **Blood Bank Integration:** Direct API sync with national blood bank databases.
- **Health AI:** AI-based health screening for donors before they accept a request.

---

## 📄 License & Credits
Developed as a community project to save lives through technology.
**Version:** 2.1 (Live Tracking Update)
**Status:** Operational ✅

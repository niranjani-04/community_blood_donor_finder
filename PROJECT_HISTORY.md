# 📜 Project History: Inception to Jan 30, 2026

This document details the development journey from the initial redesign up to the critical database recovery on Jan 30.

---

## 🗓️ 1. Initial Redesign & Setup (Jan 15, 2026)
**Focus:** Backend Architecture, SOS System, & Admin UI

### 🚀 **Kickoff: Blood Donor System Redesign**
*   **Objective**: Transform the existing system into a dynamic "Blood SOS" platform.
*   **Key Features Designed**:
    1.  **SOS Alert System**: Logic to trigger emergency blood requests (`sos_create.php`).
    2.  **GPS Tracking**: database structures for storing user latitude/longitude.
    3.  **Hospital Integration**: Added `hospitals` table and blood stock management.
*   **Database Schema**: Defined core tables: `users`, `sos_alerts`, `start_hospital`, `blood_inventory`.

### 🎨 **UI Refinement & Admin Setup**
*   **Admin Dashboard**:
    -   Refined `admin/dashboard.php` to include specific widgets for "Hospital Network" and "Active Volunteer List".
    -   Fixed the "Add Hospital File" functionality to allow admins to upload images/documents.
*   **Aesthetics**:
    -   Established the **Dark/Red/Black Theme**.
    -   Implemented glassmorphism on dashboard cards.

---

## 🗓️ 2. UI Enhancements & Bug Fixes (Jan 21 - Jan 23, 2026)
**Focus:** Critical UI Visibility & Initial Tracking Logic

### 👁️ **Jan 21: Emergency Alert Visibility Fix**
*   **Issue**: The text below the "Nearby Emergency Alert" section (Radar tab) was unreadable due to low contrast.
*   **Fix**:
    -   Updated CSS to ensure alert text is bright/visible against the dark background.
    -   Modified `index.php` (Radar section) to apply correct text classes.

### 📍 **Jan 23: Admin Tracking Implementation**
*   **Feature**: "Track Donor" functionality in Admin Panel.
*   **Implementation**:
    -   Added map interface to `admin/dashboard.php`.
    -   Backend logic to fetch `lat` and `lng` from the `users` table.

---

## 🗓️ 3. Advanced Tracking Features (Jan 29, 2026)
**Focus:** GPS Synchronization & Dual-View Tracking

### 🛰️ **Tracking Logic Upgrade**
*   **Problem**: The location feature initially only tracked the *Admin's* current location, failing to show the *Donor's* real-time position.
*   **Solution**:
    -   Updated `backend/update_location.php` to accept coordinates from the User's device.
    -   Modified Admin Tracking view to pull these stored coordinates instead of using the browser's local position.
    -   Enabled "Live Tracking" so Admins can see Donors moving on the map during an SOS.

---

## 🗓️ 4. Critical Fixes & Database Recovery (Jan 30, 2026)
**Focus:** Stability, Login Restoration, & Data Integrity

### 🔑 **Login System Verification**
*   **Issue**: Users reported inability to log in (`login.php`).
*   **Debugging**:
    -   Audited `backend/auth_login.php`.
    -   Fixed discrepancies in password hashing/verification logic (later switched to direct Register Number + DOB verification for students).
    -   Ensured session variables (`$_SESSION['user_id']`) were setting correctly.

### 💾 **Database Recovery Operation**
*   **Critical Incident**: Loss of database tables preventing the app from loading.
*   **Resolution**:
    -   Manually reconstructed the full SQL schema.
    -   Restored tables: `users`, `admin`, `sos_alerts`, `blood_inventory`, `hospitals`.
    -   Verified relationships between `users` (donors) and `sos_alerts`.

---

## ✅ Status at Jan 30
By the end of Jan 30, the system was **stable** with:
1.  **User App**: Working Login, SOS creation, Location updates.
2.  **Admin App**: Dashboard viewing, Hospital management, Basic tracking.
3.  **Database**: Fully structured and relational.

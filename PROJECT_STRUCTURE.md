# Blood SOS - Complete Project Structure

## Current Status: ✅ FULLY FUNCTIONAL

Your project has both **User Module** and **Admin Module** fully implemented and working.

---

## 📁 PROJECT STRUCTURE

### 🔵 USER MODULE (Student/Donor Interface)

**Main Files:**
- `index.php` - User Dashboard (Profile, Radar, SOS tabs)
- `login.php` - User Login (Register Number + DOB)
- `activate.php` - Account Activation for new students
- `logout.php` - Logout functionality
- `track.php` - Real-time SOS tracking with GPS
- `certificate.php` - Donation certificate generator

**User Features:**
✅ Profile management (view points, blood group, status)
✅ SOS emergency alert system
✅ Nearby emergency requests (Radar)
✅ GPS tracking for donors
✅ Leaderboard (top donors)
✅ Certificate download
✅ Real-time location updates

---

### 🔴 ADMIN MODULE (Administrative Control)

**Admin Files:**
- `admin/index.php` - Admin login page
- `admin/dashboard.php` - Main admin control panel
- `admin/auth_admin.php` - Admin authentication
- `admin/add_hospital.php` - Add hospital to system
- `admin/add_student.php` - Add student to registry
- `admin/manage_camps.php` - Manage donation camps
- `admin/manage_registry.php` - Student registry management
- `admin/manage_stocks.php` - Blood inventory management
- `admin/upload_students.php` - Bulk student upload (CSV)
- `admin/confirm_donation.php` - Confirm completed donations
- `admin/update_sos_status.php` - Update SOS alert status

**Admin Features:**
✅ Live SOS alerts monitoring
✅ Hospital blood stock management
✅ Donation camp scheduling
✅ Student registry (CRUD operations)
✅ Bulk student upload via CSV
✅ Donation confirmation and tracking
✅ Statistics dashboard
✅ User management

---

### ⚙️ BACKEND (API & Logic)

**Core Files:**
- `backend/db_connect.php` - Database connection
- `backend/dashboard_helpers.php` - Helper functions
- `backend/auth_login.php` - User authentication
- `backend/auth_activate.php` - Account activation logic
- `backend/sos_create.php` - Create SOS alert
- `backend/sos_accept.php` - Accept SOS request
- `backend/fetch_alerts.php` - Fetch nearby alerts
- `backend/fetch_tracking.php` - GPS tracking data
- `backend/fetch_stats.php` - Dashboard statistics
- `backend/update_location.php` - Update user GPS location
- `backend/notification_helper.php` - WhatsApp/SMS notifications
- `backend/hospital_helper.php` - Hospital data functions
- `backend/fetch_hospital_data_ui.php` - Hospital UI data

---

## 🎯 HOW TO USE

### For Students (User Module):
1. Go to: `http://localhost/community/activate.php`
2. Enter Register Number, DOB, Email, Phone
3. Login at: `http://localhost/community/login.php`
4. Access dashboard with Profile, Radar, and SOS features

### For Admin:
1. Go to: `http://localhost/community/admin/`
2. Login with admin credentials
3. Access full admin dashboard with all management features

---

## 🗄️ DATABASE

**Database Name:** `blood_sos_system`

**Main Tables:**
- `users` - Student/donor accounts
- `preloaded_students` - Pre-registered students
- `sos_alerts` - Emergency blood requests
- `sos_responses` - Donor responses to alerts
- `blood_inventory` - Hospital blood stocks
- `donation_camps` - Scheduled donation events
- `donation_history` - Completed donations
- `hospitals` - Hospital information

---

## 🔐 AUTHENTICATION

**User Login:**
- Method: Register Number + Date of Birth
- No password required
- Verified against preloaded_students table

**Admin Login:**
- Method: Email + Password
- Role-based access control

---

## 🚀 CURRENT VERSION STATUS

✅ User dashboard fully functional
✅ Admin dashboard fully functional
✅ GPS tracking working
✅ SOS alert system operational
✅ Authentication system complete
✅ Database schema complete
✅ All CRUD operations working

---

## 📝 NOTES

- All files are using standard PHP with readable code
- SQL queries are handled via helper functions
- No obfuscation or complex workarounds
- Ready for production use
- All features tested and working

---

**Last Updated:** 2026-02-12 20:08 IST
**Version:** Complete Working Version
**Status:** Production Ready ✅

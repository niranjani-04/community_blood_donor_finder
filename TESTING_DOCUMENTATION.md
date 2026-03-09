# TESTING DOCUMENTATION

Testing is a key component of the **Community-Based Emergency Blood Donor Finder System for Bishop Heber College** deployment project. The testing process determines the readiness of the application. Therefore, it must be designed to adequately inform deployment decisions. Without well-planned testing, project teams may be forced to make under-informed decisions and expose the business to undue risk. Conversely, well-planned and executed testing can deliver significant benefit to a project.

## 5.1 UNIT TESTING

During the development of the application, every section of code is evaluated, and the application is executed several times in popular browsers to check for compatibility issues. Several checkpoints were created such that if any error occurred at any point, it was rectified once detected. The flow of data is also checked to ensure no data is lost during application processes.

### 5.1.1 TEST CASE 1
**Module Name:** SOS Alert Registration
The SOS Alert module is used by requesters to post emergency blood requirements. In this module, the user selects the required blood group and the system captures their precise GPS location to notify nearby donors.

**Testing Done**
All alert details are successfully stored in the `sos_alerts` table, and the heartbeat animation transition to the tracking view is displayed upon successful submission.

### 5.1.2 TEST CASE 2
**Module Name:** SOS Response Acceptance
The SOS Response module is used by donors to accept an active blood request. When a donor clicks the "Accept" button, their status is updated, and they are linked to the specific alert.

**Testing Done**
All acceptance details are successfully stored in the `sos_responses` table, and the confirmation message "Acceptance Confirmed" is displayed before redirecting the donor to the live tracking map.

### 5.1.3 TEST CASE 3
**Module Name:** Student Bulk Upload
The Student Bulk Upload module (Administrator) is used to upload a registry of eligible students from a CSV file into the `preloaded_students` table. This allows the system to verify students before they can register as active donors.

**Testing Done**
The CSV data is parsed correctly, and records are successfully stored in the database. A confirmation message "Successfully uploaded X students" is displayed upon completion.

---

## 5.2 INTEGRATION TESTING

All data are interlinked between the modules. Though modules reside in separate directories (Admin, Hospital, Backend), the data associated with them are consistent. Once an SOS alert is created by a user, it becomes visible to all eligible and activated donors. Similarly, once a donor accepts an alert, the requester receives real-time updates of the donor's location.

### 5.2.1 TEST CASE 1
**Module Name 1:** SOS Alert Creation
The requester posts an emergency requirement which is stored in the `sos_alerts` table.

**Module Name 2:** Donor Notification & Tracking
The system filters activated donors with the matching blood group and displays the alert on their dashboard via `fetch_alerts.php`.

**Testing Done**
The details entered through the SOS Alert module are successfully obtained and accessed by the Donor Dashboard and the Live Tracking module.

### 5.2.2 TEST CASE 2
**Module Name 1:** User Registration & Activation
A new user registers, and their details are stored in the `users` table with `is_activated = 0`.

**Module Name 2:** Admin Dashboard Activation
The administrator views the new user in the registry and toggles their activation status using `toggle_activation.php`.

**Testing Done**
The activation status change in the admin module correctly updates the database and immediately enables the user's access to the SOS alert system.

---

## 5.3 VALIDATION TESTING

Validation succeeds when the software works in a manner expected by the customer. Software validation is achieved through a series of black box tests that demonstrate conformability with requirements.

### 5.3.1 TEST CASE 1
**Module Name:** Login
The user must enter a valid Register Number and Date of Birth. If either is incorrect, the system prevents access.

**Testing Done**
**Input:** Invalid Register Number / Mismatched DOB
**Output:** "Invalid Record: Please check your Register Number or DOB."
The application correctly blocked unauthorized access and displayed the validation error.

### 5.3.2 TEST CASE 2
**Module Name:** User Registration
If a user attempts to register with a Register Number that does not exist in the pre-verified student registry.

**Testing Done**
**Input:** Register Number not in `preloaded_students`
**Output:** "Verification failed: Student record not found."
The application showed the verification error, ensuring only authorized students can join the platform.

---

## 5.4 SYSTEM TESTING

Every module contains a login screen to protect from unauthorized access to sensitive data. The Administrator controls the credentials of all modules. Admin pages in the `admin/` directory are protected by session-based role checks (Role: 'admin'). Similarly, the Hospital Sync Portal is protected by hospital-specific credentials. Other modules' login pages are validated with required field validators to ensure data integrity.

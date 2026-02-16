# How to Host on Railway.app

This guide will walk you through deploying your **Blood SOS System** to **Railway.app**.

## Prerequisites
1.  **GitHub Account**: You need to embrace GitHub to deploy easily on Railway.
2.  **Railway Account**: Sign up at [Railway.app](https://railway.app/).

---

## Step 1: Push Code to GitHub
If your code is not yet on GitHub, you need to push it there.
1.  Create a new repository on GitHub (e.g., `blood-sos-system`).
2.  Open your project terminal and run:
    ```bash
    git init
    git add .
    git commit -m "Initial commit"
    git branch -M main
    git remote add origin https://github.com/YOUR_USERNAME/blood-sos-system.git
    git push -u origin main
    ```

---

## Step 2: Create a Project on Railway
1.  Go to your [Railway Dashboard](https://railway.app/dashboard).
2.  Click **"New Project"**.
3.  Select **"Deploy from GitHub repo"**.
4.  Select your `blood-sos-system` repository.
5.  Click **"Deploy Now"**.

---

## Step 3: Add a Database
Your PHP app needs a MySQL database.
1.  In your Railway project view, right-click on the canvas (or click "New").
2.  Select **"Database"** -> **"MySQL"**.
3.  Wait for the MySQL service to initialize.

---

## Step 4: Configure Environment Variables
Now you need to tell your PHP app how to connect to this new database.
1.  Click on the **MySQL** service card.
2.  Go to the **"Variables"** tab.
    *   You will see variables like `MYSQLHOST`, `MYSQLPORT`, `MYSQLUSER`, `MYSQLPASSWORD`, `MYSQLDATABASE`.
3.  Now, click on your **PHP App** service card.
4.  Go to the **"Variables"** tab.
5.  Add the following variables (referencing the MySQL values):
    *   `DB_HOST`: `${{MySQL.MYSQLHOST}}`
    *   `DB_PORT`: `${{MySQL.MYSQLPORT}}`
    *   `DB_NAME`: `${{MySQL.MYSQLDATABASE}}`
    *   `DB_USER`: `${{MySQL.MYSQLUSER}}`
    *   `DB_PASSWORD`: `${{MySQL.MYSQLPASSWORD}}`
6.  Railway will automatically redeploy your app with these new settings.

---

## Step 5: Import your Database Schema
You need to create the tables in your new online database.
1.  Click on the **MySQL** service card.
2.  Go to the **"Data"** tab (Railway has a built-in simplified view) OR use the **"Connect"** tab to get the credentials.
3.  **Recommended**: Use a tool like **MySQL Workbench**, **TablePlus**, or **DBeaver** on your computer.
    *   Host: (From Railway Connect tab)
    *   Port: (From Railway Connect tab)
    *   User: (From Railway Connect tab)
    *   Password: (From Railway Connect tab)
4.  Once connected, open your `database.sql` file content and run the SQL queries to create the tables.

---

## Step 6: Verify Deployment
1.  Click on your **PHP App** service card.
2.  Go to the **"Settings"** tab -> **"Domains"**.
3.  Click the generated URL (e.g., `failed-production.up.railway.app`).
4.  Your app should now be live!

### Troubleshooting
*   **"Connection failed"**: Check your Environment Variables in the PHP service. Ensure they match the MySQL service variables exactly.
*   **"404 Not Found"**: Ensure `index.php` is in the root folder.

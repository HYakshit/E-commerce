# Installing the ShopNow E-commerce Website on XAMPP

This guide will help you set up the ShopNow e-commerce website using XAMPP on your local machine.

## Prerequisites

1. [XAMPP](https://www.apachefriends.org/index.html) installed on your computer
2. [Git](https://git-scm.com/) (optional, for cloning the repository)

## Installation Steps

### Step 1: Download and Install XAMPP

1. Download XAMPP from [https://www.apachefriends.org/index.html](https://www.apachefriends.org/index.html)
2. Install XAMPP following the installation wizard

### Step 2: Start Apache and MySQL Services

1. Open the XAMPP Control Panel
2. Start the Apache and MySQL services by clicking the "Start" buttons

### Step 3: Set Up the Project Files

#### Option A: Using Git
```bash
# Navigate to the htdocs directory
cd C:\xampp\htdocs    # On Windows
cd /Applications/XAMPP/htdocs    # On macOS
cd /opt/lampp/htdocs    # On Linux

# Clone the repository
git clone https://your-repository-url.git shopnow
```

#### Option B: Manual Download
1. Download the project as a ZIP file
2. Extract the ZIP file
3. Copy all the files to the `htdocs` folder of your XAMPP installation:
   - Windows: `C:\xampp\htdocs\shopnow`
   - macOS: `/Applications/XAMPP/htdocs/shopnow`
   - Linux: `/opt/lampp/htdocs/shopnow`

### Step 4: Create the Database

1. Open your web browser and navigate to `http://localhost/phpmyadmin`
2. Click on "New" in the left sidebar to create a new database
3. Enter "shopdb" as the database name and click "Create"
4. Select the newly created "shopdb" database from the left sidebar
5. Click on the "Import" tab
6. Click "Choose File" and select the `db/mysql_shopdb.sql` file from your project folder
7. Click "Go" to import the database structure and sample data

### Step 5: Configure the Application

The application is already configured to work with XAMPP's default MySQL settings. If you've changed the default MySQL credentials, you'll need to update the database connection settings:

1. Open the file `includes/db_connect.php`
2. Locate the MySQL connection section (around line 22)
3. Update the username and password if you've changed them from XAMPP defaults:
   ```php
   $host = 'localhost';
   $dbname = 'shopdb';
   $username = 'root'; // Change if needed
   $password = ''; // Change if needed
   ```

### Step 6: Access the Website

1. Open your web browser
2. Navigate to: `http://localhost/shopnow`
3. You should see the ShopNow homepage with sample products

## Default Admin Login

You can log in as an administrator using the following credentials:

- Email: admin@shopnow.com
- Password: admin123

## Configuring Firebase (Optional)

If you want to use Firebase authentication:

1. Go to [Firebase Console](https://console.firebase.google.com/)
2. Create a new project
3. Set up a web app in your project
4. Enable Google authentication in the Firebase Authentication section
5. Get your Firebase configuration (apiKey, authDomain, projectId, etc.)
6. Update the Firebase configuration in `includes/firebase_config.php`

## Troubleshooting

### Common Issues:

1. **Database Connection Errors**
   - Verify your MySQL service is running in XAMPP Control Panel
   - Check that the database name is "shopdb"
   - Ensure username and password in `includes/db_connect.php` match your MySQL settings

2. **Missing Tables or Data**
   - Confirm you imported the `db/mysql_shopdb.sql` file correctly
   - Try importing the SQL file again

3. **404 Page Not Found**
   - Ensure the project files are in the correct location in the htdocs folder
   - Check that Apache service is running in XAMPP Control Panel

4. **500 Server Error**
   - Check the Apache error logs in XAMPP Control Panel
   - Verify PHP version is compatible (PHP 7.4+ recommended)

### Getting Help

If you encounter any issues, please check:
1. The error logs in XAMPP Control Panel
2. PHP configuration settings
3. Contact the project maintainer for support
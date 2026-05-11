# MilkMate  🥛

**MilkMate** is a comprehensive, secure, and modern web-based Dairy Milk Collection, Billing, and Accounting Management System designed specifically for dairy farmers and collection centers. 

## 🌟 Features

- **Dashboard**: A comprehensive overview of daily collections, active farmers, pending payments, and overall statistics.
- **Farmer Management**: Easily add, edit, and manage farmer profiles and their details.
- **Daily Milk Collection**: Intuitive interface for recording daily milk deposits, categorizing by milk type (Cow/Buffalo), and automatically calculating rates based on FAT and SNF content.
- **Automated Billing System**: Generate precise bills and invoices automatically based on the collection data.
- **Financial Reporting**: Detailed accounting modules to track payments, outstanding balances, and generate periodic financial reports.
- **Modern UI**: A clean, responsive, and user-friendly interface featuring glassmorphism elements built with Tailwind CSS.

## 🛠️ Technology Stack

- **Backend**: Core PHP 8+
- **Database**: MySQL
- **Frontend**: HTML5, CSS3, JavaScript, [Tailwind CSS](https://tailwindcss.com/)
- **Architecture**: Modular PHP Structure with AJAX for seamless asynchronous operations
- **Environment**: XAMPP / LAMP / WAMP Stack

## 📂 Project Structure

- `ajax/` - Asynchronous PHP handlers for dynamic frontend interactions (billing, collections, etc.).
- `assets/` - Static assets including CSS, JavaScript, and images.
- `auth/` - User authentication, login, and session management.
- `config/` - Database connection and system configuration files.
- `database/` - SQL schemas and database setup scripts.
- `includes/` - Reusable components (headers, footers, navigation).
- `modules/` - Core application features (billing, reports, farmer management).
- `uploads/` - Directory for user-uploaded files and generated reports.
- `dashboard.php` - Main application dashboard.
- `setup.php` - Initial system setup and installation wizard.

## 🚀 Getting Started

### Prerequisites

- A local server environment like [XAMPP](https://www.apachefriends.org/index.html) (or WAMP/LAMP).
- PHP 8.0 or higher.
- MySQL/MariaDB.

### Installation

1. **Clone the repository** into your server's root directory (e.g., `htdocs` for XAMPP):
   ```bash
   git clone <repository-url> MilkMate
   ```

2. **Start your local server**: Ensure both Apache and MySQL modules are running.

3. **Database Configuration**:
   - Create a new MySQL database named `milkmate_db` (or your preferred name).
   - Update the database credentials in the configuration file located at `config/database.php` (if applicable) or use the setup wizard.

4. **Run Setup**:
   - Navigate to `http://localhost/MilkMate/setup.php` in your web browser.
   - Follow the on-screen instructions to initialize the database tables and create an admin account.

5. **Login**:
   - Once setup is complete, delete or rename `setup.php` for security purposes.
   - Go to `http://localhost/MilkMate/index.php` or `http://localhost/MilkMate/auth/login.php` to access the system.

## 🔒 Security Practices

- Ensure the `setup.php` file is removed in a production environment.
- Use secure, hashed passwords for all user accounts.
- The system includes basic protection against SQL injection (using Prepared Statements) and XSS.

## 📄 License

This project is proprietary or licensed under a specific license. Please refer to the `LICENSE` file for more information.

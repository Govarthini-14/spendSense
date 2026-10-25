# SpendSense — Student Expense Tracker

SpendSense is a secure, full-stack PHP + MySQL student expense tracker for managing daily spending, monthly budgets, analytics, and savings habits.

## Features

- User registration and login with PHP sessions
- Dashboard with live budget status and spending stats
- Add, edit, delete, search, filter, sort, and paginate expenses
- Monthly reports with category and daily charts
- Monthly budget and category budget planning
- Personalized savings tips based on real data
- Responsive dashboard UI for desktop and mobile
- CSRF token validation and session-based security

## Tech Stack

- PHP 8+
- MySQL 8+
- Apache via XAMPP
- HTML5, CSS3, JavaScript
- Chart.js
- Font Awesome

## Project Structure

- `index.php` – Dashboard
- `add-expense.php` – Expense entry form
- `transactions.php` – Transaction table and filters
- `edit-expense.php` – Transaction editor
- `delete-expense.php` – Delete handler
- `reports.php` – Monthly analytics
- `budget.php` – Budget planner
- `savings-tips.php` – Personalized recommendations
- `register.php` – Signup page
- `login.php` – Login page
- `logout.php` – Secure logout
- `profile.php` – User profile and password management
- `database.sql` – Database schema and sample data
- `config/database.php` – Database settings
- `includes/` – Shared PHP logic and layout
- `assets/` – CSS and JS assets

## Setup in XAMPP

1. Install XAMPP and start Apache and MySQL.
2. Copy the project folder into `htdocs`.
3. Open phpMyAdmin and create a database or import `database.sql`.
4. Set the host, port, and credentials in `config/database.php` to match your MySQL server. XAMPP commonly uses port `3306`; if you changed it, use the configured MySQL port.
5. Open `http://localhost/spendsense/login.php` in your browser.

## Database Setup

1. Import the SQL file from the project root:
   - `database.sql`
2. Ensure the database name is `spendsense_db`.
3. Confirm the host, port, and credentials used by `config/database.php` match your local MySQL setup.

## Default Demo Account

- Email: `govarthini@gmail.com`
- Password: `password123`

The seeded sample user password is generated with PHP's `password_hash()` and matches the demo account above.

## Security Notes

- Passwords are hashed with `password_hash()`.
- PDO prepared statements are used throughout.
- Authenticated pages check session state.
- CSRF tokens are included on form submissions.

## Running in VS Code

1. Open the project folder in VS Code.
2. Install the PHP extension if needed.
3. Make sure XAMPP Apache and MySQL are running.
4. Use the built-in Live Server or open the app via the browser at `http://localhost/spendsense`.
5. Register or log in using the demo account.

## Troubleshooting

- If PHP cannot connect to MySQL, verify the username and password in `config/database.php`.
- If pages do not load, ensure your project folder is inside `htdocs`.
- If the database is not found, import `database.sql` again.
- If the application shows an authorization error, log out and log back in.

## Testing Scenarios

1. Register a new student account.
2. Log in and add an expense.
3. Confirm the dashboard updates.
4. Search and filter transactions.
5. Set a monthly budget and category limits.
6. View savings tips and budget warnings.
7. Edit and delete records to verify recalculation.

## Future Enhancements

- Multi-user admin layer
- CSV export for expenses
- Offline storage and PWA support
- Dark mode

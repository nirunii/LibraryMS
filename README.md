# LibraryMS - Library Management System (PHP MVC)

IT 403061 Enterprise Systems Technologies and Architectures - Take Home Individual Assignment

## Requirements
- XAMPP (Apache + MySQL + PHP 8.0 or newer)
- Internet connection for the dashboard chart (Chart.js loads from a CDN)

## Run it (step by step)
1. Install XAMPP and open the XAMPP Control Panel. Start **Apache** and **MySQL**.
2. Copy the whole **LibraryMS** folder into `C:\xampp\htdocs\` (so you have `C:\xampp\htdocs\LibraryMS\index.php`).
3. Open http://localhost/phpmyadmin, click the **Import** tab, choose `database/library.sql`, click **Import**. This creates the `library_db` database with sample data.
4. Open http://localhost/LibraryMS/ and log in.

If your folder or MySQL password is different, edit `config/config.php`.

## Sample accounts
| Role | Email | Password |
|---|---|---|
| Admin | admin@library.com | Admin@123 |
| Librarian | librarian@library.com | Lib@123 |
| Member | nimal@mail.com | Member@123 |
| Member | kavindi@mail.com | Member@123 |
| Member | ruwan@mail.com | Member@123 |

## Structure
```
index.php        front controller / router
config/          settings (database, loan days, fine rate)
core/            Controller, Model, Database, helpers
app/models/      User, Book, Loan, Reservation  (data + business rules)
app/controllers/ Auth, Dashboard, Book, Member, Loan, Report
app/views/       HTML templates (no SQL, no business rules)
public/css/      stylesheet
database/        library.sql
```

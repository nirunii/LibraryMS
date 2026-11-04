# LibraryMS v2 - Library Management System (PHP MVC)

Version 2 adds real-world library features on top of the assignment version:
renewals, a holds queue, fine payments with receipts, member profiles, a settings page and an audit log.

## Requirements
- XAMPP (Apache + MySQL + PHP 8.0 or newer)
- Internet connection for the dashboard chart (Chart.js loads from a CDN)

## Run it (step by step)
1. Install XAMPP and open the XAMPP Control Panel. Start **Apache** and **MySQL**.
2. Copy the whole **LibraryMS** folder into `C:\xampp\htdocs\`.
3. Open http://localhost/phpmyadmin, click the **Import** tab, choose `database/library.sql`, click **Import**.
   (This replaces any older `library_db`, so v2 starts with fresh sample data.)
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

## What is new in v2 and where to find it
| Feature | Who | Where |
|---|---|---|
| **Public catalogue**: browse, search and open book details without an account; borrowing and everything else asks you to log in, then returns you to where you were | Guests | The home page (Books) |
| **Renew a loan** (limit and days are settings; blocked if overdue or someone is waiting) | Member, staff | Loans / My loans, "Renew" button |
| **Holds queue**: reserve a book, see your place in line; when a copy is returned it is held for the first member for a few days, then offered to the next | Member (reserve), staff (see queue, cancel hold) | Books, My loans, Loans > Holds queue |
| **Fine payments**: full or part payment by cash, card or online, admin can waive; unpaid fines above a limit block borrowing | Staff | Fines |
| **Printable receipts** for every payment | Staff, the member | Fines > Receipt |
| **Member profile & borrowing history**: details, all loans, holds, payments; suspend or reactivate a membership | Staff | Members > View |
| **My profile**: edit contact details, change password, membership number | Everyone | My profile |
| **Settings**: loan days, fine per day, max books, renewals, hold days, unpaid-fine limit | Admin | Settings |
| **Audit log**: who issued, returned, renewed, paid, edited or deleted what | Admin | Audit log |

## Quick test checklist
0. Log out and open http://localhost/LibraryMS/ : the catalogue is visible without logging in. Click **Log in to borrow** on a book; after logging in you land back on that book.
1. Log in as `librarian@library.com`, open **Loans**, click **Return** on *Design Patterns* (Kavindi, overdue). Nimal's hold becomes *Ready* (see the Holds queue).
2. Open **Fines**: record a payment for Kavindi's fine, then click **Receipt**.
3. Log in as `nimal@mail.com`: **Books** shows *Design Patterns* as "Held for you"; click **Borrow**. Open **My loans** and click **Renew** on *Clean Code*.
4. As admin open **Settings**, change *Fine per overdue day*, then check **Audit log**.
5. As librarian open **Members > View** on any member to see their full history.

## Structure
```
index.php        front controller / router
config/          default settings (database, loan days, fine rate)
core/            Controller, Model, Database, helpers
app/models/      User, Book, Loan, Reservation, Payment, Setting, Audit  (data + business rules)
app/controllers/ Auth, Dashboard, Book, Member, Loan, Fine, Profile, Setting, Audit, Report
app/views/       HTML templates (no SQL, no business rules)
public/css/      stylesheet
database/        library.sql (schema + sample data)
```

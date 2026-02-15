# Library Management System

## Overview
This is a **web-based Library Management System** designed to manage library operations including book cataloging, user management, loans, reservations, and fines. The system supports multiple user roles with role-specific dashboards:

- **Staff / Librarian**
- **Member**
- **Author**
- **Publisher**

The system uses a relational database to store information about books, users, and transactions, with secure login and role-based dashboards. The provided files are view-only and focus on reporting and monitoring.

---

## Project Structure

library_management_project/
│
├── database/
│ └── library.sql # SQL script to create database and tables
│
├── frontend/
│ ├── index.html # Main HTML page
│ ├── style.css # Stylesheet
│ └── script.js # Frontend JavaScript
│
└── README.md # Project overview


---

## Database Setup

The SQL script (`library.sql`) creates the following tables:

- `Publisher` – Stores publisher details
- `Category` – Manages book categories
- `Book` – Central entity for books
- `Author` – Author information
- `BookAuthor` – Many-to-many relation between Books and Authors
- `Copy` – Physical copies of books
- `Member` – Library members
- `Staff` – Library staff
- `Loan` – Tracks borrowed books
- `Reservation` – Book reservations
- `Fine` – Records penalties

The script also includes relationships, foreign keys, and constraints to ensure data integrity.

---
## Project Preview


![Library Management Dashboard].(https://github.com/5olod5ald/library_management_project/blob/272b362f31b6fc32c5f37b74a265b4bb2d60dd0c/library_web.jpeg)

## How to Run

1. Import or run the SQL script (`library.sql`) in your MySQL or SQL Server database.
2. Open the `frontend/index.html` file in a browser to view the dashboards.
3. Use the login page to access different dashboards (Staff, Member, Author, Publisher).
4. Dashboards are **view-only** and display statistics, tables, and insights.

---

## Features

### Staff / Librarian Dashboard
- View all active reservations
- Check number of copies per book
- Most borrowed books
- All active loans
- Members with unpaid fines
- General library statistics

### Member Dashboard
- Active loans with due dates
- Reservations and status
- Unpaid fines
- Loan history

### Author Dashboard
- Published books and stats
- Most borrowed books
- Available copies

### Publisher Dashboard
- Published books stats
- Most borrowed books
- Total loans and fines
- Late loans with member names

---

## Notes
- Passwords for staff are hashed
- Non-staff users login with email + ID
- Modern dashboard UI with glassmorphism styling
- Focused on reporting, not direct modification of data

# BizTix 🎟️

BizTix is a mini event ticketing web application built using PHP and MariaDB for managing business events, workshops, and seminars.

## Features

### Public User
- View upcoming events
- View event details (description, venue, date, time, ticket price)
- Book tickets online
- Seat availability / remaining seat validation
- Booking code generation

### Admin Panel
- Admin login
admin email:admin@biztix.com
admin password:$2y$10$JlJyuFtpccjGNbItRJkcquDzqGZIbYGC6Xhp3JD0ogmkyUWAaQvKe
- Manage events (add, edit, delete)
- Manage bookings
- Update booking status (pending, confirmed, cancelled)
- Update payment status (unpaid, paid)
- Search bookings by customer name, email, event, or booking code

## Tech Stack
- PHP
- MariaDB / MySQL
- MySQLi
- HTML
- CSS
- XAMPP
- VS Code

## Project Status
Core modules completed. Currently improving dashboard analytics, event filtering, and security enhancements (prepared statements / CSRF protection).

## Folder Structure
```text
BizTix/
├── admin/
│   ├── login.php
│   ├── bookings.php
│   ├── events_manage.php
│   └── ...
├── assets/
│   └── css/
│       └── style.css
├── index.php
├── events.php
├── event_details.php
├── book_event.php
├── db.php
└── README.md
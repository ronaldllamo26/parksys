# 🚗 ParkSys Pro — Smart Parking Management System

**ParkSys Pro** is a high-fidelity, enterprise-grade parking management solution designed to streamline vehicle entry, exit, billing, and facility intelligence. Built with a focus on speed, security, and a professional user experience.

---

## 🌟 Core Features

- **🚀 Live Dashboard**: Real-time monitoring of slot availability, active sessions, and system integrity.
- **🛡️ Secure Authentication**: Role-based access control (RBAC) for Staff, Admin, and Superadmin tiers.
- **💎 VIP & Loyalty System**: Dedicated VIP slot management and loyalty points integration for frequent parkers.
- **📊 Advanced Analytics**: Comprehensive revenue tracking, peak-hour analysis, and occupancy trends.
- **🧾 Automated Billing**: Smart calculation of fees based on vehicle type, duration, and VIP status with automated receipt generation.
- **📜 Forensic Audit Logs**: High-detail system logs to track every administrative action and security event.
- **✨ Professional UI**: A clean, modern, and responsive interface designed for high-pressure operational environments.

---

## 🛠️ Technology Stack

- **Backend**: PHP 8.x (Standard Apache/XAMPP)
- **Database**: MySQL (Optimized Relational Schema)
- **Frontend**: Vanilla CSS3, Modern JavaScript (ES6+), Lucide Icons
- **Design Principles**: Professional Minimalist, Enterprise Responsive Layout

---

## 🚀 Quick Setup

1. **Clone the Repository**
   ```bash
   git clone https://github.com/ronaldllamo26/parksys.git
   ```

2. **Configure Database**
   - Create a database named `parksys` in your MySQL server.
   - Import the database schema from the `/sql` or `/api/export_db.php` if available.
   - Update `config/database.php` with your local credentials.

3. **Deploy to Local Server**
   - Move the project folder to your `htdocs` or equivalent web root.
   - Access the system via `http://localhost/parksys`.

4. **Default Credentials**
   - **Admin**: `admin@parksys.com` / `admin123`
   - **Staff**: `staff@parksys.com` / `staff123`

---

## 📂 Project Structure

- `api/` — Backend logic, data processing, and AJAX endpoints.
- `controllers/` — Business logic and role management.
- `views/` — Application interfaces (Dashboard, Entry, Exit, etc.).
- `assets/` — Modern UI assets, icons, and typography.
- `config/` — Environment and database configurations.

---

Developed with ❤️ for Academic Excellence.
**ParkSys Pro — Scaling Mobility Intelligence.**

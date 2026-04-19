# 🚗 ParkSys Pro — Smart Parking Management System

**ParkSys Pro** is a high-fidelity, enterprise-grade parking management solution designed to streamline vehicle entry, exit, billing, and facility intelligence. Built with a focus on speed, security, and a professional user experience.

---

### 🎥 System Demonstration

<div align="center">
  <!-- I-drag and drop mo lang yung video file dito sa GitHub Editor para mapalitan ito ng actual video link -->
  https://github.com/ronaldllamo26/parksys/raw/main/assets/parksys_demo.mp4
  
  <p align="center">
    <img src="https://img.shields.io/badge/PHP-8.2-777BB4?style=for-the-badge&logo=php&logoColor=white" />
    <img src="https://img.shields.io/badge/MySQL-MariaDB-003545?style=for-the-badge&logo=mariadb&logoColor=white" />
    <img src="https://img.shields.io/badge/JavaScript-ES6+-F7DF1E?style=for-the-badge&logo=javascript&logoColor=black" />
    <img src="https://img.shields.io/badge/Security-Iron_Dome-red?style=for-the-badge&logo=google-cloud-armour&logoColor=white" />
  </p>
</div>

---

## 🌟 Core Features

- **🚀 Live Dashboard**: Real-time monitoring of slot availability, active sessions, and system integrity.
- **🛡️ Secure Authentication**: Role-based access control (RBAC) for Staff, Admin, and Superadmin tiers.
- **🔐 Iron Dome Security Suite**: Integrated CSRF protection, Brute-force account lockout, and secure HTTP headers.
- **💎 VIP & Loyalty System**: Dedicated VIP slot management and loyalty points integration for frequent parkers.
- **📊 Advanced Analytics**: Comprehensive revenue tracking, peak-hour analysis, and occupancy trends.
- **🧾 Automated Billing**: Backend-validated billing logic to prevent price manipulation and ensure financial integrity.
- **📜 Forensic Audit Logs**: High-detail system logs tracking all administrative actions and security events.
- **✨ Professional UI**: Modern, responsive interface designed for high-pressure operational environments.

---

## 🛠️ Technology Stack

- **Backend**: PHP 8.x (Standard Apache/XAMPP)
- **Database**: MySQL (Optimized Relational Schema)
- **Frontend**: Vanilla CSS3, Modern JavaScript (ES6+), Lucide Icons
- **Security**: PDO Prepared Statements, Bcrypt Hashing, CSRF Tokens

---

## 🚀 Quick Setup

1. **Clone the Repository**
   ```bash
   git clone https://github.com/ronaldllamo26/parksys.git
   ```

2. **Configure Database**
   - Create a database named `parksys` in your MySQL server.
   - Import the database schema from the SQL file provided in the repository.
   - Update `config/database.php` with your local credentials.

3. **Deploy to Local Server**
   - Move the project folder to your `htdocs` or equivalent web root.
   - Access the system via `http://localhost/parksys`.

4. **Authentication**
   - Use the pre-configured administrator credentials provided during the initial setup. For security reasons, default credentials are not listed in this documentation.

---

## 📂 Project Structure

- `api/` — Backend logic, data processing, and AJAX endpoints.
- `controllers/` — Business logic and role management.
- `views/` — Application interfaces (Dashboard, Entry, Exit, etc.).
- `assets/` — Modern UI assets, icons, and typography.
- `config/` — Environment and database configurations.

---

## 📄 System Documentation

For detailed technical reports, security analysis, and project documentation, please refer to the [docs/](docs/) folder.

- **Technical Manuscript**: Professional report following the 16-section project template.
- **Security Audit**: Deep dive into the "Iron Dome" defense suite.

---

Developed with ❤️ for Academic Excellence.
**ParkSys Pro — Scaling Mobility Intelligence.**

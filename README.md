# 🚙 ParkSys Pro Enterprise v1.0.2

**ParkSys Pro** is a high-performance, enterprise-grade Parking Management System designed for large-scale facilities like malls, corporate hubs, and smart cities. It features a modern, glassmorphism-inspired UI with advanced business intelligence and role-based operational workflows.

---

## 💎 Key Features

### 👑 Superadmin Suite (Business Intelligence)
- **Live Revenue Analytics**: Real-time monitoring of daily/weekly collections via Chart.js.
- **Occupancy Insights**: Doughnut charts showing vehicle distribution (Car vs Motorcycle vs Van).
- **Audit Logs**: Comprehensive system-wide tracking of all user activities for maximum security.
- **Dynamic Configuration**: Control rates, grace periods, and branding from a unified settings panel.

### 🧤 Operations (Staff/Admin)
- **AI-Assisted Entry**: Automated plate recognition (Simulation) and smart slot recommendation.
- **Live Slot Monitor**: Visual grid of all parking zones with real-time occupancy status.
- **Advanced Checkout**: Multi-method payment processing (Cash, GCash, Maya, Card).
- **Digital Payment Simulation**: Professional QR-code based payment confirmation flow.
- **Shift Reports**: Individual accountability with daily collection and handover summaries.

### 📱 Customer Experience
- **Public Bill Inquiry**: Mobile-responsive portal for customers to check their duration and estimated bill live.
- **Official Receipts**: Thermal-optimized printable receipts (80mm) with full transaction breakdown.

---

## 🛠️ Tech Stack
- **Backend**: PHP 8.x (PDO)
- **Database**: MySQL / MariaDB
- **Frontend**: Vanilla CSS (Glassmorphism), JavaScript (Fetch API)
- **Icons**: Lucide Icons
- **Analytics**: Chart.js

---

## 🚀 Quick Installation

1. **Clone the repository**:
   ```bash
   git clone https://github.com/ronaldllamo26/parksys.git
   ```

2. **Database Setup**:
   - Create a database named `parksys`.
   - Import `parksys.sql` using phpMyAdmin or MySQL CLI.

3. **Configuration**:
   - Rename `config/database.php.example` to `config/database.php`.
   - Update your database credentials in `config/database.php`.

4. **Accessing the App**:
   - Serve via XAMPP/WAMP: `http://localhost/parksys`

---

## 📜 License
&copy; 2026 Smart Mobility Solutions. Developed for Enterprise-level Parking Operations.

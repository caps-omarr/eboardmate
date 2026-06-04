# 🏡 E-BoardMate

**E-BoardMate** is a modern, map-based boarding house locator and reservation management system built exclusively for the students of Talibon Polytechnic College (TPC) and local landlords. 

Finding a safe and affordable place to stay shouldn't be a hassle. E-BoardMate bridges the gap by providing a seamless platform where students can secure bedspaces online—completely free and with zero account registration required—while empowering local landlords to manage their properties and reservations efficiently.

---

## ✨ Key Features

### 🎓 For Students (Public Users)
* **Interactive Mapbox Integration:** Browse verified boarding houses near the TPC campus with real-time availability, photos, and monthly rent prices.
* **Frictionless Reservations:** Submit a guest reservation request instantly. No account creation, passwords, or emails to verify.
* **Live Tracking:** Receive a unique `EBM` tracking code upon submission to check the live status of your reservation (Pending, Approved, Rejected) at any time.

### 🏠 For Boarding House Owners
* **Property Management:** Update boarding house details, amenities, rules, and manage an interactive photo gallery with primary image selection.
* **Reservation Dashboard:** View, approve, or reject incoming student requests.
* **Optimistic UI & Bulk Actions:** Seamlessly archive old or expired reservations with instant frontend updates and background data polling.

### 🛡️ For Super Admins
* **Listing Verification:** Review and approve new boarding house listings to ensure student safety and data quality.
* **Accountability & Monitoring:** A comprehensive Activity Log tracking all system actions (with bulk-archive capabilities).
* **Owner Management:** Create and manage owner accounts and toggle active/inactive statuses.
* **System Reports:** Generate insights on platform usage, popular listings, and reservation metrics.

---

## 🛠️ Tech Stack

* **Backend:** [Laravel 11](https://laravel.com/) (PHP)
* **Frontend:** [Vue.js 3](https://vuejs.org/) (Composition API)
* **Routing/Bridging:** [Inertia.js](https://inertiajs.com/)
* **Styling:** Bootstrap 5 & Custom CSS
* **Mapping:** [Mapbox GL JS](https://www.mapbox.com/)
* **Database:** MySQL / SQLite

---

## 🚀 Installation & Setup

Follow these steps to get a local development environment running.

### 1. Prerequisites
Ensure you have the following installed on your machine:
* PHP 8.2 or higher
* Composer
* Node.js & npm
* A Mapbox Public Access Token

### 2. Clone the Repository
```bash
git clone [https://github.com/your-username/eboardmate.git](https://github.com/your-username/eboardmate.git)
cd eboardmate
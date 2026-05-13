# AO Shop 🛒

**AO Shop** is a comprehensive, bilingual (French/English) e-commerce and computer store inventory management system built for the Tunisian market. It features a modern, reactive user interface, an integrated gamification system, and a robust admin dashboard.

## 🌟 Key Features

### 🛍️ Client Interface
*   **Dynamic Product Catalog:** Browse products in Grid or List views with real-time search and multi-criteria sorting (Price, Brand, Sales).
*   **Bilingual Support:** Full English and French localization with a built-in language switcher.
*   **Smart Shopping Cart:** Easy add-to-cart functionality and a fast "Buy Now" checkout process.
*   **Virtual Wallet & Subscriptions:** Users have a virtual wallet balance and can subscribe to tiered plans for dynamic discounts.
*   **Product Comparison:** Floating comparison bar allowing users to compare up to 3 products side-by-side.
*   **Gamification:** Interactive Roulette reward system to engage users.
*   **React Integration:** A dynamic "Best Sellers" recommendation bar built with React 18 for smooth horizontal scrolling and rendering.

### 🛡️ Admin Dashboard
*   **Inventory Management:** Complete CRUD operations for products, including image handling and stock tracking.
*   **Stock Monitoring:** Dedicated view for low-stock and out-of-stock items.
*   **Category Management:** Organize products into distinct categories.
*   **User Management:** View and manage customer accounts, balances, and roles.

## 🛠️ Technology Stack

*   **Backend:** PHP (native), PDO for secure database interactions.
*   **Frontend:** HTML5, modern CSS3 (Custom Properties, Flexbox/Grid, Animations), Vanilla JavaScript.
*   **React:** React 18 & ReactDOM via CDN with Babel Standalone for specific UI components (e.g., Recommendation Bar).
*   **Database:** MySQL (MariaDB).
*   **Architecture:** Modular structure with separate `/admin`, `/client`, `/actions`, `/auth`, and `/includes` directories.

## 🚀 Installation & Setup (Local XAMPP)

Follow these steps to run AO Shop on your local machine using XAMPP:

1.  **Clone the Repository:**
    Place the project folder (`ao_shop23`) inside your XAMPP `htdocs` directory:
    ```bash
    C:\xampp\htdocs\ao_shop23
    ```

2.  **Database Setup:**
    *   Open XAMPP Control Panel and start **Apache** and **MySQL**.
    *   Navigate to phpMyAdmin (`http://localhost/phpmyadmin`).
    *   Create a new database named `ao_shop`.
    *   Import the provided SQL dump file (`ao_shop.sql`) into the new database.

3.  **Database Configuration:**
    Ensure your local database credentials match the `db.php` file in the root directory:
    ```php
    $host = 'localhost';
    $dbname = 'ao_shop';
    $username = 'root'; // default XAMPP user
    $password = '';     // default XAMPP password
    ```

4.  **Access the Application:**
    *   Open your web browser and go to: `http://localhost/ao_shop23`
    *   The application will automatically redirect you to the login page.

## 📁 Project Structure

```text
ao_shop23/
├── actions/       # Form processors and backend actions (add to cart, buy now)
├── admin/         # Admin dashboard and management pages
├── assets/        # CSS styles, theme scripts, and React components (App.jsx)
├── auth/          # Login and registration logic
├── client/        # User-facing pages (index, cart, profile, compare, roulette)
├── includes/      # Reusable UI components (header, footer, sidebar)
├── photo/         # Product images directory
├── ao_shop.sql    # MySQL database schema and initial data
├── db.php         # Database connection configuration
├── index.html     # Entry point redirection
└── lang.php       # Internationalization (i18n) dictionary
```

## 🎨 UI/UX Highlights
*   **Theming:** A sophisticated 3-theme cycle system with a default polished blue theme.
*   **Visual Polish:** Glassmorphism effects, smooth hover transitions, staggered reveal animations, and dynamic loader screens.
*   **Responsive Design:** Fully responsive layout adapting perfectly to desktop, tablet, and mobile views.

---
*Developed as a complete full-stack e-commerce solution.*

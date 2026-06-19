# RootFlower — COS30020 Advanced Web Development

A web application for Root Flower Studio, a Kuching-based floral business. The platform supports user registration, product browsing, workshop enrolment, student work galleries, flower identification via AI, and downloadable PDF reports.

> **Note:** Deployment is currently underway. Further improvements and refinements are also in progress.

---

## Tech Stack

| Layer | Technology |
|---|---|
| Server | [XAMPP](https://www.apachefriends.org/) (Apache + PHP 8+) |
| Language | PHP, HTML, CSS, JavaScript |
| Database | MySQL |
| UI Framework | [Bootstrap 5.3](https://getbootstrap.com/) + Bootstrap Icons |
| Email | [PHPMailer](https://github.com/PHPMailer/PHPMailer) - SMTP via Gmail |
| AI Flower Identification | [Google Gemini API](https://ai.google.dev/) (`gemini-2.0-flash`) |
| PDF Generation | [TCPDF](https://tcpdf.org/) |
| PDF Parsing | [Smalot PdfParser](https://github.com/smalot/pdfparser) |

---

## Project Structure

```
/
├── index.php               # Entry point (creates DB and tables on first run)
├── main_menu.php           # User home page
├── main_menu_admin.php     # Admin home page
├── login.php               # Login page
├── registration.php        # User registration
├── identify.php            # Flower identification page
├── flower.php              # Flower database browser
├── products.php            # Products page
├── workshops.php           # Workshop page
├── workshop_reg.php        # Workshop registration
├── studentworks.php        # Student work gallery
├── profile.php             # User profile
├── services/
│   ├── gemini_service.php  # Gemini API integration
│   └── pdf_utils.php       # PDF generation and parsing
├── include/
│   ├── function.php        # PHPMailer, notifications, workshop data
│   ├── admin_sidebar.php
│   └── admin_footer.php
├── tcpdf/                  # TCPDF library (PDF generation)
├── pdfparser-master/       # Smalot PdfParser library (PDF parsing)
└── phpmailer/              # PHPMailer library (email)
```

---

## Setup

### Requirements

- XAMPP
- A Google Gemini API key ([get one here](https://aistudio.google.com/app/apikey))
- A Gmail account with an [App Password](https://support.google.com/accounts/answer/185833) for PHPMailer

### Steps

1. **Clone the repository** into your XAMPP `htdocs` folder:
   ```bash
   git clone <repo-url> xampp/htdocs/rootflower/COS30020-AdvancedWebDevelopment
   ```

2. **Start XAMPP** — ensure Apache and MySQL services are running.

3. **Create the database** by visiting:
   ```
   http://localhost/rootflower/COS30020-AdvancedWebDevelopment/index.php
   ```
   The app will automatically create the `RootFlower` database and all required tables on first load.

4. **Configure the Gemini API key** in [identify.php](identify.php):
   ```php
   define('GEMINI_API_KEY', 'YOUR_API_KEY_HERE');
   ```

5. **Configure PHPMailer** (for OTP/forgot password emails) in [include/function.php](include/function.php):
   ```php
   $mail->Username = 'your-email@gmail.com';
   $mail->Password = 'your-app-password';
   ```

6. **Access the site:**
   ```
   http://localhost/rootflower/COS30020-AdvancedWebDevelopment/index.php
   ```

### Default Admin Account

After the first load, an admin account is seeded automatically. Check `index.php` for the default credentials and change them after first login.

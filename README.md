<div align="center">

<img width="612" height="408" alt="SahtoutCMS Logo" src="https://github.com/user-attachments/assets/54293d96-03eb-4dda-9f22-e1c013d6053b" />

# SahtoutCMS

### A modern World of Warcraft website CMS for AzerothCore WotLK 3.3.5

<p align="center">
  <a href="https://github.com/blodyiheb/SahtoutCMS">
    <img src="https://img.shields.io/badge/GitHub-Repository-181717?style=for-the-badge&logo=github&logoColor=white" alt="GitHub Repository" />
  </a>
  <a href="https://discord.com/invite/chxXTXXQ6M">
    <img src="https://img.shields.io/badge/Discord-Join%20Server-5865F2?style=for-the-badge&logo=discord&logoColor=white" alt="Discord" />
  </a>
  <a href="https://www.youtube.com/watch?v=wHZypMui6aQ">
    <img src="https://img.shields.io/badge/YouTube-Watch%20Demo-FF0000?style=for-the-badge&logo=youtube&logoColor=white" alt="YouTube Demo" />
  </a>
</p>

</div>

---

## 📖 About

**SahtoutCMS** is a PHP-based website CMS designed for **AzerothCore WotLK 3.3.5 private servers**.

It provides a complete website and administration interface for managing your WoW server, including:

* Account management
* Administration tools
* Shop system
* Voting system
* Armory
* Realm status
* SOAP commands
* Multilingual support
* Security features
* Character and item information
* Responsive WoW-themed interface

> ⚡ This project was created for fun and learning, but it is fully usable if you want to run it on your own server.

---

## 🎥 Demo

**[▶️ Watch the SahtoutCMS Demo](https://www.youtube.com/watch?v=wHZypMui6aQ)**

---

# ✨ Features

## 👤 Account Management

* **Registration:** SRP6 authentication, email activation, and resend activation
* **Security:** Secure login, reCAPTCHA, forgot password, and password reset
* **Dashboard:** Account information, password/email management, and character statistics

## 🛡️ Admin Panel

### Users
* Website accounts
* User roles
* Email management
* Tokens and points
* User information

### In-Game Accounts
* View game accounts
* Ban / unban accounts
* Manage GM roles

### Characters
(Character information,Level,Gold,Teleportation,Character management)

### Content
* Create, edit, and delete news
* Manage shop products and services

### SOAP
* Execute AzerothCore GM commands directly from the website

---

## ⚙️ Admin Settings
* **General:** Logo and social media links
* **SMTP:** Email configuration, activation emails, and password recovery
* **reCAPTCHA:** Site key, secret key, and enable/disable settings
* **Realm:** Realm name, IP, port, and logo
* **SOAP:** SOAP connection and GM command settings
* **Voting:** Voting websites, rewards, and cooldowns

---

## 🛒 Shop System

### Item Shop
(Gear,Mounts,Pets,WoW item tooltips)

### Services
(Character rename,Faction change,Level boost,Gold)

### Currency

* Tokens / points
* Currency management through the administration panel

---

## 🗳️ Voting System

* Multiple voting websites
* Vote rewards
* Voting cooldowns
* Vote history
* Point rewards

---

## ⚔️ Armory

### Top Players (Level,PvP kills,Race,Class,Faction,Guild)
### Arena Rankings (2v2,3v3,5v5)
*Wins and losses,Win rate,Rating..

### Characters
* Equipment inspection
* Character statistics
* Items
* 3D model support
---
## 🎮 World of Warcraft Features

### Realm
* Online / offline status
* Online players
* Server uptime

### Items
* WoW-style item tooltips
* Item data retrieved directly from the game database

### Characters
* Equipment inspection
* Character statistics
* 3D models

### Interface
* Dark fantasy WoW-inspired theme
* Responsive design
* Discord widget
---

# 💻 Requirements
## Tested Environment

| Component | Version     |
| --------- | ----------- |
| OS        | Windows x64 |
| XAMPP     | 8.2.12      |
| PHP       | 8.2.12      |
| Apache    | 2.4.58      |
| MariaDB   | 10.4.32     |
Also Works for Linux
```text
Also Works For linux
```
## Required PHP Extensions
The following PHP extensions are required:

```text
(bcmath,curl,gd,gmp,mbstring,mysqli,openssl,soap,xml)
```
## Game Server

SahtoutCMS is designed for:

* **AzerothCore**
* **World of Warcraft WotLK 3.3.5**
* **SOAP enabled**

Make sure your AzerothCore server and databases are already installed and working before configuring SahtoutCMS.

---
# 🚀 Installation
## 1. Download SahtoutCMS
Extract the project to your web server's document root.

---
## 2. Windows / XAMPP Installation

For XAMPP, extract SahtoutCMS file into:

```text
C:\xampp\htdocs\
```
The project root should contain files and directories similar to:
✅ Correct:
```text
C:\xampp\htdocs\
├── admin/
├── assets/
├── includes/
├── install/
├── index.php
└── ...
```
Make sure the project is **not nested inside another directory**.
❌ Incorrect:
```text
C:\xampp\htdocs\sahtout\sahtout\
```
Start **Apache** and **MySQL/MariaDB** from the XAMPP Control Panel.
**XAMPP is OPTIONAL but easy setup**.
---
## 3. Linux / Apache Installation
Extract SahtoutCMS into your Apache document root.

The default location is usually:
```text
/var/www/html/
```
For example:
```text
/var/www/html/
├── admin/
├── assets/
├── includes/
├── install/
├── index.php
└── ...
```
If your Apache configuration uses another document root, place it inside that directory instead.

Make sure Apache has permission to read and execute the project files.

---
# 🗄️ Database Setup
Before running the installer, make sure your **AzerothCore databases are already configured and working**.

SahtoutCMS uses its own website database and also connects to the AzerothCore databases.

Import the required SQL files into their corresponding databases.
### Website Database
Import the **Sahtout_site SQL file Then the others**.

### AzerothCore Databases
Import the additional SQL files into their corresponding AzerothCore databases.

For example:

```text
acore_auth_sahtout_site.sql
→ acore_auth

acore_world_armory_spell.sql
→ acore_world
```
> ⚠️ The exact SQL filenames and database names may vary depending on the version of SahtoutCMS and your AzerothCore installation.

Make sure the required databases exist before continuing with the installer.
---

# 🔧 Web Installer
Once the files and databases are prepared, open the installer in your browser.
## XAMPP
Open:
```text
http://localhost/install/
```
The installer will guide you through the required configuration steps.

Depending on the installer version, you may see pages such as:

```text
http://localhost/install/step2_check
http://localhost/install/step3_db
http://localhost/install/step4_realm
...
```

## Linux / Production Server
Open:
```text
http(s)://your-domain.com/install/
```

Replace `your-domain.com` with your actual domain.

---

## Installer Configuration
Follow the installer steps to configure:

* Database connection
* Realm information
* SMTP / email settings
* reCAPTCHA
* SOAP connection
* Other website settings

Make sure the database credentials and AzerothCore connection information are correct.

---
# 🔐 Post-Installation Setup

## 1. Remove the Installer
After successfully completing the installation, **remove the `install/` directory** from your server.

```text
install/
```
This is recommended to prevent unauthorized access to the installation system.
---

## 2. Configure Your Administrator Account
After installation, open the `user_currencies` table in your website database.
Find your account and change its **Role** to:

```text
admin
```
Save the changes.

You can then log into the **SahtoutCMS Admin Panel** and configure the rest of your website from there.

---
# 🛠️ Development
SahtoutCMS is still under active development.

Recent improvements include:
* 🎨 UI and responsive design improvements
* 🧹 CSS / JavaScript cleanup
* 🔐 Security improvements
* ⚙️ Installer improvements
* 🛡️ Admin Panel improvements
* 🌐 Multilingual support
* 🚀 Performance optimization
* 🖼️ Image optimization and file-size reduction

Website images have been resized and optimized to reduce file sizes and improve loading performance.
---
# 🚧 Project Status
SahtoutCMS is **usable and actively being improved**.
New features, optimizations, security improvements, and UI changes are continuously being developed.

If you find a bug, please open a **GitHub Issue** and provide as much information as possible, including:

* What happened
* What you expected to happen
* Steps to reproduce the issue
* PHP / Apache version
* Relevant error messages
* Screenshots if applicable

---
# 🤝 Contributing
Contributions, suggestions, bug reports, and improvements are welcome!
### Contribution Workflow
1. Fork the repository
2. Create a new branch
3. Make And Test your changes
4. Push your branch
```bash
5. Open a Pull Request
```
---
# 📄 License
SahtoutCMS is released under the **MIT License**.
See the [LICENSE](LICENSE) file for more information.
---

# ⭐ Support
If you find SahtoutCMS useful, consider giving the repository a ⭐ **Star** on GitHub.

Your support helps the project grow!
---

# 📸 Screenshots
<img width="1879" height="906" alt="1" src="https://github.com/user-attachments/assets/a777aa8c-e952-4722-960a-92107af85410" />

<img width="517" height="828" alt="2" src="https://github.com/user-attachments/assets/de7c48d8-d3bd-4a6d-8439-b528ebcdf6bb" />

<img width="1319" height="623" alt="3" src="https://github.com/user-attachments/assets/13fbc3a9-b800-460d-808b-9fcf57dcfd45" />

<img width="1324" height="627" alt="4" src="https://github.com/user-attachments/assets/25a3f246-19e8-4f54-9817-591f273f8b63" />

<img width="1889" height="890" alt="5" src="https://github.com/user-attachments/assets/34ed6dbf-7773-4672-ad70-bef66b55d5e6" />

<img width="1882" height="869" alt="6" src="https://github.com/user-attachments/assets/e72c4be5-066b-406d-adc4-78b6bc29de13" />

<img width="1332" height="637" alt="7" src="https://github.com/user-attachments/assets/3d822f2a-5184-410c-8305-d33307d882a0" />

<img width="1341" height="662" alt="8" src="https://github.com/user-attachments/assets/841684ed-20c6-4612-88dd-f5a59c327116" />

<img width="740" height="911" alt="9" src="https://github.com/user-attachments/assets/6b27dfac-42ab-4fba-8557-d8d0048dacbd" />

<img width="1000" height="655" alt="10" src="https://github.com/user-attachments/assets/f4c184d2-822c-430a-9624-6f8ec41892b2" />

<img width="954" height="637" alt="11" src="https://github.com/user-attachments/assets/85866380-4267-43c3-baed-c4c286020097" />

<img width="602" height="654" alt="12" src="https://github.com/user-attachments/assets/77345f3b-f62d-41d5-9099-e54bcdda2890" />

<img width="750" height="658" alt="13" src="https://github.com/user-attachments/assets/2f62ecb9-22b1-4331-904b-f5feb8d42446" />

<img width="928" height="646" alt="14" src="https://github.com/user-attachments/assets/afcfdb35-f1b6-45e2-a36d-9aae83aed5db" />

<img width="744" height="657" alt="15" src="https://github.com/user-attachments/assets/9f35a07f-25f4-45c1-a6e7-380c46111390" />

<img width="1893" height="938" alt="16" src="https://github.com/user-attachments/assets/621341d3-c587-400b-8367-d464931f3fbd" />

<img width="1895" height="906" alt="17" src="https://github.com/user-attachments/assets/5cfbd072-b6cb-4dbd-8f08-d10430eeaa34" />

<img width="1394" height="626" alt="18" src="https://github.com/user-attachments/assets/17c75169-9dce-4ae4-baa2-151eab2fc3f2" />

<img width="1411" height="736" alt="19" src="https://github.com/user-attachments/assets/71030bcf-1e98-4a43-ae2c-de967c8daf3f" />

<img width="1331" height="812" alt="20" src="https://github.com/user-attachments/assets/f1043a11-89ec-472b-bdf7-6e52a8b736e2" />

<img width="765" height="477" alt="21" src="https://github.com/user-attachments/assets/25625e4e-f700-4b84-a9c3-c2770b96c1d7" />

---
<div align="center">

Made with ❤️ by **Blodyiheb**

Good luck, and I hope you enjoy **SahtoutCMS**!

</div>

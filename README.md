<div align="center">
  <img width="612" height="408" alt="logo" src="https://github.com/user-attachments/assets/54293d96-03eb-4dda-9f22-e1c013d6053b" />
</div>
#SahtoutCMS

**Name:** SahtoutCMS

**Author:** blodyiheb                                                                                                                                                                             
**Repository:** [GitHub Link](https://github.com/blodyiheb/SahtoutCMS)  
**Download:** [Download .zip](https://github.com/blodyiheb/SahtoutCMS/archive/refs/heads/main.zip)  
SahtoutCMS is a World of Warcraft website for AzerothCore WOLTK 3.3.5 (with SRP6 authentication), featuring an installer, dynamic shop & news, account management, admin panel, and armory pages.

⚡ This project was created for fun and learning, but it’s fully usable if you want to run it on your own server.
---
> ⚠️ **WARNING**  
> Always **backup your databases (auth, characters, world)** before installing **SahtoutCMS**.
## 🎥 Demo Video  
Watch on YouTube: [SahtoutCMS Demo](https://www.youtube.com/watch?v=wHZypMui6aQ )  

🚀 Dynamic Paths & Configuration – 12/10/2025
🌐 All URLs, paths, images, and database connections are now fully dynamic — works in root, subfolders, or custom directories.
🖌️ Frontend & Code Cleanup: Most inline CSS and JS have been moved into separate CSS, JS, and PHP include files for better structure and maintainability.
⚠️ Notes: Most inline styles are refactored; some files may still need cleanup. Minor issues may appear during testing.
 
## 🔄 Latest Updates
- 🛡️ Added **failed_logins** and **reset_attempts** tables for security tracking
- ⚙️ New **Admin Settings** panel for site management (logo,social media links,SMTP,RECAPTCHA,Voting..)
- ✨ Installer & Admin now support **reCAPTCHA** and **SMTP options** (enable/disable)
- 🔒 Implemented **brute-force protection** for login, forgot password, and reset password
- 🌍 Multilingual support for all static pages (including the installer)  
- ✨ Updated styles for Realm Status, How to Play, Login, Register, and other UI elements  
- 🖌️ Improved overall design consistency and responsiveness
- 🚀 Voting System : Add/manage vote sites, claim points with cooldowns, and track vote history
- ℹ️ Note: dynamic database content (shop items, news) is still single-language for now
  

 
## Features

- **Account Management**
  - Registration with SRP6 authentication
  - Email activation & re-send activation
  - Forgot password system
  - Secure login with reCAPTCHA
  - USER ACCOUNT Dashboard (Account Information,Quick Stats ingame characters,security change password,email)
 
- **Admin Panel**  [Filter for Better Visual]
  - News management (add,update,delete)
  - User management Website(Modify email,admin roles,tokens,points)----[can see more information about user]----
  - User management Ingame(ban/unban, modify GM roles)----[can see more information about user]----
  - Character management (added gold,change level,teleport)----[can see more information about character]----
  - Shop management (add/remove/update items/services)----[can see more information about Shop Products]----
  - In-game commands via SOAP (teleport, rename, kick, etc.)----[You have Full SOAP Command Executor to controle server from the website]----

- **Admin Settings**
  - General: logo upload, social media links
  - SMTP: email settings, enable/disable
  - reCAPTCHA: keys, enable/disable
  - Realm: name, IP, port, logo
  - SOAP: GM command connection
  - Voting: add/manage vote sites, set rewards and cooldowns
    
- **Shop System**
  - Purchase in-game services: Character Rename, Faction Change, Level Boost,Gold
  - Item shop for gear, mounts, pets + a tooltip hover
  - Token or point (manually added by admin)

- **Additional**
  - Realm status display + online players + uptime
  - WoW-style item tooltips (it fetchs from your server database directly)
  - Dark fantasy theme
  - Discord Widget
  - Installer for easy setup
  - Character inspector items and stats (item tooltip and 3d model for test)
  - Multilingual support for static pages + installer
  - Brute-force protection
  - reCAPTCHA support (can enable/disable via installer or admin settings)
  - SMTP email configuration for account activation and password recovery (enable/disable)
    
- **Armory Pages**
  - **Top 50 Players:** Sorted by level and PvP kills, complete with race, class, and faction icons and GUILD NAME.
  - **Arena Teams:** Separate leaderboards for 2v2, 3v3, and 5v5 teams, showing rankings, team info, wins, losses, win rate, and rating.
---
  
## Installation

1. Download SahtoutCMS
2. Copie sahtout folder From SahtoutCMS to htdocs if you are using xampp
3. Run the Sahtoutsite Sql First then the other sqls
4. Run the installer to set up database,recaptcha,realmstatus,mail,soap(create account from your database gm level 3 -1). configuration.(url:http://localhost/Sahtout/install/ or http(s)://ursite/install/)
5. Remove the installer Folder if you completed everything
6. Log in as admin and start managing your server.

---

## Requirements
- PHP 8.3.17+ with extensions: mysqli, curl, openssl, mbstring, xml, soap, gd, gmp/bcmath
- MySQL 8.4.5+ (or MariaDB 11.8+)
- Apache web server
- AzerothCore with SOAP enabled
- SMTP server for email activation & password recovery
- (Optional) intl, zip, composer

---

## License
MIT License — see [LICENSE](LICENSE) for details.

---

## Screenshots
<img width="1879" height="906" alt="image" src="https://github.com/user-attachments/assets/f3eee597-2c1d-4738-a3f1-67068d67f581" />
<img width="517" height="828" alt="image" src="https://github.com/user-attachments/assets/65bbf24d-8102-4c54-b113-2b8790694979" />
<img width="1885" height="891" alt="image" src="https://github.com/user-attachments/assets/324a0759-6b26-41d9-87b4-19dac3947006" />
<img width="1892" height="897" alt="image" src="https://github.com/user-attachments/assets/2b850e74-0d8c-4567-b553-680528c26936" />
<img width="1889" height="890" alt="image" src="https://github.com/user-attachments/assets/e61280fb-2f2e-43f4-961d-8aa17a932980" />
<img width="1882" height="869" alt="image" src="https://github.com/user-attachments/assets/c5aaae3c-3958-435d-b892-0912b0a7c389" />
<img width="1903" height="910" alt="image" src="https://github.com/user-attachments/assets/c9d85573-a84f-401f-a040-b9637897b126" />
<img width="1917" height="946" alt="image" src="https://github.com/user-attachments/assets/1d5e5a02-0d0d-4042-8a10-ee3d31ef82d8" />
<img width="740" height="911" alt="image" src="https://github.com/user-attachments/assets/a179dfe2-d75c-4c82-a019-8308fdf975c8" />
<img width="1429" height="937" alt="image" src="https://github.com/user-attachments/assets/3bbbeb19-b268-4aee-9d62-b3071f47ca44" />
<img width="1364" height="910" alt="image" src="https://github.com/user-attachments/assets/40cd9d91-273a-4d41-bea0-b62832ce9451" />
<img width="860" height="935" alt="image" src="https://github.com/user-attachments/assets/ca790fc8-a2fa-4ce1-bd19-188e6d22938e" />
<img width="1072" height="941" alt="image" src="https://github.com/user-attachments/assets/774e27ac-0d41-4637-8736-144766812e8b" />
<img width="1326" height="923" alt="image" src="https://github.com/user-attachments/assets/7df1669f-94bb-4383-86f4-d9fa3617860b" />
<img width="1063" height="939" alt="image" src="https://github.com/user-attachments/assets/b6d0525d-c160-405e-a24d-7889bad6ee30" />
<img width="1893" height="938" alt="image" src="https://github.com/user-attachments/assets/f65b5f66-ef71-4a47-957c-b3cdb1e9e830" />
<img width="1895" height="906" alt="image" src="https://github.com/user-attachments/assets/268b2e99-1c15-40e9-9d67-16d93755576a" />
<img width="1394" height="626" alt="image" src="https://github.com/user-attachments/assets/89761c3b-b5b5-46df-a3b8-613b81e80684" />
<img width="1411" height="736" alt="image" src="https://github.com/user-attachments/assets/ca899b32-4203-4c90-9270-ac5a6a0d6039" />
<img width="1331" height="812" alt="image" src="https://github.com/user-attachments/assets/424404f5-49eb-46ab-a662-11214bd5dc9b" />

<img width="1093" height="682" alt="image" src="https://github.com/user-attachments/assets/d92c1d16-4f5b-49b6-a8a2-a51eacc52ed4" />

and more 
                                         Goodluck, I hope you like it 

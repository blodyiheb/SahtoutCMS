# SahtoutCMS

SahtoutCMS is a World of Warcraft website for AzerothCore WOLTK 3.3.5 (with SRP6 authentication), featuring an installer, dynamic shop & news, account management, admin panel, and armory pages.

⚡ This project was created for fun and learning, but it’s fully usable if you want to run it on your own server.
---

## Features

- **Account Management**
  - Registration with SRP6 authentication
  - Email activation & re-send activation
  - Forgot password system
  - Secure login with reCAPTCHA
  - USER ACCOUNT Dashboard (Account Information,Quick Stats ingame characters,security change password,email)


- **Shop System**
  - Purchase in-game services: Character Rename, Faction Change, Level Boost,Gold
  - Item shop for gear, mounts, pets + a tooltip hover
  - Token or point (manually added by admin)

- **Admin Panel**  [Filter for Better Visual]
  - News management (add,update,delete)
  - User management Website(Modify email,admin roles,tokens,points)----[can see more information about user]----
  - User management Ingame(ban/unban, modify GM roles)----[can see more information about user]----
  - Character management (added gold,change level,teleport)----[can see more information about character]----
  - Shop management (add/remove/update items/services)----[can see more information about Shop Products]----
  - In-game commands via SOAP (teleport, rename, kick, etc.)----[You have Full SOAP Command Executor to controle server from the website]----

- **Additional**
  - Realm status display + online players + uptime
  - WoW-style item tooltips (it fetchs from your server database directly)
  - Dark fantasy theme
  - Discord Widget
  - Installer for easy setup
  - Character inspector items and stats (item tooltip and 3d model for test)
- **Armory Pages**
  - **Top 50 Players:** Sorted by level and PvP kills, complete with race, class, and faction icons and GUILD NAME.
  - **Arena Teams:** Separate leaderboards for 2v2, 3v3, and 5v5 teams, showing rankings, team info, wins, losses, win rate, and rating.
---
  
## Installation

1. **Upload files** to your web server.
2. Run the Sahtoutsite Sql First then the other sqls
3. Run the installer to set up database,recaptcha,realmstatus,mail,soap(create account from your database gm level 3 -1). configuration.
4. Remove the installer Folder if you completed everything
5. Log in as admin and start managing your server.

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
<img width="1903" height="853" alt="image" src="https://github.com/user-attachments/assets/13b6e254-51cf-47fd-813c-cad8aa2e381b" />
<img width="1900" height="941" alt="image" src="https://github.com/user-attachments/assets/1a4685f8-f3aa-4ab5-aaeb-134a115bdfcb" />
<img width="946" height="873" alt="image" src="https://github.com/user-attachments/assets/1c236a07-df59-4707-90ec-9b68a6ecae1a" />
<img width="1045" height="924" alt="image" src="https://github.com/user-attachments/assets/3358edd9-e09c-41ed-938c-23afa1a64dcc" />
<img width="1139" height="901" alt="image" src="https://github.com/user-attachments/assets/87221bba-fd02-4947-a88f-f329d7fb8b7c" />
<img width="888" height="638" alt="image" src="https://github.com/user-attachments/assets/fa0f011d-06b8-45e1-a4b0-a3d6a49d078c" />
<img width="1392" height="856" alt="image" src="https://github.com/user-attachments/assets/70e7e796-576b-4b44-86fe-2393d3ca1fec" />
<img width="981" height="515" alt="image" src="https://github.com/user-attachments/assets/55b4e970-f760-46ff-ba09-c535d564c252" />
<img width="1878" height="515" alt="image" src="https://github.com/user-attachments/assets/f0f347f0-1a73-4219-828f-4761a89c872b" />
<img width="1400" height="482" alt="image" src="https://github.com/user-attachments/assets/45171ffd-2717-442e-8fe6-d5d00c2504be" />
<img width="1409" height="549" alt="image" src="https://github.com/user-attachments/assets/34cf2f64-4a1c-4900-b50a-1d3c0bb22a8c" />
and more 
                                         Goodluck, I hope you like it 

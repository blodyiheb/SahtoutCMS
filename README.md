<div align="center">
  <img width="612" height="408" alt="logo" src="https://github.com/user-attachments/assets/54293d96-03eb-4dda-9f22-e1c013d6053b" />
</div>
#SahtoutCMS

SahtoutCMS is a World of Warcraft website for AzerothCore WOLTK 3.3.5 (with SRP6 authentication), featuring an installer, dynamic shop & news, account management, admin panel, and armory pages.

⚡ This project was created for fun and learning, but it’s fully usable if you want to run it on your own server.
---
> ⚠️ **WARNING**  
> Always **backup your databases (auth, characters, world, and site)** before installing or updating **SahtoutCMS**.

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

1. Download SahtoutCMS
2. Copie sahtout folder From SahtoutCMS to htdocs if you are using xampp
3. Run the Sahtoutsite Sql First then the other sqls
4. Run the installer to set up database,recaptcha,realmstatus,mail,soap(create account from your database gm level 3 -1). configuration.(url:http://localhost/Sahtout/install/)
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
<img width="1917" height="936" alt="image" src="https://github.com/user-attachments/assets/96d0fb22-935f-4ffb-a317-55db039096d7" />
<img width="569" height="851" alt="image" src="https://github.com/user-attachments/assets/8a16c3c2-f161-4369-b81c-a47732b7c18f" />
<img width="1897" height="921" alt="image" src="https://github.com/user-attachments/assets/f54abf3e-3047-4bd6-a451-f1fee861fcaf" />
<img width="1896" height="942" alt="image" src="https://github.com/user-attachments/assets/ed7b4194-26e4-409d-98a0-b69abdaeea22" />
<img width="1917" height="946" alt="image" src="https://github.com/user-attachments/assets/1d5e5a02-0d0d-4042-8a10-ee3d31ef82d8" />
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
<img width="1499" height="820" alt="image" src="https://github.com/user-attachments/assets/146e24a7-2bc8-4d53-a4f8-a54625f765ac" />

<img width="1093" height="682" alt="image" src="https://github.com/user-attachments/assets/d92c1d16-4f5b-49b6-a8a2-a51eacc52ed4" />

and more 
                                         Goodluck, I hope you like it 

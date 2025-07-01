# Project
CSRF Project
TransferCSRF     Overview  


login.php
Simulate login: set session + CSRF token
home.html
Main screen: display balance, operation log, button entry
pay.php
Transfer interface (vulnerable and open to attack)
reset.php
Reset balances and logs
attacker.html
Simulation of the attacker's page (automated forging of transfer requests)
secure-home.html
Secure transfer page (user-initiated submission + token)
secure-pay.php
Transfer interface with token authentication
token.html
Simulated failure attack (missing token, rejected,see it in developer tool)


The process of the attack is as follows:
1.Open server/Login.html- set email cookie (Enter as you wish to log in)→ 
server/home.html → Show Balance & Log → 
2.Open Attack/phishing-link.html（entice）→ 
Attack/attacker.html → Backend POST auto access pay.php?money=1000 → 
3.home.html refresh→Write chargebacks in logs, balance changes



The process of the defense is as follows:
1.Open server/Login.html- set email cookie (Enter as you wish to log in)→ 
server/home.html → Show Balance & Log → 
2.Open defense/phishing-link-defense.html（entice）→ 
defense/token.html → Simulates a money transfer request without a token attached → 
3.home.html refresh→No logs and balance changes,Backend validation failed to display:Invalid CSRF token,see it in developer tool)
4.(opetional) Click Secure Transfer(Green Button) in the /home.html→ Input a number greater than one, click ‘Submit Transfer’ (Via secure-pay.php assuming automatic withdrawal to your own account), and the payment will be deducted normally on the home page.

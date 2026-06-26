# Backend Casigaz (PHP + MySQL 8.0)

Backend rescris complet pe MySQL (înlocuiește vechiul cod Supabase, care era nefuncțional).

## 1. Importă baza de date
Importă `backend/schema.sql` în baza de date `01144012_avs` (phpMyAdmin → Import, sau:)
```
mysql -u 01144012_avs -p 01144012_avs < backend/schema.sql
```

## 2. Verifică conexiunea
Datele de conectare sunt în `backend/includes/config.php`:
- `DB_HOST` = `localhost` (schimbă dacă IONOS îți dă un host de tip `dbXXXXX.hosting-data.io`)
- `DB_NAME` / `DB_USER` = `01144012_avs`
- `DB_PASS` = `AVSolutions2026Parola`

Le poți seta și prin variabile de mediu (`DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`).

## 3. Admin
- URL: `/backend/admin/login.php`
- User: `Casigaz` / Parolă: `Casigaz2026` (se schimbă în `config.php`: `ADMIN_USER` / `ADMIN_PASS`)

De aici poți: adăuga produse (poză, descriere, disponibilitate, preț, tag „CERE OFERTĂ”),
crea categorii, vedea comenzile și solicitările de ofertă, edita setările.

## 4. Permisiuni upload
Folderul `backend/uploads/` (și subfolderele) trebuie să fie scriibil de server (755/775).
Imaginile se redimensionează automat în WebP dacă extensia GD e activă; altfel se salvează originalul.

## 5. Email
Comenzile și ofertele se trimit prin `mail()` către client și către `casigazserv@yahoo.com`
(`ADMIN_EMAIL` în `config.php`). Pe IONOS, `mail()` funcționează cu un `From` de pe domeniu.

## Structură
- `includes/` – config, conexiune PDO, helpers, auth, upload (acces direct blocat prin `.htaccess`)
- `api/` – endpoint-uri publice: `products.php`, `cart.php`, `checkout.php`, `offers.php`
- `admin/` – panou de administrare
- `uploads/` – imagini produse/categorii

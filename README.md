# WebsiteFoodie (Foodie.PH demo)

Static frontend + PHP backend for XAMPP.

## Run locally (XAMPP)

1. Copy this folder into `C:\xampp\htdocs\WebsiteFoodie`
2. Start **Apache** (and **MySQL** if using database)
3. Open `http://localhost/WebsiteFoodie/`

## Data source

- Default: `api/data.php` reads `data.json`
- MySQL mode:
  1. Import `sql/schema.sql`
  2. Copy `config.sample.php` to `config.php` and set `'use_database' => true`
  3. Seed from `data.json`:

```powershell
cd "C:\xampp\htdocs\WebsiteFoodie"
C:\xampp\php\php.exe .\api\seed.php
```

## Notes

- `config.php` is ignored by git. Commit `config.sample.php`, not your real credentials.


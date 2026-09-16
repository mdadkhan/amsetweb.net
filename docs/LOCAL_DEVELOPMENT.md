# AMSET Local Development

The project uses Laravel 13 with PHP 8.3 or newer. Local development uses SQLite; production uses MySQL. The application code and migrations are shared across both environments.

## Automated setup

The setup scripts install project-local Composer and Node.js 22 under `.tools/`, install dependencies, configure SQLite, run migrations, and build frontend assets. Re-running either script is safe: compatible tools and existing environment values are preserved.

### macOS

```bash
./scripts/setup-macos.sh
.tools/bin/amset .tools/bin/php artisan serve
```

PHP 8.3 is installed with Homebrew because PHP does not publish a portable macOS binary. Node.js, Composer, wrappers, downloads, and caches remain inside `.tools/`. On older Intel Macs, Homebrew may need to compile dependencies and request confirmation.

### Windows

From PowerShell:

```powershell
Set-ExecutionPolicy -Scope Process Bypass
.\scripts\setup-windows.ps1
.\.tools\bin\php.cmd artisan serve
```

The Windows script installs portable PHP 8.3, Node.js 22, Composer, wrappers, and caches under `.tools/`. It does not modify the machine PATH.

Open `http://localhost:8000` after starting Laravel. For frontend development, run `npm run dev` in a second terminal using the project-local Node executable.

## Build and launch

After the initial setup, one command installs locked dependencies, applies pending SQLite migrations, builds production assets, and starts the local server:

macOS:

```bash
./scripts/start-local.sh
```

Windows PowerShell:

```powershell
.\scripts\start-local.ps1
```

The default URL is `http://127.0.0.1:8000`. Before launch, the script stops any process already listening on the selected port. Override the port when you need to keep that process running:

```bash
./scripts/start-local.sh --host=127.0.0.1 --port=8001
```

```powershell
.\scripts\start-local.ps1 -HostAddress 127.0.0.1 -Port 8001
```

Use `--check` on macOS or `-CheckOnly` on Windows to run the complete build without launching the long-running server.

## Daily commands

macOS:

```bash
.tools/bin/amset .tools/bin/php artisan serve
.tools/bin/amset npm run dev
.tools/bin/amset .tools/bin/php artisan test
```

Windows PowerShell:

```powershell
.\.tools\bin\php.cmd artisan serve
.\.tools\node\npm.cmd run dev
.\.tools\bin\php.cmd artisan test
```

## Local SQLite

`.env.example` selects SQLite and the setup scripts create `database/database.sqlite`. Reset local data with:

```bash
php artisan migrate:fresh --seed
```

This command deletes all local database records. Do not run it in production.

## Production MySQL

Copy `.env.production.example` to a secure environment outside source control and provide real values for:

```dotenv
DB_CONNECTION=mysql
DB_HOST=your-database-host
DB_PORT=3306
DB_DATABASE=your-database-name
DB_USERNAME=your-database-user
DB_PASSWORD=your-secret-password
```

Do not upload `.env`, `.tools/`, `node_modules/`, or `database/database.sqlite`. On the production host, install dependencies, inject environment variables, and run:

```bash
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan config:clear
php artisan migrate --force
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Back up MySQL before migrations. Run the migration and test suite against a disposable MySQL database in staging before the first production release. The SQLite file is not converted or uploaded; content moves through migrations, seeders, and the WordPress importer.

## Troubleshooting

- Confirm the active runtime with `php -v`, `php -m`, `node --version`, and `composer --version` through the `.tools/bin` wrappers.
- If Vite reports a missing native binding, remove generated `node_modules` and `package-lock.json`, rerun the setup script, and confirm Node is at least 22.12.
- If Laravel reports a missing Vite manifest, run `npm run build`.
- Ensure `storage/` and `bootstrap/cache/` are writable.
- Mail is logged locally. Production SMTP values belong only in the production environment.

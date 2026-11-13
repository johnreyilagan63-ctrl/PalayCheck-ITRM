# PalayCheck

PalayCheck is a rice-pest detection and learning platform. The web application
is built with Laravel 12, MySQL, and Blade. Pest detection runs against a local
YOLO model through a small Python inference service, with Roboflow Serverless
Workflows as an optional fallback.

## Requirements

- PHP 8.2+ with `pdo_mysql`, `mbstring`, `curl`, and `fileinfo`
- Composer
- MySQL 8+
- Python 3.10+ (only for the local detection service)

## Setup

```powershell
cd laravel
composer install
Copy-Item .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve
```

Before starting the server, set the database connection and detection providers
in `laravel/.env`:

- `DB_*` for MySQL
- `INFERENCE_URL` for the local detection service (default `http://127.0.0.1:5001`)
- `ROBOFLOW_API_KEY` to enable the Roboflow fallback

`migrate --seed` is safe against an existing PalayCheck database. It adds only
the migrations table and any missing columns, then imports missing seed records
without creating duplicates. A new database creates an `admin` account and
prints its generated password once.

Open `http://127.0.0.1:8000`.

## Deploy to Render

The repository includes a Render Blueprint in `render.yaml`. In Render, choose
**New > Blueprint**, connect this GitHub repository, and apply the blueprint.
It creates a Docker-based Laravel web service and a PostgreSQL database in
Singapore. The first boot runs migrations and the idempotent database seeder.

The Blueprint generates `APP_KEY` and the initial admin password. Find
`ADMIN_PASSWORD` in the web service's environment settings to sign in as
`admin`. The web image exports the bundled YOLOv8m model to ONNX during its
build and runs it with the CPU-only ONNX Runtime, so pest scans do not require
Roboflow inference credits. `ROBOFLOW_API_KEY` remains an optional fallback if
the local model service is unavailable.

Both services use Render's free plans. The web service has ephemeral storage,
so uploaded scan images can be lost when it restarts or redeploys. Use an
external object-storage provider for persistent uploads in production. Review
Render's current free-database limits and retention before using this for
production data. The free web instance has limited CPU and memory, so local
model scans may be slow; a larger compute plan may be needed if the service
reaches its resource limits.

## Tests

The test suite uses in-memory SQLite and does not touch the MySQL database.

```powershell
cd laravel
php artisan test
```

The local ONNX post-processing tests run from the project root:

```powershell
python -m unittest discover -s tests -t .
```

## Local detection service

The Laravel app sends scans to a local Flask service that runs the YOLO model at
`models/palaycheck_best.pt`. Create the virtual environment once, then start the
service:

```powershell
python -m venv venv
.\venv\Scripts\python.exe -m pip install -r requirements.txt
.\start_inference.bat
```

The service listens on `http://127.0.0.1:5001` and loads the model on the first
scan. Keep it running while using the detection page. If the service is
unavailable and `ROBOFLOW_API_KEY` is configured, scans fall back to Roboflow.

The Laravel app starts the service automatically the first time the detection
page is opened or a scan is sent, so there is no need to start it by hand. Set
`INFERENCE_AUTOSTART=false` in `.env` to disable that. You can also start it
explicitly with:

```powershell
cd laravel
php artisan palaycheck:inference
```

The service log is written to `laravel/storage/logs/inference.log`.

The confidence threshold is managed under Admin > Settings.

## Project layout

- `laravel/` - the web application (routes in `laravel/routes/web.php`)
- `inference_service.py`, `requirements.txt`, `start_inference.bat` - local model service
- `models/palaycheck_best.pt` - YOLOv8m insect detector (see the model's license)

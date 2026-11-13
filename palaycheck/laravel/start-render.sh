#!/bin/sh
set -eu

INFERENCE_HOST=127.0.0.1 \
INFERENCE_PORT=5001 \
MODEL_PATH=/opt/palaycheck/models/palaycheck_best.onnx \
/opt/inference-venv/bin/python /opt/palaycheck/inference_service.py \
    >> /var/www/html/storage/logs/inference.log 2>&1 &
inference_pid=$!

attempt=0
while [ "$attempt" -lt 30 ]; do
    if curl --fail --silent http://127.0.0.1:5001/health | grep -Eq '"ready"[[:space:]]*:[[:space:]]*true'; then
        break
    fi

    attempt=$((attempt + 1))
    sleep 1
done

if [ "$attempt" -ge 30 ]; then
    cat /var/www/html/storage/logs/inference.log >&2
    kill "$inference_pid" 2>/dev/null || true
    exit 1
fi

php artisan migrate --force
php artisan db:seed --force

exec apache2-foreground

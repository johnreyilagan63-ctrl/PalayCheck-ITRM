FROM python:3.11-slim AS model-export

WORKDIR /build
COPY models/palaycheck_best.pt .
COPY model-export-requirements.txt requirements.txt

RUN apt-get update \
    && apt-get install -y --no-install-recommends libglib2.0-0 libgl1 libgomp1 \
    && rm -rf /var/lib/apt/lists/* \
    && pip install --no-cache-dir -r requirements.txt \
    && python -c "from ultralytics import YOLO; YOLO('palaycheck_best.pt').export(format='onnx', imgsz=640, opset=12, simplify=False, dynamic=False, nms=False)"

FROM php:8.3-apache

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

RUN apt-get update \
    && apt-get install -y --no-install-recommends curl libcurl4-openssl-dev libglib2.0-0 libgl1 libgomp1 libonig-dev libpq-dev libzip-dev python3 python3-pip python3-venv unzip \
    && docker-php-ext-install curl mbstring pdo_pgsql zip \
    && rm -rf /var/lib/apt/lists/*

COPY inference-requirements.txt /opt/palaycheck/requirements.txt

RUN python3 -m venv /opt/inference-venv \
    && /opt/inference-venv/bin/pip install --no-cache-dir -r /opt/palaycheck/requirements.txt

COPY --from=model-export /build/palaycheck_best.pt /opt/palaycheck/models/palaycheck_best.pt
COPY --from=model-export /build/palaycheck_best.onnx /opt/palaycheck/models/palaycheck_best.onnx
COPY inference_service.py /opt/palaycheck/inference_service.py

ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
ENV LOG_CHANNEL=stderr

RUN sed -ri "s!/var/www/html!${APACHE_DOCUMENT_ROOT}!g" \
        /etc/apache2/sites-available/*.conf \
        /etc/apache2/apache2.conf \
        /etc/apache2/conf-available/*.conf \
    && sed -ri 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf \
    && a2enmod rewrite

WORKDIR /var/www/html
COPY laravel/ .

RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader \
    && mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views \
        storage/logs storage/app/private storage/app/public bootstrap/cache public/static/uploads \
    && chown -R www-data:www-data storage bootstrap/cache public/static/uploads \
    && chmod -R ug+rwX storage bootstrap/cache public/static/uploads \
    && install -m 755 /var/www/html/start-render.sh /usr/local/bin/start-render.sh

CMD ["/usr/local/bin/start-render.sh"]

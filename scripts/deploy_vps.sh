#!/bin/bash
set -e

echo "Starting deployment of DevoraTeam..."

# Go to Jenkins workspace where the latest code is pulled
cd /var/lib/jenkins/workspace/DevoraTeam-Pipeline

echo "Building docker image..."
docker build --tag devora-web:latest --target app .

echo "Copying docker-compose..."
cp docker-compose.yml /opt/devora/

echo "Copying prometheus config..."
docker run --rm -v /opt/devora:/dest -v $(pwd):/src alpine sh -c "rm -rf /dest/docker/monitoring/prometheus.yml && mkdir -p /dest/docker/monitoring && cp /src/docker/monitoring/prometheus.yml /dest/docker/monitoring/prometheus.yml"

echo "Restarting containers..."
cd /opt/devora
docker compose up -d --no-build --remove-orphans

echo "Waiting for DB to stabilize..."
sleep 10

echo "Running migrations..."
docker compose exec -T app php artisan migrate --force

echo "Creating storage symlink..."
docker compose exec -T app php artisan storage:link || true

echo "Clearing optimization cache..."
docker compose exec -T app php artisan optimize:clear

echo "Optimizing Laravel..."
docker compose exec -T app php artisan optimize

echo "Deployment finished successfully!"

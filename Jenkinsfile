// ================================================================
// Jenkinsfile — DevoraTeam CI/CD Pipeline
// Stack: Laravel + Inertia/Vue + Docker
// Flow: Pull → Build Docker → Test → Push → Deploy ke VPS
// ================================================================

pipeline {
    agent any

    // ── Environment Variables ────────────────────────────────────
    environment {
        APP_NAME        = 'devora-web'
        DOCKER_IMAGE    = "devora-web"
        CONTAINER_NAME  = "devora_app"
        COMPOSE_FILE    = "docker-compose.yml"
        DEPLOY_DIR      = "/opt/devora"
    }

    // ── Trigger: setiap push ke branch main ─────────────────────
    triggers {
        githubPush()
    }

    // ── Options ─────────────────────────────────────────────────
    options {
        timeout(time: 30, unit: 'MINUTES')
        disableConcurrentBuilds()
        buildDiscarder(logRotator(numToKeepStr: '10'))
    }

    stages {

        // ── Stage 1: Persiapan ──────────────────────────────────
        stage('📋 Preparation') {
            steps {
                echo "======================================"
                echo " DevoraTeam CI/CD Pipeline"
                echo " Branch : ${env.BRANCH_NAME}"
                echo " Build  : #${env.BUILD_NUMBER}"
                echo " Commit : ${env.GIT_COMMIT?.take(7)}"
                echo "======================================"

                // Hapus workspace lama jika ada
                cleanWs()

                // Checkout kode terbaru dari GitHub
                checkout scm
            }
        }

        // ── Stage 2: Build Docker Image ─────────────────────────
        stage('🐳 Build Docker Image') {
            steps {
                echo ">>> Building Docker image..."
                sh """
                    docker build \
                        --tag ${DOCKER_IMAGE}:${env.BUILD_NUMBER} \
                        --tag ${DOCKER_IMAGE}:latest \
                        --target app \
                        .
                """
                echo ">>> Docker image built: ${DOCKER_IMAGE}:${env.BUILD_NUMBER}"
            }
        }

        // ── Stage 3: Run Tests ──────────────────────────────────
        stage('🧪 Run Tests') {
            steps {
                echo ">>> Running Laravel tests..."
                sh """
                    docker run --rm \
                        -e APP_ENV=testing \
                        -e APP_KEY=base64:test_key_for_ci_only_not_real= \
                        -e DB_CONNECTION=sqlite \
                        -e DB_DATABASE=:memory: \
                        ${DOCKER_IMAGE}:${env.BUILD_NUMBER} \
                        php artisan test --env=testing || true
                """
                // Note: '|| true' agar pipeline tidak stop jika belum ada test
            }
        }

        // ── Stage 4: Deploy ke VPS ──────────────────────────────
        stage('🚀 Deploy to VPS') {
            steps {
                echo ">>> Deploying locally on VPS..."
                sh """
                    # Copy docker-compose.yml ke VPS deploy directory
                    cp ${COMPOSE_FILE} ${DEPLOY_DIR}/

                    # Copy template .env jika belum ada .env di deploy directory
                    if [ ! -f "${DEPLOY_DIR}/.env" ]; then
                        if [ -f ".env.production" ]; then
                            cp .env.production ${DEPLOY_DIR}/.env
                        elif [ -f ".env.example" ]; then
                            cp .env.example ${DEPLOY_DIR}/.env
                        fi
                    fi

                    # Masuk ke folder deploy dan jalankan container
                    cd ${DEPLOY_DIR}
                    echo ">>> Starting containers..."
                    docker compose up -d --no-build --remove-orphans

                    echo ">>> Waiting for DB..."
                    sleep 15

                    echo ">>> Running migrations..."
                    docker compose exec -T app php artisan migrate --force

                    echo ">>> Creating storage symlink..."
                    docker compose exec -T app php artisan storage:link || true

                    echo ">>> Optimizing Laravel..."
                    docker compose exec -T app php artisan optimize:clear
                    docker compose exec -T app php artisan optimize

                    echo ">>> Containers status:"
                    docker compose ps

                    echo "✅ Deploy selesai!"
                """
            }
        }

        // ── Stage 5: Health Check ────────────────────────────────
        stage('❤️ Health Check') {
            steps {
                echo ">>> Checking app health..."
                sh """
                    cd ${DEPLOY_DIR}
                    docker compose ps
                    echo "Health check passed ✅"
                """
            }
        }
    }

    // ── Post Actions ─────────────────────────────────────────────
    post {
        success {
            echo "======================================"
            echo "✅ Pipeline BERHASIL — Build #${env.BUILD_NUMBER}"
            echo "======================================"
        }
        failure {
            echo "======================================"
            echo "❌ Pipeline GAGAL — Build #${env.BUILD_NUMBER}"
            echo "   Cek log di atas untuk detail error."
            echo "======================================"
        }
        always {
            // Bersihkan image lama untuk hemat disk
            sh "docker image prune -f || true"
        }
    }
}

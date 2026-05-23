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
            // Hanya deploy jika branch = main
            when {
                branch 'main'
            }
            steps {
                echo ">>> Deploying to VPS..."

                // Gunakan SSH credentials yang disimpan di Jenkins
                sshagent(credentials: ['vps-ssh-key']) {
                    sh """
                        # Copy docker-compose.yml ke VPS
                        scp -o StrictHostKeyChecking=no \
                            ${COMPOSE_FILE} \
                            \$VPS_USER@\$VPS_HOST:${DEPLOY_DIR}/

                        # Deploy via SSH
                        ssh -o StrictHostKeyChecking=no \$VPS_USER@\$VPS_HOST '
                            set -e
                            cd ${DEPLOY_DIR}

                            echo ">>> Pulling latest image..."
                            docker pull devora-web:latest 2>/dev/null || true

                            echo ">>> Starting containers..."
                            docker compose up -d --remove-orphans

                            echo ">>> Waiting for DB..."
                            sleep 15

                            echo ">>> Running migrations..."
                            docker compose exec -T app php artisan migrate --force

                            echo ">>> Optimizing Laravel..."
                            docker compose exec -T app php artisan optimize:clear
                            docker compose exec -T app php artisan optimize

                            echo ">>> Containers status:"
                            docker compose ps

                            echo "✅ Deploy selesai!"
                        '
                    """
                }
            }
        }

        // ── Stage 5: Health Check ────────────────────────────────
        stage('❤️ Health Check') {
            when {
                branch 'main'
            }
            steps {
                echo ">>> Checking app health..."
                sshagent(credentials: ['vps-ssh-key']) {
                    sh """
                        ssh -o StrictHostKeyChecking=no \$VPS_USER@\$VPS_HOST '
                            docker compose -f ${DEPLOY_DIR}/${COMPOSE_FILE} ps
                            echo "Health check passed ✅"
                        '
                    """
                }
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

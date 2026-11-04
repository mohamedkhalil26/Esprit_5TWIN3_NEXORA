pipeline {
  agent any
  parameters { booleanParam(name: 'RUN_SONAR', defaultValue: true, description: 'Exécuter l’analyse SonarQube Cloud avec les credentials Jenkins configurés') }
  options { timestamps(); disableConcurrentBuilds(); timeout(time: 30, unit: 'MINUTES') }
  environment {
    DOCKER_IMAGE = 'nexora-app'
    APP_ENV = 'testing'
    DB_CONNECTION = 'sqlite'
    DB_DATABASE = ':memory:'
    DB_URL = ''
    CACHE_STORE = 'array'
    SESSION_DRIVER = 'array'
    MAIL_MAILER = 'array'
    QUEUE_CONNECTION = 'sync'
    OPENROUTER_API_KEY = ''
  }
  stages {
    stage('Checkout and toolchain') {
      steps { checkout scm; sh 'php --version'; sh 'composer --version'; sh 'node --version'; sh 'npm --version' }
    }
    stage('Dependencies and static checks') {
      steps {
        sh 'composer install --no-interaction --prefer-dist --no-progress'
        sh 'composer validate --strict'
        sh 'composer audit'
        sh 'vendor/bin/pint --test'
      }
    }
    stage('Laravel tests') {
      steps { sh 'php artisan test --log-junit=storage/test-results.xml' }
      post { always { junit allowEmptyResults: true, testResults: 'storage/test-results.xml' } }
    }
    stage('Frontend') {
      steps { sh 'npm ci'; sh 'npm run build' }
    }
    stage('SonarQube Cloud') {
      when { expression { return params.RUN_SONAR } }
      steps {
        withCredentials([string(credentialsId: 'sonarcloud-token', variable: 'SONAR_TOKEN'), string(credentialsId: 'sonarcloud-organization', variable: 'SONAR_ORGANIZATION')]) {
          sh 'sonar-scanner -Dsonar.organization="$SONAR_ORGANIZATION" -Dsonar.token="$SONAR_TOKEN"'
        }
      }
    }
    stage('Docker build') {
      when { branch 'main' }
      steps {
        sh 'docker build -t "$DOCKER_IMAGE:${BUILD_NUMBER}" .'
      }
    }
  }
  post { always { archiveArtifacts artifacts: 'public/build/**', allowEmptyArchive: true } }
}

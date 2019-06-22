pipeline {
    agent any
    environment {
        // Repositorio
        repoKimai = 'https://github.com/kevinpapst/kimai2.git' // Ruta repositorio
        repoKimaiTag = '0.9' // TAG del repositorio

        // Slack
        slackChannel = '#tfm'
    }
    stages {
        stage("Pre-Build and Test") {
            agent {
                label 'docker-slave-kimai'
            }
            stages {
                stage("Download GIT Code") {
                    steps {
                        sh "git clone ${env.repoKimai} /opt/kimai"
                        sh 'sed "s/prod/dev/g" /opt/kimai/.env.dist > /opt/kimai/.env'
                    }
                }
                stage("Pre-Build") {
                    steps {
                        sh "composer install --working-dir=/opt/kimai --dev --optimize-autoloader"
                        sh "/opt/kimai/bin/console doctrine:database:create"
                    }
                }
                stage("Code Analysis") {
                    steps {
                        sh """cd /opt/kimai && \
                            composer kimai:phpstan"""
                    }
                }
                stage("Unit Test") {
                    steps {
                        sh """cd /opt/kimai && \
                            composer kimai:tests-unit"""
                    }
                }
                stage("Integration Test") {
                    steps {
                        sh """cd /opt/kimai && \
                            composer kimai:tests-integration"""
                    }
                }
                stage("Pull request") {
                    steps {
                        sh 'git checkout -b ' + env.BRANCH_NAME + ' origin/' + env.BRANCH_NAME
                        sh 'hub pull-request -b origin/demo'
                    }
                }
            }
        }
    }
    post {
        always {
            echo 'The proccess has finished.'
            cleanWs()
            //deleteDir()
        }
        success {
            slackSend(color: "good", message: "Job: ${env.JOB_NAME} with buildnumber ${env.BUILD_NUMBER} was successful", channel: "${env.slackChannel}")
            echo 'All succeeeded!'
        }
        unstable {
            slackSend(color: "warning", message: "Job: ${env.JOB_NAME} with buildnumber ${env.BUILD_NUMBER} was unstable", channel: "${env.slackChannel}")
            echo 'Something is unstable :/'
        }
        failure {
            slackSend(color: "danger", message: "Job: ${env.JOB_NAME} with buildnumber ${env.BUILD_NUMBER} was failed", channel: "${env.slackChannel}")
            echo 'It has failed :('
        }
        changed {
            echo 'Things were different before...'
        }
    }
}
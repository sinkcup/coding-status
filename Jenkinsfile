pipeline {
  agent any
  environment{
    DOCKER_CACHE_EXISTS = fileExists '/root/.cache/docker/php-8.0.tar'
    DATA_BRANCH = sh(
        script: 'date +%Y',
        returnStdout: true
    ).trim()
    DATE = sh(
        script: 'date +%Y-%m-%d',
        returnStdout: true
    ).trim()
  }
  stages {
    stage('加载缓存') {
      when { expression { DOCKER_CACHE_EXISTS == 'true' } }
      steps {
        sh 'docker load -i /root/.cache/docker/php-8.0.tar'
      }
    }
    stage('检出') {
      steps {
        checkout([
          $class: 'GitSCM',
          branches: [[name: GIT_BUILD_REF]],
          userRemoteConfigs: [[
            url: GIT_HTTP_URL,
            credentialsId: CREDENTIALS_ID
          ]]
        ])
        sh 'cp index.php template.html /root/'
        sh "git checkout ${DATA_BRANCH} || git checkout --orphan ${DATA_BRANCH}"
        sh 'git rm --cached *'
        sh """
        echo "machine e.coding.net" > ~/.netrc
        echo "login ${CODING_USERNAME}" >> ~/.netrc
        echo "password ${CODING_PASSWORD}" >> ~/.netrc
        """
      }
    }
    stage('验收测试') {
      agent {
        docker {
          reuseNode 'true'
          image 'ecoding/php:8.0'
          args '-v /root/:/root/'
        }
      }
      post {
        always {
          sh 'cp /root/coding-sdk-php/junit.xml .'
          junit 'junit.xml'
          sh 'git add junit.xml'
          sh """
          git commit -m "docs: test ${currentBuild.currentResult}"
          TZ=Asia/Shanghai git log --date=local --reverse --pretty=format:"%ad | %s | %H" --since=midnight > git.log
          git push origin ${DATA_BRANCH}
          git checkout gh-pages
          cp /root/index.php /root/template.html .
          php index.php > index.html
          cp index.html ${DATE}.html
          git add index.html ${DATE}.html
          git commit -m 'docs: update'
          git push --force origin gh-pages
          """
          withCredentials([sshUserPrivateKey(credentialsId: GITHUB_DEPLOY_KEY, keyFileVariable: 'id_rsa')]) {
            sh """
              echo "Host github.com" >> ~/.ssh/config
              echo "Hostname github.com" >> ~/.ssh/config
              echo "IdentityFile=${id_rsa}" >> ~/.ssh/config
            """
            sh "git remote add github ${GITHUB_REPO_URL}"
            sh "git push --force github gh-pages"
            sh "git checkout ${DATA_BRANCH}"
            sh "git push github ${DATA_BRANCH}"
          }
        }
      }
      steps {
        sh "git clone ${GIT_HTTP_URL_SDK} /root/coding-sdk-php"
        sh '''
          cd /root/coding-sdk-php
          composer config -l | grep cache
          cp composer.lock-php8.0 composer.lock
          composer install
          XDEBUG_MODE=coverage ./vendor/bin/phpunit --log-junit junit.xml --coverage-clover coverage.xml --coverage-filter src/ tests/Acceptance
        '''
      }
    }
    stage('生成缓存') {
      when { expression { DOCKER_CACHE_EXISTS == 'false' } }
      steps {
        sh 'mkdir -p /root/.cache/docker/'
        sh 'docker save -o /root/.cache/docker/php-8.0.tar ecoding/php:8.0'
      }
    }
  }
}

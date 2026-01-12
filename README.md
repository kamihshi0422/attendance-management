## 環境構築
**Dockerビルド**

1. `git clone git@github.com:kamihshi0422/attendance-management.git`
2. `cd attendance-management`
3. DockerDesktopアプリを立ち上げる
4. `docker-compose up -d --build`

**Laravel環境構築**

1. phpコンテナへ入る
``` bash
docker-compose exec php bash
```

2. `composer install`

> _composerインストールでエラーが発生した際は、phpコンテナ内で以下のコマンドを実行してから再度composerインストールを実行してください。

> Laravelアプリが正常に動作するためのフォルダ作成と権限の変更になります。_
```bash
mkdir -p bootstrap/cache storage/framework/cache/data
mkdir -p storage/framework/views
mkdir -p storage/framework/sessions
mkdir -p storage/framework/testing
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache
```

3. 「.env.example」ファイルを コピーして「.env」と命名
4.  .envに以下の環境変数を追加

``` text
DB_USERNAME=laravel_user
DB_PASSWORD=laravel_pass
```

> _.env を変更した際、反映されないことがあるため、phpコンテナ内でまとめて以下を実行してください。_

``` bash
docker-compose exec php bash
```
```bash
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
composer dump-autoload
php artisan package:discover
php artisan config:cache
```

5. アプリケーションキーの作成

``` bash
php artisan key:generate
```

6. マイグレーションの実行

``` bash
php artisan migrate
```

7. シーディングの実行

``` bash
php artisan db:seed
```

> _Permission denied（権限のエラー）が出た際、以下をsudoコマンドを実行してください。_

``` bash
sudo chmod -R 777 src/*
```

## メール認証
mailtrapというツールを使用しています。<br>
以下のリンクから会員登録をしてください。　<br>
https://mailtrap.io/

SandboxesよりSandboxを作成し、<br>
IntegrationsのSMTPからUsernameとPasswordコピー＆ペースト、<br>
MAIL_FROM_ADDRESSは任意のメールアドレスを入力してください。

```text
MAIL_USERNAME=****Username
MAIL_PASSWORD=****Password
```

## テスト用環境設定
1. 「.env」ファイルを コピーして「.env.testing」と命名
2.  .env.testingに以下の環境変数を修正

```text
APP_ENV=testing

DB_DATABASE=attendance_test
DB_USERNAME=root
DB_PASSWORD=root

MAIL_MAILER=array
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your_mailtrap_username
MAIL_PASSWORD=your_mailtrap_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=no-reply@test.com
MAIL_FROM_NAME="Attendance App"
```
> _※DB_DATABASE=laravel_db のままだと本番DBが消えてしまいますのでご注意ください。_

3. テスト用DBを作成（ターミナルで実行）
``` bash
docker exec -it attendance-management-mysql-1 mysql -u root -proot\
"CREATE DATABASE IF NOT EXISTS attendance_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
GRANT ALL PRIVILEGES ON attendance_test.* TO 'root'@'%';
FLUSH PRIVILEGES;"
```

4. .env.testingの設定反映（ターミナルで実行）
```bash
docker-compose exec php bash -c "
php artisan config:clear &&
php artisan cache:clear &&
php artisan route:clear &&
php artisan view:clear &&
composer dump-autoload &&
php artisan package:discover"
```

5. テスト専用マイグレーション

``` bash
php artisan migrate --env=testing
```

6. テスト専用シーディング

``` bash
php artisan db:seed --env=testing
```

7. テスト実行

``` bash
php artisan test tests/Feature --env=testing
```

## URL
- ログイン画面 ：http://localhost/login
- 会員登録画面 :http://localhost/register
- 管理者ログイン画面 ：http://localhost/admin/login
- phpMyAdmin:：http://localhost:8080/

## テストアカウント
name: 管理者ユーザー
email: host@example.com
password: password
-------------------------
name: 管理者ユーザー
email: user@example.com
password: password
-------------------------

## 追加機能の説明
**コーチの確認・許可のもと、機能を加えています**
- メール認証画面で「認証はこちらから」ボタンを押下するとmailtrapに遷移し、認証すると勤怠登録画面に遷移する。
- 管理者も一般ユーザーとしてログイン、勤怠登録など可能

## 使用技術(実行環境)
- PHP8.1 (php-fpm)
- Laravel 8.83.8
- MySQL 8.0.26
- nginx 1.21.1
- Docker / Docker Compose

## ER 図
![ER図](./ER.drawio.png)

## Tree
.
├── ER.drawio.png
├── README.md
├── docker
│   ├── mysql
│   │   ├── data
│   │   └── my.cnf
│   ├── nginx
│   │   └── default.conf
│   └── php
│       ├── Dockerfile
│       └── php.ini
├── docker-compose.yml
└── src
    ├── README.md
    ├── app
    │   ├── Actions
    │   ├── Console
    │   ├── Exceptions
    │   ├── Http
    │   ├── Models
    │   ├── Providers
    │   └── Services
    ├── artisan
    ├── bootstrap
    │   ├── app.php
    │   └── cache
    ├── composer.json
    ├── composer.lock
    ├── config
    │   ├── app.php
    │   ├── auth.php
    │   ├── broadcasting.php
    │   ├── cache.php
    │   ├── cors.php
    │   ├── database.php
    │   ├── filesystems.php
    │   ├── fortify.php
    │   ├── hashing.php
    │   ├── logging.php
    │   ├── mail.php
    │   ├── queue.php
    │   ├── sanctum.php
    │   ├── services.php
    │   ├── session.php
    │   └── view.php
    ├── database
    │   ├── factories
    │   ├── migrations
    │   └── seeders
    ├── package.json
    ├── phpunit.xml
    ├── public
    │   ├── css
    │   ├── favicon.ico
    │   ├── index.php
    │   ├── robots.txt
    │   └── storage -> /var/www/storage/app/public
    ├── resources
    │   ├── js
    │   ├── lang
    │   └── views
    ├── routes
    │   ├── api.php
    │   ├── channels.php
    │   ├── console.php
    │   └── web.php
    ├── server.php
    ├── storage
    │   ├── app
    │   ├── framework
    │   └── logs
    ├── tests
    │   ├── CreatesApplication.php
    │   ├── Feature
    │   ├── TestCase.php
    │   └── Unit
    └── webpack.mix.js
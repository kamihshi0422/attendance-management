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

8. 出品画像、プロフィール画像のディレクトリ作成とサンプル画像の移動

- `mkdir -p src/storage/app/public`
- `mv img products_images src/storage/app/public`
- `mkdir src/storage/app/public/user_images`

> _Permission denied（権限のエラー）が出た際、以下を実行してください。_

``` bash
sudo chmod -R 777 src/*
```

9.  画像ディレクトリを正常に作動させるため、phpコンテナ内で以下のコマンドを実行

``` bash
php artisan storage:link
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache
```

**Mailtrap 設定**
1. https://stripe.com/jp にサインアップ
2. ダッシュボードの「開発者」→「APIキー」から以下を取得
  - 公開可能キー (STRIPE_KEY)
  - シークレットキー (STRIPE_SECRET)
3. 上記で取得したAPIキーを.env に設定

``` text
STRIPE_KEY=pk_test_XXXXXXXXXXXX
STRIPE_SECRET=sk_test_XXXXXXXXXXXX
```

## テスト用環境設定

1. 「.env」ファイルを コピーして「.env.testing」と命名
2.  .env.testingに以下の環境変数を追加

```text
APP_ENV=testing

DB_DATABASE=attendance_test
DB_USERNAME=root
DB_PASSWORD=root
```
> _※DB_DATABASE=laravel_db のままだと本番DBが消えてしまいますのでご注意ください。_

3. テスト用DBを作成（ターミナルで実行）
``` bash
docker exec -it free-market-mysql-1 mysql -u root -proot -e \
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

- MailHog ：http://localhost:8025/

## 追加機能の説明
**コーチの確認・許可のもと、機能を加えています**

- メール認証画面で「認証はこちらから」ボタンを押下するとMailHog（http://localhost:8025/） に遷移し、認証するとプロフィール編集画面に遷移する。
- 未承認ユーザーが認証が必要なアクションを行いログインした場合、元の画面に遷移する
  - 例：商品詳細画面で「いいね」後ログイン → 商品詳細画面に戻る
  - 例：マイページボタン → ログイン後マイページに遷移
- 商品出品画面、プロフィール編集、送付先住所変更のエラーメッセージは要件定義にないため独自実装
- 購入済商品の挙動：
  - 詳細画面は表示可能
  - 購入手続きボタンは非表示
- 購入するボタンを押下時、トップページに戻り、Stipe画面に遷移を別タブで行うためにJavaScriptを使用しています

## 使用技術(実行環境)
- PHP8.1 (php-fpm)
- Laravel 8.83.8
- MySQL 8.0.26
- nginx 1.21.1
- Docker / Docker Compose

## ER 図
![ER図](./ER.drawio.png)

## Tree
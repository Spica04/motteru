<?php
/**
 * 環境設定
 * 
 * @author tatsuuma
 *
 */

// 共通定義
define('FLAG_OFF', 0);
define('FLAG_ON', 1);

// 文字コード
define('CCODE_BROWSER',			'UTF-8');
define('CCODE_WORKING',			'UTF-8');

/** DB設定 */
define('DB_TYPE', 'MySQL');
define('DB_HOST', 'localhost');
define('DB_NAME', 'database');
define('DB_USER', 'root');
define('DB_PASS', 'password');

// メール設定
define('MAIL_SENDER_ADDRES', 'sender@example.com');
define('MAIL_SENDER_NAME', 'tweet');

// パス
//  本体
define('_RUN_DIR', _ROOT_DIR . 'private/run/');
//  IMAGE
define('_UPLOAD_DIR', _ROOT_DIR . 'private/var/template/');

// -------------------------------------------------------------------------- **

require_once 'master.php';

// -------------------------------------------------------------------------- **

// 共通関数を読み込む
require_once _ROOT_DIR . 'private/classes/' . 'Abstract.php';
require_once _ROOT_DIR . 'private/classes/' . 'Application.php';
require_once _ROOT_DIR . 'private/classes/' . 'Common.php';

// 各ライブラリを読み込む
require_once _ROOT_DIR . 'private/classes/' . 'Database.php';
require_once _ROOT_DIR . 'private/classes/' . 'Session.php';
require_once _ROOT_DIR . 'private/classes/' . 'Validator.php';
require_once _ROOT_DIR . 'private/classes/' . 'View.php';

// -------------------------------------------------------------------------- **

Validator::$ENCODING = CCODE_WORKING;

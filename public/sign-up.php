<?php
// ROOTまでの階層分 dirnameで戻る
define('_ROOT_DIR', dirname(dirname(__FILE__)) . '/');
// 処理本体を呼び出す
require_once _ROOT_DIR . 'private/run/' . 'sign-up.php';

<?php
// ROOTまでの階層分 dirnameで戻る
define('_ROOT_DIR', dirname(dirname(__FILE__)) . '/');


$filename = array_key_exists('file', $_GET) ? $_GET['file'] : null;
$pathname = _ROOT_DIR . 'logs/' . $filename;

$command = array_key_exists('cmd', $_GET) ? $_GET['cmd'] : null;

if (file_exists($pathname))
{
	switch ($command)
	{
//		case 'rotate':
//			file_put_contents($pathname . '.' . date('Ymdhis'), file_get_contents($pathname));
//			file_put_contents($pathname, '');
//			$url = 'http://' . $_SERVER['SERVER_NAME'] . '/logs.php?file=' . $filename;
//			header('Location: ' . $url);
//			exit;

		case 'read':
		default:
			$data = file_get_contents($pathname);
			$data = explode(PHP_EOL, $data);
			$data = array_reverse($data);
			$data = implode(PHP_EOL, $data);

			echo '<pre>' . $data . '</pre>';
			break;
	}
}

echo date('Y-m-d H:i:s') . <<< HTML_STRING
<br>
<a href="/logs.php?file={$filename}&cmd=rotate">rotate</a>
HTML_STRING;

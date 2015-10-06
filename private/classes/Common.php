<?php
/**
 * Commonクラス定義
 * 
 * @author kitamura
 * @author hoaki
 * @author tatsuuma
 *
 */
class Common
{
	/**
	 * テンポラリファイルの削除。
	 * @param $dir ディレクトリ。"/"付き
	 * @param $hour 最終アクセス経過時間。
	 * @return void
	 */
	public static function DeleteFilesTimeout($dir, $hour = 0)
	{
		if ($handle = opendir($dir))
		{
			while (false !== ($file = readdir($handle)))
			{
				if($file==='.'||$file==='..')
					continue;
				$delta = time() - fileatime($dir.$file);
				if($delta / 60 / 60 > $hour)
				{
					unlink($dir.$file);
				}
			}
			closedir($handle);
		}
	}

	/**
	 * 文字エンコーディングを変換します。
	 * 
	 * @param	mixed	$data	変換対象データ
	 * @param	string	$to		変換したい文字エンコード
	 * @param	stirng	$from	変換対象の元の文字エンコード
	 * @return	mixed
	 */
	public static function convertEncoding($data, $to = null, $from = null)
	{
		if (is_null($to) || $to == $from)
		{
			return $data;
		}
		if (is_array($data))
		{
			foreach (array_keys($data) as $key)
			{
				$data[$key] = self::convertEncoding($data[$key], $to, $from);
			}
		}
		else if (is_string($data))
		{
			$data = mb_convert_encoding($data, $to, $from);
		}
		return $data;
	}

	/**
	 * トリミングした値を返します。
	 * 
	 * @param	mixed	$value		変換対象データ
	 * @param	array	$opt		いろいろオプション
	 * @return	mixed
	 */
	public static function parseString($value, $opt = array())
	{
		$opt = array_merge(array(
			'DEFAULT_VALUE' => null,    // 空文字になった時の対応
			'CCODE_WORKING' => null,    // mb系を使う為
			'MB_CONVERT_KANA' => null,  // mb_convert_kanaのoption値
		), $opt);
		if (is_array($value))
		{
			foreach (array_keys($value) as $key)
			{
				$value[$key] = self::parseString($value[$key], $opt);
			}
		}
		else if (is_string($value))
		{
			$value = trim($value);
			if ($opt['MB_CONVERT_KANA'] !== null)
			{
				$value = $opt['CCODE_WORKING'] !== null
					? mb_convert_kana($value, $opt['MB_CONVERT_KANA'], $opt['CCODE_WORKING'])
					: mb_convert_kana($value, $opt['MB_CONVERT_KANA'])
				;
			}
			if (strlen($value) === 0)
			{
				$value = $opt['DEFAULT_VALUE'];
			}
		}
		return $value;
	}

	/**
	 * 乱数生成。
	 * 
	 * @return	string
	 */
	public static function random()
	{
		return md5(uniqid(mt_rand(), true) . getmypid());
	}

	/**
	 * 第一引数に指定した配列に対して以降に指定したキーの値で配列にする
	 *
	 * @param array dataset
	 * @param string keys
	 */
	public static function CreateArray()
	{
		$args = func_get_args();
		$data = array_shift($args);

		$result = array();
		foreach ($args as $key)
		{
			if (isset($data[$key]))
			{
				$result[] = $data[$key];
			}
		}
		return $result;
	}

	/**
	 * リダイレクト。
	 */
	public static function Redirect($url)
	{
		header("Location: " . $url);
		exit;
	}
}

function d() { echo '<pre>'; foreach (func_get_args() as $func) { var_dump($func); } echo '</pre>'; exit; }


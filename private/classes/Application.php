<?php
/**
 * ページクラスのテンプレートクラスです。
 *
 * @author tatsuuma
 *
 */
abstract class Page_Application extends Page_Abstract
{
	// Common::parseStringで使うパラメータ
	private static $parseStringOptions = array(
		'CCODE_WORKING' => CCODE_WORKING,
		'MB_CONVERT_KANA' => "asKV",
	);

	//
	//
	//
	protected function getParam($name, $def_val = null)
	{
		if (array_key_exists($name, $_REQUEST))
		{
			$val = $_REQUEST[$name];
			//$val = Common::convertEncoding($val, CCODE_WORKING, CCODE_BROWSER);
			$val = Common::parseString($val, self::$parseStringOptions);
		}
		else
		{
			$val = $def_val;
		}
		return $val;
	}
}

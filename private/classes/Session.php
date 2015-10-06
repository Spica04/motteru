<?php
/**
 * Sessionクラス定義
 * 
 * @author tatsuuma
 *
 */
class Session
{
	/**
	 * is_start
	 *
	 */
	private static $is_start = false;

	/**
	 * start
	 *
	 */
	private static function start()
	{
		if (!self::$is_start)
		{
			session_start();
			self::$is_start = true;
		}
	}

	/**
	 * set
	 *
	 */
	public function set($name, $val)
	{
		self::start();
		$_SESSION[$name] = $val;
	}

	/**
	 * get
	 *
	 */
	public function get($name)
	{
		self::start();
		return isset($_SESSION[$name]) ? $_SESSION[$name] : null;
	}

	/**
	 * delete
	 *
	 */
	public function delete($name = null)
	{
		self::start();
		if ($name === null)
		{
			unset($_SESSION);
		}
		else
		{
			unset($_SESSION[$name]);
		}
	}
};

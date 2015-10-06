<?php
/**
 * ページクラスのテンプレートクラスです。
 *
 * @author tatsuuma
 *
 */
abstract class Page_Abstract
{
	/**
	 * セッション情報
	 */
	private $session = null;

	/**
	 * ログイン情報
	 */
	protected $login_user_id = null;

	/**
	 * セッションクラスを取得する
	 * 
	 * @return	object
	 */
	protected function getSession()
	{
		if ($this->session === null)
		{
			$this->session = new Session();
		}
		return $this->session;
	}

	/**
	 * ログイン状態をチェックする
	 * - 未ログインの場合はログインページヘ
	 * 
	 * @return	object
	 */
	protected function checkSignin()
	{
		$this->login_user_id = $this->getSession()->get('uid');
		if (is_null($this->login_user_id))
		{
			$this->signout();
			$this->redirect('./sign-in.php');
		}
	}

	/**
	 * ログイン状態をチェックする
	 * - 未ログインの場合は例外
	 * 
	 * @return	object
	 */
	protected function errorSignin()
	{
		$this->login_user_id = $this->getSession()->get('uid');
		if (is_null($this->login_user_id))
		{
			throw new Exception("is_null($this->login_user_id)");
		}
	}

	/**
	 * サインインする
	 * 
	 * @return	object
	 */
	protected function signin($uid)
	{
		$this->login_user_id = $uid;
		$this->getSession()->set('uid', $uid);
	}

	/**
	 * サインアウトする
	 * 
	 * @return	object
	 */
	protected function signout()
	{
		$this->getSession()->delete('uid');
	}

	/**
	 * リダイレクトをします
	 *
	 * @param	string	$adddress
	 */
	protected function redirect($adddress, $use_referer=false)
	{
		if ($use_referer)
		{
			$relay = $_SERVER['PHP_SELF'];
			if (strlen($_SERVER['QUERY_STRING']) !== 0)
			{
				$relay .= '?' . $_SERVER['QUERY_STRING'];
			}
			$adddress .= '?referer=' . $relay;
		}

		//header("HTTP/1.1 301 Moved Permanently");
		header("Location: " . $adddress);
		exit;
	}
}

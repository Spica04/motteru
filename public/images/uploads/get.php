<?php
define('_ROOT_DIR', dirname(dirname(dirname(dirname(__FILE__)))) . '/');
require_once _ROOT_DIR . 'private/' . 'setting.php';
require_once _ROOT_DIR . 'private/classes/' . 'Mail.php';

/**
 * アンケートフォーム
 *
 * @author tatsuuma
 *
 */
class Page_This extends Page_Application
{
	//
	//
	//
	public function run()
	{
		if (!headers_sent()) { $this->initialize(); }
		if (!headers_sent()) { $this->execute(); }
		if (!headers_sent()) { $this->prerender(); }
		if (!headers_sent()) { $this->view(); }
	}

	// ********************************************************************** //

	/**
	 * Database情報
	 *
	 * @var	object
	 */
	private $db = null;

	/**
	 * 拡張情報
	 *
	 * @var	object
	 */
	private $filename = null;

	/**
	 * 初期化
	 *
	 * @return	void
	 */
	protected function initialize()
	{
		$this->errorSignin();

		$this->db = new DBManager(array(
			"type" => DB_TYPE,
			"host" => DB_HOST,
			"name" => DB_NAME,
			"user" => DB_USER,
			"pass" => DB_PASS,
		));
	}

	/**
	 * ロジック処理
	 *
	 * @return	void
	 */
	protected function execute()
	{
		$seq = $this->getParam('seq');
		if ($seq === null)
			throw new Exception('$seq === null');

		//

		$sql
			= 'SELECT *'
			. ' FROM `possession`'
			. ' WHERE `uid` = :USER_ID AND `seq` = :SEQ'
		;
		$prm = array(
			'SEQ' => $seq,
			'USER_ID' => $this->login_user_id,
		);
		$this->db->select($sql, $prm);
		$row = $this->db->getCursor(0);

		$this->filename = _UPLOAD_DIR . $this->login_user_id . '/' . $seq;

		if (!file_exists($this->filename))
			throw new Exception('!file_exists($this->filename)');
	}

	/**
	 * 表示前処理
	 *
	 * @return	void
	 */
	protected function prerender()
	{
	}

	/**
	 * 表示処理
	 *
	 * @return	void
	 */
	protected function view()
	{
		// 画像ファイルの種類チェック
		switch (exif_imagetype($this->filename))
		{
			case IMAGETYPE_GIF:  $type = "gif";  break;
			case IMAGETYPE_JPEG: $type = "jpeg"; break;
			case IMAGETYPE_PNG:  $type = "png";  break;
			default: $type = "jpeg"; // throw new Exception("over exif_imagetype");
		}

		//ファイル送信
		$buf = file_get_contents($this->filename);
		header("Content-type: image/" . $type);
		echo $buf;

		exit;
	}
}

try
{
	$page = new Page_This();
	$page->run();
	unset($page);
}
catch (Exception $ex)
{
	$buf = file_get_contents(_ROOT_DIR . 'public/images/404.png');
	header("Content-type: image/png");
	header("HTTP/1.0 404 Not Found");
	echo $buf;
}
exit;

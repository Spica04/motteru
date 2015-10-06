<?php
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
	 * 入力データ
	 *
	 * @var	object
	 */
	private $inputs = null;

	/**
	 * 表示データ
	 *
	 * @var	object
	 */
	private $data = null;

	/**
	 * 拡張情報
	 *
	 * @var	object
	 */
	private $options = null;

	/**
	 * エラー情報
	 *
	 * @var	object
	 */
	private $errors = null;

	/**
	 * 初期化
	 *
	 * @return	void
	 */
	protected function initialize()
	{
		$this->checkSignin();

		$this->db = new DBManager(array(
			"type" => DB_TYPE,
			"host" => DB_HOST,
			"name" => DB_NAME,
			"user" => DB_USER,
			"pass" => DB_PASS,
		));

		$this->options = [];
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
		{
			$this->redirect('/portfolio.php?command=category');
		}

		// 読み込み
		$this->tweet_load();
	}

	/**
	 * ロジック処理
	 *
	 * @return	void
	 */
	private function tweet_load()
	{
		$seq = $this->getParam('seq');

		//

		$sql = <<< SQL_STRING
SELECT
		`T1`.*
FROM
	`possession` TB
		INNER JOIN `possession` `T1` ON `T1`.`lbl` = `TB`.`lbl`
WHERE
		`T1`.`uid` = :USER_ID
	AND `TB`.`seq` = :SEQ
ORDER BY
		`dt` DESC

SQL_STRING;
		$prm = array(
			'USER_ID' => $this->login_user_id,
			'SEQ' => $seq,
		);
		$this->db->select($sql, $prm);
		$rows = $this->db->getCursor();

		$this->data = $rows;
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
		$view = new View(CCODE_BROWSER, CCODE_WORKING);

		$view->inputs = $this->inputs;
		$view->data = $this->data;
		$view->options = $this->options;
		$view->errors = $this->errors;

		$view->display(_RUN_DIR . 'single.html');
	}
}

$page = new Page_This();
$page->run();
unset($page);

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
		switch (strtolower($this->getParam('command')))
		{
			case 'single':
				// カテゴリ内の itemを取得する
				$this->load_single();
				break;

			case 'category':
				// 各カテゴリのそれぞれ最新を取得する
				$this->load_category();
				break;

			case 'whatsnew':
			default:
				// 投稿された最新を取得する
				$this->load_whatsnew();
				break;
		}
	}

	/**
	 * カテゴリ内の itemを取得する
	 *
	 * @return	array
	 */
	private function load_single()
	{
		$this->options['TEMPLATE'] = 'single';

		//

		$sql
			= 'SELECT *'
			. ' FROM `possession`'
			. ' WHERE `uid` = :USER_ID'
			. ' ORDER BY `dt` DESC, `seq` DESC'
		;
		$prm = array(
			'USER_ID' => $this->login_user_id,
		);
		$this->db->select($sql, $prm);
		$rows = $this->db->getCursor();

		$this->data = $rows;
	}

	/**
	 * 各カテゴリのそれぞれ最新を取得する
	 *
	 * @return	array
	 */
	private function load_category()
	{
		$this->options['TEMPLATE'] = 'category';

		//

		$sql
			= 'SELECT *'
			. ' FROM `possession`'
			. ' WHERE `uid` = :USER_ID'
			. ' ORDER BY `dt` DESC, `seq` DESC'
		;
		$prm = array(
			'USER_ID' => $this->login_user_id,
		);
		$this->db->select($sql, $prm);
		$rows = $this->db->getCursor();

		$this->data = $rows;
	}

	/**
	 * 投稿された最新を取得する
	 *
	 * @return	array
	 */
	private function load_whatsnew()
	{
		$this->options['TEMPLATE'] = 'whatsnew';

		//

		$sql
			= 'SELECT *'
			. ' FROM `possession`'
			. ' WHERE `uid` = :USER_ID'
			. ' ORDER BY `dt` DESC, `seq` DESC'
		;
		$prm = array(
			'USER_ID' => $this->login_user_id,
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

		$view->display(_RUN_DIR . 'portfolio-' . $this->options['TEMPLATE'] . '.html');
	}
}

$page = new Page_This();
$page->run();
unset($page);

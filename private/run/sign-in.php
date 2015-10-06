<?php
require_once _ROOT_DIR . 'private/' . 'setting.php';

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
		if ($this->getParam('token') !== null)
		{
			if ($this->errors === null) { $this->signin_post(); }
			if ($this->errors === null) { $this->signin_validate(); }
			if ($this->errors === null) { $this->signin_complete(); }
			if ($this->errors === null)
			{
				// ログインに成功したら遷移
				$this->redirect('/portfolio.php');
			}
		}
	}

	/**
	 * ロジック処理
	 *
	 * @return	void
	 */
	private function signin_post()
	{
		$inputs = array(
			'TOKEN' => $this->getParam('token'),
			'ACCOUNT' => $this->getParam('account'),
			'PASSWORD' => $this->getParam('password'),
		);

		$this->inputs = array_merge(
			$inputs
		,	array()
		);
	}

	/**
	 * ロジック処理
	 *
	 * @return	void
	 */
	private function signin_validate()
	{
		$defs = array(
			'TOKEN' => array(
				'name'			=> 'ユニークキー',
				'required'		=> true,
			),
			//
			'ACCOUNT' => array(
				'name'			=> 'アカウント',
				'required'		=> true,
			),
			'PASSWORD' => array(
				'name'			=> 'パスワード',
				'required'		=> true,
				'length'		=> 50,
			),
			//
			'signin' => array(
				'name'			=> 'ログインチェック',
				'custom_all'	=> array($this, 'signin_validate_signin'),
			),
		);

		//

		$vals = $this->inputs;

		// TOKENが異なる場合は Valueを消そう
		if ($this->getSession()->get('token') != $this->inputs['TOKEN'])
		{
			$vals['TOKEN'] = null;
		}

		//

		$validator = new Validator($defs, $vals);
		$validator->run();
		if (count($validator->getErrors()) !== 0)
		{
			$this->errors = $validator->getErrors();
		}
		unset($validator);
	}

	/**
	 * ロジック処理
	 *
	 * @return	void
	 */
	public function signin_validate_signin($values, $label)
	{
		$sql = 'SELECT `uid` FROM `user` WHERE `account` = :ACCOUNT AND `password` = :PASSWORD';
		$prm = array(
			'ACCOUNT' => $values['ACCOUNT'],
			'PASSWORD' => sha1($values['PASSWORD'] + '@motteru'),
		);
		$this->db->select($sql, $prm);
		$row = $this->db->getCursor(0);

		if (count($row) === 0)
			return 'アカウント、またはパスワードが正しくありません。';

		return null;
	}

	/**
	 * サインイン後処理
	 *
	 * @return	void
	 */
	protected function signin_complete()
	{
		$sql = 'SELECT `uid` FROM `user` WHERE `account` = :ACCOUNT AND `password` = :PASSWORD';
		$prm = array(
			'ACCOUNT' => $this->inputs['ACCOUNT'],
			'PASSWORD' => sha1($this->inputs['PASSWORD'] + '@motteru'),
		);
		$this->db->select($sql, $prm);
		$row = $this->db->getCursor(0);

		$this->signin($row['uid']);
	}

	/**
	 * 表示前処理
	 *
	 * @return	void
	 */
	protected function prerender()
	{
		$this->options['TOKEN'] = Common::random();
		$this->getSession()->set('token', $this->options['TOKEN']);
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

		$view->display(_RUN_DIR . 'sign-in.html');
	}
}

$page = new Page_This();
$page->run();
unset($page);

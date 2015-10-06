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
		$this->checkLogin();

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
			switch (strtolower($this->getParam('command')))
			{
				case 'insert':
					// 更新
					if ($this->errors === null) { $this->tweet_insert_post(); }
					if ($this->errors === null) { $this->tweet_insert_validate(); }
					if ($this->errors === null) { $this->tweet_insert_save(); }
					if ($this->errors === null)
					{
						// 投稿に成功したら遷移
						$this->redirect('/tweet.php');
					}
					break;

				case 'delete':
					// 更新
					if ($this->errors === null) { $this->tweet_delete_post(); }
					if ($this->errors === null) { $this->tweet_delete_validate(); }
					if ($this->errors === null) { $this->tweet_delete_save(); }
					if ($this->errors === null)
					{
						// 投稿に成功したら遷移
						$this->redirect('/tweet.php');
					}
					break;
			}
		}

		// 読み込み
		$this->tweet_load();
	}

	/**
	 * ロジック処理
	 *
	 * @return	void
	 */
	private function tweet_insert_post()
	{
		$inputs = array(
			'TOKEN' => $this->getParam('token'),
			'REPLY_SEQ' => $this->getParam('reply_seq'),
			'ROOT_SEQ' => null,
			//
			'USER_ID' => $this->login_user_id,
			'BODY' => $this->getParam('body'),
			'IMP_DATETIME' => $this->getParam('imp_datetime'),
			'SENDMAIL' => $this->getParam('sendmail'),
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
	private function tweet_insert_validate()
	{
		$defs = array(
			'TOKEN' => array(
				'name'			=> 'ユニークキー',
				'required'		=> true,
			),
			'REPLY_SEQ' => array(
				'name'			=> '返信先',
			),
			'ROOT_SEQ' => array(
				'name'			=> '祖先ID',
			),
			//
			'USER_ID' => array(
				'name'			=> 'ユーザ情報',
				'required'		=> true,
			),
			'BODY' => array(
				'name'			=> 'メッセージ',
				'required'		=> true,
				'length'		=> 5000,
			),
			'IMP_DATETIME' => array(
				'name'			=> '実施日',
				'required'		=> false,
				'date'			=> true,
			),
			'SENDMAIL' => array(
				'name'			=> 'メール送信先',
				'required'		=> false,
				'length'		=> 150,
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
	private function tweet_insert_save()
	{
//		$matches = [];
//		$n = preg_match_all('/(^|　|\t| )?#(.*)(^|　|\t| )/', $this->inputs['BODY'], $matches, PREG_SET_ORDER);

		try
		{
			$this->db->beginTransaction();

			if (strlen($this->inputs['REPLY_SEQ']) !== 0)
			{
				$sql = 'SELECT ROOT_SEQ FROM `TWEET` WHERE SEQ = :SEQ';
				$prm = array(
					'SEQ' => $this->inputs['REPLY_SEQ'],
				);
				$this->db->select($sql, $prm);
				$row = $this->db->getCursor(0);

				if ($row !== null)
				{
					$this->inputs['ROOT_SEQ'] = $row['ROOT_SEQ'];
				}
				if ($this->inputs['ROOT_SEQ'] == null)
				{
					$this->inputs['ROOT_SEQ'] = $this->inputs['REPLY_SEQ'];
				}
			}

			//

			$sql
				= "INSERT INTO `TWEET`(`REPLY_SEQ`,`ROOT_SEQ`,`USER_ID`,`BODY`,`SENDMAIL`,`IMP_DATETIME`,`CRT_DATETIME`)"
				. "VALUES(:REPLY_SEQ,:ROOT_SEQ,:USER_ID,:BODY,:SENDMAIL,COALESCE(:IMP_DATETIME, NOW()),NOW())"
			;
			$prm = array(
				'REPLY_SEQ' => $this->inputs['REPLY_SEQ'],
				'ROOT_SEQ' => $this->inputs['ROOT_SEQ'],
				'USER_ID' => $this->inputs['USER_ID'],
				'BODY' => $this->inputs['BODY'],
				'SENDMAIL' => $this->inputs['SENDMAIL'],
				'IMP_DATETIME' => $this->inputs['IMP_DATETIME'],
			);
			$this->db->execute($sql, $prm);

			$seq = $this->db->lastInsertId();

			//

			$sql = 'SELECT `USER_ID` FROM `USER` WHERE USER_ID = :USER_ID';
			$prm = array(
				'USER_ID' => $this->login_user_id,
			);
			$this->db->select($sql, $prm);
			$row = $this->db->getCursor(0);

			if ($row === null)
			{
				// ユーザ情報を取得
				$user = $this->getUser($this->login_user_id);

				$sql
					= "INSERT INTO `USER` (`USER_ID`,`NICK_NAME`,`READ_SEQ`)"
					. "VALUES(:USER_ID, :NICK_NAME, 0)"
				;
				$prm = array(
					'USER_ID' => $user['USER_ID'],
					'NICK_NAME' => $user['STAFF_NAME'],
				);
				$this->db->execute($sql, $prm);
			}

			$this->db->commit();
		}
		catch (Exception $ex)
		{
			$this->db->rollback();
			throw $ex;
		}

		if (strlen($this->inputs['SENDMAIL']) !== 0)
		{
			$to = $this->inputs['SENDMAIL'];

			$subject = str_replace(array("\r\n", "\r"), "\n", $this->inputs['BODY']);
			$subject_len = strpos($subject, "\n");
			if ($subject_len !== false)
			{
				$subject = substr($subject, 0, $subject_len);
			}

			{	// メール送信者の名前を取得
				$sql = 'SELECT `USER_ID`, `NICK_NAME` FROM `USER` WHERE `USER_ID` = :USER_ID';
				$prm = array(
					'USER_ID' => $this->login_user_id,
				);
				$this->db->select($sql, $prm);
				$row = $this->db->getCursor(0);
			}

			$body = <<< MAIL_STRING
おつかれさまです、{$row['NICK_NAME']}です。

{$this->inputs['BODY']}

以上、よろしくお願いいたします。
それでは、失礼いたします。
**************************************************
このメールはHJタイムラインから送信されました。
http://192.168.0.115/
**************************************************

MAIL_STRING;

			Mail::Send($to, $subject, $body);
		}
	}

	/**
	 * ロジック処理
	 *
	 * @return	void
	 */
	private function tweet_delete_post()
	{
		$inputs = array(
			'TOKEN' => $this->getParam('token'),
			'DELETE_SEQ' => $this->getParam('delete_seq'),
			'ROOT_SEQ' => null,
			//
			'USER_ID' => $this->login_user_id,
			'BODY' => null,
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
	private function tweet_delete_validate()
	{
		$defs = array(
			'TOKEN' => array(
				'name'			=> 'ユニークキー',
				'required'		=> true,
			),
			'DELETE_SEQ' => array(
				'name'			=> '削除',
				'required'		=> true,
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
	private function tweet_delete_save()
	{
		try
		{
			$this->db->beginTransaction();

			$sql = "DELETE FROM `TWEET` WHERE `SEQ` = :DELETE_SEQ AND `USER_ID` = :USER_ID";
			$prm = array(
				'DELETE_SEQ' => $this->inputs['DELETE_SEQ'],
				'USER_ID' => $this->login_user_id,
			);
			$this->db->execute($sql, $prm);

			$this->db->commit();
		}
		catch (Exception $ex)
		{
			$this->db->rollback();
			throw $ex;
		}
	}

	/**
	 * ロジック処理
	 *
	 * @return	void
	 */
	private function tweet_load()
	{
		$tweet_id = $this->getParam('id');

		// レコード表示
		if (strlen($tweet_id) === 0)
		{
			$sql
				= 'SELECT TB.*, T1.`NICK_NAME`, T2.`CRT_DATETIME` AS MOD_DATETIME'
				. ' FROM `TWEET` TB'
				. ' LEFT JOIN `USER` T1 ON T1.`USER_ID` = TB.`USER_ID`'
				. ' LEFT JOIN `TWEET` T2 ON T2.`ROOT_SEQ` = TB.`SEQ` AND T2.`SEQ` = (SELECT T2A.`SEQ` FROM `TWEET` T2A WHERE T2A.`ROOT_SEQ` = TB.`SEQ` ORDER BY T2A.`CRT_DATETIME` LIMIT 1)'
				. ' WHERE DATE_ADD(TB.`IMP_DATETIME`, INTERVAL 1 WEEK) > NOW() AND TB.`ROOT_SEQ` IS NULL'
				. ' ORDER BY `SEQ` DESC'
			;
			$prm = array(
			);
			$this->db->select($sql, $prm);
			$rows = $this->db->getCursor();
		}
		else
		{
			$sql
				= 'SELECT TB.*, T1.`NICK_NAME`'
				. ' FROM `TWEET` TB'
				. ' LEFT JOIN `USER` T1 ON T1.`USER_ID` = TB.`USER_ID`'
				. ' INNER JOIN `TWEET` T2 ON (T2.`ROOT_SEQ` = TB.`ROOT_SEQ` || T2.`ROOT_SEQ` = TB.`SEQ` || T2.`SEQ` = TB.`SEQ`) AND TB.`SEQ` <= T2.`SEQ`'
				. ' WHERE T2.`SEQ` = :SEQ'
				. ' ORDER BY `SEQ` DESC'
			;
			$prm = array(
				'SEQ' => $tweet_id,
			);
			$this->db->select($sql, $prm);
			$rows = $this->db->getCursor();

			$relay = [];
			foreach ($rows as $row)
			{
				$n = count($relay);
				if ($n === 0 || $relay[$n - 1]['REPLY_SEQ'] == $row['SEQ'])
				{
					$relay[] = $row;
				}
			}
			$rows = $relay;
		}

		$this->data = $this->tweet_parse_for_view($rows);
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

		$this->options['LOGIN_USER_ID'] = $this->login_user_id;

		{
			$sql
				= 'SELECT * FROM `USER` WHERE `USER_ID` <> :LOGIN_USER_ID ORDER BY `USER_ID`'
			;
			$prm = array(
				'LOGIN_USER_ID' => $this->login_user_id,
			);
			$this->db->select($sql, $prm);
			$rows = $this->db->getCursor();

			$this->options['LIST_USER'] = $rows;
		}

		$tweet_id = $this->getParam('id');
		if (strlen($tweet_id) === 0)
		{
			$this->options['LEVEL_TWEETS'] = [];
		}
		else
		{
			$sql
				= 'SELECT TB.*, T1.`NICK_NAME`'
				. ' FROM `TWEET` TB'
				. ' LEFT JOIN `USER` T1 ON T1.`USER_ID` = TB.`USER_ID`'
				. ' WHERE TB.`REPLY_SEQ` = :SEQ'
				. ' ORDER BY `SEQ` DESC'
			;
			$prm = array(
				'SEQ' => $tweet_id,
			);
			$this->db->select($sql, $prm);
			$rows = $this->db->getCursor();

			$this->options['LEVEL_TWEETS'] = $this->tweet_parse_for_view($rows);
		}

		$this->options['HAS_TARGET'] = strlen($tweet_id) !== 0;

		$this->options['LIST_SENDMAIL'] = json_decode(LIST_SENDMAIL);
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

		$view->display(_PROJECT_DIR . "tpl-tweet.html");
	}
}

$page = new Page_This();
$page->run();
unset($page);

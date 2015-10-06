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
		if ($this->getParam('token') !== null)
		{
			switch (strtolower($this->getParam('command')))
			{
				case 'insert':
					// 更新
					if ($this->errors === null) { $this->insert_post(); }
					if ($this->errors === null) { $this->insert_validate(); }
					if ($this->errors === null) { $this->insert_save(); }
					if ($this->errors === null)
					{
						// 投稿に成功したら遷移
						$this->redirect('/single.php?seq=' . $this->options['SEQ']);
					}
					break;

				case 'update':
					// 更新
					if ($this->errors === null) { $this->update_post(); }
					if ($this->errors === null) { $this->update_validate(); }
					if ($this->errors === null) { $this->update_save(); }
					if ($this->errors === null)
					{
						// 投稿に成功したら遷移
						$this->redirect('/single.php?seq=' . $this->options['SEQ']);
					}
					break;

				case 'delete':
					// 更新
					if ($this->errors === null) { $this->delete_post(); }
					if ($this->errors === null) { $this->delete_validate(); }
					if ($this->errors === null) { $this->delete_save(); }
					if ($this->errors === null)
					{
						// 投稿に成功したら遷移
						$this->redirect('/single.php');
					}
					break;
			}
		}
	}

	/**
	 * ロジック処理
	 *
	 * @return	void
	 */
	private function insert_post()
	{
		$inputs = array(
			'TOKEN' => $this->getParam('token'),
			'SEQ' => $this->getParam('seq'),
			//
			'IMG' => isset($_FILES['img']) ? $_FILES['img']['name'] : null,
			'LBL' => $this->getParam('lbl'),
			'DT' => $this->getParam('dt'),
			'NOTE' => $this->getParam('note'),
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
	private function insert_validate()
	{
		$defs = array(
			'TOKEN' => array(
				'name'			=> 'ユニークキー',
				'required'		=> true,
			),
			//
			'IMG' => array(
				'name'			=> '写真',
				'required'		=> true,
			),
			'LBL' => array(
				'name'			=> 'カテゴリ',
				'required'		=> true,
				'length'		=> 50,
			),
			'DT' => array(
				'name'			=> '登録日',
				'required'		=> true,
				'date'			=> true,
			),
			'NOTE' => array(
				'name'			=> '備考',
				'required'		=> false,
				'length'		=> 1000,
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
	private function insert_save()
	{
		try
		{
			$this->db->beginTransaction();

			//

			$sql = <<< SQL_STRING
INSERT INTO `possession`(`uid`, `img`, `lbl`, `dt`, `num`, `note`, `crt_datetime`, `crt_userinfo`)
VALUES (:USER_ID, :IMAGE_NAME, :CATEGORY, :ENTRY_DT, NULL, :NOTE, NOW(), :USER_INFO)

SQL_STRING;
			$prm = array(
				'USER_ID' => $this->login_user_id,
				'IMAGE_NAME' => $this->inputs['IMG'],
				'CATEGORY' => $this->inputs['LBL'],
				'ENTRY_DT' => $this->inputs['DT'],
				'NOTE' => $this->inputs['NOTE'],
				'USER_INFO' => $_SERVER['REMOTE_ADDR'],
			);
			$this->db->execute($sql, $prm);

			$seq = $this->db->lastInsertId();

			//

			if (!file_exists(_UPLOAD_DIR . $this->login_user_id))
			{
				mkdir(_UPLOAD_DIR . $this->login_user_id, 0777);
			}

			$temp = $_FILES['img']['tmp_name'];
			$dest = _UPLOAD_DIR . $this->login_user_id . '/' . $seq;

			move_uploaded_file($temp, $dest);

			//

			$this->db->commit();
		}
		catch (Exception $ex)
		{
			$this->db->rollback();
			throw $ex;
		}

		$this->options['SEQ'] = $seq;
	}

	/**
	 * ロジック処理
	 *
	 * @return	void
	 */
	private function update_post()
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
	private function update_validate()
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
	private function update_save()
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
	}

	/**
	 * ロジック処理
	 *
	 * @return	void
	 */
	private function delete_post()
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
	private function delete_validate()
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
	private function delete_save()
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
	 * 表示前処理
	 *
	 * @return	void
	 */
	protected function prerender()
	{
		$this->options['TOKEN'] = Common::random();
		$this->getSession()->set('token', $this->options['TOKEN']);

		{
			$sql
				= 'SELECT DISTINCT `lbl` FROM `possession` WHERE `uid` = :USER_ID ORDER BY `lbl`'
			;
			$prm = array(
				'USER_ID' => $this->login_user_id,
			);
			$this->db->select($sql, $prm);
			$rows = $this->db->getCursor();

			$relay = [];

			foreach ($rows as $row)
			{
				$relay[] = $row['lbl'];
			}

			$this->options['LIST_LABEL'] = $relay;
		}
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

		$view->display(_RUN_DIR . 'entry.html');
	}
}

$page = new Page_This();
$page->run();
unset($page);

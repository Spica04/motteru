<?php
/**
 * Database Manager
 * ver.MySQL
 * @author higashi
 * @author tatsuuma
 *
 */
class DBManager
{
	/**
	 * MySQL の接続リソース
	 *
	 * @var	resource
	 */
	private $conn = null;

	/**
	 * 結果の値を保持する配列
	 *
	 * @var	array
	 */
	private $statement = null;

	/**
	 * 設定値を保持する配列
	 *
	 * @var	array
	 */
	private $config = null;

	/**
	 * エラー内容を示す文字列
	 *
	 * @var	string
	 */
	private $error = null;

	/**
	 * トランザクションの有無
	 *
	 * @var	string
	 */
	private $transaction = false;

	/**
	 * Instance
	 * 
	 * @var instance
	 */
	private static $_instance;

	// =========================================================================

	/**
	 * コンストラクタ
	 *
	 * @construct
	 */
	public function __construct($config)
	{
		$this->config = array_merge(array(
			'host' => null,
			'port' => null,
			'name' => null,
			'user' => null,
			'pass' => null,
		), $config);
	}

	/**
	 * デストラクタです。
	 *
	 */
	public function __destruct()
	{
		$this->disconnect();
	}

	/**
	 * データベースに接続します。
	 *
	 * @return	void
	 */
	public function connect()
	{
		$config_set = array(
			'host'    => $this->config["host"],
			'port'    => $this->config["port"],
			'dbname'  => $this->config["name"],
			'charset' => 'utf8',
		);

		$server = array();
		foreach ($config_set as $key => $val)
		{
			if (strlen($val) !== 0)
			{
				$server[] = $key . '=' . $val;
			}
		}
		try
		{
			$this->conn = new PDO(
				'mysql:' . implode(';', $server)
			,	$this->config["user"]
			,	$this->config["pass"]
			,	array(
					PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
					PDO::ATTR_EMULATE_PREPARES => false,
					PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8',
				)
			);
		}
		catch (PDOException $e)
		{
			$this->error = $e->getMessage();
			$this->raise();
		}

		$this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

		return true;
	}

	/**
	 * データベースとの接続を閉じます。
	 *
	 * @return	void
	 */
	public function disconnect()
	{
		if (!is_null($this->conn) && $this->transaction)
		{
			$this->rollback();
		}
		$this->conn = null;
	}

	/**
	 * クエリを送信します。
	 *
	 * @param	string	$query	クエリ
	 * @return	void
	 */
	public function execute($query = null, $params = array())
	{
		$definition = is_null($this->conn);
		if ($definition)
		{
			$this->connect();
		}

		//

		try
		{
			$stmt = $this->conn->prepare($query);
		}
		catch (Exception $ex)
		{
			$this->loged($ex->getMessage(), $query);
			throw $ex;
		}

		foreach ($params as $key => $val)
		{
			if (is_int($val))
			{
				$stmt->bindValue($key, $val, PDO::PARAM_INT);
			}
			else
			{
				$stmt->bindValue($key, $val);
			}
		}

		$stmt->execute();

		if ($stmt->errorCode() != '00000')
		{
			$this->loged($stmt->errorInfo(), $query);
		}

		//

		//if ($definition)
		//{
		//	$this->disconnect();
		//}
	}

	/**
	 * SELECTクエリを送信します。
	 * プロトタイプです。
	 *
	 * @param	array	$params	パラメータの配列
	 * @return	void
	 */
	public function select($query, $params = array())
	{
		$definition = is_null($this->conn);
		if ($definition)
		{
			$this->connect();
		}

		//

		preg_match_all("/:\w+/s", $query, $results);
		if (count($results[0]) !== count(array_unique($results[0])))
		{
			if (defined('_DEBUG') && _DEBUG)
			{
				d(debug_backtrace(), $query, $results);
			}
		}

		//

		try
		{
			$stmt = $this->conn->prepare($query);
		}
		catch (Exception $ex)
		{
			$this->loged($ex->getMessage(), $query);
			throw $ex;
		}

		foreach ($params as $key => $val)
		{
			if (is_int($val))
			{
				$stmt->bindValue($key, $val, PDO::PARAM_INT);
			}
			else
			{
				$stmt->bindValue($key, $val);
			}
		}

		$stmt->execute();

		if ($stmt->errorCode() != '00000')
		{
			$this->loged($stmt->errorInfo(), $query);
		}

		$this->statement = $stmt;

		return $this->statement;
	}

	/**
	 * 直前のカーソルを取得します。
	 *
	 * @return	array	直前のカーソル
	 */
	public function getCursor($index = null)
	{
		$result = null;

		if ($index === null)
		{
			$result = $this->statement->fetchAll(PDO::FETCH_ASSOC);
		}
		else
		{
			while ($row = $this->statement->fetch(PDO::FETCH_ASSOC))
			{
				if ($index <= 0)
				{
					$relay = array();
					foreach ($row as $key => $val)
					{
						if (!is_int($key))
						{
							$relay[$key] = $val;
						}
					}
					$result = $relay;
					break;
				}
				$index --;
			}

			$this->statement->closeCursor();
		}

		$this->statement = null;

		return $result;
	}

	/**
	 * 構造的例外を生成します。
	 *
	 * @return	void
	 */
	private function raise($message = null)
	{
		if (is_null($message))
		{
			$message = $this->error;
		}

		throw new Exception($message);
	}

	/**
	 * ログ
	 *
	 * @return	void
	 */
	private function loged($content, $query)
	{
		$relay = trim($query);
		$relay = preg_replace('/[\n\r\t]/', ' ', $relay);
		$relay = preg_replace('/\s(?=\s)/', '', $relay);

		if (!is_array($content))
		{
			$content = array($content);
		}
		$content[] = $_SERVER['REQUEST_URI'];
		$content[] = $relay;
		@file_put_contents(_LOG_DIR . "pdo" . date('Ymd') . ".log", "[" . date(DATE_ATOM) . "]\t" . implode("\t", $content) . "\n", FILE_APPEND | LOCK_EX);
	}

	/**
	 * lastInsertId
	 */
	public function lastInsertId($name = null)
	{
		return $this->conn->lastInsertId($name);
	}

	/**
	 * TRANSACTIONを開始します
	 *
	 * @return	void
	 */
	public function beginTransaction()
	{
		if (!$this->transaction)
		{
			if (is_null($this->conn))
			{
				$this->connect();
			}
			$this->conn->beginTransaction();
			$this->transaction = true;
		}
	}

	/**
	 * TRANSACTIONを採用します
	 *
	 * @return	void
	 */
	public function commit()
	{
		if ($this->transaction)
		{
			$this->transaction = false;
			$this->conn->commit();
			//$this->disconnect();
		}
	}

	/**
	 * TRANSACTIONを破棄します
	 *
	 * @return	void
	 */
	public function rollback()
	{
		if ($this->transaction)
		{
			$this->transaction = false;
			$this->conn->rollBack();
			//$this->disconnect();
		}
	}

	/**
	 * IN句生成
	 */
	public function parseQueryIn($key, $vals)
	{
		$query = array();

		foreach (is_array($vals) ? $vals : array($vals) as $val)
		{
			$query_key = $key . count($query);
			$query[$query_key] = $val;
		}

		return array(':' . implode(',:', array_keys($query)), $query);
	}
}

<?php
/**
 * バリデータクラスです。
 * multiple属性を持つselect要素や、
 * 同名name属性のラジオボックス・チェックボックスのことは考慮してないので、
 * 汎用的なクラスではありません
 * 
 * @author hoaki
 * @author tatsuuma
 * 
 * 入力項目のname属性値をキーとした連想配列に各検証項目を設定してください。
 * 
 * name			=> (string)			エラー表示時に使用される名前です。
 * 									指定しない場合は自身のキー名が使用されます。
 * 
 * parse		=> (array)			検証用の値に調整します
 * 					=> (callable)	実行条件を指定します。
 * 
 * file			=> (bool)			アップロードファイル検証です。
 * required		=> (bool)			必須入力検証です。
 * 				=> (array)			配列の場合は条件制。ref で指定されたキーが condition と同値だった場合に、
 * 									検証が実行されます。
 * 			ref			=> (string)		参照キーを指定します。
 * 			condition	=> (mixed)		実行条件を指定します。
 * length		=> (int)			文字長検証です。許容される最大文字数を指定してください。
 * nummax		=> (int)			数値の最大値指定です。
 * nummin		=> (int)			数値の最小値指定です。
 * alphanum		=> (bool)			半角英数検証です。
 * alpha		=> (bool)			半角英字検証です。
 * digit		=> (bool)			半角数字検証です。
 * bool			=> (bool)			真偽値検証です。
 * select		=> (int)			選択数の上限を検証する。
 * date			=> (bool)			日時の妥当性検証です。
 * email		=> (bool)			メールアドレス検証です。
 * url			=> (bool)			URL検証です。
 * tel			=> (bool)			電話番号検証です。
 * zip			=> (bool)			郵便番号検証です。
 * tag			=> (array)			HTMLタグ検証です。
 * 									指定したリスト以外の要素が含まれている時にエラーを出します。
 * regexp		=> (string)			正規表現での検証です。使用する正規表現を指定してください。
 * time_series	=> (string)			時系列の整合性検証です。
 * 									自分より過去でないといけないキーを指定してください。
 * custom		=> (callable)
 * custom_all	=> (callable)
 */
class Validator
{

	/** 半角英数検証に使う正規表現です */
	private $ALPHANUM_PATTERN = '/^[a-zA-Z0-9]*$/i';

	/** 半角英字検証に使う正規表現です */
	private $ALPHA_PATTERN = '/^[a-zA-Z]*$/i';

	/** Eメール検証に使う正規表現です */
	private $EMAIL_PATTERN = '/^[\w\-\.%]+@[a-z0-9\-]+(\.[a-z0-9\-]+)+$/i';

	/** URL検証に使う正規表現です */
	private $URL_PATTERN = '/^[a-z]+:\/\/./i';

	/** 電話番号検証に使う正規表現です */
	private $TEL_PATTERN = '/^(\d+\-\d+\-\d+|\d+)$/i';

	/** 郵便番号検証に使う正規表現です */
	private $ZIP_PATTERN = '/^(\d\d\d|\d\d\d-\d\d\d\d)$/i';

	/**
	 * エラーメッセージです。
	 * 
	 * @var	array
	 */
	public $errors = array();

	/**
	 * 検証情報
	 * 
	 * @var	array
	 */
	private $defs;

	/**
	 * 検証値
	 * 
	 * @var	array
	 */
	private $values;

	/**
	 * オプション値
	 * 
	 * @var	array
	 */
	public static $ENCODING = null;


	/**
	 * コンストラクタです。
	 * 
	 * @param	array	$defs
	 * @param	array	$values
	 * @return	void
	 */
	public function __construct($defs, $values)
	{
		$this->defs = $defs;
		$this->values = $values;
	}

	/**
	 * エラー情報を取得します。
	 * 
	 * @return	array
	 */
	public function getErrors()
	{
		return $this->errors;
	}

	/**
	 * VALIDATIONの為の値変換
	 *
	 */
	private function loop($skip_array_keys, $prefix, $data)
	{
		if (in_array($prefix, $skip_array_keys))
		{
			$this->values[$prefix] = $data;
			return;
		}
		if (strlen($prefix) !== 0)
		{
			$prefix .= "@";
		}
		foreach ($data as $key => $val)
		{
			if (is_array($val))
			{
				$this->loop($skip_array_keys, $prefix . $key, $val);
			}
			else
			{
				$this->values[$prefix . $key] = $val;
			}
		}
	}

	/**
	 * facadeメソッドです。
	 * 
	 * @return	int	エラー数
	 */
	public function run()
	{
		$skip_array_keys = array();
		foreach ($this->defs as $def_key => $def_val)
		{
			if (array_key_exists('parse', $def_val) && in_array('is_array', $def_val['parse']))
			{
				$skip_array_keys[] = $def_key;
			}
		}

		$src_values = $this->values;

		$this->values = array();
		$this->loop($skip_array_keys, "", $src_values);

		// ----

		//$names = array_intersect(array_keys($this->defs), array_keys($this->values));
		$names = array_keys($this->defs);

		$relay = array();
		foreach ($names as $name)
		{
			$error = $this->run_single($name);

			if (!is_null($error)) $relay[$name] = $error;
		}
		$this->errors = $relay;
		return count($this->errors);
	}

	/**
	 * 検証を実行します。
	 * 
	 * @param	$name
	 * @return	string	問題ない場合は null を返します。
	 */
	public function run_single($name)
	{
		$defs = $this->defs;

		// 定義が存在しないので null を返す
		if (!array_key_exists($name, $defs)) return null;

		$def = $defs[$name];

		// nameキーによる指定が無い場合は自身のキー名を使う
		if (!isset($def['name'])) $def['name'] = $name;

		//

		// valueの値を検証用に調整する
		$this->values[$name] = array_key_exists($name, $this->values) ? $this->_parse($name) : null;

		//

		// 必須項目
		if ($this->_required($name) === false)
		{
			return array_key_exists('select', $def)
				? sprintf("%sを選択してください。", $def['name'])
				: sprintf("%sを入力してください。", $def['name'])
			;
		}

		// ファイルアップロード
		$ret = $this->_file($name);
		if ($ret !== true)
		{
			return $ret;
		}

		// 文字長
		if ($this->_length($name) === false)
		{
			return sprintf("%sは%d文字以内で入力して下さい", $def['name'], $def['length']);
		}

		// 最大値
		if ($this->_nummax($name) === false)
		{
			return sprintf("%sは%d以下を入力して下さい", $def['name'], $def['nummax']);
		}

		// 最小値
		if ($this->_nummin($name) === false)
		{
			return sprintf("%sは%d以上を入力して下さい", $def['name'], $def['nummin']);
		}

		// 英数
		if ($this->_alphanum($name) === false)
		{
			return sprintf("%sは半角英数で入力して下さい", $def['name']);
		}

		// 英字
		if ($this->_alpha($name) === false)
		{
			return sprintf("%sは半角英字で入力して下さい", $def['name']);
		}

		// 数値
		if ($this->_digit($name) === false)
		{
			return sprintf("%sは半角数字で入力して下さい", $def['name']);
		}

		// 真偽値
		if ($this->_bool($name) === false)
		{
			return sprintf("%sは不正な入力です", $def['name']);
		}

		// 選択数上限
		if ($this->_select($name) === false)
		{
			return intval($def['select']) === 1
				? sprintf("%sは複数選択できません", $def['name'])
				: sprintf("%sは%dつ以内で選択して下さい", $def['name'], $def['select'])
			;
		}

		// 日付(タイムスタンプ含む)
		if ($this->_date($name) === false)
		{
			return sprintf("%sは不正な日付です", $def['name']);
		}

		// 時系列の整合性(タイムスタンプ含む)
		if ($this->_timeseries($name) === false)
		{
			$key = $def['time_series'];
			return sprintf("%sが%sより過去を指定されています", $def['name'], $defs[$key]['name']);
		}

		// メールアドレス
		if ($this->_email($name) === false)
		{
			return sprintf('%sは不正なメールアドレスです', $def['name']);
		}

		// URL
		if ($this->_url($name) === false)
		{
			return sprintf('%sは不正なURLです', $def['name']);
		}

		// 電話番号
		if ($this->_tel($name) === false)
		{
			return sprintf('%sは不正な電話番号です', $def['name']);
		}

		// 郵便番号
		if ($this->_zip($name) === false)
		{
			return sprintf('%sは不正な郵便番号です', $def['name']);
		}

		// HTMLタグ
		if ($this->_tag($name) === false)
		{
			return sprintf('%sに登録できないHTMLタグが含まれています', $def['name']);
		}

		// 正規表現
		if (array_key_exists('regexp', $def) && $this->_regexp($name, $def['regexp']) === false)
		{
			return sprintf('%sに使用できない文字が含まれています', $def['name']);
		}

		// カスタム検証
		$ret = $this->_custom($name);
		if ($ret !== true)
		{
			return $ret;
		}

		// カスタム検証
		$ret = $this->_custom_all($name);
		if ($ret !== true)
		{
			return $ret;
		}

		return null;
	}

	/**
	 * 検証用に値を調整します。
	 * 
	 * @param	string	$name
	 * @return	string
	 */
	private function _parse($name)
	{
		$defs   = $this->defs;
		$values = $this->values;
		$def    = $defs[$name];
		$value  = $values[$name];

		if (!array_key_exists('parse', $def) || !is_array($def['parse'])) return $value;

		foreach ($def['parse'] as $command)
		{
			if (is_callable($command))
			{
				$value = call_user_func($command, $value);
			}
		}

		return $value;
	}

	/**
	 * ファイルアップロード検証を行います。
	 * 
	 * @param	string	$name
	 * @return	bool
	 */
	private function _file($name)
	{
		$defs   = $this->defs;
		$values = $this->values;
		$def    = $defs[$name];
		$value  = $values[$name];

		if (!is_array($value) || !array_key_exists('file', $def)) return true;

		if (is_array($def['file']))
		{
			$extension =& $def['file']['ext'];

			if (!isset($extension))
			{
				// 不十分な定義です(必要なキーが足りない)
				trigger_error('form definition is insufficient');
			}

			$ext = array();
			$ext = split(COMMA, $extension);

			foreach ($ext as $k => $v) $ext[$k] = trim($v);
		}
		else
		{
			trigger_error('form definition is not array');
		}

		if (!array_key_exists('error', $value)
		 || !array_key_exists('tmp_name', $value)
		 || !array_key_exists('size', $value))
		{
			// 不十分なデータです(必要なキーが足りない)
			trigger_error('form value is insufficient');
		}

		switch ($value['error'])
		{
			case UPLOAD_ERR_NO_FILE:
				return true;

			case UPLOAD_ERR_OK:
				break;

			case UPLOAD_ERR_INI_SIZE:
				$max = ini_get('upload_max_filesize');
				return sprintf("%sで指定したファイルは %s byte を超えています", $def['name'], $max);

			case UPLOAD_ERR_PARTIAL:
				return sprintf("%sで指定したファイルは一部のみしかアップロードされていません", $def['name']);

			case UPLOAD_ERR_NO_TMP_DIR:	// テンポラリフォルダがありません
				return sprintf("%sのアップロードに失敗しました", $def['name']);

			case UPLOAD_ERR_CANT_WRITE:	// ディスクへの書き込みに失敗しました
				return sprintf("%sのアップロードに失敗しました", $def['name']);

			case UPLOAD_ERR_EXTENSION:	// ファイルのアップロードが拡張モジュールによって停止されました
				return sprintf("%sのアップロードに失敗しました", $def['name']);

			default:
				return sprintf("%sのアップロードに失敗しました", $def['name']);
		}

		// HTTP POSTによりアップロードされたファイルかどうかを調べる
		// これが false になる場合は結構タチが悪い
		if (!is_uploaded_file($value['tmp_name']))
		{
			return sprintf("%sで指定したファイルはHTTP POSTによりアップロードされたファイルではありません", $def['name']);
		}

		if ($value['size'] == 0)
		{
			return sprintf("%sで指定したファイルは 0 byte のファイルです", $def['name']);
		}

		$info = pathinfo($value['name']);

		if (array_search($info['extension'], $ext) === false)
		{
			return sprintf("%sで指定したファイルは許可されていない拡張子です", $def['name']);
		}

		return true;
	}

	/**
	 * 必須入力検証を行います。
	 * 
	 * @param	string	$name
	 * @return	bool
	 */
	private function _required($name)
	{
		$defs   = $this->defs;
		$values = $this->values;
		$def    = $defs[$name];
		$value  = $values[$name];

		if (!array_key_exists('required', $def)) return true;

		if (is_array($def['required']))
		{
			$required =& $def['required'];

			if (!isset($required['ref'], $required['condition']))
			{
				trigger_error('form definition is insufficient');
			}

			$ref =& $required['ref'];
			$condition =& $required['condition'];

			if ($values[$ref] != $condition) return true;
		}
		else if (!is_bool($def['required']))
		{
			trigger_error('form definition is not boolean');
		}

		/**
		 * multiple属性を持つselect要素や、
		 * 同名name属性のラジオボックス・チェックボックスは考慮してないので、
		 * 汎用的なクラスではありません
		 */
		if (is_array($value))
		{
			return !($def['required'] && count($value) == 0);
		}
		else
		{
			return !($def['required'] && strlen(trim($value)) == 0);
		}
//			if (!isset($value['error']))
//			{
//				// 不十分なデータです(必要なキーが足りない)
//				trigger_error('form value is insufficient');
//			}
//
//			return !is_null($value['name']);
	}

	/**
	 * 文字長検証を行います。
	 * 
	 * @param	string	$name
	 * @return	bool
	 */
	private function _length($name)
	{
		$defs   = $this->defs;
		$values = $this->values;
		$def    = $defs[$name];
		$value  = $values[$name];

		if (!array_key_exists('length', $def)) return true;

		if (!is_int($def['length']))
		{
			trigger_error('form definition is not integer');
		}
		return !($def['length'] < (self::$ENCODING === null ? mb_strlen($value) : mb_strlen($value, self::$ENCODING)));
	}

	/**
	 * 最大値検証を行います。
	 * 
	 * @param	string	$name
	 * @return	bool
	 */
	private function _nummax($name)
	{
		$defs   = $this->defs;
		$values = $this->values;
		$def    = $defs[$name];
		$value  = $values[$name];

		if (!array_key_exists('nummax', $def) || is_null($value) || strlen($value) == 0) return true;

		if (!is_int($def['nummax']))
		{
			trigger_error('form definition is not integer');
		}
		return !($def['nummax'] < $value);
	}

	/**
	 * 最小値検証を行います。
	 * 
	 * @param	string	$name
	 * @return	bool
	 */
	private function _nummin($name)
	{
		$defs   = $this->defs;
		$values = $this->values;
		$def    = $defs[$name];
		$value  = $values[$name];

		if (!array_key_exists('nummin', $def) || is_null($value) || strlen($value) == 0) return true;

		if (!is_int($def['nummin']))
		{
			trigger_error('form definition is not integer');
		}
		return !($value < $def['nummin']);
	}

	/**
	 * 数値検証を行います。
	 * 
	 * @param	string	$name
	 * @return	bool
	 */
	private function _digit($name)
	{
		$defs   = $this->defs;
		$values = $this->values;
		$def    = $defs[$name];
		$value  = $values[$name];

		if (!array_key_exists('digit', $def) || is_null($value) || strlen($value) == 0) return true;

		if (!is_bool($def['digit']))
		{
			trigger_error('form definition is not boolean');
		}
		return !($def['digit'] && !ctype_digit($value));
	}

	/**
	 * 真偽値検証を行います。
	 * 
	 * @param	string	$name
	 * @return	bool
	 */
	private function _bool($name)
	{
		$defs   = $this->defs;
		$values = $this->values;
		$def    = $defs[$name];
		$value  = $values[$name];

		if (!array_key_exists('bool', $def) || is_null($value) || strlen($value) == 0) return true;

		if (!is_bool($def['bool']))
		{
			trigger_error('form definition is not boolean');
		}
		return !($def['bool'] && !is_bool($value));
	}

	/**
	 * 日時の妥当性検証を行います。
	 * 
	 * @param	string	$name
	 * @return	bool
	 */
	private function _date($name)
	{
		$defs   = $this->defs;
		$values = $this->values;
		$def    = $defs[$name];
		$value  = $values[$name];

		if (!array_key_exists('date', $def) || is_null($value) || strlen($value) == 0) return true;

		if (!is_bool($def['date']))
		{
			trigger_error('form definition is not boolean');
		}
		return !($def['date'] && strToTime($value) === false);
	}

	/**
	 * 時系列検証を行います。
	 * 
	 * @param	string	$name
	 * @return	bool
	 */
	private function _timeseries($name)
	{
		$defs   = $this->defs;
		$values = $this->values;
		$def    = $defs[$name];
		$value  = $values[$name];

		if (!array_key_exists('time_series', $def) || is_null($value) || strlen($value) == 0) return true;

		if (!is_string($def['time_series']))
		{
			trigger_error('form definition is not string');
		}

		$target =& $def['time_series'];
		$past   =  $values[$target];

		if (empty($past)) $past = date('Y-m-d H:i:00', time());

		return !($def['time_series'] && strToTime($value) <= strToTime($past));
	}

	/**
	 * HTMLタグ検証を行います。
	 * 
	 * @param	string	$name
	 * @return	bool
	 */
	private function _tag($name)
	{
		$defs   = $this->defs;
		$values = $this->values;
		$def    = $defs[$name];
		$value  = $values[$name];

		if (!array_key_exists('tag', $def) || is_null($value) || strlen($value) == 0) return true;

		if (!is_array($def['tag']))
		{
			trigger_error('form definition is not array');
		}

		$after = strip_tags($value, join('', $def['tag']));

		return !($def['tag'] && $value != $after);
	}

	/**
	 * 正規表現を指定して検証を行います。
	 * 
	 * @param	string	$name
	 * @param	string	$key
	 * @param	string	$regexp
	 * @return	bool
	 */
	private function _regexp($name, $regexp, $key = 'regexp')
	{
		$defs   = $this->defs;
		$values = $this->values;
		$def    = $defs[$name];
		$value  = $values[$name];

		if (!array_key_exists($key, $def) || is_null($value) || strlen($value) == 0) return true;

		if ($key == 'regexp' && !is_string($def[$key]))
		{
			trigger_error('form definition is not string');
		}
		else if ($key != 'regexp' && !is_bool($def[$key]))
		{
			trigger_error('form definition is not boolean');
		}
		return !($def[$key] && !preg_match($regexp, $value));
	}

	/**
	 * 半角英数検証を行います。
	 * 
	 * @param	string	$name
	 * @return	bool
	 */
	private function _alphanum($name)
	{
		return $this->_regexp($name, $this->ALPHANUM_PATTERN, 'alphanum');
	}

	/**
	 * 半角英字検証を行います。
	 * 
	 * @param	string	$name
	 * @return	bool
	 */
	private function _alpha($name)
	{
		return $this->_regexp($name, $this->ALPHA_PATTERN, 'alpha');
	}

	/**
	 * Eメール検証を行います。
	 * 
	 * @param	string	$name
	 * @return	bool
	 */
	private function _email($name)
	{
		return $this->_regexp($name, $this->EMAIL_PATTERN, 'email');
	}

	/**
	 * URL検証を行います。
	 * 
	 * @param	string	$name
	 * @return	bool
	 */
	private function _url($name)
	{
		return $this->_regexp($name, $this->URL_PATTERN, 'url');
	}

	/**
	 * 電話番号検証を行います。
	 * 
	 * @param	string	$name
	 * @return	bool
	 */
	private function _tel($name)
	{
		return $this->_regexp($name, $this->TEL_PATTERN, 'tel');
	}

	/**
	 * 郵便番号検証を行います。
	 * 
	 * @param	string	$name
	 * @return	bool
	 */
	private function _zip($name)
	{
		return $this->_regexp($name, $this->ZIP_PATTERN, 'zip');
	}

	/**
	 * 選択数検証を行います。
	 * 
	 * @param	string	$name
	 * @return	bool
	 */
	private function _select($name)
	{
		$defs   = $this->defs;
		$values = $this->values;
		$def    = $defs[$name];
		$value  = $values[$name];

		if (!array_key_exists('select', $def)) return true;
		if ($def['select'] === null) return true;

		if (!is_int($def['select']))
		{
			trigger_error('form definition is not integer');
		}
		return !($def['select'] < count($value));
	}

	/**
	 * カスタム検証を行います。
	 * 
	 * @param	string	$name
	 * @return	bool
	 */
	private function _custom($name)
	{
		$defs   = $this->defs;
		$values = $this->values;
		$def    = $defs[$name];
		$value  = $values[$name];

		if (!array_key_exists('custom', $def)) return true;

		if (!is_callable($def['custom']))
		{
			trigger_error('form definition is not callable');
		}
		$error = call_user_func($def['custom'], $value, $def['name']);
		if (strlen($error) === 0) return true;

		return $error;
	}

	/**
	 * カスタム検証（値全体）を行います。
	 * 
	 * @param	string	$name
	 * @return	bool
	 */
	private function _custom_all($name)
	{
		$defs   = $this->defs;
		$values = $this->values;
		$def    = $defs[$name];
		$value  = $values[$name];

		if (!array_key_exists('custom_all', $def)) return true;

		if (!is_callable($def['custom_all']))
		{
			trigger_error('form definition is not callable');
		}
		$error = call_user_func($def['custom_all'], $values, $def['name']);
		if (strlen($error) === 0) return true;

		return $error;
	}
}

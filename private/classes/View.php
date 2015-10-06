<?php
/**
 * 巣のファイルをテンプレートにするクラスです。
 * 
 * @author tatsuuma
 *
 */
class View
{
	/**
	 *
	 */
	private $_charset = null;

	/**
	 *
	 */
	private $_vars = null;

	/**
	 *
	 */
	public function __construct($output_charset = null, $worker_charset = null)
	{
		if ($worker_charset === null && $output_charset !== null)
			throw new Exception('$worker_charset === null && $output_charset !== null');
		if ($worker_charset !== null && $output_charset === null)
			throw new Exception('$worker_charset !== null && $output_charset === null');

		if ($worker_charset !== null && $output_charset !== null && $worker_charset != $output_charset)
		{
			$this->_charset = array(
				'WORKER' => $worker_charset,
				'OUTPUT' => $output_charset,
			);
		}
	}

	/**
	 *
	 */
	public function display($template)
	{
		if ($this->_charset !== null)
		{
			header("Content-type: text/html; charset=" . $this->_charset['OUTPUT']);
		}
		include($template);
	}

	/**
	 *
	 */
	public function render($template)
	{
		ob_start();
		include($template);
		$out = ob_get_contents();
		ob_end_clean();
		return $out;
	}

	/**
	 *
	 */
	private function change_characode($val)
	{
		if ($this->_charset !== null)
		{
			if (is_array($val))
			{
				foreach (array_keys($val) as $key)
				{
					$val[$key] = $this->change_characode($val[$key]);
				}
			}
			else if (is_string($val))
			{
				$val = mb_convert_encoding($val, $this->_charset['OUTPUT'], $this->_charset['WORKER']);
			}
		}
		return $val;
	}

	/**
	 *
	 */
	public function __set($key, $val)
	{
		$val = $this->change_characode($val);
		$this->_vars[$key] = $val;
	}

	/**
	 *
	 */
	public function __get($key)
	{
		return $this->_vars[$key];
	}

	// ********************************************************************** //

	/**
	 * echo
	 *
	 */
	public function p($value, $def_value = null)
	{
		echo strlen($value) === 0 ? $def_value : $value;
	}

	/**
	 * echo and htmlspecialchars
	 *
	 */
	public function ph($value, $def_value = null)
	{
		echo strlen($value) === 0 ? $def_value : htmlspecialchars($value); //, ENT_QUOTES, $this->_charset['OUTPUT']);
	}

	/**
	 * echo and htmlspecialchars and nl2br
	 * 
	 */
	public function mh($value, $def_value = null)
	{
		echo strlen($value) === 0 ? $def_value : nl2br(htmlspecialchars($value)); //, ENT_QUOTES, $this->_charset['OUTPUT']);
	}

	/**
	 * implode
	 * 
	 */
	public function i(array $master, $value, $separator, $def_value = null)
	{
		$this->i_core($master, $value, $separator, $def_value, array());
	}

	/**
	 * implode and htmlspecialchars
	 * 
	 */
	public function ih(array $master, $value, $separator, $def_value = null)
	{
		$this->i_core($master, $value, $separator, $def_value, array("htmlspecialchars"));
	}

	/**
	 * implode and strip_tags
	 * 
	 */
	public function is(array $master, $value, $separator, $def_value = null)
	{
		$this->i_core($master, $value, $separator, $def_value, array("strip_tags"));
	}

	/**
	 * implode の処理本体
	 * 
	 */
	private function i_core(array $master, $value, $separator, $def_value, $flags)
	{
		if ($value === null)
		{
			$value = array();
		}
		else if (!is_array($value))
		{
			$value = array($value);
		}

		$flag_strip_tags = in_array("strip_tags", $flags);

		$relay = array();
		foreach ($value as $value_val)
		{
			if (array_key_exists($value_val, $master))
			{
				if ($flag_strip_tags)
				{
					$relay[] = strip_tags($master[$value_val]);
				}
				else
				{
					$relay[] = $master[$value_val];
				}
			}
			else
			{
				$relay[] = $def_value;
			}
		}
		$relay = implode($separator, $relay);

		if (in_array("htmlspecialchars", $flags))
		{
			$this->ph($relay, $def_value);
		}
		else
		{
			$this->p($relay, $def_value);
		}
	}

	/**
	 *
	 */
	public function selected($flag)
	{
		if ($flag)
		{
			echo 'selected="selected" ';
		}
	}

	/**
	 *
	 */
	public function checked($flag)
	{
		if ($flag)
		{
			echo 'checked="checked" ';
		}
	}

	/**
	 *
	 */
	public function checked_in($id, $value)
	{
		if (count($value) !== 0 && in_array($id, $value))
		{
			echo 'checked="checked" ';
		}
	}

	/**
	 *
	 */
	public function disabled($flag)
	{
		if ($flag)
		{
			echo 'disabled="disabled" ';
		}
	}

	/**
	 *
	 */
	public function showed($flag, $tag = true)
	{
		if ($flag)
		{
			echo $tag ? ' style="display: block;" ' : 'display: block;';
		}
	}
}

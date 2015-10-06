<?php
/**
 * 定数定義
 * 
 * @author tatsuuma
 *
 */

// 未入力
define('DEF_VIEW',				'----');

// マスターデータ
class Master
{
	/**
	 * 具体的な使途
	 */
	public $purpose = array(
		'NEW' => array(
			"LABEL" => "［現在］",
			"DIVISION" => 2015, // DBへの登録値
			"COUNT" => 5, // 現在の初期表示行数
		),
		'OLD' => array(
			"LABEL" => "［過去］",
			"DIVISION" => 2014, // DBへの登録値
			"COUNT" => 5, // 過去の初期表示行数
		),
	);

//	/**
//	 * コンストラクタ
//	 *
//	 * @construct
//	 */
//	public function __construct($config)
//	{
//	}
}

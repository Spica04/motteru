<?php
/**
 * Mailクラス定義
 * 
 * @author kitamura
 * @author hoaki
 * @author tatsuuma
 *
 */
class Mail
{
	/**
	 * mb_convert_encodingとmb_convert_encodingの組み合わせ
	 * 分割する長さは、現在 74 文字に固定されています
	 * 
	 * @param	mixed	$value
	 * @param	mixed	$to_encode
	 * @param	mixed	$from_encode
	 * @return	string
	 */
	private static function ToMimeheader($value, $to_encode, $from_encode)
	{
		$value = mb_convert_encoding($value, $to_encode, $from_encode);

		$split = 37;
		$pos = 0;

		$result = '';
		while ($pos < mb_strlen($value, $to_encode))
		{
			$output = mb_strimwidth($value, $pos, $split, "", $to_encode);
			$pos += mb_strlen($output, $to_encode);
			$result .= mb_encode_mimeheader($output, $to_encode, "B");
		}
		return $result;
	}

	/**
	 * メールの送信先を解析します。
	 * 
	 * @param	mixed	$address
	 * @return	string
	 */
	private static function ParseAddress($address, $to_encode, $from_encode)
	{
		$relay = array();
		foreach ($address as $address_key => $address_val)
		{
			if (is_string($address_key) && strlen($address_val) !== 0)
			{
				$address_val = str_replace("\"", "\\\"", $address_val);
				$relay[] = '"' . self::ToMimeheader($address_val, $to_encode, $from_encode) . '"' . " <" . $address_key . ">";
			}
			else
			{
				$relay[] = $address_val;
			}
		}
		return implode(", ", $relay);
	}

	/**
	 * メールを送信します。
	 * 
	 * @param	string	$to
	 * @param	string	$subject
	 * @param	string	$body
	 * @return	bool
	 */
	public static function Send($to, $subject, $body, $opt = array())
	{
		if (defined('_DEBUG') && _DEBUG)
		{
//			Logger::writeLine(sprintf("Common::sendMail\nTO    : %s\nTITLE : %s\nBODY  :\n%s"
//				, $to
//				, $subject
//				, $body
//			));
			return true;
		}

		$opt = array_merge(array(
			'FROM_MAIL' => MAIL_SENDER_ADDRES,
			'FROM_NAME' => MAIL_SENDER_NAME,
			'TO_ENCODE' => 'ISO-2022-JP-MS',
			'TO_ENCODE_WRITE' => 'ISO-2022-JP',
			'FROM_ENCODE' => 'AUTO',
			'CC' => null,
			'BCC' => null,
			'HTML_MAIL' => false,
			'EOL' => "\n",
		), $opt);

		// 複合時の区切り
		$boundary = uniqid(rand(), 1);

		mb_language('ja');

		// mb_encode_mimeheaderを使う前に…
		$default_internal_encode = mb_internal_encoding();
		if ($default_internal_encode != $opt['FROM_ENCODE'])
		{
			mb_internal_encoding($opt['FROM_ENCODE']);
		}

		if (is_array($to))
		{
			$to = self::ParseAddress($to, $opt['TO_ENCODE'], $opt['FROM_ENCODE']);
		}
		$cc = null;
		if (count($opt['CC']) !== 0)
		{
			$cc = self::ParseAddress($opt['CC'], $opt['TO_ENCODE'], $opt['FROM_ENCODE']);
		}
		$bcc = null;
		if (count($opt['BCC']) !== 0)
		{
			$bcc = self::ParseAddress($opt['BCC'], $opt['TO_ENCODE'], $opt['FROM_ENCODE']);
		}

		$headers = array();
		if ($opt['HTML_MAIL'])
		{
			$headers[] = sprintf("Content-Type: Multipart/alternative; boundary=\"%s\"", $boundary);
		}
		$headers[] = "MIME-Version: 1.0";
		$headers[] = sprintf("From: %s <%s>", self::ToMimeheader($opt['FROM_NAME'], $opt['TO_ENCODE'], $opt['FROM_ENCODE']), $opt['FROM_MAIL']);
		if ($cc !== null)
		{
			$headers[] = "Cc: " . $cc;
		}
		if ($bcc !== null)
		{
			$headers[] = "Bcc: " . $bcc;
		}
		$headers[] = sprintf("Reply-To: %s <%s>", self::ToMimeheader($opt['FROM_NAME'], $opt['TO_ENCODE'], $opt['FROM_ENCODE']), $opt['FROM_MAIL']);
		$headers[] = sprintf("Return-Path: %s", $opt['FROM_MAIL']);
		if (!$opt['HTML_MAIL'])
		{
			$headers[] = sprintf("Content-Type: text/plain; charset=%s", $opt['TO_ENCODE_WRITE']);
		}
		$headers = implode($opt['EOL'], $headers);

		$body = str_replace(array("\r\n", "\r"), "\n", $body);
		if ("\n" !== $opt['EOL'])
		{
			$body = str_replace("\n", $opt['EOL'], $body);
		}
		if ($opt['HTML_MAIL'])
		{
			$lines = array();

			$lines[] = "--" . $boundary;

			$lines[] = "Content-Type: text/plain; charset=" . $opt['TO_ENCODE_WRITE'];
			$lines[] = "Content-Transfer-Encoding: 7bit";
			$lines[] = "";
			$lines[] = mb_convert_encoding(strip_tags($body), $opt['TO_ENCODE'], $opt['FROM_ENCODE']);
			$lines[] = "";

			$lines[] = "--" . $boundary;

			$lines[] = "Content-Type: text/html; charset=" . $opt['TO_ENCODE_WRITE'];
			$lines[] = "Content-Transfer-Encoding: 7bit";
			$lines[] = "";
			$lines[] = mb_convert_encoding(str_replace($opt['EOL'], "<br />" . $opt['EOL'], $body), $opt['TO_ENCODE'], $opt['FROM_ENCODE']);

			$body = implode($opt['EOL'], $lines);
		}
		else
		{
			$body = mb_convert_encoding($body, $opt['TO_ENCODE'], $opt['FROM_ENCODE']);
		}

		$sendmail_params  = sprintf("-f %s", $opt['FROM_MAIL']);

		$subject = self::ToMimeheader($subject, $opt['TO_ENCODE'], $opt['FROM_ENCODE']);

		//$result = mail($to, $subject, $body, $headers, $sendmail_params);
		$result = mail($to, $subject, $body, $headers);

		// 元のinternal_encodeに戻す
		mb_internal_encoding($default_internal_encode);

		return $result;
	}
}

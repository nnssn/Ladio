<?php

/**
 * Program
 *
 * First edition 2014-12-07
 *
 * @author nnssn
 */

namespace Nnssn\Ladio;

/**
 * @property string    $relation_url   関連URL
 * @property string    $genre          ジャンル
 * @property string    $title          タイトル
 * @property string    $mount          放送マウント
 * @property \DateTime $start          放送開始時刻
 * @property int       $listener       現在のリスナー数
 * @property int       $total_listener 延べリスナー数
 * @property string    $server         配信サーバホスト名
 * @property int       $port           配信サーバポート番号
 * @property int       $bitrate        ビットレート
 * @property string    $detail_url     詳細URL
 * @property string    $dj             DJ名
 * @property string    $play_url       再生URL
 */

class Program
{
	private $relation_url;
	private $genre;
	private $title;
	private $mount;
	private $start;
	private $listener;
	private $total_listener;
	private $server;
	private $port;
	private $bitrare;
	private $detail_url;
	private $dj;
	private $play_url;

	public function __construct($line)
	{
		$l = (mb_detect_encoding($line) === "UTF-8")
				? $line
				: mb_convert_encoding($line, "UTF-8", "SJIS");
		$program = str_getcsv($l);

		$mix                  = $program[3];
		$this->relation_url   = $program[0];
		$this->genre          = $program[1];
		$this->title          = $program[2];
		$this->mount          = $program[4];
		$this->start          = \DateTime::createFromFormat("y/m/d H:i:s", $program[6]);
		$this->listener       = $program[7];
		$this->total_listener = $program[8];
		$this->server         = $program[9];
		$this->port           = $program[10];
		$this->bitrare        = $program[12];
		$this->detail_url     = (preg_match('#http://ladio.net/src/.{4}#', $mix, $url)) ? $url[0] : "";
		$this->dj             = (preg_match('#\【DJ\:(.*?)\】#', $mix, $dj)) ? $dj[1] : "";
		$this->play_url       = sprintf("http://%s:%s%s.m3u", $this->server, $this->port, $this->mount);
	}

	public function __get($name)
	{
		return (property_exists($this, $name)) ? $this->{$name} : null;
	}

	/**
	 * 比較実処理
	 * 
	 * @param mixed $key
	 * @param \callable $callback
	 * @return bool
	 */
	private function compare($key, callable $callback)
	{
		$properties = ($key)
				? [$key => $this->{$key}]
				: get_object_vars($this);
		foreach ($properties as $property) {
			$value = (is_a($property, "\DateTime"))
					? $property->format("Y-m-d H:i:s")
					: $property;
			if ($callback($value)) {
				return true;
			}
		}
		return false;
	}

	/**
	 * 正規表現で比較
	 * 
	 * @param string $pattern
	 * @param string|null $key
	 * @return boolean
	 */
	public function match($pattern, $key=null)
	{
		$callback = function($value) use($pattern) {
			return preg_match($pattern, $value);
		};
		return $this->compare($key, $callback);
	}

	/**
	 * 比較
	 * 
	 * @param string $word
	 * @param string|null $key
	 * @param bool $strict
	 * @return bool
	 */
	public function pos($word, $key=null, $strict=false)
	{
		$callback = ($strict)
				? function($value) use($word) {return $value === $word;}
				: function($value) use($word) {return mb_strpos($value, $word) !== false;};
		return $this->compare($key, $callback);
	}
}

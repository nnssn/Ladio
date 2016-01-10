<?php

/**
 * Table
 *
 * First edition 2014-12-07
 *
 * @author nnssn
 */

namespace Nnssn\Ladio;

class Table implements \IteratorAggregate
{
	const DEFAULT_CSV_URL = "http://yp.ladio.net/stats/list.csv";
	const SORT_ASC        = false;
	const SORT_DESC       = true;
	

	/**
	 * @var Program[]
	 */
	private $programs;

	/**
	 * CSVファイルの取得・パース
	 * 
	 * @param string $csv_url
	 * @return array
	 * @throws \RuntimeException CSVファイルの読み込みに失敗
	 */
	private static function loadCsvFile($csv_url)
	{
		$csv = @file($csv_url);
		if ($csv) {
			return $csv;
		}
		throw new \RuntimeException($csv_url ." couldn't be read.", 1);
	}

	/**
	 * データから番組クラスを生成
	 * 
	 * @param array $csv_lines
	 * @return Program[]
	 */
	private static function parse(array $csv_lines)
	{
		array_shift($csv_lines);
		$callback = function($line) {
			return new Program($line);
		};
		return array_map($callback, $csv_lines);
	}

	/**
	 * CSVファイルをダウンロード
	 * 
	 * @param string $filename
	 * @return int
	 */
	public static function download($filename="ladio.csv")
	{
		$lines = file_get_contents(self::DEFAULT_CSV_URL);
		return file_put_contents($filename, $lines);
	}

	/**
	 * 検索結果から新規インスタンスを生成
	 * 
	 * @param Program[] $programs
	 * @return static
	 */
	protected static function result(array $programs)
	{
		$new = new static(null);
		$new->programs = $programs;
		return $new;
	}

	/**
	 * コンストラクタ
	 * 
	 * @param string $csv_url
	 */
	public function __construct($csv_url=self::DEFAULT_CSV_URL)
	{
		if (is_string($csv_url)) {
			$lines = static::loadCsvFile($csv_url);
			$this->programs = static::parse($lines);
		}
	}

	/**
	 * 番組数
	 * 
	 * @return int
	 */
	public function count()
	{
		return count($this->programs);
	}

	/**
	 * 番組を取得
	 * 
	 * @param int|null $num
	 * @return Program|Program[]
	 */
	public function get($num=null)
	{
		if (is_null($num)) {
			return $this->programs;
		}
		$n = $num -1;
		return (isset($this->programs[$n])) ? $this->programs[$n] : null;
	}

	/**
	 * 最初の番組を取得
	 * 
	 * @return Program|null
	 */
	public function first()
	{
		if (! $this->count()) {
			return null;
		}
		return reset($this->programs);
	}

	/**
	 * 最後の番組を取得
	 * 
	 * @return Program|null
	 */
	public function last()
	{
		$count = $this->count();
		if (! $count) {
			return null;
		}
		return $this->programs[$count -1];
	}

	/**
	 * ランダムに1件取得
	 * 
	 * @return Program
	 */
	public function sample()
	{
		$num = array_rand($this->programs);
		return $this->programs[$num];
	}

	/**
	 * 検索実処理
	 * 
	 * @param callable $callback
	 * @return static
	 */
	private function compare(callable $callback)
	{
		$programs = array_merge(array_filter($this->programs, $callback));
		return static::result($programs);
	}

	/**
	 * 正規表現で検索
	 * 
	 * @param string $pattern
	 * @param string|null $key
	 * @return static
	 */
	public function match($pattern, $key=null)
	{
		$callback = function(Program $program) use($pattern, $key) {
			return ($program->match($pattern, $key));
		};
		return $this->compare($callback);
	}

	/**
	 * 検索
	 * 
	 * @param string $word
	 * @param string|null $key
	 * @param bool $strict
	 * @return static
	 */
	public function find($word, $key=null, $strict=false)
	{
		$callback = function(Program $program) use($word, $key, $strict) {
			return $program->pos($word, $key, $strict);
		};
		return $this->compare($callback);
	}

	/**
	 * いずれかの条件に一致
	 * 
	 * @param array $conditions [[key1, word1],[key2, word2],]
	 * @param bool $strict
	 * @return static
	 */
	public function conditionsAny(array $conditions, $strict=false)
	{
		$callback = function(Program $program) use($conditions, $strict) {
			foreach ($conditions as list($key, $value)) {
				if ($program->pos($value, $key, $strict)) {
					return true;
				}
			}
			return false;
		};
		return $this->compare($callback);
	}

	/**
	 * すべての条件に一致
	 * 
	 * @param array $conditions [[key1, word1],[key2, word2],]
	 * @param bool $strict
	 * @return static
	 */
	public function conditionsAll(array $conditions, $strict=false)
	{
		$callback = function(Program $program) use($conditions, $strict) {
			foreach ($conditions as list($key, $value)) {
				if (! ($program->pos($value, $key, $strict))) {
					return false;
				}
			}
			return true;
		};
		return $this->compare($callback);
	}

	/**
	 * フィルター
	 * 
	 * @return static
	 */
	public function filter(callable $callback)
	{
		return $this->compare($callback);
	}

	/**
	 * ソート
	 * 
	 * @param string $key
	 * @return $this
	 */
	public function sort($key="listener", $desc=false)
	{
		$numbers = ['listener', 'total_listener', 'bitrare'];
		if (is_callable($key)) {
			$callback = $key;
		}
		elseif ($key === "start") {
			$callback = function(Program $pro1, Program $pro2) use($key, $desc) {
				$res = ($pro1->start > $pro2->start) ? 1 : -1;
				return ($desc) ? -$res : $res;
			};
		}
		elseif(in_array($key, $numbers)) {
			$callback = function(Program $pro1, Program $pro2) use($key, $desc) {
				if ($pro1->{$key} === $pro2->{$key}) {
					return 0;
				}
				$res = ($pro1->{$key} > $pro2->{$key}) ? 1 : -1;
				return ($desc) ? -$res : $res;
			};
		}
		else {
			$callback = function(Program $pro1, Program $pro2) use($key, $desc) {
				$res = strcmp($pro1->{$key}, $pro2->{$key});
				return ($desc) ? -$res : $res;
			};
		}
		usort($this->programs , $callback);
		return $this;
	}

	/**
	 * IteratorAggregate
	 * 
	 * @return Program
	 */
	public function getIterator()
	{
		foreach ($this->programs as $program) {
			yield $program;
		}
	}
}

<?php

use Nnssn\Ladio\Table;
use Nnssn\Ladio\Program;

class TimetableTest extends \PHPUnit_Framework_TestCase
{
	const DOWNLOAD_FILENAME = 'tests/download.csv';
	const TEST_CSV_FILENAME = 'tests/test.csv';

	/**
	 * @var Table
	 */
	protected $object;

	protected function setUp()
	{
		if ($this->object) {
			return;
		}
		$this->object = new Table(self::TEST_CSV_FILENAME);
	}

	protected function tearDown()
	{
		file_exists(self::DOWNLOAD_FILENAME) and unlink(self::DOWNLOAD_FILENAME);
	}

	public function testLoadFail()
	{
		try {
			new Table("localhost/none/none.csv");
		}
		catch (Exception $e) {
			$this->assertGreaterThan(0, $e->getCode());
		}
	}

	public function testSample()
	{
		$result = $this->object->sample();
		$this->assertInstanceOf(Program::class, $result);
	}

	public function testDownload()
	{
		$size = Table::download(self::DOWNLOAD_FILENAME);
		$this->assertGreaterThan(0, $size);
		$this->assertFileExists(self::DOWNLOAD_FILENAME);
	}

	public function testMatchDone()
	{
		$title  = 'キッチンドランカー';
		$pattern  = '/キ.*?カー/';
		$result = $this->object->match($pattern, 'title');
		$this->assertEquals($title, $result->get(1)->title);
	}

	public function testMatchFail()
	{
		$title  = 'キッチンドランカー';
		$pattern  = '/キ.*?カーー/';
		$result = $this->object->match($pattern, 'title');
		$this->assertEmpty($result->count());
	}

	public function testFindDone()
	{
		$title  = 'キッチンドランカー';
		$result = $this->object->find($title, 'title', true);
		$this->assertEquals($title, $result->get(1)->title);
	}

	public function testFindFail()
	{
		$title  = 'キッチンイーター';
		$result = $this->object->find($title, 'title', true);
		$this->assertEmpty($result->count());
	}

	public function testConditionsAllDone()
	{
		//どちらも一致する番組がある
		$conditions = [
			['dj', 'おうか＆まな板'],
			['mount', '/manaita'],
		];
		$result = $this->object->conditionsAll($conditions, true);
		$this->assertEquals(1, $result->count());
	}

	public function testConditionsAllFail()
	{
		//マウントが一致しないのでダメ
		$conditions = [
			['dj', 'おうか＆まな板'],
			['mount', '/hukin'],
		];
		$result = $this->object->conditionsAll($conditions, true);
		$this->assertEmpty($result->count());
	}

	public function testConditionsAnyDone()
	{
		//それぞれ片方の条件に一致した2番組がヒット
		$conditions = [
			["mount", "/vipderajio"],
			["mount", "/manaita"],
		];
		$result = $this->object->conditionsAny($conditions, true);
		$this->assertEquals(2, $result->count());
	}

	public function testConditionsAnyFail()
	{
		//ありません
		$conditions = [
			["mount", "/nanjderajio"],
			["mount", "/hukin"],
		];
		$result = $this->object->conditionsAny($conditions, true);
		$this->assertEmpty($result->count());
	}

	public function testFilter()
	{
		//関連URLが設定されていない、かつリスナー数1人以上の放送だけを抽出
		$callback = function(Program $program) {
			return (! $program->relation_url && $program->listener >= 1);
		};
		$result = $this->object->filter($callback);
		$this->assertGreaterThan($result->count(), $this->object->count());
	}

	public function testSort()
	{
		//リスナー数順
		$this->object->sort("listener", Table::SORT_ASC);
		$this->assertGreaterThan($this->object->first()->listener, $this->object->last()->listener);
		$this->object->sort("listener", Table::SORT_DESC);
		$this->assertGreaterThan($this->object->last()->listener, $this->object->first()->listener);
		//放送開始順
		$this->object->sort("start", Table::SORT_ASC);
		$this->assertGreaterThan($this->object->first()->start, $this->object->last()->start);
		$this->object->sort("start", Table::SORT_DESC);
		$this->assertGreaterThan($this->object->last()->start, $this->object->first()->start);
		//番組名順
		$this->object->sort("title", Table::SORT_ASC);
		$this->assertGreaterThan($this->object->first()->title, $this->object->last()->title);
		$this->object->sort("title", Table::SORT_DESC);
		$this->assertGreaterThan($this->object->last()->title, $this->object->first()->title);
	}

	public function testIterator()
	{
		//foreachで回すと番組をひとつずつ返します
		$titles = '';
		foreach ($this->object as $program) {
			$titles .= $program->title;
		}
		$this->assertNotEmpty($titles);
	}
}

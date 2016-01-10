<?php


use Nnssn\Ladio\Program;

class ProgramTest extends \PHPUnit_Framework_TestCase
{
	const TEST_CSV_FILENAME = 'tests/test.csv';

	/**
	 * @var Program
	 */
	protected $object;

	protected function setUp()
	{
		if ($this->object) {
			return;
		}
		$csv = file(self::TEST_CSV_FILENAME);
		$this->object = new Program($csv[12]);
	}

	public function testPlayUrl()
	{
		$p = "|http://std\d.ladio.net:\d{4}/.*|";
		$play_url = $this->object->play_url;
		$res = preg_match($p, $play_url);
		$this->assertEquals(1, $res);
	}

	public function testMatchDone()
	{
		$pattern = "/おうか.+まな板/";
		$hit  = $this->object->match($pattern);
		$this->assertTrue($hit);
	}

	public function testMatchFail()
	{
		$pattern = "/おうか\d+まな板/";
		$hit  = $this->object->match($pattern);
		$this->assertFalse($hit);
	}

	public function testPosDone()
	{
		$word = "おうか＆まな板";
		$hit  = $this->object->pos($word);
		$this->assertTrue($hit);
	}

	public function testPosFail()
	{
		$word = "おうorまな板";
		$hit  = $this->object->pos($word);
		$this->assertFalse($hit);
	}
}

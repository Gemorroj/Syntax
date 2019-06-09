<?php
namespace Syntax\Tests;

use Syntax\Php;

class PhpTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Php
     */
    private $syntax;

    public function setUp()
    {
        $this->syntax = new Php();
    }

    public function testCheck()
    {
        $result = $this->syntax->check('<?php echo 1; ?>');

        self::assertEquals(['validity' => true, 'errors' => null], $result);
    }


    public function testCheckFile()
    {
        $result = $this->syntax->checkFile(__DIR__ . '/fixtures/correct.php');

        self::assertEquals(['validity' => true, 'errors' => null], $result);
    }


    public function testCheckFail()
    {
        $result = $this->syntax->check('<?php echo "; ?>');

        self::assertTrue(\is_array($result));

        self::assertFalse($result['validity']);
        self::assertTrue(\is_array($result['errors']));
        self::assertCount(1, $result['errors']);

        self::assertTrue(\is_null($result['errors'][0]['file']));
        self::assertTrue(\is_int($result['errors'][0]['code']));
        self::assertTrue(\is_int($result['errors'][0]['line']));
        self::assertTrue(\is_string($result['errors'][0]['type']));
        self::assertTrue(\is_string($result['errors'][0]['message']));
    }


    public function testCheckFileFail()
    {
        $result = $this->syntax->checkFile(__DIR__ . '/fixtures/fail.php');

        self::assertTrue(\is_array($result));

        self::assertFalse($result['validity']);
        self::assertTrue(\is_array($result['errors']));
        self::assertCount(1, $result['errors']);

        self::assertTrue(\is_string($result['errors'][0]['file']));
        self::assertTrue(\is_int($result['errors'][0]['code']));
        self::assertTrue(\is_int($result['errors'][0]['line']));
        self::assertTrue(\is_string($result['errors'][0]['type']));
        self::assertTrue(\is_string($result['errors'][0]['message']));

        self::assertEquals(__DIR__ . '/fixtures/fail.php', $result['errors'][0]['file']);
    }


    public function testFormatOutputHelper()
    {
        $result = Php::formatOutputHelper(
            '<?php echo ";' . "\n" . 'echo 1; ?>',
            1
        );

        self::assertEquals('<div class="syntax-code"><pre><code><span class="syntax-incorrect-line">1</span> <span style="color: #0000BB">&lt;?php </span><span style="color: #007700">echo </span><span style="color: #DD0000">";
<span class="syntax-correct-line">2</span> echo 1; ?&gt;</span>
</code></pre></div>', $result);
    }
}

<?php

namespace Syntax\Tests;

use PHPUnit\Framework\TestCase;
use Syntax\Php;

/**
 * @internal
 *
 * @coversNothing
 */
class PhpTest extends TestCase
{
    /**
     * @var Php
     */
    private $syntax;

    protected function setUp(): void
    {
        $this->syntax = new Php();
    }

    public function testCheck(): void
    {
        $result = $this->syntax->check('<?php echo 1; ?>');

        self::assertEquals(['validity' => true, 'errors' => null], $result);
    }

    public function testCheckFile(): void
    {
        $result = $this->syntax->checkFile(__DIR__.'/fixtures/correct.php');

        self::assertEquals(['validity' => true, 'errors' => null], $result);
    }

    public function testCheckFail(): void
    {
        $result = $this->syntax->check('<?php echo "; ?>');

        self::assertIsArray($result);

        self::assertFalse($result['validity']);
        self::assertIsArray($result['errors']);
        self::assertCount(1, $result['errors']);

        self::assertNull($result['errors'][0]['file']);
        self::assertIsInt($result['errors'][0]['code']);
        self::assertIsInt($result['errors'][0]['line']);
        self::assertIsString($result['errors'][0]['type']);
        self::assertIsString($result['errors'][0]['message']);
    }

    public function testCheckFileFail(): void
    {
        $result = $this->syntax->checkFile(__DIR__.'/fixtures/fail.php');

        self::assertIsArray($result);

        self::assertFalse($result['validity']);
        self::assertIsArray($result['errors']);
        self::assertCount(1, $result['errors']);

        self::assertIsString($result['errors'][0]['file']);
        self::assertIsInt($result['errors'][0]['code']);
        self::assertIsInt($result['errors'][0]['line']);
        self::assertIsString($result['errors'][0]['type']);
        self::assertIsString($result['errors'][0]['message']);

        self::assertEquals(__DIR__.'/fixtures/fail.php', $result['errors'][0]['file']);
    }

    public function testFormatOutputHelper(): void
    {
        $result = Php::formatOutputHelper(
            '<?php echo ";'."\n".'echo 1; ?>',
            1
        );

        self::assertEquals('<div class="syntax-code"><pre><code><span class="syntax-incorrect-line">1</span> <span style="color: #0000BB">&lt;?php </span><span style="color: #007700">echo </span><span style="color: #DD0000">";'."\n".
'<span class="syntax-correct-line">2</span> echo 1; ?&gt;</span>'."\n".
'</code></pre></div>', $result);
    }
}

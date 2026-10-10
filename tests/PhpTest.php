<?php

declare(strict_types=1);

namespace Syntax\Tests;

use PHPUnit\Framework\TestCase;
use Syntax\Php;

class PhpTest extends TestCase
{
    private Php $syntax;

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
        self::assertSame(1, $result['errors'][0]['line']);
        self::assertSame('Parse error', $result['errors'][0]['type']);
        self::assertStringContainsString('syntax error', $result['errors'][0]['message']);
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
        self::assertSame(4, $result['errors'][0]['line']);
        self::assertSame('Parse error', $result['errors'][0]['type']);
        self::assertStringContainsString('syntax error', $result['errors'][0]['message']);

        self::assertEquals(__DIR__.'/fixtures/fail.php', $result['errors'][0]['file']);
    }

    public function testCheckThrowsOnInvalidCli(): void
    {
        $this->syntax->setCli('definitely-not-a-php-binary');

        $this->expectException(\RuntimeException::class);

        $this->syntax->check('<?php echo 1; ?>');
    }

    public function testCheckRemovesTempFile(): void
    {
        $directory = \sys_get_temp_dir().'/syntax-test-'.\uniqid();
        \mkdir($directory);

        try {
            $this->syntax->setTempDirectory($directory);
            $this->syntax->check('<?php echo 1; ?>');

            self::assertSame([], \glob($directory.'/*'));
        } finally {
            \array_map('unlink', \glob($directory.'/*') ?: []);
            \rmdir($directory);
        }
    }

    public function testCli(): void
    {
        self::assertSame('php', $this->syntax->getCli());
        self::assertSame($this->syntax, $this->syntax->setCli('/usr/bin/php'));
        self::assertSame('/usr/bin/php', $this->syntax->getCli());
    }

    public function testTempDirectory(): void
    {
        self::assertSame(\sys_get_temp_dir(), $this->syntax->getTempDirectory());
        self::assertSame($this->syntax, $this->syntax->setTempDirectory('/tmp'));
        self::assertSame('/tmp', $this->syntax->getTempDirectory());
    }

    public function testSourceCharset(): void
    {
        self::assertNull($this->syntax->getSourceCharset());
        self::assertSame($this->syntax, $this->syntax->setSourceCharset('CP1251'));
        self::assertSame('CP1251', $this->syntax->getSourceCharset());
    }

    public function testResultCharset(): void
    {
        self::assertSame('UTF-8', $this->syntax->getResultCharset());
        self::assertSame($this->syntax, $this->syntax->setResultCharset('CP1251'));
        self::assertSame('CP1251', $this->syntax->getResultCharset());
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

    public function testFormatOutputHelperWithCustomClasses(): void
    {
        $result = Php::formatOutputHelper('<?php echo 1; ?>', 1, 'code', 'ok-line', 'bad-line');

        self::assertStringStartsWith('<div class="code"><pre><code>', $result);
        self::assertStringContainsString('<span class="bad-line">1</span> ', $result);
        self::assertStringEndsWith('</code></pre></div>', $result);
    }

    public function testFormatOutputHelperNumbersMultipleLines(): void
    {
        $lines = ['<?php'];
        for ($i = 1; $i <= 11; ++$i) {
            $lines[] = '$v'.$i.' = '.$i.';';
        }

        $result = Php::formatOutputHelper(\implode("\n", $lines), 12);

        self::assertStringContainsString('<span class="syntax-incorrect-line">12</span> ', $result);
        self::assertStringContainsString('&#160;1</span> ', $result);
        self::assertSame(11, \substr_count($result, 'class="syntax-correct-line"'));
        self::assertSame(1, \substr_count($result, 'class="syntax-incorrect-line"'));
    }
}

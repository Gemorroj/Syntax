<?php

declare(strict_types=1);

namespace Syntax\Tests;

use PHPUnit\Framework\TestCase;
use Syntax\Php;

class PhpTest extends TestCase
{
    private Php $syntax;

    /**
     * @var string[]
     */
    private array $tempFiles = [];

    protected function setUp(): void
    {
        $this->syntax = new Php();
    }

    protected function tearDown(): void
    {
        foreach ($this->tempFiles as $file) {
            if (\is_file($file)) {
                \unlink($file);
            }
        }

        $this->tempFiles = [];
    }

    public function testCheckValidSource(): void
    {
        self::assertValid($this->syntax->check(''));
        self::assertValid($this->syntax->check('<?php echo 1; ?>'));
    }

    public function testCheckReportsParseError(): void
    {
        self::assertError($this->syntax->check('<?php echo "; ?>'), null, 1);
    }

    public function testCheckReportsErrorLineNumber(): void
    {
        self::assertError($this->syntax->check("<?php\n\$a = 1;\necho \";"), null, 3);
    }

    public function testCheckFileReportsErrorWithFilePath(): void
    {
        $file = $this->writeTempFile('<?php echo ";');

        self::assertError($this->syntax->checkFile($file), $file, 1);
    }

    public function testCheckRemovesTempFile(): void
    {
        $this->withTempDirectory(function (string $directory): void {
            $this->syntax->check('<?php echo 1; ?>');

            self::assertSame([], \glob($directory.'/*'));
        });
    }

    public function testCheckRemovesTempFileOnFailure(): void
    {
        $this->withTempDirectory(function (string $directory): void {
            $this->syntax->setCli('definitely-not-a-php-binary');

            $thrown = null;
            try {
                $this->syntax->check('<?php echo 1; ?>');
            } catch (\RuntimeException $e) {
                $thrown = $e;
            }

            self::assertInstanceOf(\RuntimeException::class, $thrown);
            self::assertSame([], \glob($directory.'/*'));
        });
    }

    public function testOptions(): void
    {
        self::assertSame('php', $this->syntax->getCli());
        self::assertSame($this->syntax, $this->syntax->setCli('/usr/bin/php'));
        self::assertSame('/usr/bin/php', $this->syntax->getCli());

        self::assertSame(\sys_get_temp_dir(), $this->syntax->getTempDirectory());
        self::assertSame($this->syntax, $this->syntax->setTempDirectory('/tmp'));
        self::assertSame('/tmp', $this->syntax->getTempDirectory());

        self::assertNull($this->syntax->getSourceCharset());
        self::assertSame($this->syntax, $this->syntax->setSourceCharset('CP1251'));
        self::assertSame('CP1251', $this->syntax->getSourceCharset());

        self::assertSame('UTF-8', $this->syntax->getResultCharset());
        self::assertSame($this->syntax, $this->syntax->setResultCharset('CP1251'));
        self::assertSame('CP1251', $this->syntax->getResultCharset());
    }

    public function testConvertMessage(): void
    {
        $syntax = new class extends Php {
            public function convert(string $message): string
            {
                return $this->convertMessage($message);
            }
        };
        $syntax->setSourceCharset('CP1251')->setResultCharset('UTF-8');

        $cp1251 = \mb_convert_encoding('Привет', 'CP1251', 'UTF-8');

        self::assertSame('Привет', $syntax->convert($cp1251));
    }

    public function testFormatOutputHelper(): void
    {
        $result = Php::formatOutputHelper(
            '<?php echo ";'."\n".'echo 1; ?>',
            1
        );

        self::assertSame('<div class="syntax-code"><pre><code><span class="syntax-incorrect-line">1</span> <span style="color: #0000BB">&lt;?php </span><span style="color: #007700">echo </span><span style="color: #DD0000">";'."\n".
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

    private static function assertValid(array $result): void
    {
        self::assertSame(['validity' => true, 'errors' => null], $result);
    }

    private static function assertError(array $result, ?string $file, int $line): void
    {
        self::assertFalse($result['validity']);
        self::assertCount(1, $result['errors']);
        self::assertIsInt($result['errors'][0]['code']);
        self::assertSame($file, $result['errors'][0]['file']);
        self::assertSame($line, $result['errors'][0]['line']);
        self::assertSame('Parse error', $result['errors'][0]['type']);
        self::assertStringContainsString('syntax error', $result['errors'][0]['message']);
    }

    private function writeTempFile(string $source): string
    {
        $file = \tempnam(\sys_get_temp_dir(), 'syntax-test');
        self::assertNotFalse($file);
        \file_put_contents($file, $source);

        $this->tempFiles[] = $file;

        return $file;
    }

    private function withTempDirectory(callable $callback): void
    {
        $directory = \sys_get_temp_dir().'/syntax-test-'.\uniqid();
        \mkdir($directory);

        try {
            $this->syntax->setTempDirectory($directory);
            $callback($directory);
        } finally {
            \array_map('unlink', \glob($directory.'/*') ?: []);
            \rmdir($directory);
        }
    }
}

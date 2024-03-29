<?php

declare(strict_types=1);

namespace Syntax;

use Symfony\Component\Process\Process;

class Php
{
    // UNIX /usr/bin/php
    // BSD /usr/local/bin/php
    // Win C:/php/php.exe
    private string $cli = 'php';
    private string $tempDirectory;
    private ?string $sourceCharset = null;
    private string $resultCharset = 'UTF-8';

    public function __construct()
    {
        $this->tempDirectory = \sys_get_temp_dir();
    }

    public function setResultCharset(string $resultCharset): self
    {
        $this->resultCharset = $resultCharset;

        return $this;
    }

    public function getResultCharset(): string
    {
        return $this->resultCharset;
    }

    public function setSourceCharset(?string $sourceCharset): self
    {
        $this->sourceCharset = $sourceCharset;

        return $this;
    }

    public function getSourceCharset(): ?string
    {
        return $this->sourceCharset;
    }

    public function setCli(string $path): self
    {
        $this->cli = $path;

        return $this;
    }

    public function getCli(): string
    {
        return $this->cli;
    }

    public function setTempDirectory(string $path): self
    {
        $this->tempDirectory = $path;

        return $this;
    }

    public function getTempDirectory(): string
    {
        return $this->tempDirectory;
    }

    public function check(string $source): array
    {
        $file = \tempnam($this->getTempDirectory(), 'syntax');
        if (false === $file) {
            throw new \Exception('Could not create temp file');
        }
        $fp = \fopen($file, 'w');
        if (false === $fp) {
            throw new \Exception('Could not open temp file');
        }
        $write = \fwrite($fp, $source);
        if (false === $write) {
            throw new \Exception('Could not write source to temp file');
        }
        $close = \fclose($fp);
        if (false === $close) {
            throw new \Exception('Could not close temp file');
        }

        try {
            $result = $this->checkFile($file);
        } catch (\Throwable $e) {
            \unlink($file);

            throw $e;
        }

        \unlink($file);

        return $this->formatCheckOutput($result);
    }

    public function checkFile(string $file): array
    {
        $result = $this->execute($file);

        if (0 === $result['code']) {
            return [
                'validity' => true,
                'errors' => null,
            ];
        }

        $fullMessage = \preg_replace('/ in (?:.+) on line (?:[0-9]+)$/', '', $result['output']);
        \preg_match('/ on line ([0-9]+)$/', $result['output'], $matchLine);
        $line = isset($matchLine[1]) ? (int) ($matchLine[1]) : null;

        [$type, $message] = \explode(': ', $fullMessage);

        return [
            'validity' => false,
            'errors' => [
                [
                    'file' => $file,
                    'code' => $result['code'],
                    'line' => $line,
                    'type' => $type,
                    'message' => $this->convertMessage($message),
                ],
            ],
        ];
    }

    public static function formatOutputHelper(string $source, int $line, string $cssCodeClass = 'syntax-code', string $cssCodeCorrectLineClass = 'syntax-correct-line', string $cssCodeIncorrectLineClass = 'syntax-incorrect-line'): string
    {
        return '<div class="'.\htmlspecialchars($cssCodeClass).'"><pre><code>'.self::formatCode($source, $line, $cssCodeCorrectLineClass, $cssCodeIncorrectLineClass).'</code></pre></div>';
    }

    protected function formatCheckOutput(array $result): array
    {
        if (isset($result['errors'])) {
            \array_walk($result['errors'], static function (&$item) {
                $item['file'] = null;
            });
        }

        return $result;
    }

    protected function convertMessage(string $message): string
    {
        if (null !== $this->getSourceCharset()) {
            return \mb_convert_encoding($message, $this->getResultCharset(), $this->getSourceCharset());
        }

        return $message;
    }

    protected function execute(string $file): array
    {
        $process = new Process([$this->getCli(), '-d display_errors=1', '-l', $file]);
        $process->run();

        if ($process->isSuccessful()) {
            return [
                'output' => null,
                'code' => $process->getExitCode(),
            ];
        }

        $output = $process->getOutput();
        if (!$output) {
            throw new \Exception('Could not check syntax', $process->getExitCode() ?: 0);
        }

        return [
            'output' => \explode("\n", \trim($output))[0],
            'code' => $process->getExitCode(),
        ];
    }

    protected static function formatCode(string $source, int $line, string $cssCodeCorrectLineClass = 'syntax-correct-line', string $cssCodeIncorrectLineClass = 'syntax-incorrect-line'): string
    {
        $array = self::formatXhtmlHighlight($source);
        $all = \count($array);
        $len = \strlen((string) $all);
        $page = '';
        for ($i = 0; $i < $all; ++$i) {
            $next = $i + 1;
            $l = \strlen((string) $next);
            $page .= '<span class="'.\htmlspecialchars($line === $next ? $cssCodeIncorrectLineClass : $cssCodeCorrectLineClass).'">'.($l < $len ? \str_repeat('&#160;', $len - $l) : '').$next.'</span> '.$array[$i]."\n";
        }

        return $page;
    }

    protected static function formatXhtmlHighlight(string $source): array
    {
        $highlightString = \highlight_string($source, true);
        $highlightString = \str_replace(
            ['&nbsp;', '<code style="color: #000000">', '<code>', '</code>', '<pre>', '</pre>', '<br />'],
            [' ', '', '', '', '', '', "\n"],
            \preg_replace(
                '#color="(.*?)"#',
                'style="color: $1"',
                \str_replace(
                    ['<font ', '</font>'],
                    ['<span ', '</span>'],
                    $highlightString
                )
            )
        );

        $arr = \explode("\n", $highlightString);

        if (\PHP_VERSION_ID >= 80300) {
            return $arr;
        }

        return \array_slice(
            $arr,
            1,
            -2
        );
    }
}

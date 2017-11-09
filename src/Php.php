<?php
namespace Syntax;

use Symfony\Component\Process\ProcessBuilder;

class Php
{
    // UNIX /usr/bin/php
    // BSD /usr/local/bin/php
    // Win C:/php/php.exe
    private $cli = 'php';
    private $tempDirectory;
    private $sourceCharset;
    private $resultCharset = 'UTF-8';


    public function __construct()
    {
        $this->setTempDirectory(\sys_get_temp_dir());
    }

    /**
     * @param string $resultCharset
     *
     * @return Php
     */
    public function setResultCharset($resultCharset)
    {
        $this->resultCharset = $resultCharset;
        return $this;
    }

    /**
     * @return string
     */
    public function getResultCharset()
    {
        return $this->resultCharset;
    }

    /**
     * @param string $sourceCharset
     *
     * @return Php
     */
    public function setSourceCharset($sourceCharset)
    {
        $this->sourceCharset = $sourceCharset;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getSourceCharset()
    {
        return $this->sourceCharset;
    }


    /**
     * @param string $path
     * @return Php
     */
    public function setCli($path)
    {
        $this->cli = $path;
        return $this;
    }

    /**
     * @return string
     */
    public function getCli()
    {
        return $this->cli;
    }


    /**
     * @param string $path
     * @return Php
     */
    public function setTempDirectory($path)
    {
        $this->tempDirectory = $path;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getTempDirectory()
    {
        return $this->tempDirectory;
    }


    /**
     * @param string $source
     *
     * @return array
     * @throws \Exception
     */
    public function check($source)
    {
        $file = tempnam($this->getTempDirectory(), 'syntax');
        if (false === $file) {
            throw new \Exception('Could not create temp file');
        }
        $fp = fopen($file, 'w');
        if (false === $fp) {
            throw new \Exception('Could not open temp file');
        }
        $write = fwrite($fp, $source);
        if (false === $write) {
            throw new \Exception('Could not write source to temp file');
        }
        $close = fclose($fp);
        if (false === $close) {
            throw new \Exception('Could not close temp file');
        }


        try {
            $result = $this->checkFile($file);
        } catch (\Exception $e) {
            unlink($file);
            throw $e;
        }

        unlink($file);

        return $this->formatCheckOutput($result);
    }


    /**
     * @param array $result
     * @return array
     */
    protected function formatCheckOutput(array $result)
    {
        if (isset($result['errors'])) {
            array_walk($result['errors'], function (&$item) {
                $item['file'] = null;
            });
        }

        return $result;
    }


    /**
     * @param string $file
     *
     * @throws \Exception
     * @return array
     */
    public function checkFile($file)
    {
        $processBuilder = new ProcessBuilder();
        $processBuilder->setPrefix($this->getCli());
        $processBuilder->setArguments(array('-l', $file));
        $result = $this->execute($processBuilder);

        if (0 === $result['code']) {
            return array(
                'validity' => true,
                'errors' => null
            );
        }

        $fullMessage = preg_replace('/ in (?:.+) on line (?:[0-9]+)$/', '', $result['output']);
        preg_match('/ on line ([0-9]+)$/', $result['output'], $matchLine);
        $line = isset($matchLine[1]) ? intval($matchLine[1]) : null;

        list($type, $message) = explode(': ', $fullMessage);

        return array(
            'validity' => false,
            'errors' => array(
                array(
                    'file' => $file,
                    'code' => $result['code'],
                    'line' => $line,
                    'type' => $type,
                    'message' => $this->convertMessage($message)
                ),
            ),
        );
    }


    /**
     * @param string $message
     * @return string
     */
    protected function convertMessage($message)
    {
        if (null !== $this->getSourceCharset()) {
            return mb_convert_encoding($message, $this->getResultCharset(), $this->getSourceCharset());
        }

        return $message;
    }


    /**
     * @param ProcessBuilder $processBuilder
     * @return array
     * @throws \Exception
     */
    protected function execute(ProcessBuilder $processBuilder)
    {
        $process = $processBuilder->getProcess();
        $process->run();

        $output = $process->getOutput();
        if (!$output) {
            throw new \Exception('Could not check syntax', $process->getExitCode() ?: 0);
        }

        $data = explode("\n", trim($output));
        return array(
            'output' => $process->isSuccessful() ? null : $data[0],
            'code' => $process->getExitCode(),
        );
    }


    /**
     * @param string $source
     * @param int    $line
     * @param string $cssCodeClass
     * @param string $cssCodeCorrectLineClass
     * @param string $cssCodeIncorrectLineClass
     *
     * @return string
     */
    public static function formatOutputHelper($source, $line, $cssCodeClass = 'syntax-code', $cssCodeCorrectLineClass = 'syntax-correct-line', $cssCodeIncorrectLineClass = 'syntax-incorrect-line')
    {
        return '<div class="' . htmlspecialchars($cssCodeClass) . '"><pre><code>' . self::formatCode($source, $line, $cssCodeCorrectLineClass, $cssCodeIncorrectLineClass) . '</code></pre></div>';
    }


    /**
     * @param string $source
     * @param int    $line
     * @param string $cssCodeCorrectLineClass
     * @param string $cssCodeIncorrectLineClass
     *
     * @return string
     */
    protected static function formatCode($source, $line, $cssCodeCorrectLineClass, $cssCodeIncorrectLineClass)
    {
        $array = self::formatXhtmlHighlight($source);
        $all = sizeof($array);
        $len = strlen($all);
        $page = '';
        for ($i = 0; $i < $all; ++$i) {
            $next = (string)($i + 1);
            $l = strlen($next);
            $page .= '<span class="' . htmlspecialchars($line == $next ? $cssCodeIncorrectLineClass : $cssCodeCorrectLineClass) . '">' . ($l < $len ? str_repeat('&#160;', $len - $l) : '') . $next . '</span> ' . $array[$i] . "\n";
        }

        return $page;
    }


    /**
     * @param string $source
     *
     * @return array
     */
    protected static function formatXhtmlHighlight($source)
    {
        return array_slice(
            explode(
                "\n",
                str_replace(
                    array('&nbsp;', '<code>', '</code>', '<br />'),
                    array(' ', '', '', "\n"),
                    preg_replace(
                        '#color="(.*?)"#', 'style="color: $1"',
                        str_replace(
                            array('<font ', '</font>'),
                            array('<span ', '</span>'),
                            highlight_string($source, true)
                        )
                    )
                )
            ),
            1,
            -2
        );
    }
}

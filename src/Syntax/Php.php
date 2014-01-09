<?php
namespace Syntax;

/**
 *
 * This software is distributed under the GNU GPL v3.0 license.
 * @author Gemorroj
 * @copyright 2013 http://wapinet.ru
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 * @link https://github.com/Gemorroj/Syntax
 * @version 0.1
 *
 */
class Php
{
    // UNIX /usr/bin/php
    // BSD /usr/local/bin/php
    // Win C:/php/php.exe
    private $cli = 'php';
    private $tempDirectory;
    private $sourceCharset;
    private $resultCharset = 'UTF-8';


    /**
     * @param string $resultCharset
     *
     * @return $this
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
     * @return $this
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
        $cmd = escapeshellcmd($this->getCli()) . ' -c -f -l ' . escapeshellarg($file);
        $result = $this->execute($cmd);

        if (0 === $result['code']) {
            return array('validity' => true, 'errors' => null);
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
     * @param string $cmd
     * @return array
     * @throws \Exception
     */
    protected function execute($cmd)
    {
        exec($cmd, $output, $code);

        if (!$output) {
            throw new \Exception('Could not check syntax', $code);
        }

        return array(
            'output' => isset($output[1]) ? $output[1] : null,
            'code' => $code,
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

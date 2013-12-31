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
            $result = $this->checkFile($file, true);
        } catch (\Exception $e) {
            unlink($file);
            throw $e;
        }

        unlink($file);

        return $result;
    }


    /**
     * @param string $file
     * @param bool $hideFile
     *
     * @return array
     * @throws \Exception
     */
    public function checkFile($file, $hideFile = false)
    {
        exec(escapeshellcmd($this->getCli()) . ' -c -f -l ' . escapeshellarg($file), $rt, $v);

        $size = sizeof($rt);

        if (!$size) {
            throw new \Exception('Could not check syntax');
        }

        if ($v === 255 || $size > 2) {
            $error = $rt[1];

            if (null !== $this->getSourceCharset()) {
                $error = mb_convert_encoding($error, $this->getResultCharset(), $this->getSourceCharset());
            }
            if (true === $hideFile) {
                $error = str_replace($file, '...', $error);
            }
            $result = array(
                'line' => (int) preg_replace('/.*\s(\d*)$/', '$1', $error, 1),
                'description' => $error,
            );
        } else {
            $result = array();
        }

        return $result;
    }


    /**
     * @param string $source
     * @param int $line
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
     * @param int $line
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

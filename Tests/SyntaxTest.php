<?php

class SyntaxTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var Syntax
     */
    private $syntax;

    public function setUp()
    {
        require_once dirname(__FILE__) . '/../Syntax.php';
        $this->syntax = new Syntax();
        //$this->syntax->setCli('s:\OpenServer\modules\php\PHP-5.4.13\php.exe');
    }

    public function testCheck()
    {
        $result = $this->syntax->check('<?php echo 1; ?>');

        $this->assertEquals(array(), $result);
    }


    public function testCheckFile()
    {
        $result = $this->syntax->checkFile(dirname(__FILE__) . '/correct.php');

        $this->assertEquals(array(), $result);
    }


    public function testCheckFail()
    {
        $result = $this->syntax->check('<?php echo "; ?>');

        if (version_compare(PHP_VERSION, '5.3.0', '<')) {
            $expect = array(
                'line' => 1,
                'description' => 'Parse error: syntax error, unexpected $end in ... on line 1'
            );
        } elseif (version_compare(PHP_VERSION, '5.4.0', '<')) {
            $expect = array(
                'line' => 1,
                'description' => 'Parse error: syntax error, unexpected $end, expecting T_VARIABLE or T_DOLLAR_OPEN_CURLY_BRACES or T_CURLY_OPEN in ... on line 1'
            );
        } else {
            $expect = array(
                'line' => 1,
                'description' => 'Parse error: syntax error, unexpected end of file, expecting variable (T_VARIABLE) or ${ (T_DOLLAR_OPEN_CURLY_BRACES) or {$ (T_CURLY_OPEN) in ... on line 1'
            );
        }

        $this->assertEquals($expect, $result);
    }


    public function testCheckFileFail()
    {
        $result = $this->syntax->checkFile(dirname(__FILE__) . '/fail.php');

        if (version_compare(PHP_VERSION, '5.3.0', '<')) {
            $expect = array(
                'line' => 4,
                'description' => 'Parse error: syntax error, unexpected $end in ' . dirname(__FILE__) . '/fail.php on line 4'
            );
        } elseif (version_compare(PHP_VERSION, '5.4.0', '<')) {
            $expect = array(
                'line' => 4,
                'description' => 'Parse error: syntax error, unexpected $end, expecting T_VARIABLE or T_DOLLAR_OPEN_CURLY_BRACES or T_CURLY_OPEN in ' . dirname(__FILE__) . '/fail.php on line 4'
            );
        } else {
            $expect = array(
                'line' => 4,
                'description' => 'Parse error: syntax error, unexpected end of file, expecting variable (T_VARIABLE) or ${ (T_DOLLAR_OPEN_CURLY_BRACES) or {$ (T_CURLY_OPEN) in ' . dirname(__FILE__) . '/fail.php on line 4'
            );
        }

        $this->assertEquals($expect, $result);
    }


    public function testFormatOutputHelper()
    {
        $result = Syntax::formatOutputHelper(
            '<?php echo ";' . "\n" . 'echo 1; ?>',
            1
        );

        $this->assertEquals('<div class="syntax-code"><pre><code><span class="syntax-incorrect-line">1</span> <span style="color: #0000BB">&lt;?php </span><span style="color: #007700">echo </span><span style="color: #DD0000">";
<span class="syntax-correct-line">2</span> echo 1; ?&gt;</span>
</code></pre></div>', $result);
    }
}

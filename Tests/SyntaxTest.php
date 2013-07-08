<?php

class SyntaxTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var Syntax
     */
    private $syntax;

    public function setUp()
    {
        require_once __DIR__ . '/../Syntax.php';
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
        $result = $this->syntax->checkFile(__DIR__ . '/correct.php');

        $this->assertEquals(array(), $result);
    }


    public function testCheckFail()
    {
        $result = $this->syntax->check('<?php echo "; ?>');


        $this->assertEquals(array(
            'line' => 1,
            'description' => 'Parse error: syntax error, unexpected end of file, expecting variable (T_VARIABLE) or ${ (T_DOLLAR_OPEN_CURLY_BRACES) or {$ (T_CURLY_OPEN) in ... on line 1'
        ), $result);
    }


    public function testCheckFileFail()
    {
        $result = $this->syntax->checkFile(__DIR__ . '/fail.php');

        $this->assertEquals(array(
            'line' => 4,
            'description' => 'Parse error: syntax error, unexpected end of file, expecting variable (T_VARIABLE) or ${ (T_DOLLAR_OPEN_CURLY_BRACES) or {$ (T_CURLY_OPEN) in ' . __DIR__ . '/fail.php on line 4'
        ), $result);
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

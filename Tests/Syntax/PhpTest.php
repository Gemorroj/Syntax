<?php
namespace Tests\Syntax;

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
        //$this->syntax->setCli('s:\OpenServer\modules\php\PHP-5.4.13\php.exe');
    }

    public function testCheck()
    {
        $result = $this->syntax->check('<?php echo 1; ?>');

        $this->assertEquals(array('validity' => true, 'errors' => null), $result);
    }


    public function testCheckFile()
    {
        $result = $this->syntax->checkFile(__DIR__ . '/correct.php');

        $this->assertEquals(array('validity' => true, 'errors' => null), $result);
    }


    public function testCheckFail()
    {
        $result = $this->syntax->check('<?php echo "; ?>');

        if (version_compare(PHP_VERSION, '5.4.0', '<')) {
            $expect = array(
                'validity' => false,
                'errors' => array(
                    array(
                        'file' => null,
                        'code' => -1,
                        'line' => 1,
                        'type' => 'Parse error',
                        'message' => 'syntax error, unexpected $end, expecting T_VARIABLE or T_DOLLAR_OPEN_CURLY_BRACES or T_CURLY_OPEN',
                    )
                ),
            );
        } else {
            $expect = array(
                'validity' => false,
                'errors' => array(
                    array(
                        'file' => null,
                        'code' => -1,
                        'line' => 1,
                        'type' => 'Parse error',
                        'message' => 'syntax error, unexpected end of file, expecting variable (T_VARIABLE) or ${ (T_DOLLAR_OPEN_CURLY_BRACES) or {$ (T_CURLY_OPEN)',
                    )
                ),
            );
        }

        // remove file
        $result['errors'][0]['file'] = null;

        $this->assertEquals($expect, $result);
    }


    public function testCheckFileFail()
    {
        $result = $this->syntax->checkFile(__DIR__ . '/fail.php');

        if (version_compare(PHP_VERSION, '5.4.0', '<')) {
            $expect = array(
                'validity' => false,
                'errors' => array(
                    array(
                        'file' => __DIR__ . '/fail.php',
                        'code' => -1,
                        'line' => 4,
                        'type' => 'Parse error',
                        'message' => 'syntax error, unexpected end of file, expecting variable (T_VARIABLE) or ${ (T_DOLLAR_OPEN_CURLY_BRACES) or {$ (T_CURLY_OPEN)',
                    )
                ),
            );
        } else {
            $expect = array(
                'validity' => false,
                'errors' => array(
                    array(
                        'file' => __DIR__ . '/fail.php',
                        'code' => -1,
                        'line' => 4,
                        'type' => 'Parse error',
                        'message' => 'syntax error, unexpected end of file, expecting variable (T_VARIABLE) or ${ (T_DOLLAR_OPEN_CURLY_BRACES) or {$ (T_CURLY_OPEN)',
                    )
                ),
            );
        }

        $this->assertEquals($expect, $result);
    }


    public function testFormatOutputHelper()
    {
        $result = Php::formatOutputHelper(
            '<?php echo ";' . "\n" . 'echo 1; ?>',
            1
        );

        $this->assertEquals('<div class="syntax-code"><pre><code><span class="syntax-incorrect-line">1</span> <span style="color: #0000BB">&lt;?php </span><span style="color: #007700">echo </span><span style="color: #DD0000">";
<span class="syntax-correct-line">2</span> echo 1; ?&gt;</span>
</code></pre></div>', $result);
    }
}

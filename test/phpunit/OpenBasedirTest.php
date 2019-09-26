<?php

class OpenBasedirTest extends phpunit_bootstrap
{
    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testOpenBasedir()
    {
        chdir($this->root_directory);
        $previousOpenBasedir = ini_set('open_basedir', implode(PATH_SEPARATOR, array(
            $this->root_directory,
            sys_get_temp_dir(),
        )));
        if ($previousOpenBasedir === false) {
            $this->markTestSkipped('Unable to change open_basedir');
        }
        $error = null;
        $css = null;
        try {
            $parser = new Less_Parser(array());
            $parser->parseFile($this->fixtures_dir . '/open_basedir'.'/subfolder/main.less');
            $css = $parser->getCss();
            
        } catch (Exception $x) {
            $error = $x;
        } catch (Throwable $x) {
            $error = $x;
        }
        $this->assertNull($error);
        $this->assertSame('.existing{color:green}', preg_replace('/[\s;]+/', '', $css));
    }
}

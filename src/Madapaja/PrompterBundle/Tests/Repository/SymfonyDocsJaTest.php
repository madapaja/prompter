<?php

namespace Madapaja\PrompterBundle\Tests\Repository;

use Madapaja\PrompterBundle\Repository\SymfonyDocsJaRepository;

class SymfonyDocsJaTest extends BaseTest
{
    public function getBaseDir()
    {
        return __DIR__.'/Resource/SymfonyDocsJa/';
    }

    public function getRepository()
    {
        return new SymfonyDocsJaRepository($this->getBaseDir());
    }

    public function getFileInfo($filename)
    {
        return $this->getRepository($this->getBaseDir())
            ->getFileInfo($this->getBaseDir().$filename);
    }

    /**
     * @test
     */
    public function testGetFilename()
    {
        $this->assertSame($this->getBaseDir().'1.txt', $this->getFileInfo('1.txt')->filename);
    }

    /**
     * @test
     */
    public function testGetRealpath()
    {
        $this->assertSame($this->getFileInfo('1.txt')->filename, $this->getFileInfo('1.txt')->realpath);
    }

    /**
     * @test
     */
    public function testGetPathname()
    {
        $this->assertSame('1.txt', $this->getFileInfo('1.txt')->pathname);
    }

    /**
     * @test
     */
    public function testGetBasename()
    {
        $this->assertSame('1.txt', $this->getFileInfo('1.txt')->basename);
    }

    /**
     * @test
     */
    public function testGetVersion()
    {
        $this->assertSame('5c17bf', $this->getFileInfo('1.txt')->version);
        $this->assertSame('96e813', $this->getFileInfo('2.txt')->version);
        $this->assertSame('abcdef', $this->getFileInfo('3.txt')->version);
        $this->assertSame(null,  $this->getFileInfo('5.txt')->version);

        $this->assertSame('5c17bf', $this->getFileInfo('1.txt')->getVersion());
        $this->assertSame('5c17', $this->getFileInfo('1.txt')->getVersion(4));
    }

    /**
     * @test
     */
    public function testGetModified()
    {
        $this->assertSame('2012/07/01', $this->getFileInfo('1.txt')->modified->format('Y/m/d'));
        $this->assertSame('2012/07/05', $this->getFileInfo('2.txt')->modified->format('Y/m/d'));
        $this->assertSame('2012/07/02', $this->getFileInfo('3.txt')->modified->format('Y/m/d'));
        $this->assertSame(null, $this->getFileInfo('5.txt')->modified);
    }

    /**
     * @test
     */
    public function testGetSource()
    {
        $this->assertSame(
            "edit message\n"
                ."..   2012/07/01    out    5c17bfz無視されるはず\n",
            $this->getFileInfo('1.txt')->getSource()
        );
        $this->assertSame(
            ".. 2012/07/03 madapaja 00000000\n"
                ."edit message\n"
                ."    .. 2012/07/05 sync 96E813z\n",
            $this->getFileInfo('2.txt')->getSource()
        );
        $this->assertSame(
            "    .. 2012/07/02 ERROR abcdef\n"
                .".. 2012/07/01 madapaja 123456\n",
            $this->getFileInfo('3.txt')->getSource()
        );
        $this->assertSame(
            "test\n",
            $this->getFileInfo('5.txt')->getSource()
        );
    }

    /**
     * @test
     */
    public function testGetDiff()
    {
        try {
            $this->getFileInfo('1.txt')->getDiff('');
        } catch (\Exception $e) {
            $this->assertSame("not supported", $e->getMessage());
            return;
        }

        $this->fail('期待した例外が発生しない');
    }
}
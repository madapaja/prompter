<?php

namespace Madapaja\PrompterBundle\Tests\Repository;

use Madapaja\PrompterBundle\Repository\GitRepository;
use Symfony\Component\Process\ProcessBuilder;

class GitTest extends BaseTest
{
    function setUp()
    {
        if (is_dir($this->getBaseDir())) {
            $builder = new ProcessBuilder(array('rm', '-rf', $this->getBaseDir()));
            $builder->setWorkingDirectory(dirname($this->getBaseDir()));

            $process = $builder->getProcess();
            $process->run();

            if (!$process->isSuccessful()) {
                throw new \RuntimeException($process->getErrorOutput());
            }
        }

        $builder = new ProcessBuilder(array('git', 'clone', 'git://github.com/madapaja/prompter-test.git', $this->getBaseDir()));
        $builder->setWorkingDirectory(dirname($this->getBaseDir()));

        $process = $builder->getProcess();
        $process->run();

        if (!$process->isSuccessful()) {
            throw new \RuntimeException($process->getErrorOutput());
        }
    }

    public function getBaseDir()
    {
        return __DIR__.'/Resource/Git/';
    }

    public function getRepository()
    {
        return new GitRepository($this->getBaseDir());
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
        $this->assertSame('197efa1eec70834d359f86baed175acd6984cd13', $this->getFileInfo('1.txt')->version);
        $this->assertSame('5c17bf562f978342f7255aa28b3b1e9ed88513d5', $this->getFileInfo('2.txt')->version);
        $this->assertSame('96e8131acbc5d7d9429d78296d2a4c7a819528d9', $this->getFileInfo('3.txt')->version);
        $this->assertSame('96e8131acbc5d7d9429d78296d2a4c7a819528d9', $this->getFileInfo('4.txt')->version);

        $this->assertSame('197efa1eec70834d359f86baed175acd6984cd13', $this->getFileInfo('1.txt')->getVersion(), 'getVersionMethod()');
        $this->assertSame('197ef', $this->getFileInfo('1.txt')->getVersion(5), 'getVersionMethod(5)');
    }

    /**
     * @test
     */
    public function testGetModified()
    {
        $this->assertSame('2012/07/08 17:28:30 +0900', $this->getFileInfo('1.txt')->modified->format('Y/m/d H:i:s O'));
        $this->assertSame('2012/07/08 17:28:14 +0900', $this->getFileInfo('2.txt')->modified->format('Y/m/d H:i:s O'));
        $this->assertSame('2012/07/08 17:28:55 +0900', $this->getFileInfo('3.txt')->modified->format('Y/m/d H:i:s O'));
        $this->assertSame('2012/07/08 17:28:55 +0900', $this->getFileInfo('4.txt')->modified->format('Y/m/d H:i:s O'));
    }

    /**
     * @test
     */
    public function testGetSource()
    {
        $this->assertSame("edit message\n", $this->getFileInfo('1.txt')->getSource());
        $this->assertSame("edit message\n", $this->getFileInfo('2.txt')->getSource());
        $this->assertSame("test\n", $this->getFileInfo('3.txt')->getSource());
        $this->assertSame("test\n", $this->getFileInfo('4.txt')->getSource());
    }

    /**
     * @test
     */
    public function testGetDiff()
    {
        $this->assertSame('', $this->getFileInfo('1.txt')->getDiff('197efa1eec70834d359f86baed175acd6984cd13'));
        $this->assertSame(
            "diff --git a/1.txt b/1.txt\n"
                ."index e69de29..9c13a05 100644\n"
                ."--- a/1.txt\n"
                ."+++ b/1.txt\n"
                ."@@ -0,0 +1 @@\n"
                ."+edit message\n",
            $this->getFileInfo('1.txt')->getDiff('b532fce0be9d7f61e115145892a2bb9698045c1e')
        );
        $this->assertSame(
            "diff --git a/1.txt b/1.txt\n"
                ."index d014168..9c13a05 100644\n"
                ."--- a/1.txt\n"
                ."+++ b/1.txt\n"
                ."@@ -1 +1 @@\n"
                ."-this is test\n"
                ."+edit message\n",
            $this->getFileInfo('1.txt')->getDiff('01e59a5cf7b35638e80c0fa7941a624671802ed5')
        );
        $this->assertSame(
            "diff --git a/1.txt b/1.txt\n"
                ."index d014168..9c13a05 100644\n"
                ."--- a/1.txt\n"
                ."+++ b/1.txt\n"
                ."@@ -1 +1 @@\n"
                ."-this is test\n"
                ."+edit message\n",
            $this->getFileInfo('1.txt')->getDiff('5c17bf562f978342f7255aa28b3b1e9ed88513d5')
        );
        $this->assertSame('', $this->getFileInfo('2.txt')->getDiff('197efa1eec70834d359f86baed175acd6984cd13'));
    }
}

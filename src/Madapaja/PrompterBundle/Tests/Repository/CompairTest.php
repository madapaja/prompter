<?php

namespace Madapaja\PrompterBundle\Tests\Repository;

use Madapaja\PrompterBundle\Repository\CompareFileInfo;
use Madapaja\PrompterBundle\Repository\GitRepository;
use Madapaja\PrompterBundle\Repository\SymfonyDocsJaRepository;
use Symfony\Component\Process\ProcessBuilder;

/**
 * @group unit
 * @group db
 */
class CompairTest extends BaseTest
{
    public function getGitBaseDir()
    {
        return __DIR__.'/Resource/Git/';
    }

    function setUp()
    {
        if (is_dir($this->getGitBaseDir())) {
            $builder = new ProcessBuilder(array('rm', '-rf', $this->getGitBaseDir()));
            $builder->setWorkingDirectory(dirname($this->getGitBaseDir()));

            $process = $builder->getProcess();
            $process->run();

            if (!$process->isSuccessful()) {
                throw new \RuntimeException($process->getErrorOutput());
            }
        }

        $builder = new ProcessBuilder(array('git', 'clone', 'git://github.com/madapaja/prompter-test.git', $this->getGitBaseDir()));
        $builder->setWorkingDirectory(dirname($this->getGitBaseDir()));

        $process = $builder->getProcess();
        $process->run();

        if (!$process->isSuccessful()) {
            throw new \RuntimeException($process->getErrorOutput());
        }
    }

    public function getCompereFileInfo($file)
    {
        $gitRepos = new GitRepository($this->getGitBaseDir());
        $gitFile = is_file($this->getGitBaseDir().$file)
            ? $gitRepos->getFileInfo($this->getGitBaseDir().$file)
            : null;

        $jaRepos = new SymfonyDocsJaRepository(__DIR__.'/Resource/SymfonyDocsJa/');
        $jaFile = is_file(__DIR__.'/Resource/SymfonyDocsJa/'.$file)
            ? $jaRepos->getFileInfo(__DIR__.'/Resource/SymfonyDocsJa/'.$file)
            : null;

        return new CompareFileInfo($gitFile, $jaFile);
    }

    /**
     * @test
     */
    public function compareStatus()
    {
        $this->assertSame(CompareFileInfo::OUTDATED, $this->getCompereFileInfo('1.txt')->compare()->status);
        $this->assertSame(CompareFileInfo::SYNCHRONIZED, $this->getCompereFileInfo('2.txt')->compare()->status);
        $this->assertSame(CompareFileInfo::UNKNOWN, $this->getCompereFileInfo('3.txt')->compare()->status);
        $this->assertSame(CompareFileInfo::NO_DEST, $this->getCompereFileInfo('4.txt')->compare()->status);
        $this->assertSame(CompareFileInfo::NO_SRC, $this->getCompereFileInfo('5.txt')->compare()->status);
    }

    /**
     * @test
     */
    public function diff()
    {
        $this->assertSame(
            "diff --git a/1.txt b/1.txt\n"
                ."index d014168..9c13a05 100644\n"
                ."--- a/1.txt\n"
                ."+++ b/1.txt\n"
                ."@@ -1 +1 @@\n"
                ."-this is test\n"
                ."+edit message\n",
            $this->getCompereFileInfo('1.txt')->compare()->getDiff()
        );
        $this->assertSame(false, $this->getCompereFileInfo('2.txt')->compare()->getDiff());
    }
}

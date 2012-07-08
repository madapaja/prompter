<?php

namespace Madapaja\PrompterBundle\Repository;

use Symfony\Component\Process\ProcessBuilder;

class GitRepository extends Repository
{
    public function command($args)
    {
        array_unshift($args, 'git');

        $builder = new ProcessBuilder($args);
        $builder->setWorkingDirectory($this->getBaseDir());

        $process = $builder->getProcess();
        $process->run();

        if (!$process->isSuccessful()) {
            throw new \RuntimeException($process->getErrorOutput());
        }

        return $process->getOutput();
    }

    public function getFileInfo($filename, $version = null)
    {
        return new GitFileInfo($this, $filename, $version);
    }
}
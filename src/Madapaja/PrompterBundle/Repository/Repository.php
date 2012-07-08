<?php

namespace Madapaja\PrompterBundle\Repository;

abstract class Repository implements RepositoryInterface
{
    private $baseDir;

    public function __construct($baseDir)
    {
        $this->setBaseDir(realpath($baseDir));
    }

    protected function setBaseDir($baseDir)
    {
        if (!is_dir($baseDir)) {
            throw new \InvalidArgumentException('dir not found: '.$baseDir);
        }

        $this->baseDir = $baseDir;
        return $this;
    }

    public function getBaseDir()
    {
        return $this->baseDir;
    }
}
<?php

namespace Madapaja\PrompterBundle\Repository;

interface RepositoryInterface
{
    public function __construct($baseDir);
    public function getBaseDir();
    public function getFileInfo($filename, $version = null);
}

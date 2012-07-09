<?php

namespace Madapaja\PrompterBundle\Repository;

class SymfonyDocsJaRepository extends Repository
{
    public function getFileInfo($filename, $version = null)
    {
        return new SymfonyDocsJaFileInfo($this, $filename, $version);
    }
}

<?php

namespace Madapaja\PrompterBundle\Repository;

interface FileInfoInterface
{
    public function __construct(Repository $repository, $filename, $version = null);
    public function getRepository();
    public function asArray(array $keys = null);
    public function __get($name);
    public function getVersion($maxLength = null);
    public function getSource();
    public function getDiff($version);
}
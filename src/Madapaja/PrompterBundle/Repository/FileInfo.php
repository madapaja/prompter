<?php

namespace Madapaja\PrompterBundle\Repository;

abstract class FileInfo implements FileInfoInterface
{
    protected $repository;
    protected $filename;
    protected $version;
    protected $modified;

    public function __construct(Repository $repository, $filename, $version = null)
    {
        if (!is_file($filename)) {
            throw new \Exception('file not found: '.$filename);
        }

        $this->repository = $repository;
        $this->filename = realpath($filename);
        $this->version = $version;
    }

    public function getRepository()
    {
        return $this->repository;
    }

    abstract protected function fetch();

    public function __get($name)
    {
        switch ($name) {
            case 'filename':
            case 'realpath':
                return $this->filename;
                break;
            case 'pathname':
                return substr($this->filename, strlen($this->repository->getBaseDir()) + 1);
                break;
            case 'basename':
                return basename($this->filename);
                break;

            case 'version':
            case 'modified':
                $this->fetch();
                break;
        }

        return isset($this->$name) ? $this->$name : null;
    }

    public function asArray(array $keys = null)
    {
        if (is_null($keys)) {
            $keys = array('filename', 'pathname', 'basename', 'version', 'modified');
        }

        $result = array();
        foreach ($keys as $key) {
            $result[$key] = $this->__get($key);
        }

        return $result;
    }

    public function getVersion($maxLength = null)
    {
        $this->fetch();
        return $maxLength ? substr($this->version, 0, $maxLength) : $this->version;
    }

    public function getSource()
    {
        return file_get_contents($this->filename);
    }
}

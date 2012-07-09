<?php

namespace Madapaja\PrompterBundle\Repository;

class GitFileInfo extends FileInfo
{
    protected $fetched = false;

    public function __construct(Repository $repository, $filename, $version = null)
    {
        if (!($repository instanceof GitRepository)) {
            throw \Exception();
        }

        parent::__construct($repository, $filename, $version);
    }

    protected function fetch()
    {
        if ($this->fetched) {
            return;
        }

        // git log -1 --pretty=%ci,%H [VERSION] FILE in ORIGIN_ROOT
        $args = array('log', '-1', '--pretty=%ci,%H');

        if ($this->version) {
            $args[] = $this->version;
        }

        $args[] = $this->filename;

        $result = trim($this->repository->command($args));

        if (!strpos($result, ',')) {
            throw new \RuntimeException('no log');
        }

        list($modified, $version) = explode(',', $result);

        $this->modified = new \DateTime($modified);
        $this->version = $version;

        $this->fetched = true;
    }

    public function getDiff($version)
    {
        $this->fetch();
        return $this->repository->command(array('diff', $version.'..'.$this->version, $this->filename));
    }
}

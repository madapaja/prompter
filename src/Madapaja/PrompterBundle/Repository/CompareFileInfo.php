<?php

namespace Madapaja\PrompterBundle\Repository;

class CompareFileInfo
{
    const SYNCHRONIZED = 1;
    const OUTDATED = 2;
    const UNKNOWN = 3;
    const NO_SRC = 4;
    const NO_DEST = 5;

    public $src;
    public $dest;
    public $status;

    public function __construct(FileInfoInterface $src = null, FileInfoInterface $dest = null)
    {
        $this->src = $src;
        $this->dest = $dest;
        $this->status = static::UNKNOWN;
    }

    protected function isMatchVersion($a, $b)
    {
        if (min(strlen($a), strlen($b)) < 4) {
            throw new \Exception('too short version text');
        }

        if (strlen($a) > strlen($b)) {
            $long = $a;
            $short = $b;
        } else {
            $long = $b;
            $short = $a;
        }

        return strpos(strtolower($long), strtolower($short)) === 0;
    }

    protected function isSameVersion()
    {
        if ($this->isMatchVersion($this->src->version, $this->dest->version)) {
            return true;
        }

        try {
            $fileInfo = $this->src->getRepository()
                ->getFileInfo($this->src->realpath, $this->dest->version);
        } catch (\Exception $e) {
            return false;
        }

        return $this->isMatchVersion($this->src->version, $fileInfo->version);
    }

    public function compare()
    {
        $this->status = static::UNKNOWN;

        if (!($this->src instanceof FileInfoInterface)) {
            $this->status = static::NO_SRC;
            return $this;
        }

        if (!($this->dest instanceof FileInfoInterface)) {
            $this->status = static::NO_DEST;
            return $this;
        }

        if (!$this->src->version || !$this->dest->version) {
            $this->status = static::UNKNOWN;
            return $this;
        }

        try  {
            $this->status = $this->isSameVersion() ? static::SYNCHRONIZED : static::OUTDATED;
            return $this;
        } catch (\Exception $e) {
            $this->status = static::UNKNOWN;
        }

        return $this;
    }

    public function getDiff()
    {
        if ($this->status !== static::OUTDATED) {
            return false;
        }

        return $this->src->getDiff($this->dest->getVersion());
    }

    public function getSource()
    {
        return $this->src->getSource();
    }
}

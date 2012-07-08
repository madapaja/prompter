<?php

namespace Madapaja\PrompterBundle\Repository;

class SymfonyDocsJaFileInfo extends FileInfo
{
    protected $fetched = false;
    protected $translator;

    public function __construct(Repository $repository, $filename, $version = null)
    {
        parent::__construct($repository, $filename, $version);
    }

    public function __get($name)
    {
        if ($name == 'translator') {
            $this->fetch();
        }

        return parent::__get($name);
    }

    protected function fetch()
    {
        if ($this->fetched) {
            return;
        }

        $file = new \SplFileObject($this->filename);
        $file->setFlags(\SplFileObject::SKIP_EMPTY);

        $fileInfo = false;
        while (!$file->eof()) {
            $line = trim($file->fgets());

            // .. YYYY/MM/DD USERNAME COMMIT_HASH
            if (preg_match('@
                \.\. # ..
                \s+
                ( # date
                    [0-9]{4}
                    [/-]
                    [01]?[0-9]
                    [/-]
                    [0123]?[0-9]
                )
                \s+
                ([a-z_.\@-]+) # username
                \s+
                ([0-9a-f]{6,}) # commit hash
            @iux', $line, $matches)
            ) {
                $temp = array(
                    'version' => strtolower($matches[3]),
                    'modified' => new \DateTime($matches[1]),
                    'translator' => $matches[2],
                );

                if (!$fileInfo || $fileInfo['modified'] < $temp['modified']) {
                    $fileInfo = $temp;
                }
            }
        }

        if (!$fileInfo) {
            // file info not found
            return;
        }

        $this->modified = $fileInfo['modified'];
        $this->version = $fileInfo['version'];
        $this->translator = $fileInfo['translator'];

        $this->fetched = true;
    }

    public function asArray(array $keys = null)
    {
        if (is_null($keys)) {
            $keys = array('filename', 'pathname', 'basename', 'version', 'modified', 'translator');
        }

        return parent::asArray($keys);
    }

    public function getDiff($version)
    {
        throw new \Exception('not supported');
    }
}
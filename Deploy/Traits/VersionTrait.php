<?php

namespace App\Submodules\ToolsLaravelMicroservice\Deploy\Traits;

trait VersionTrait
{
    protected $currentVersion;

    protected $newVersion;

    protected function outputCurrentVersion()
    {
        $version = $this->git->getTags();

        if (count($version) < 1) {
            $version[0] = "0.0.0";
        }

        $this->currentVersion = array_pop($version);

        $this->out('Current version is:', 'line', "\n ");
        $this->out($this->currentVersion, 'line', "\n\t");
    }

    protected function incrementVersion()
    {
        if ($this->argument('version') == 'none') {
            return false;
        }

        $version = $this->convertToInt(explode(".", $this->currentVersion));

        switch ($this->argument('version')) {
            case 'none':
                break;

            case 'patch':
                $version = $this->incrementPatch($version);
                $this->newVersion = 1;
                break;

            case 'minor':
                $version = $this->incrementMinor($version);
                $this->newVersion = 1;
                break;

            case 'major':
                $version = $this->incrementMajor($version);
                $this->newVersion = 1;
                break;

            default:
                break;
        }

        $this->newVersion = implode(".", $version);

        $this->out('New version tag will be:', 'comment', " \n");
        $this->out($this->newVersion, 'line', "\n\t");
    }

    protected function incrementPatch($version)
    {
        $increment = $version[2];

        $increment++;
        $version[2] = $increment;

        return $version;
    }

    protected function incrementMinor($version)
    {
        $increment = $version[1];
        $increment++;

        $version[1] = $increment;
        $version[2] = 0;

        return $version;
    }

    protected function incrementMajor($version)
    {
        $increment = $version[0];
        $increment++;

        $version[0] = $increment;
        $version[1] = 0;
        $version[2] = 0;

        return $version;
    }

    protected function convertToInt(array $array)
    {
        foreach ($array as &$element) {
            $element = intval($element);
        }

        return $array;
    }
}

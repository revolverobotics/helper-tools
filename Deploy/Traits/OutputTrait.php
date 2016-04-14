<?php

namespace App\Submodules\ToolsLaravelMicroservice\Deploy\Traits;

trait OutputTrait
{
    protected $outputBuffer;

    protected $logTime;

    protected $logApp;

    protected $logStart = false;

    /*
        line        - Bright text
        info        - Dull text
        comment     - gold text
        question    - blue background text
        error       - red background text
     */
    protected $outputType = ['line', 'info', 'comment', 'question', 'error'];

    protected function out($output = null, $outputType = 'line', $indent = ' ')
    {
        if (is_null($output)) {
            return;
        }

        if (!$this->logStart) {
            $this->logStart = true;
            $this->logTime = $this->pushTime;

            $path = explode("/", base_path());
            $this->logApp = array_pop($path);
        }

        if (is_array($output)) {
            foreach($output as $line)
            {
                $this->$outputType($indent.$line);
                $this->outputToLog($indent.$line);
            }
        } elseif (is_string($output)) {
            $this->$outputType($indent.$output);
            $this->outputToLog($indent.$output);
        }
    }

    protected function outputToLog($line)
    {
        file_put_contents(
            '/tmp/'.$this->logTime.'_'.$this->logApp.'_push_log.txt',
            $line."\n",
            FILE_APPEND
        );
    }

    protected function outError($line)
    {
        $this->out("\n ERROR: {$line}\n", 'error', "\n");
    }

    protected function outWarning($line)
    {
        $this->out("\n WARNING: {$line}\n", 'error');
    }

    protected function outputSeparator()
    {
        $this->out(PHP_EOL.'-----------------------------------------------'
            .PHP_EOL, 'line', '');
    }

    protected function clearOutputBuffer()
    {
        $this->outputBuffer = [];
    }


    protected function searchOutput($searchTerm)
    {

    }
}

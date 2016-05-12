<?php

namespace App\Submodules\ToolsLaravelMicroservice\Deploy\Traits;

use Symfony\Component\Console\Formatter\OutputFormatterStyle;

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

    /**
     * Write a string as standard output, allow our custom styles
     *
     * @param  string  $string
     * @param  string  $style
     * @param  null|int|string  $verbosity
     * @return void
     */
    public function line($string, $style = null, $verbosity = null)
    {
        $style1 = new OutputFormatterStyle('white', null, ['bold']);
        $this->output->getFormatter()->setStyle('white', $style1);

        $cyan = new OutputFormatterStyle('cyan', null, ['bold']);
        $this->output->getFormatter()->setStyle('cyan', $cyan);

        $green = new OutputFormatterStyle('green', null, ['bold']);
        $this->output->getFormatter()->setStyle('green', $green);

        $styled = $style ? "<$style>$string</$style>" : $string;

        $this->output->writeln($styled);
    }

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
            foreach ($output as $line) {
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
            '/var/tmp/'.$this->logApp.'_push_'.$this->logTime.'.log',
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
}

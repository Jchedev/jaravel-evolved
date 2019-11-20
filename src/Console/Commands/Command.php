<?php

namespace Jchedev\Laravel\Console\Commands;

use Illuminate\Foundation\Bus\DispatchesJobs;

abstract class Command extends \Illuminate\Console\Command
{
    use DispatchesJobs;

    /**
     * Data used during the log (if activated)
     */
    protected $return = [];

    /**
     * Should we also count the number of queries? Resource heavy
     */
    protected $countQueries = false;

    /**
     * @var
     */
    protected $activeProgressBar;

    /**
     * This method is called by the handle() method
     *
     * @return mixed
     */
    abstract protected function handleLogic();

    /**
     * The constructor  check if we want to create a log object based on $_with_log
     * Verbosity level:
     *  -v :    Display errors + execution details + query count (when applicable) but NOT the exception trace
     *  -vv :   Display errors + execution details + query count + query list but NOT the exception trace
     *  -vvv :  Display errors + execution details + query count + query list + exception trace
     */
    public function __construct()
    {
        $this->signature .= ' {--with-sql : Count the queries. Display the number with -v, and the full list with -vv}';

        parent::__construct();
    }

    /**
     * This method calls a sub method handleLogic() if possible and contain the execution logic
     */
    public function handle()
    {
        if ($this->countQueries == true || $this->option('with-sql') === true) {
            $this->countQueries = true;

            $this->enableQueryLog();
        }

        $this->info('[*] Beginning of the command ' . $this->getCommandName(), $this->parseVerbosity('v'));

        $startTime = microtime(true);

        try {
            $this->handleLogic();

            $this->onSuccess();
        }
        catch (\Exception $e) {
            if ($this->onError($e) === false) {
                unset($e);
            }
        } finally {
            $this->onComplete();
        }

        $endTime = microtime(true);

        $this->info('[*] End of the command', $this->parseVerbosity('v'));

        $this->return['execution_time'] = ($executionTime = round($endTime - $startTime, 2));

        $this->comment('- Execution time: ' . $executionTime, $this->parseVerbosity('v'), 1);

        if ($this->countQueries === true) {
            $queries = $this->getQueryLog();

            $this->return['nb_queries'] = ($nbQueries = count($queries));

            $this->comment('- Queries: ' . $nbQueries, $this->parseVerbosity('v'), 1);

            if (in_array($this->output->getVerbosity(), [$this->parseVerbosity('vv'), $this->parseVerbosity('vvv')])) {
                foreach ($queries as $query) {
                    $this->comment('- ' . $query['query'] . ' (' . $query['time'] . ')', null, 2);
                }
            }
        }

        if (isset($e)) {
            throw $e;
        }
    }

    /**
     * @return bool|string
     */
    protected function getCommandName()
    {
        return substr($this->signature, 0, strpos($this->signature, ' '));
    }

    /**
     * This method is called if an error is generated during the execution
     *
     * @param \Exception $exception
     */
    protected function onError(\Exception $exception)
    {
        $this->error('An error occurred: ' . $exception->getMessage(), $this->parseVerbosity('v'));

        $this->return['error'] = $exception->getMessage();

        if (in_array($this->output->getVerbosity(), [$this->parseVerbosity('v'), $this->parseVerbosity('vv')])) {
            return false;
        }
    }

    /**
     * This method is called at the end of the execution (even if an error occurred)
     */
    protected function onComplete()
    {
        // Can be overwritten by a child class
    }

    /**
     * This method is called if the execution is successful
     */
    protected function onSuccess()
    {
        // Can be overwritten by a child class
    }

    /**
     *
     */
    protected function enableQueryLog()
    {
        \DB::enableQueryLog();
    }

    /**
     * @return mixed
     */
    protected function getQueryLog()
    {
        return \DB::getQueryLog();
    }

    /**
     * Custom info message with step number at the beginning
     *
     * @param $message
     * @param null $verbosity
     * @param int $tab
     */
    public function step($message, $verbosity = null, $tab = 0)
    {
        static $pos = 1;

        $this->info($pos++ . '. ' . $message, $verbosity, $tab);
    }

    /**
     * Allow the management of tabulation at the beginning of a message
     *
     * @param string $message
     * @param null $verbosity
     * @param int $tab
     */
    public function info($message, $verbosity = null, $tab = 0)
    {
        for ($i = 0; $i < $tab; $i++) {
            $message = '  ' . $message;
        }

        parent::info($message, $verbosity);
    }

    /**
     * Allow the management of tabulation at the beginning of a message
     *
     * @param string $message
     * @param null $verbosity
     * @param int $tab
     */
    public function comment($message, $verbosity = null, $tab = 0)
    {
        for ($i = 0; $i < $tab; $i++) {
            $message = '  ' . $message;
        }

        parent::comment($message, $verbosity);
    }

    /**
     * Allow the management of tabulation at the beginning of a message
     *
     * @param string $message
     * @param null $verbosity
     * @param int $tab
     */
    public function error($message, $verbosity = null, $tab = 0)
    {
        for ($i = 0; $i < $tab; $i++) {
            $message = '  ' . $message;
        }

        parent::error($message, $verbosity);
    }

    /**
     * Create a new progressbar and save it as the command level for future use
     *
     * @param $nbLines
     * @return mixed
     */
    public function createProgressBar($nbLines)
    {
        $this->activeProgressBar = $this->output->createProgressBar($nbLines);

        return $this->activeProgressBar;
    }

    /**
     * Advance the progressbar (if active)
     *
     * @param int $nbLinesMoved
     */
    public function advanceProgressBar($nbLinesMoved = 1)
    {
        if ($this->hasActiveProgressBar() === false) {
            return;
        }

        $this->activeProgressBar->advance($nbLinesMoved);
    }

    /**
     * Close the progressbar (if active)
     */
    public function finishProgressBar()
    {
        if ($this->hasActiveProgressBar() === false) {
            return false;
        }

        $this->activeProgressBar->finish();

        $this->activeProgressBar = null;

        $this->info("");
    }

    /**
     * Check if a progress bar is active
     *
     * @return bool
     */
    public function hasActiveProgressBar()
    {
        return !is_null($this->activeProgressBar);
    }
}
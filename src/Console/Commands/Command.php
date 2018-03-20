<?php

namespace Jchedev\Laravel\Console\Commands;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\DispatchesJobs;

abstract class Command extends \Illuminate\Console\Command
{
    use DispatchesJobs;

    /**
     * Data used during the log (if activated)
     */
    protected $_return = [];

    /**
     * Save the progressbar as an internal value to track the status
     */
    protected $_active_progressbar;

    /**
     * Should we also count the number of queries? Resource heavy
     */
    protected $_count_queries = false;

    /**
     * This method is called by the handle() method
     *
     * @return mixed
     */
    abstract protected function handleLogic();

    /**
     * The constructor  check if we want to create a log object based on $_with_log
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
        //Check if we should activate the queries log by looking at the option --with-sql

        if ($this->_count_queries == true || $this->option('with-sql') === true) {
            \DB::enableQueryLog();

            $this->_count_queries = true;
        }

        // Info about the command execution

        $command_name = substr($this->signature, 0, strpos($this->signature, ' '));

        $this->info('[*] Beginning of the command ' . $command_name, $this->parseVerbosity('v'));

        // Execute the logic and catch exception to display as "error"

        $starting_time = microtime(true);

        try {
            // That's where we handle the logic and the full command
            $this->handleLogic();

            // Callback called because the command was a success
            $this->onSuccess();
        }
        catch (\Exception $e) {
            // Callback called because an error occurred during the execution
            if ($this->onError($e) === false) {
                unset($e);
            }
        }

        $this->onComplete();

        // Log execution time and output info about the execution results

        $this->info('[*] End of the command', $this->parseVerbosity('v'));

        $end_time = microtime(true);

        $this->_return['execution_time'] = round($end_time - $starting_time, 2);

        $this->comment('- Execution time: ' . $this->_return['execution_time'], $this->parseVerbosity('v'), 1);

        // Count and display the queries (if activated)

        if ($this->_count_queries === true) {
            $queries = \DB::getQueryLog();

            $this->_return['nb_queries'] = count($queries);

            $this->comment('- Queries: ' . $this->_return['nb_queries'], $this->parseVerbosity('v'), 1);

            // If the verbosity is right, we output all the queries executed

            if ($this->output->getVerbosity() == $this->parseVerbosity('vv')) {
                foreach ($queries as $query) {
                    $this->comment('-- ' . $query['query'] . ' (' . $query['time'] . ')', null, 1);
                }
            }

            unset ($queries);
        }

        // Throw any caught error during the execution

        if (isset($e)) {
            throw $e;
        }
    }

    /**
     * This method is called if an error is generated during the execution
     *
     * @param \Exception $exception
     */
    protected function onError(\Exception $exception)
    {
        $this->error('An error occurred: ' . $exception->getMessage());

        $this->_return['error'] = $exception->getMessage();
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
     * @param $nb_lines
     * @return bool|\Symfony\Component\Console\Helper\ProgressBar
     */
    public function createProgressBar($nb_lines)
    {
        $this->_active_progressbar = $this->output->createProgressBar($nb_lines);

        return $this->_active_progressbar;
    }

    /**
     * Advance the progressbar (if active)
     *
     * @param $nb_lines_moved
     */
    public function advanceProgressBar($nb_lines_moved = 1)
    {
        if ($this->hasActiveProgressBar() === false) {
            return;
        }

        $this->_active_progressbar->advance($nb_lines_moved);
    }

    /**
     * Close the progressbar (if active)
     */
    public function finishProgressBar()
    {
        if ($this->hasActiveProgressBar() === false) {
            return false;
        }

        $this->_active_progressbar->finish();

        unset($this->_active_progressbar);

        $this->info("");
    }

    /**
     * Check if a progress bar is active
     *
     * @return bool
     */
    public function hasActiveProgressBar()
    {
        return !is_null($this->_active_progressbar);
    }

    /**
     * Check if we want to execute a job now or defer it
     *
     * @param \Illuminate\Contracts\Queue\ShouldQueue $job
     * @param bool $deferred
     */
    protected function handleJobOrDefer(ShouldQueue $job, $deferred = false)
    {
        if ($deferred === true) {
            $this->dispatch($job);
        } else {
            $job->handle();
        }
    }
}
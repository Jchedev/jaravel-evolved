<?php

namespace Jchedev\Laravel\Classes\DatabaseManager;

use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\Event;

class DatabaseManager extends \Illuminate\Database\DatabaseManager
{
    /**
     * @var array
     */
    protected $queryLog = null;

    /**
     * @var null
     */
    protected $traceQueryMessage = null;

    /**
     * DatabaseManager constructor.
     *
     * @param \Illuminate\Contracts\Foundation\Application $app
     * @param \Illuminate\Database\Connectors\ConnectionFactory $factory
     */
    public function __construct(\Illuminate\Contracts\Foundation\Application $app, \Illuminate\Database\Connectors\ConnectionFactory $factory)
    {
        parent::__construct($app, $factory);

        $this->queryLog = collect([]);

        $this->listenQueryLogging();
    }

    /**
     * @return array
     */
    public function getQueryLog()
    {
        $connectionName = $this->connection()->getName();

        return $this->queryLog->where('connection', $connectionName)->all();
    }

    /**
     * @param $message
     * @param \Closure $closure
     * @return mixed
     */
    public function trace($message, \Closure $closure)
    {
        $previousMessage = $this->traceQueryMessage;

        $this->traceQueryMessage = $message;

        $response = $closure();

        $this->traceQueryMessage = $previousMessage;

        return $response;
    }

    /**
     * @param $message
     * @return $this
     */
    public function startTrace($message)
    {
        $this->traceQueryMessage = $message;

        return $this;
    }

    /**
     * @return $this
     */
    public function stopTrace()
    {

        $this->traceQueryMessage = null;

        return $this;
    }

    /**
     * This is the only way to keep both query log in sync unfortunately
     */
    protected function listenQueryLogging()
    {
        Event::listen(QueryExecuted::class, function ($event) {
            if ($connection = $event->connection->logging()) {
                $this->queryLog->push(array_merge([
                        'query'      => $event->sql,
                        'bindings'   => $event->bindings,
                        'time'       => $event->time,
                        'connection' => $event->connectionName,
                    ], $this->traceQueryMessage ? [
                        'message' => $this->traceQueryMessage
                    ] : [])
                );
            }
        });
    }
}
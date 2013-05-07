<?php

class MyIndo_Db_Profiler_Log extends Zend_Db_Profiler
{


    protected $_log;
    /**
     * counter of the total elapsed time
     * 
     * @var double
     */
    protected $_totalElapsedTime;

    public function __construct ($enabled = false)
    {
        parent::__construct($enabled);
        $this->_log = new Zend_Log();
        $writer = new Zend_Log_Writer_Stream( APPLICATION_PATH . '/../data/var/query/queries.log' );
        $this->_log->addWriter($writer);
    }

    /**
     * Intercept the query end and log the profiling data.
     *
     * @param integer $queryId            
     * @throws Zend_Db_Profiler_Exception
     * @return void
     */
    public function queryEnd ($queryId)
    {
        $state = parent::queryEnd($queryId);
        
        if (! $this->getEnabled() || $state == self::IGNORED) {
            return;
        }
        
        // get profile of the current query
        $profile = $this->getQueryProfile($queryId);
        
        // update totalElapsedTime counter
        $this->_totalElapsedTime += $profile->getElapsedSecs();
        
        // create the message to be logged
        $message = "\r\nElapsed Secs: " . round($profile->getElapsedSecs(), 5) .
                 "\r\n";
        $message .= "Query: " . $profile->getQuery() . "\r\n";
        $message .= "Params: " . Zend_Json::encode($profile->getQueryParams()) . "\r\n";
        
        
        // log the message as INFO message
        $this->_log->info($message);
        //App::log($message,Zend_Log::INFO,'query.log', false , 'query');
    }
}


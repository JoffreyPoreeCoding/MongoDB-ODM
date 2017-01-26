<?php

namespace JPC\MongoDB\ODM\Tools\Logger;

class MemoryLogger implements LoggerInterface {

	protected $debugLogs = [];
	protected $infoLogs = [];
	protected $warningLogs = [];
	protected $errorLogs = [];

	public function debug($message, $metadata = [])
	{
		$this->debugLogs[] = ["message" => $message, "metadata" => $metadata];
	}

	public function info($message, $metadata = [])
	{
		$this->infoLogs[] = ["message" => $message, "metadata" => $metadata];
	}

	public function warning($message, $metadata = [])
	{
		$this->warningLogs[] = ["message" => $message, "metadata" => $metadata];
	}

	public function error($message, $metadata = [])
	{
		$this->errorLogs[] = ["message" => $message, "metadata" => $metadata];
	}

    /**
     * Gets the value of debugLogs.
     *
     * @return mixed
     */
    public function getDebugLogs()
    {
        return $this->debugLogs;
    }

    /**
     * Gets the value of infoLogs.
     *
     * @return mixed
     */
    public function getInfoLogs()
    {
        return $this->infoLogs;
    }

    /**
     * Gets the value of warningLogs.
     *
     * @return mixed
     */
    public function getWarningLogs()
    {
        return $this->warningLogs;
    }

    /**
     * Gets the value of errorLogs.
     *
     * @return mixed
     */
    public function getErrorLogs()
    {
        return $this->errorLogs;
    }
}


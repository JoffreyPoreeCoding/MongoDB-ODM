<?php

namespace JPC\MongoDB\ODM\Tools\Logger;

interface LoggerInterface {
	public function debug($message, $metadata = []);

    public function info($message, $metadata = []);

    public function warning($message, $metadata = []);

    public function error($message, $metadata = []);
}


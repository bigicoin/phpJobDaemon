<?php

/**
 * This is a simple queue consumer that uses:
 * 1. Redis for the physical queue
 * 2. Mixpanel as your analytics service to send data to
 * It works well enough for a small project, even in a production environment.
 *
 * It assumes the usage of the Mixpanel PHP library. Be sure to include:
 * "require": {
 *   "mixpanel/mixpanel-php" : "2.*"
 * }
 * As part of your composer.json file if running this.
 *
 * Additionally, it assumes your environment has a Redis client library installed.
 * In our case, it uses phpiredis commands, which is a PHP extension:
 * https://github.com/nrk/phpiredis
 */

class TrackingConsumer extends JobDaemon {
	/**
	 * Instance of the Redis connection object
	 */
	private $redis;

	/**
	 * Redis connection info
	 */
	private $redisHost = '';
	private $redisPort = 0;
	private $redisQueueName = '';

	/**
	 * Mixpanel token
	 */
	private $mixpanelToken;

	/**
	 * Sets the Redis connection info
	 */
	public function setRedisServer($host, $port, $queueName) {
		$this->redisHost = $host;
		$this->redisPort = $port;
		$this->redisQueueName = $queueName;
	}

	/**
	 * Sets Mixpanel keys
	 */
	public function setTrackParams($mixpanelToken) {
		$this->mixpanelToken = $mixpanelToken;
	}

	/**
	 * TrackingConsumer parentProcess.
	 * Instantiates the Mixpanel libraries.
	 * Connects to redis server, blocking-dequeues from the tracking queue, assigns each piece of track data to a child thread.
	 */
	protected function parentProcess() {
		$this->log("Starting track queue consumer...\n");
		$this->redis = phpiredis_connect($this->redisHost, $this->redisPort);
		if (empty($this->redis)) {
			throw new Exception("Could not connect to Redis host at \"".$this->redisHost.":".$this->redisPort."\"");
		}
		while (true) {
			$response = phpiredis_command_bs($this->redis, array('BRPOP', strval($this->redisQueueName), '0'));
			// $response[0] is queue name, $response[1] is the data in it
			if (!empty($response)) {
				$entry = json_decode($response[1], true);
				if (!empty($entry) && !empty($entry['name'])) { // check if valid entry
					$childLaunched = $this->launchJob($entry);
					if (!$childLaunched) {
						$this->log("I/O Error: Could not fork child process! Continuing...\n");
					}
				} else {
					$this->log("Queue entry looks invalid, discarding.\n[Entry]:".$response[1]."\n");
				}
			} else {
				$this->log("Unknown error popping queue: \"".$this->redisQueueName."\". Continuing...\n");
			}
		}
	}

	/**
	 * TrackingConsumer childProcess.
	 * Takes the piece of track data, makes the REST API calls to Mixpanel with the data.
	 *
	 * @param	object	The argument object passed from the parent process via launchJob() call.
	 */
	protected function childProcess($args) {
		$mixpanel = Mixpanel::getInstance($this->mixpanelToken);
		$params = (!empty($args['params'])) ? $args['params'] : array();
		$mixpanel->track($args['name'], $params);
	}
}

$consumer = new TrackingConsumer();
$consumer->setRedisServer('127.0.0.1', '6379', 'myqueue');
$consumer->setTrackParams('1234567890abcdef');
$consumer->setMaxChildren(100);
$consumer->setOutput(JobDaemon::OUTPUT_STDOUT, JobDaemon::LEVEL_WARNING);
$consumer->run();

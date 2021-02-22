<?php
/**
 * Logger class
 *
 * @package Mofsy/WC_Robokassa
 */
defined('ABSPATH') || exit;

class WC_Robokassa_Logger
{
	/**
	 * Log name
	 *
	 * @var string
	 */
	private $name = 'wc-robokassa.boot.log';

	/**
	 * Path
	 *
	 * @var string
	 */
	public $path = '';

	/**
	 * Default level
	 *
	 * @var int
	 */
	public $level = 400;

	/**
	 * Datetime
	 */
	public $date_time;

	/**
	 * Logging levels (RFC 5424)
	 *
	 * @var array
	 */
	public $levels = array
	(
		100 => 'DEBUG',
		200 => 'INFO',
		250 => 'NOTICE',
		300 => 'WARNING',
		400 => 'ERROR',
		500 => 'CRITICAL',
		550 => 'ALERT',
		600 => 'EMERGENCY',
	);

	/**
	 * WC_Robokassa_Logger constructor
	 *
	 * @param $path
	 * @param int $level
	 * @param string $name
	 *
	 * @throws Exception
	 *
	 * @return void
	 */
	public function __construct($path = '', $level = 400, $name = '')
	{
		if($name !== '')
		{
			$this->set_name($name);
		}

		if($path !== '')
		{
			$this->set_path($path);
		}

		if($level !== '')
		{
			$this->level = $level;
		}
	}

	/**
	 * @return string
	 */
	public function get_name()
	{
		return $this->name;
	}

	/**
	 * @param string $name
	 */
	public function set_name($name)
	{
		$this->name = $name;
	}

	/**
	 * @return mixed
	 */
	public function get_path()
	{
		return $this->path;
	}

	/**
	 * @param mixed $path
	 */
	public function set_path($path)
	{
		$this->path = $path;
	}

	/**
	 * @return int
	 */
	public function get_level()
	{
		return $this->level;
	}

	/**
	 * @param int $level
	 */
	public function set_level($level)
	{
		$this->level = $level;
	}

	/**
	 * @return DateTime
	 */
	public function get_date_time()
	{
		return $this->date_time;
	}

	/**
	 * @param DateTime $date_time
	 */
	public function set_date_time($date_time)
	{
		$this->date_time = $date_time;
	}

	/**
	 * @param $message
	 */
	public function warning($message)
	{
		$this->add(300, $message);
	}

	/**
	 * @param $message
	 * @param null $object
	 */
	public function error($message, $object = null)
	{
		$this->add(400, $message, $object);
	}

	/**
	 * @param $message
	 * @param null $object
	 */
	public function debug($message, $object = null)
	{
		$this->add(100, $message, $object);
	}

	/**
	 * @param $message
	 */
	public function info($message)
	{
		$this->add(200, $message);
	}

	/**
	 * @param $message
	 */
	public function notice($message)
	{
		$this->add(250, $message);
	}

	/**
	 * @param $message
	 * @param null $object
	 */
	public function critical($message, $object = null)
	{
		$this->add(500, $message, $object);
	}

	/**
	 * @param $message
	 * @param null $object
	 */
	public function alert($message, $object = null)
	{
		$this->add(550, $message, $object);
	}

	/**
	 * @param $message
	 * @param null $object
	 */
	public function emergency($message, $object = null)
	{
		$this->add(600, $message, $object);
	}

	/**
	 * Save to file
	 *
	 * @param $level
	 * @param $message
	 * @param null $object
	 *
	 * @return bool
	 * @throws Exception
	 */
	public function add($level, $message, $object = null)
	{
		if($this->get_level() > $level)
		{
			return false;
		}

		try
		{
			$this->set_date_time(new DateTime('now', new DateTimeZone('UTC')));
		}
		catch(Exception $e)
		{
			return false;
		}

		$content = array
		(
			$level,
			$this->get_date_time()->format(DATE_ATOM),
			$this->levels[$level],
			$message
		);

		if(is_object($object) || is_array($object))
		{
			$content['object'] = print_r($object, true);
		}
		elseif(is_bool($object))
		{
			$content['object'] = $object ? 'true' : 'false';
		}
		elseif(!is_null($object) && $object !== '')
		{
			$content['object'] = $object;
		}

		$content = implode(' |- ', $content);

		$file = $this->get_path() . DIRECTORY_SEPARATOR . $this->get_name();

		if(!file_exists($this->get_path()) && !mkdir($this->get_path(), 0755, true))
		{
			return false;
		}

		file_put_contents
		(
			$file,
			$content . PHP_EOL,
			FILE_APPEND | LOCK_EX
		);

		return true;
	}
}
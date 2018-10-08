<?php
/*
  +----------------------------------------------------------+
  | Gatework                                                 |
  +----------------------------------------------------------+
  | Author: Oleg Budrin (Mofsy) <support@mofsy.ru>           |
  | Author website: https://mofsy.ru                         |
  +----------------------------------------------------------+
*/

class WC_Gatework_Logger
{
    /**
     * @var array
     */
    public $buffer;

    /**
     * Path
     */
    public $path;

    /**
     * Default level
     *
     * @var int
     */
    public $level = 400;

    /**
     * Datetime
     */
    public $dt;

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
     * Logger constructor.
     *
     * @param $path
     * @param $level
     */
    public function __construct($path, $level)
    {
        $this->path = $path;
        $this->dt = new DateTime('now', new DateTimeZone( 'UTC' ));

        if($level !== '')
        {
            $this->level = $level;
        }
    }

    /**
     * @param $message
     */
    public function addWarn($message)
    {
        $this->add(300, $message);
    }

    /**
     * @param $message
     * @param null $object
     */
    public function addError($message, $object = null)
    {
        $this->add(400, $message, $object);
    }

    /**
     * @param $message
     * @param null $object
     */
    public function addDebug($message, $object = null)
    {
        $this->add(100, $message, $object);
    }

    /**
     * @param $message
     */
    public function addInfo($message)
    {
        $this->add(200, $message);
    }

    /**
     * @param $message
     */
    public function addNotice($message)
    {
        $this->add(250, $message);
    }

    /**
     * @param $message
     * @param null $object
     */
    public function addCritical($message, $object = null)
    {
        $this->add(500, $message, $object);
    }

    /**
     * @param $message
     * @param null $object
     */
    public function addAlert($message, $object = null)
    {
        $this->add(550, $message, $object);
    }

    /**
     * @param $message
     * @param null $object
     */
    public function addEmergency($message, $object = null)
    {
        $this->add(600, $message, $object);
    }

    /**
     * @param $level
     * @param $message
     * @param null $object
     *
     * @return bool
     */
    public function add($level, $message, $object = null)
    {
        /**
         * Check level
         */
        if($this->level > $level)
        {
            return false;
        }

        $content = array
        (
            $level,
            $this->dt->format(DATE_ATOM),
            $this->levels[$level],
            $message
        );

	    if(is_object($object) || is_array($object))
	    {
		    $content['content'] = print_r($object, true);
	    }
	    else
	    {
		    $content['content'] = $object;
	    }

        /**
         * Content
         */
        $content = implode(' -|- ', $content);

        file_put_contents
        (
            $this->path,
            $content . PHP_EOL,
            FILE_APPEND | LOCK_EX
        );

        return true;
    }
}
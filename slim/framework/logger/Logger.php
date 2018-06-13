<?php

namespace slim\logger;

use InvalidArgumentException;
use slim\Api;
use slim\base\Services;
use slim\contracts\logger\Logger as LoggerContract;

class Logger implements LoggerContract
{

    /**
     * The api instance.
     *
     * @var \slim\Api
     */
    private $api;
    private $config
        = [
            'min_level'   => 'debug',
            'apart_level' => [],
            'max_files'   => 30,
            'time_format' => 'Y-m-d H:i:s',
            'path'        => LOG_PATH,
            'cut_type'    => 'daily',   /* daily month year  20180101  201801 */
            'file_size'   => 1024 * 1024,   /* if cut_type is size, then setting the file size */
            'ext'         => '.log',
        ];

    private $apart;
    private $minLevelIndex;
    private $formatter;

    const EMERGENCY = 'emergency';
    const ALERT     = 'alert';
    const CRITICAL  = 'critical';
    const ERROR     = 'error';
    const WARNING   = 'warning';
    const NOTICE    = 'notice';
    const INFO      = 'info';
    const DEBUG     = 'debug';

    private static $levels
        = array(
            self::DEBUG     => 0,
            self::INFO      => 1,
            self::NOTICE    => 2,
            self::WARNING   => 3,
            self::ERROR     => 4,
            self::CRITICAL  => 5,
            self::ALERT     => 6,
            self::EMERGENCY => 7,
        );

    /**
     * Logger constructor.
     *
     * @param Api $api
     */
    public function __construct(Api $api, callable $formatter = null)
    {
        $this->api    = $api;
        $this->config = array_merge($this->config, $this->api->make('config')->get('api.log'));
        $this->register($formatter);
    }


    /**
     * register
     *
     * @param callable|null $formatter
     *
     * @return $this
     */
    public function register(callable $formatter = null)
    {
        $minLevel = $this->config['min_level'];
        if (null === $minLevel) {
            $minLevel = self::WARNING;

            if (isset($_ENV['SHELL_VERBOSITY']) || isset($_SERVER['SHELL_VERBOSITY'])) {
                switch ((int)(isset($_ENV['SHELL_VERBOSITY']) ? $_ENV['SHELL_VERBOSITY'] : $_SERVER['SHELL_VERBOSITY'])) {
                    case -1:
                        $minLevel = self::ERROR;
                        break;
                    case 1:
                        $minLevel = self::NOTICE;
                        break;
                    case 2:
                        $minLevel = self::INFO;
                        break;
                    case 3:
                        $minLevel = self::DEBUG;
                        break;
                }
            }
        }

        if (!isset(self::$levels[$minLevel])) {
            throw new InvalidArgumentException(sprintf('The log level "%s" does not exist.', $minLevel));
        }

        $this->minLevelIndex = self::$levels[$minLevel];
        $this->formatter     = $formatter ?: array($this, 'format');
    }

    /**
     * getLogFile
     *
     * @return string
     */
    private function getLogFile($level)
    {
        $apart = $this->apart ? '_'.$level : '';

        $cutType = [
            'year'  => date('Y'),
            'month' => date('Y') . DS . date('m'),
            'daily' => date('Y') . DS . date('md'),
        ];

        $file = $this->config['path']  . $cutType[$this->config['cut_type']]. $apart . $this->config['ext'];

        !is_dir(dirname($file)) && mkdir(dirname($file), 0755, true);

        $this->cutLog($file);

        return $file;
    }

    /**
     * cutLog
     *
     * @param $file
     */
    private function cutLog($file)
    {
        if (is_file($file) && floor($this->config['file_size']) <= filesize($file)) {
            rename($file, dirname($file) . DS . basename($file, $this->config['ext']) . '_' . date('hi') . $this->config['ext']);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function write($level, $message, array $context = array())
    {
        if (!isset(self::$levels[$level])) {
            throw new InvalidArgumentException(sprintf('The log level "%s" does not exist.', $level));
        }

        if (self::$levels[$level] < $this->minLevelIndex) {
            return;
        }

        if (in_array($level, $this->config['apart_level'])) {
            $this->apart = true;
        }

        $logFile = $this->getLogFile($level);
        if (false === $handle = @fopen($logFile, 'a')) {
            throw new InvalidArgumentException(sprintf('Unable to open "%s".', $logFile));
        }

        $formatter = $this->formatter;
        fwrite($handle, $formatter($level, $message, $context));
    }

    /**
     * format
     *
     * @param string $level
     * @param string $message
     * @param array  $context
     *
     * @return string
     */
    private function format(string $level, string $message, array $context) : string
    {
        if (false !== strpos($message, '{')) {
            $replacements = array();
            foreach ($context as $key => $val) {
                if (null === $val || is_scalar($val) || (\is_object($val) && method_exists($val, '__toString'))) {
                    $replacements["{{$key}}"] = $val;
                } elseif ($val instanceof \DateTimeInterface) {
                    $replacements["{{$key}}"] = $val->format(\DateTime::RFC3339);
                } elseif (\is_object($val)) {
                    $replacements["{{$key}}"] = '[object ' . \get_class($val) . ']';
                } else {
                    $replacements["{{$key}}"] = '[' . \gettype($val) . ']';
                }
            }

            $message = strtr($message, $replacements);
        }

        return sprintf('> %s [%s] %s %s %s', date($this->config['time_format']), $level, ip(), PHP_EOL, $message) . \PHP_EOL;
    }


    /**
     * System is unusable.
     *
     * @param string $message
     * @param array  $context
     *
     * @return void
     */
    public function emergency($message, array $context = array())
    {
        $this->write(self::EMERGENCY, $message, $context);
    }

    /**
     * Action must be taken immediately.
     *
     * Example: Entire website down, database unavailable, etc. This should
     * trigger the SMS alerts and wake you up.
     *
     * @param string $message
     * @param array  $context
     *
     * @return void
     */
    public function alert($message, array $context = array())
    {
        $this->write(self::ALERT, $message, $context);
    }

    /**
     * Critical conditions.
     *
     * Example: Application component unavailable, unexpected exception.
     *
     * @param string $message
     * @param array  $context
     *
     * @return void
     */
    public function critical($message, array $context = array())
    {
        $this->write(self::CRITICAL, $message, $context);
    }

    /**
     * Runtime errors that do not require immediate action but should typically
     * be logged and monitored.
     *
     * @param string $message
     * @param array  $context
     *
     * @return void
     */
    public function error($message, array $context = array())
    {
        $this->write(self::ERROR, $message, $context);
    }

    /**
     * Exceptional occurrences that are not errors.
     *
     * Example: Use of deprecated APIs, poor use of an API, undesirable things
     * that are not necessarily wrong.
     *
     * @param string $message
     * @param array  $context
     *
     * @return void
     */
    public function warning($message, array $context = array())
    {
        $this->write(self::WARNING, $message, $context);
    }

    /**
     * Normal but significant events.
     *
     * @param string $message
     * @param array  $context
     *
     * @return void
     */
    public function notice($message, array $context = array())
    {
        $this->write(self::NOTICE, $message, $context);
    }

    /**
     * Interesting events.
     *
     * Example: User logs in, SQL logs.
     *
     * @param string $message
     * @param array  $context
     *
     * @return void
     */
    public function info($message, array $context = array())
    {
        $this->write(self::INFO, $message, $context);
    }

    /**
     * Interesting events.
     *
     * Example: User logs in, SQL logs.
     *
     * @param string $message
     * @param array  $context
     *
     * @return void
     */
    public function debug($message, array $context = array())
    {
        $this->write(self::DEBUG, $message, $context);
    }

}
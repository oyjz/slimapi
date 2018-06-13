<?php

namespace slim\bootstrap;

use slim\Api;
use slim\exception\FatalThrowableError;
use slim\exception\FatalErrorException;

class Exception
{
    /**
     * The api instance.
     *
     * @var \slim\Api
     */
    protected $api;

    protected $config;
    protected $logger;

    /**
     * Bootstrap the given application.
     *
     * @param  \slim\Api $api
     *
     * @return void
     */
    public function bootstrap(Api $api)
    {
        $this->api    = $api;
        $this->config = $api->make('config')->get('api');

        error_reporting(-1);

        set_error_handler([$this, 'handleError']);

        set_exception_handler([$this, 'handleException']);

        register_shutdown_function([$this, 'handleShutdown']);

        if (!$this->config['debug']) {
            ini_set('display_errors', 'Off');
        }
    }

    /**
     * Convert PHP errors to ErrorException instances.
     *
     * @param  int    $level
     * @param  string $message
     * @param  string $file
     * @param  int    $line
     * @param  array  $context
     *
     * @return void
     *
     * @throws \ErrorException
     */
    public function handleError($level, $message, $file = '', $line = 0, $context = [])
    {
        if (error_reporting() & $level) {
            throw new \ErrorException($message, 0, $level, $file, $line);
        }
    }

    /**
     * Handle an uncaught exception from the application.
     *
     * Note: Most exceptions can be handled via the try / catch block in
     * the HTTP and Console kernels. But, fatal error exceptions must
     * be handled differently since they are not normal exceptions.
     *
     * @param  \Throwable $e
     *
     * @return void
     */
    public function handleException($exception)
    {
        if (!$exception instanceof \Exception) {
            $exception = new FatalThrowableError($exception);
        }

        try {
            // just log
            $this->api->make('logger')->error($this->getLogText($exception));

            $code     = $this->api->make('code');
            $status   = $code->getStatus($code::ERROR);
            $response = $this->api->make('response');

            if ($this->config['debug'] === true || $this->config['debug'] === 1) {
                $this->debug($exception, $status);
            } else {
                $response->error($code::ERROR);
            }

        } catch (\Exception $exception) {
            // just log
            $this->api->make('logger')->error($this->getLogText($exception));
            if ($this->config['debug'] === true || $this->config['debug'] === 1) {
                $this->debug($exception, 500);
            } else {
                // TODO 终极错误，不能再错
                echo $exception->getMessage();
            }
        }
    }

    public function getLogText(\Exception $exception)
    {
        $message = $this->api->handleMessage($exception->getMessage());
        $text    = 'Message: ' . $message . PHP_EOL;
        $text    .= 'File: ' . $exception->getFile() . PHP_EOL;
        $text    .= 'Line: ' . $exception->getLine();

        return $text;
    }

    /**
     * debug
     *
     * @param $exception
     * @param $status
     */
    public function debug(\Exception $exception, $status)
    {
        $vars = $this->collectVars($exception, $status);
        extract($vars);

        while (ob_get_level() > 1) {
            ob_end_clean();
        }

        ob_start();
        include(SLIM_PATH . 'framework\\http\\error_tpl.php');
        $buffer = ob_get_contents();
        ob_end_clean();
        echo $buffer;
    }

    /**
     * Handle the PHP shutdown event.
     *
     * @return void
     */
    public function handleShutdown()
    {
        if (!is_null($error = error_get_last()) && $this->isFatal($error['type'])) {
            $this->handleException($this->fatalExceptionFromError($error, 0));
        }
    }

    /**
     * Create a new fatal exception instance from an error array.
     *
     * @param  array    $error
     * @param  int|null $traceOffset
     *
     * @return \slim\exception\FatalErrorException
     */
    protected function fatalExceptionFromError(array $error, $traceOffset = null)
    {
        return new FatalErrorException(
            $error['message'], $error['type'], 0, $error['file'], $error['line'], $traceOffset
        );
    }

    /**
     * Determine if the error type is fatal.
     *
     * @param  int $type
     *
     * @return bool
     */
    protected function isFatal($type)
    {
        return in_array($type, [E_COMPILE_ERROR, E_CORE_ERROR, E_ERROR, E_PARSE]);
    }

    /**
     * collectVars
     *
     * @param \Exception $exception
     * @param int        $code
     *
     * @return array
     */
    protected function collectVars(\Exception $exception, int $code)
    {
        return [
            'title'   => get_class($exception),
            'type'    => get_class($exception),
            'code'    => $code,
            'message' => $exception->getMessage() ?? '(null)',
            'file'    => $exception->getFile(),
            'line'    => $exception->getLine(),
            'trace'   => $exception->getTrace(),
        ];
    }

    /**
     * highlightFile
     *
     * @param     $file
     * @param     $lineNumber
     * @param int $lines
     *
     * @return bool|string
     */
    public function highlightFile($file, $lineNumber, $lines = 15)
    {
        if (empty($file) || !is_readable($file)) {
            return false;
        }

        // Set our highlight colors:
        if (function_exists('ini_set')) {
            ini_set('highlight.comment', '#767a7e; font-style: italic');
            ini_set('highlight.default', '#c7c7c7');
            ini_set('highlight.html', '#06B');
            ini_set('highlight.keyword', '#f1ce61;');
            ini_set('highlight.string', '#869d6a');
        }

        try {
            $source = file_get_contents($file);
        } catch (\Throwable $e) {
            return false;
        }

        $source = str_replace(["\r\n", "\r"], "\n", $source);
        $source = explode("\n", highlight_string($source, true));
        $source = str_replace('<br />', "\n", $source[1]);

        $source = explode("\n", str_replace("\r\n", "\n", $source));

        // Get just the part to show
        $start = $lineNumber - (int)round($lines / 2);
        $start = $start < 0 ? 0 : $start;

        // Get just the lines we need to display, while keeping line numbers...
        $source = array_splice($source, $start, $lines, true);

        // Used to format the line number in the source
        $format = '% ' . strlen($start + $lines) . 'd';

        $out = '';
        // Because the highlighting may have an uneven number
        // of open and close span tags on one line, we need
        // to ensure we can close them all to get the lines
        // showing correctly.
        $spans = 1;

        foreach ($source as $n => $row) {
            $spans += substr_count($row, '<span') - substr_count($row, '</span');
            $row   = str_replace(["\r", "\n"], ['', ''], $row);

            if (($n + $start + 1) == $lineNumber) {
                preg_match_all('#<[^>]+>#', $row, $tags);
                $out .= sprintf("<span class='line highlight'><span class='number'>{$format}</span> %s\n</span>%s", $n + $start + 1, strip_tags($row), implode('', $tags[0])
                );
            } else {
                $out .= sprintf('<span class="line"><span class="number">' . $format . '</span> %s', $n + $start + 1, $row) . "\n";
            }
        }

        if ($spans > 0) {
            $out .= str_repeat('</span>', $spans);
        }

        return '<pre><code>' . $out . '</code></pre>';
    }
}
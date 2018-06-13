<?php

namespace app;

use slim\http\Code as CodeBase;

class Code extends CodeBase
{
    const SUCCESS            = 'success';
    const ERROR              = 'error';
    const UNDEFINED_VARIABLE = 'UndefinedVariable';
    const UNDEFINED_INDEX    = 'UndefinedIndex';
    const PARSE_ERROR        = 'ParseError';
    const TYPE_ERROR         = 'TypeError';
    const FATAL_ERROR        = 'FatalError';
    const SYNTAX_ERROR       = 'SyntaxError';
    const METHOD_NOT_EXISTS  = 'MethodNotExists';


    protected function message()
    {
        return [
            self::SUCCESS           => [
                'status'  => 200,
                'message' => 'success',
            ],
            self::ERROR             => [
                'status'  => 500,
                'message' => 'error, %s,, ,, .',
            ],
            self::METHOD_NOT_EXISTS => [
                //'status'  => 500,
                'message' => 'method not exists, %s',
            ],
        ];
    }
}
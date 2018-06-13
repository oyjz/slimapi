<?php

namespace slim\support;

class Number
{

    public static function counter(&$counter = 0)
    {
        $counter++;

        return $counter;
    }
}
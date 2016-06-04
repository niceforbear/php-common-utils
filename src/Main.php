<?php
/**
 * @author nesfoubaer
 * @date 16/6/4 下午8:00
 */

namespace niceforbear\utils;


class Main
{
    public static function dumpTs()
    {
        echo date('Y-m-d H:i:s', time()), PHP_EOL;
    }
}
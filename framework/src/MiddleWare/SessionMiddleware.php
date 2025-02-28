<?php

namespace StormBin\Package\MiddleWare;

class SessionMiddleware
{
    public static function start()
    {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
    }
}

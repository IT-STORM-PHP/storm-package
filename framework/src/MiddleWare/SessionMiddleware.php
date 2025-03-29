<?php

namespace StormBin\Package\MiddleWare;

use Illuminate\Support\Facades\Session;

class SessionMiddleware
{
    public static function start()
    {
        if (!Session::isStarted()) {
            Session::start();
        }
    }
}


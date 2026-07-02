<?php

declare(strict_types=1);

use Session\Session;

function session(): Session
{
    return app()->session;
}

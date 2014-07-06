<?php

namespace Filler;

use Filler\Command\LoadFixturesCommand;
use Symfony\Component\Console\Application as BaseApplication;

class Application extends BaseApplication
{
    const FILLER_VERSION = '0.1';

    public function __construct($name = 'UNKNOWN', $version = 'UNKNOWN')
    {
        parent::__construct('Filler', self::FILLER_VERSION);

        $this->add(new LoadFixturesCommand());
    }
}
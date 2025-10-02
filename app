#!/usr/bin/env php
<?php

require __DIR__.'/vendor/autoload.php';

use Interface\Cli\Commands\MenuCommand;
use Symfony\Component\Console\Application;

$app = new Application('Vacation CLI', '1.0');
$app->add(new MenuCommand);
$app->run();

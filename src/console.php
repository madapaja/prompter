<?php

use Symfony\Component\Console\Application;
use Madapaja\PrompterBundle\Command\CheckCommand;

$console = new Application('prompter', '0.1');
$console->add(new CheckCommand());

return $console;

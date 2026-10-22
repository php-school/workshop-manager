<?php

namespace PhpSchool\WorkshopManager;

use DI\Container;
use Psr\Container\ContainerInterface;
use Silly\Application as SillyApplication;

class Application extends SillyApplication
{
    /**
     * @var string
     */
    private static $logo = '
   ____    __  __  ____        ____            __                    ___
  /\  _`\ /\ \/\ \/\  _`\     /\  _`\         /\ \                  /\_ \
  \ \ \L\ \ \ \_\ \ \ \L\ \   \ \,\L\_\    ___\ \ \___     ___    __\//\ \
   \ \ ,__/\ \  _  \ \ ,__/    \/_\__ \   /\'___\ \  _ `\  / __`\ / __`\ \ \
    \ \ \/  \ \ \ \ \ \ \/       /\ \L\ \/\ \__/\ \ \ \ \/\ \L\ /\ \L\ \_\ \_
     \ \_\   \ \_\ \_\ \_\       \ `\____\ \____\\ \_\ \_\ \____\ \____/\____\
      \/_/    \/_/\/_/\/_/        \/_____/\/____/ \/_/\/_/\/___/ \/___/\/____/';

    public function __construct(
        string $name = 'UNKNOWN',
        string $version = 'UNKNOWN',
        ?ContainerInterface $container = null
    ) {
        parent::__construct($name, $version);

        $this->useContainer($container ?? new Container(), true, true);
    }

    public function getHelp(): string
    {
        return sprintf(
            "<fg=magenta>%s</>\n\n<comment>%s</comment> <info>%s</info>",
            self::$logo,
            $this->getName(),
            $this->getVersion()
        );
    }
}

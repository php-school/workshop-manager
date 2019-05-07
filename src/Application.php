<?php

namespace PhpSchool\WorkshopManager;

use Silly\Edition\PhpDi\Application as PhpDiApp;

class Application extends PhpDiApp
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


    public function getHelp()
    {
        return sprintf(
            "<fg=magenta>%s</>\n\n<comment>%s</comment> <info>%s</info>",
            self::$logo,
            $this->getName(),
            $this->getVersion()
        );
    }
}

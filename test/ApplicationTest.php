<?php

namespace PhpSchool\WorkshopManagerTest;

use PhpSchool\WorkshopManager\Application;
use PHPUnit_Framework_TestCase;

/**
 * @author Aydin Hassan <aydin@hotmail.co.uk>
 */
class ApplicationTest extends PHPUnit_Framework_TestCase
{
    public function testGetHelp()
    {
        $application = new Application('PHP School Workshop Manager', '1.0.0');

        $expected = '<fg=magenta>
   ____    __  __  ____        ____            __                    ___
  /\  _`\ /\ \/\ \/\  _`\     /\  _`\         /\ \                  /\_ \
  \ \ \L\ \ \ \_\ \ \ \L\ \   \ \,\L\_\    ___\ \ \___     ___    __\//\ \
   \ \ ,__/\ \  _  \ \ ,__/    \/_\__ \   /\'___\ \  _ `\  / __`\ / __`\ \ \
    \ \ \/  \ \ \ \ \ \ \/       /\ \L\ \/\ \__/\ \ \ \ \/\ \L\ /\ \L\ \_\ \_
     \ \_\   \ \_\ \_\ \_\       \ `\____\ \____\\ \_\ \_\ \____\ \____/\____\
      \/_/    \/_/\/_/\/_/        \/_____/\/____/ \/_/\/_/\/___/ \/___/\/____/</>

<comment>PHP School Workshop Manager</comment> <info>1.0.0</info>';

        $this->assertSame($expected, $application->getHelp());
    }
}

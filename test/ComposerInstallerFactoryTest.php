<?php

namespace PhpSchool\WorkshopManagerTest;

use Composer\Factory;
use Composer\Installer;
use Composer\IO\NullIO;
use PhpSchool\WorkshopManager\ComposerInstallerFactory;
use PHPUnit_Framework_TestCase;

/**
 * Class ComposerInstallerFactoryTest
 * @author Aydin Hassan <aydin@hotmail.co.uk>
 */
class ComposerInstallerFactoryTest extends PHPUnit_Framework_TestCase
{
    public function testCreate()
    {
        $tmpDir = sprintf('%s/%s', sys_get_temp_dir(), $this->getName());
        @mkdir($tmpDir);
        file_put_contents(sprintf('%s/composer.json', $tmpDir), json_encode(['name' => 'project']));

        $factory = new ComposerInstallerFactory(new Factory, new NullIO);

        $this->assertInstanceOf(Installer::class, $factory->create($tmpDir));

        unlink(sprintf('%s/composer.json', $tmpDir));
        rmdir($tmpDir);
    }
}

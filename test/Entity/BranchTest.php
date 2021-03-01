<?php

declare(strict_types=1);

namespace PhpSchool\WorkshopManagerTest\Entity;

use PhpSchool\WorkshopManager\Entity\Branch;
use PHPUnit\Framework\TestCase;

class BranchTest extends TestCase
{
    public function testBranchWithoutRepo(): void
    {
        $branch = new Branch('master');

        $this->assertEquals('master', $branch->getBranch());
        $this->assertFalse($branch->isDifferentRepository());
        $this->assertNull($branch->getGitHubOwner());
        $this->assertNull($branch->getGitHubRepoName());
        $this->assertEquals('master', (string) $branch);
    }

    public function testBranchWithDifferentRepo(): void
    {
        $branch = new Branch('master', 'https://github.com/AydinHassan/php8-appreciate');

        $this->assertEquals('master', $branch->getBranch());
        $this->assertTrue($branch->isDifferentRepository());
        $this->assertEquals('AydinHassan', $branch->getGitHubOwner());
        $this->assertEquals('php8-appreciate', $branch->getGitHubRepoName());
        $this->assertEquals('https://github.com/AydinHassan/php8-appreciate:master', (string) $branch);
    }
}

<?php

declare(strict_types=1);

namespace PhpSchool\WorkshopManager\Exception;

use PhpSchool\WorkshopManager\Entity\Workshop;

final class NoTaggedReleaseException extends \RuntimeException
{
    public static function fromWorkshop(Workshop $workshop): self
    {
        return new self("Workshop {$workshop->getDisplayName()} has no tagged releases.");
    }
}

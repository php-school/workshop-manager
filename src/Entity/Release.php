<?php

namespace PhpSchool\WorkshopManager\Entity;

class Release
{
    /**
     * @var string
     */
    private $tag;

    /**
     * @var string
     */
    private $sha;

    public function __construct(string $tag, string $sha)
    {
        $this->tag = $tag;
        $this->sha = $sha;
    }

    public function getTag(): string
    {
        return $this->tag;
    }

    public function getSha(): string
    {
        return $this->sha;
    }
}

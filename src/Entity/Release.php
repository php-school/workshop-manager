<?php

namespace PhpSchool\WorkshopManager\Entity;

/**
 * @author Aydin Hassan <aydin@hotmail.co.uk>
 */
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

    /**
     * @param string $tag
     * @param string $sha
     */
    public function __construct($tag, $sha)
    {
        $this->tag = $tag;
        $this->sha = $sha;
    }

    /**
     * @return string
     */
    public function getTag()
    {
        return $this->tag;
    }

    /**
     * @return string
     */
    public function getSha()
    {
        return $this->sha;
    }
}

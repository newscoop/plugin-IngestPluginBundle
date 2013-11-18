<?php

namespace Newscoop\IngestPluginBundle\Parsers;

/**
 * Parser interface
 */
interface Parser
{
    /**
     * Parser name
     *
     * @var string
     */
    public $parserName;

    /**
     * Parser description
     *
     * @var string
     */
    public $parserDescription;

    /**
     * Parser domain, can use basic regexp for matching
     *
     * @var string
     */
    public $parserDomain;

    /**
     * Get ID
     *
     * @return string
     */
    public function getNewsItemId();

    /**
     * Get title
     *
     * @return string
     */
    public function getTitle();

    /**
     * Get content
     *
     * @return string
     */
    public function getContent();

    /**
     * Get created
     *
     * @return DateTime
     */
    public function getCreated();

    /**
     * Get updated
     *
     * @return DateTime
     */
    public function getUpdated();
}

<?php

namespace Newscoop\IngestPluginBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping AS ORM;
use Newscoop\IngestPluginBundle\Entity\Feed;

/**
 * @ORM\Entity(repositoryClass="Newscoop\IngestPluginBundle\Entity\Repository\ParserRepository")
 * @ORM\Table(name="plugin_ingest_parser")
 */
class Parser
{
    /**
     * @ORM\Id @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     * @var int
     */
    private $id;

    /**
     * @ORM\Column(type="string", unique=true)
     * @var string
     */
    private $namespace;

    /**
     * @ORM\Column(type="string")
     * @var string
     */
    private $name;

    /**
     * @ORM\Column(type="string")
     * @var string
     */
    private $description;

    /**
     * @ORM\Column(type="string")
     * @var string
     */
    private $domain;

    /**
     * @ORM\OneToMany(targetEntity="Newscoop\IngestPluginBundle\Entity\Feed", mappedBy="parser")
     * @var Doctrine\Common\Collections\ArrayCollection
     */
    private $feeds;

    function __construct() {
        $this->feeds = new ArrayCollection();
    }

    function __toString() {
        return $this->getName();
    }

    /**
     * Getter for id
     *
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Getter for namespace
     *
     * @return mixed
     */
    public function getNamespace()
    {
        return $this->namespace;
    }

    /**
     * Setter for namespace
     *
     * @param mixed $namespace Value to set
     * @return self
     */
    public function setNamespace($namespace)
    {
        $this->namespace = $namespace;
        return $this;
    }


    /**
     * Getter for name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Setter for name
     *
     * @param string $name Value to set
     * @return self
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * Getter for description
     *
     * @return mixed
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Setter for description
     *
     * @param mixed $description Value to set
     * @return self
     */
    public function setDescription($description)
    {
        $this->description = $description;
        return $this;
    }

    /**
     * Getter for domain
     *
     * @return mixed
     */
    public function getDomain()
    {
        return $this->domain;
    }

    /**
     * Setter for domain
     *
     * @param mixed $domain Value to set
     * @return self
     */
    public function setDomain($domain)
    {
        $this->domain = $domain;
        return $this;
    }

    /**
     * Getter for feeds
     *
     * @return Doctrine\Common\Collections\ArrayCollection
     */
    public function getFeeds()
    {
        return $this->feeds;
    }

    /**
     * Setter for feeds
     *
     * @param Doctrine\Common\Collections\ArrayCollection $feeds Value to set
     * @return self
     */
    public function setFeeds(ArrayCollection $feeds)
    {
        $this->feeds = $feeds;
        return $this;
    }

}

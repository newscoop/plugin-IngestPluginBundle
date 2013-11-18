<?php
/**
 * @package Newscoop
 * @copyright 2011 Sourcefabric o.p.s.
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 */

namespace Newscoop\IngestPluginBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping AS ORM;
use Newscoop\IngestPluginBundle\Entity\Feed\Entry;
use Newscoop\IngestPluginBundle\Entity\Parser;

/**
 * @ORM\Entity(repositoryClass="Newscoop\IngestPluginBundle\Entity\Repository\FeedRepository")
 * @ORM\Table(name="plugin_ingest_feed")
 */
class Feed
{
    /**
     * @ORM\Id @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     * @var int
     */
    private $id;

    /**
     * @ORM\Column(type="string")
     * @var string
     */
    private $name;

    /**
     * @ORM\Column(type="string")
     * @var string
     */
    private $url;

    /**
     * @ORM\ManyToMany(targetEntity="Newscoop\Entity\Section")
     * @ORM\JoinTable(name="feeds_sections",
     *      joinColumns={@ORM\JoinColumn(name="feed_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="section_id", referencedColumnName="id", unique=true)}
     *      )
     **/
    private $sections;

    /**
     * @ORM\Column(type="datetime", nullable=True)
     * @var DateTime
     */
    private $updated;

    /**
     * @ORM\Column(type="string")
     * @var string
     */
    private $mode;

    /**
     * @ORM\ManyToOne(targetEntity="Newscoop\IngestPluginBundle\Entity\Parser", inversedBy="feeds")
     * @ORM\JoinColumn(name="parser_id", referencedColumnName="id")
     * @var Newscoop\IngestPluginBundle\Entity\Parser
     */
    private $parser;

    /**
     * @ORM\OneToMany(targetEntity="Newscoop\IngestPluginBundle\Entity\Feed\Entry", mappedBy="feed")
     * @var Doctrine\Common\Collections\ArrayCollection
     */
    private $entries;

    /**
     * @param string $title
     */
    public function __construct($name='New Feed')
    {
        $this->name = $name;
        $this->mode = "manual";
        $this->entries = new ArrayCollection();
        $this->sections = new ArrayCollection();
    }

    /**
     * Get id
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->title;
    }

    /**
     * Set name
     *
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * Getter for url
     *
     * @return mixed
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Setter for url
     *
     * @param mixed $url Value to set
     * @return self
     */
    public function setUrl($url)
    {
        $this->url = $url;
        return $this;
    }

    /**
     * Getter for sections
     *
     * @return mixed
     */
    public function getSections()
    {
        return $this->sections;
    }

    /**
     * Setter for sections
     *
     * @param mixed sections Value to set
     * @return self
     */
    public function setSections(ArrayCollection $sections)
    {
        $this->sections = $sections;
        return $this;
    }


    /**
     * Set updated
     *
     * @param DateTime $updated
     * @return self
     */
    public function setUpdated(\DateTime $updated)
    {
        $this->updated = $updated;
        return $this;
    }

    /**
     * Get updated
     *
     * @return DateTime
     */
    public function getUpdated()
    {
        return $this->updated;
    }

    /**
     * Set mode (manual|automatic)
     *
     * @param string $mode
     * @return self
     */
    public function setMode($mode)
    {
        $this->mode = $mode;
        return $this;
    }

    /**
     * Get mode
     *
     * @return string
     */
    public function getMode()
    {
        return (string) $this->mode;
    }

    public function isAutoMode()
    {
        return (bool) ($this->getMode() === "auto");
    }

    /**
     * Getter for parser
     *
     * @return Newscoop\IngestPluginBundle\Entity\Parser
     */
    public function getParser()
    {
        return $this->parser;
    }

    /**
     * Setter for parser
     *
     * @param Newscoop\IngestPluginBundle\Entity\Parser $parser Value to set
     * @return self
     */
    public function setParser(\Newscoop\IngestPluginBundle\Entity\Parser $parser)
    {
        $this->parser = $parser;
        return $this;
    }

    /**
     * Getter for entries
     *
     * @return Doctrine\Common\Collections\ArrayCollection
     */
    public function getEntries()
    {
        return $this->entries;
    }

    /**
     * Setter for entries
     *
     * @param Doctrine\Common\Collections\ArrayCollection $entries Value to set
     * @return self
     */
    public function setEntries(ArrayCollection $entries)
    {
        $this->entries = $entries;
        return $this;
    }

}

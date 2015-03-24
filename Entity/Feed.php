<?php
/**
 * @package   Newscoop\IngestPluginBundle
 * @author    Mischa Gorinskat <mischa.gorinskat@sourcefabric.org>
 * @copyright 2013 Sourcefabric o.p.s.
 * @license   http://www.gnu.org/licenses/gpl-3.0.txt
 */

namespace Newscoop\IngestPluginBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping AS ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Newscoop\Entity\Language;
use Newscoop\IngestPluginBundle\Entity\Feed\Entry;
use Newscoop\IngestPluginBundle\Entity\Parser;

/**
 * Feed entity
 *
 * @ORM\Entity(repositoryClass="Newscoop\IngestPluginBundle\Entity\Repository\FeedRepository")
 * @ORM\Table(name="plugin_ingest_feed")
 */
class Feed
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(name="id", type="integer")
     * @var int
     */
    protected $id;

    /**
     * @ORM\Column(name="enabled", type="boolean")
     * @var boolean
     */
    protected $enabled;

    /**
     * @ORM\Column(type="string")
     * @var string
     */
    protected $name;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @var string
     */
    protected $url;

    /**
     * @ORM\ManyToOne(targetEntity="Newscoop\Entity\Publication")
     * @ORM\JoinColumn(name="publication_id", referencedColumnName="Id", nullable=true)
     * @var Newscoop\Entity\Publication
     */
    protected $publication;

    /**
     * @ORM\ManyToOne(targetEntity="Newscoop\Entity\Issue")
     * @ORM\JoinColumn(name="issue_id", referencedColumnName="id", nullable=true)
     * @var Newscoop\Entity\Issue
     */
    protected $issue;

    /**
     * @ORM\ManyToMany(targetEntity="Newscoop\Entity\Section")
     * @ORM\JoinTable(name="plugin_ingest_feeds_sections",
     *      joinColumns={@ORM\JoinColumn(name="feed_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="section_id", referencedColumnName="id")}
     *      )
     * @var Doctrine\Common\Collections\ArrayCollection
     **/
    protected $sections;

    /**
     * @ORM\ManyToOne(targetEntity="Newscoop\Entity\Language")
     * @ORM\JoinColumn(name="language_id", referencedColumnName="Id")
     * @var Newscoop\Entity\Language
     */
    protected $language;

    /**
     * @ORM\ManyToMany(targetEntity="Newscoop\NewscoopBundle\Entity\Topic")
     * @ORM\JoinTable(name="plugin_ingest_feeds_topics",
     *      joinColumns={@ORM\JoinColumn(name="feed_id", referencedColumnName="id")},
     *      inverseJoinColumns={
     *          @ORM\JoinColumn(name="topic_id", referencedColumnName="id")
     *      }
     * )
     * @var Doctrine\Common\Collections\ArrayCollection
     */
    protected $topics;

    /**
     * @ORM\Column(type="datetime", nullable=True)
     * @var DateTime
     */
    protected $updated;

    /**
     * @ORM\Column(type="string")
     * @var string
     */
    protected $mode = "manual";

    /**
     * @ORM\ManyToOne(targetEntity="Newscoop\IngestPluginBundle\Entity\Parser", inversedBy="feeds")
     * @ORM\JoinColumn(name="parser_id", referencedColumnName="id")
     * @var Newscoop\IngestPluginBundle\Entity\Parser
     */
    protected $parser;

    /**
     * @ORM\OneToMany(targetEntity="Newscoop\IngestPluginBundle\Entity\Feed\Entry", mappedBy="feed")
     * @var Doctrine\Common\Collections\ArrayCollection
     */
    protected $entries;

    /**
     * Initialize object
     *
     * @param string $name
     */
    public function __construct($name='New Feed')
    {
        $this->name = $name;
        $this->entries = new ArrayCollection();
        $this->sections = new ArrayCollection();
        $this->topics = new ArrayCollection();
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
     * Get id
     *
     * @param int $id
     *
     * @return self
     */
    public function setId($id)
    {
        $this->id = $id;

        return self;
    }

    /**
     * Getter for enabled
     *
     * @return boolean
     */
    public function getEnabled()
    {
        return $this->enabled;
    }

    /**
     * Setter for enabled
     *
     * @param boolean $enabled
     *
     * @return self
     */
    public function setEnabled($enabled)
    {
        $this->enabled = $enabled;

        return $this;
    }

    /**
     * Check if enabled
     *
     * @return boolean
     */
    public function isEnabled()
    {
        return ($this->getEnabled() === true);
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set name
     *
     * @param string $name
     *
     * @return self
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
     *
     * @return self
     */
    public function setUrl($url)
    {
        $this->url = $url;

        return $this;
    }

    /**
     * Getter for publication
     *
     * @return \Newscoop\Entity\Publication
     */
    public function getPublication()
    {
        return $this->publication;
    }

    /**
     * Setter for publication
     *
     * @param \Newscoop\Entity\Publication $publication Value to set
     *
     * @return self
     */
    public function setPublication($publication)
    {
        $this->publication = $publication;

        return $this;
    }

    /**
     * Getter for Issue
     *
     * @return mixed
     */
    public function getIssue()
    {
        return $this->issue;
    }

    /**
     * Setter for Issue
     *
     * @param mixed $Issue Value to set
     *
     * @return self
     */
    public function setIssue($issue)
    {
        $this->issue = $issue;

        return $this;
    }


    /**
     * Getter for sections
     *
     * @return Doctrine\Common\Collections\ArrayCollection
     */
    public function getSections()
    {
        return $this->sections;
    }

    /**
     * Setter for sections
     *
     * @param Doctrine\Common\Collections\ArrayCollection $sections Value to set
     *
     * @return self
     */
    public function setSections($sections)
    {
        $this->sections = $sections;

        return $this;
    }

    /**
     * Returns whether languages should be auto detected or not
     *
     * @return boolean
     */
    public function languageAutoMode()
    {
        return ($this->getLanguage() === null);
    }

    /**
     * Getter for language
     *
     * @return Newscoop\Entity\Language
     */
    public function getLanguage()
    {
        return $this->language;
    }

    /**
     * Setter for language
     *
     * @param Newscoop\Entity\Language $language Value to set
     *
     * @return self
     */
    public function setLanguage($language)
    {
        $this->language = $language;

        return $this;
    }

    /**
     * Getter for topics
     *
     * @return mixed
     */
    public function getTopics()
    {
        return $this->topics;
    }

    /**
     * Setter for topics
     *
     * @param mixed $topics Value to set
     *
     * @return self
     */
    public function setTopics($topics)
    {
        $this->topics = $topics;

        return $this;
    }


    /**
     * Set updated
     *
     * @param DateTime $updated
     *
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
     *
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

    /**
     * Check if mode it automatic
     *
     * @return boolean
     */
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
     *
     * @return self
     */
    public function setParser(Parser $parser)
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
     *
     * @return self
     */
    public function setEntries(ArrayCollection $entries)
    {
        $this->entries = $entries;

        return $this;
    }
}

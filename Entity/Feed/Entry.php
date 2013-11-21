<?php
/**
 * @package   Newscoop\IngestPluginBundle
 * @author    Mischa Gorinskat <mischa.gorinskat@sourcefabric.org>
 * @copyright 2013 Sourcefabric o.p.s.
 * @license   http://www.gnu.org/licenses/gpl-3.0.txt
 */

namespace Newscoop\IngestPluginBundle\Entity\Feed;

use Doctrine\ORM\Mapping AS ORM;
use Newscoop\IngestPluginBundle\Entity\Feed;
use Newscoop\IngestPluginBundle\Parser;

/**
 * Feed entry entity
 *
 * @ORM\Entity(repositoryClass="Newscoop\IngestPluginBundle\Entity\Repository\Feed\EntryRepository")
 * @ORM\Table(name="plugin_ingest_feed_entry")
 */
class Entry
{
    /**
     * @ORM\Id @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     * @var int
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="Newscoop\IngestPluginBundle\Entity\Feed", inversedBy="entries")
     * @ORM\JoinColumn(name="feed_id", referencedColumnName="id")
     * @var Newscoop\IngestPluginBundle\Entity\Feed
     */
    private $feed;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @var string
     */
    private $newsItemid;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @var string
     */
    private $articleId;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @var string
     */
    private $language;

    /**
     * @ORM\Column(type="string")
     * @var string
     */
    private $title;

    /**
     * @ORM\Column(type="text", nullable=true)
     * @var string
     */
    private $content;

    /**
     * @ORM\Column(type="text", nullable=true)
     * @var string
     */
    private $catchline;

    /**
     * @ORM\Column(type="text", nullable=true)
     * @var string
     */
    private $summary;

    /**
     * @ORM\Column(type="datetime")
     * @var DateTime
     */
    private $created;

    /**
     * @ORM\Column(type="datetime")
     * @var DateTime
     */
    private $updated;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     * @var DateTime
     */
    private $published;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @var String
     */
    private $product;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @var string
     */
    private $status;

    /**
     * @ORM\Column(type="integer", nullable=true)
     * @var int
     */
    private $priority;

    /**
     * @ORM\Column(type="object", nullable=true)
     * @var \Newscoop\Entity\Section
     */
    private $section;

    /**
     * @ORM\Column(type="array", nullable=true)
     * @var array
     */
    private $keywords;

    /**
     * @ORM\Column(type="array", nullable=true)
     * @var array
     */
    private $authors;

    /**
     * @ORM\Column(type="array", nullable=true)
     * @var array
     */
    private $images;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     * @var DateTime
     */
    private $embargoed;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @var string
     */
    private $link;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @var string
     */
    private $date_id;

    /**
     * @ORM\Column(type="array", nullable=true)
     * @var array
     */
    private $attributes;

    /**
     * Set properties
     */
    public function __construct()
    {
        $this->created      = new \DateTime();
        $this->updated      = new \DateTime();
        $this->attributes   = array();
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
     * Setter for id
     *
     * @param int $id Value to set
     *
     * @return self
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Get feed
     *
     * @return Newscoop\IngestPluginBundle\Entity\Feed
     */
    public function getFeed()
    {
        return $this->feed;
    }

    /**
     * Set feed
     *
     * @param Newscoop\IngestPluginBundle\Entity\Feed $feed
     *
     * @return self
     */
    public function setFeed(\Newscoop\IngestPluginBundle\Entity\Feed $feed)
    {
        $this->feed = $feed;

        return $this;
    }

    /**
     * Get news item id
     *
     * @return string
     */
    public function getNewsItemId()
    {
        return $this->newsItemId;
    }

    /**
     * Setter for newsItemId
     *
     * @param string $newsItemId Value to set
     *
     * @return self
     */
    public function setNewsItemId($newsItemId)
    {
        $this->newsItemId = $newsItemId;

        return $this;
    }

    /**
     * Get language code
     *
     * @return string
     */
    public function getLanguage()
    {
        return $this->language;
    }

    /**
     * Setter for language
     *
     * @param string $language Value to set
     *
     * @return self
     */
    public function setLanguage($language)
    {
        $this->language = $language;

        return $this;
    }

    /**
     * Get title
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Setter for title
     *
     * @param string $title Value to set
     *
     * @return self
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get content
     *
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * Setter for content
     *
     * @param string $content Value to set
     *
     * @return self
     */
    public function setContent($content)
    {
        $this->content = $content;

        return $this;
    }

    /**
     * Get catch line
     *
     * @return string
     */
    public function getCatchLine()
    {
        return $this->catchLine;
    }

    /**
     * Setter for catchLine
     *
     * @param mixed $catchLine Value to set
     *
     * @return self
     */
    public function setCatchLine($catchLine)
    {
        $this->catchLine = $catchLine;

        return $this;
    }

    /**
     * Get summary
     *
     * @return string
     */
    public function getSummary()
    {
        return $this->summary;
    }

    /**
     * Setter for summary
     *
     * @param string $summary Value to set
     *
     * @return self
     */
    public function setSummary($summary)
    {
        $this->summary = $summary;

        return $this;
    }

    /**
     * Get created
     *
     * @return \DateTime
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * Setter for created
     *
     * @param \DateTime $created Value to set
     *
     * @return self
     */
    public function setCreated(\DateTime $created)
    {
        $this->created = $created;

        return $this;
    }

    /**
     * Get date id
     *
     * @return string
     */
    public function getDateId()
    {
        $date = $this->getCreated();

        return $date->format('dmY');
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
     * Setter for updated
     *
     * @param \DateTime $updated Value to set
     *
     * @return self
     */
    public function setUpdated(\DateTime $updated)
    {
        $this->updated = $updated;

        return $this;
    }

    /**
     * Get published
     *
     * @return DateTime
     */
    public function getPublished()
    {
        return $this->published;
    }

    /**
     * Set published
     *
     * @param \DateTime $published
     *
     * @return self
     */
    public function setPublished(\DateTime $published)
    {
        $this->published = $published;

        return $this;
    }

    // TODO: check if this is correct
    /**
     * Test if is published
     *
     * @return bool
     */
    public function isPublished()
    {
        return ($this->published !== null);
    }

    /**
     * Get product
     *
     * @return string
     */
    public function getProduct()
    {
        return $this->product;
    }

    /**
     * Setter for product
     *
     * @param string $product Value to set
     *
     * @return self
     */
    public function setProduct($product)
    {
        $this->product = $product;

        return $this;
    }

    /**
     * Get status
     *
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Setter for status
     *
     * @param string $status Value to set
     *
     * @return self
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get priority
     *
     * @return int
     */
    public function getPriority()
    {
        return $this->priority;
    }

    /**
     * Setter for priority
     *
     * @param int $priority Value to set
     *
     * @return self
     */
    public function setPriority($priority)
    {
        $this->priority = $priority;

        return $this;
    }

    /**
     * Getter for section
     *
     * @return mixed
     */
    public function getSection()
    {
        return $this->section;
    }

    /**
     * Setter for section
     *
     * @param null|\Newscoop\Entity\Section $section Value to set
     *
     * @return self
     */
    public function setSection($section)
    {
        $this->section = $section;

        return $this;
    }

    /**
     * Getter for keywords
     *
     * @return array
     */
    public function getKeywords()
    {
        return $this->keywords;
    }

    /**
     * Setter for keywords
     *
     * @param array $keywords Value to set
     *
     * @return self
     */
    public function setKeywords(Array $keywords)
    {
        $this->keywords = $keywords;

        return $this;
    }

    /**
     * Get authors
     *
     * @return array
     */
    public function getAuthors()
    {
        return $this->authors;
    }

    /**
     * Setter for authors
     *
     * @param array $authors Value to set
     *
     * @return self
     */
    public function setAuthors(Array $authors)
    {
        $this->authors = $authors;

        return $this;
    }

    /**
     * Get images
     *
     * @return array
     */
    public function getImages()
    {
        return $this->images;
    }

    /**
     * Setter for images
     *
     * @param array $images Value to set
     *
     * @return self
     */
    public function setImages(Array $images)
    {
        $this->images = $images;

        return $this;
    }

    /**
     * Get embargoed
     *
     * @return DateTime
     */
    public function getEmbargoed()
    {
        return $this->embargoed;
    }

    /**
     * Setter for embargoed
     *
     * @param \DateTime $embargoed Value to set
     *
     * @return self
     */
    public function setEmbargoed(\DateTime $embargoed)
    {
        $this->embargoed = $embargoed;

        return $this;
    }

    /**
     * Get link
     *
     * @return string
     */
    public function getLink()
    {
        return $this->link;
    }

    /**
     * Setter for link
     *
     * @param string $link Value to set
     *
     * @return self
     */
    public function setLink($link)
    {
        $this->link = $link;

        return $this;
    }

    /**
     * Get all attributes
     *
     * @param boolean $serialized Serialeze a json string or not
     *
     * @return string|array
     */
    public function getAttributes($serialized = false)
    {
        return ($serialized) ? json_encode($this->attributes) : $this->attributes;
    }

    /**
     * Setter for attributes
     *
     * @param array $attributes Value to set
     *
     * @return self
     */
    public function setAttributes(Array $attributes)
    {
        $this->attributes = $attributes;

        return $this;
    }

    /**
     * Set attribute
     *
     * @param string $name
     * @param mixed  $value
     *
     * @return self
     */
    public function setAttribute($name, $value)
    {
        $this->attributes[$name] = $value;

        return $this;
    }

    /**
     * Get attribute, returns null when attribute doesn't exist.
     *
     * @param string $name
     *
     * @return mixed
     */
    public function getAttribute($name)
    {
        return ($this->attributeExists($name)) ? $this->attributes[$name] : null;
    }

    /**
     * Check if a attribute exists
     *
     * @param string $name Attribute name
     *
     * @return boolean
     */
    public function attributeExists($name)
    {
        return array_key_exists($name, $this->attributes);
    }

    /**
     * Update entry
     *
     * @param Newscoop\IngestPluginBundle\Parser $parser
     * @return self
     */
    // public function update(\Newscoop\IngestPluginBundle\Parser $parser)
    // {
    //     $this->updated = $parser->getUpdated();
    //     $this->title = $parser->getTitle();
    //     $this->content = $parser->getContent();
    //     $this->priority = $parser->getPriority();
    //     $this->summary = (string) $parser->getSummary();
    //     $this->status = (string) $parser->getStatus();
    //     $this->embargoed = $parser->getLiftEmbargo();
    //     self::setAttributes($this, $parser);
    //     self::setImages($this, $parser);
    //     return $this;
    // }

    /**
     * Entry factory
     *
     * @param Newscoop\IngestPluginBundle\Parser $parser
     * @return Newscoop\IngestPluginBundle\Entity\Feed\Entry
     */
    // public static function create(Newscoop\IngestPluginBundle\Parser $parser)
    // {
    //     $entry = new self($parser->getTitle(), $parser->getContent());
    //     $entry->created = $parser->getCreated() ?: $entry->created;
    //     $entry->updated = $parser->getUpdated() ?: $entry->updated;
    //     $entry->priority = (int) $parser->getPriority();
    //     $entry->summary = (string) $parser->getSummary();
    //     $entry->date_id = (string) $parser->getDateId();
    //     $entry->news_item_id = (string) $parser->getNewsItemId();
    //     $entry->status = (string) $parser->getStatus();
    //     $entry->embargoed = $parser->getLiftEmbargo();
    //     self::setAttributes($entry, $parser);
    //     self::setImages($entry, $parser);
    //     return $entry;
    // }

    /**
     * Set entry attributes
     *
     * @param Newscoop\IngestPluginBundle\Entity\Ingest\Entry $entry
     * @param Newscoop\IngestPluginBundle\Ingest\Parser $parser
     */
    // private static function setAttributes(self $entry, Parser $parser)
    // {
    //     $entry->setAttribute('service', (string) $parser->getService());
    //     $entry->setAttribute('language', (string) $parser->getLanguage());
    //     $entry->setAttribute('subject', (string) $parser->getSubject());
    //     $entry->setAttribute('country', (string) $parser->getCountry());
    //     $entry->setAttribute('product', (string) $parser->getProduct());
    //     $entry->setAttribute('subtitle', (string) $parser->getSubtitle());
    //     $entry->setAttribute('provider_id', (string) $parser->getProviderId());
    //     $entry->setAttribute('revision_id', (string) $parser->getRevisionId());
    //     $entry->setAttribute('location', (string) $parser->getLocation());
    //     $entry->setAttribute('provider', (string) $parser->getProvider());
    //     $entry->setAttribute('source', (string) $parser->getSource());
    //     $entry->setAttribute('catch_line', (string) $parser->getCatchLine());
    //     $entry->setAttribute('catch_word', (string) $parser->getCatchWord());
    //     $entry->setAttribute('authors', (string) $parser->getAuthors());
    // }

    /**
     * Set entry images
     *
     * @param Newscoop\IngestPluginBundle\Entity\Feed\Entry $entry
     * @param Newscoop\IngestPluginBundle\Parser $parser
     */
    // private static function setImages(self $entry, Parser $parser)
    // {
    //     $images = array();
    //     $parserImages = $parser->getImages();
    //     if (is_array($parserImages)) {
    //         foreach ($parserImages as $image) {
    //             $images[basename($image->getPath())] = $image->getTitle();
    //         }
    //     }

    //     $entry->setAttribute('images', $images);
    // }
}

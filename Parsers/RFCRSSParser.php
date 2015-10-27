<?php
/**
 * @category  IngestPlugin
 * @package   Newscoop\IngestPluginBundle
 * @author    Mischa Gorinskat <mischa.gorinskat@sourcefabric.org>
 * @copyright 2013 Sourcefabric o.p.s.
 * @license   http://www.gnu.org/licenses/gpl-3.0.txt  GNU GENERAL PUBLIC LICENSE Version 3
 */

namespace Newscoop\IngestPluginBundle\Parsers;

use Newscoop\IngestPluginBundle\Parsers;
use SimplePie;

/**
 * Parses RSS (all versions) and ATOM feeds.
 */
class RFCRSSParser extends AbstractParser
{
    /**
     * Parser name
     *
     * @var string
     */
    protected static $parserName = 'RSS';

    /**
     * Parser description
     *
     * @var string
     */
    protected static $parserDescription = 'This parser can be used for RSS 1.0, RSS 2.0 and Atom';

    /**
     * Parser domain, can use basic regexp for matching
     *
     * @var string
     */
    protected static $parserDomain = '*';

    /**
     * Simplepie_item object which represents entry in a feed
     *
     * @var \Simplepie_item
     */
    private $entry;

    /**
     * Get all feed entries as a parser instance
     *
     * @param \Newscoop\IngestPluginBundle\Entity\Feed $feedEntity Feed entity
     *
     * @return array Array should contain feed entries
     */
    public static function getStories(\Newscoop\IngestPluginBundle\Entity\Feed $feedEntity)
    {
        $feed = new SimplePie();
        $feed->set_feed_url($feedEntity->getUrl());
        $feed->set_cache_location(sprintf('%s/../../../../cache', __DIR__));

        $feedInitialized = $feed->init();

        if (!$feedInitialized) {
            throw new \Exception($feed->error());
        }

        $items = $feed->get_items();

        $entries = array();
        foreach ($items as $item) {
            $entries[] = new RFCRSSParser($item);
        }

        return array_reverse($entries);
    }

    /**
     * Initialize object with simpe pie entry
     *
     * @param \SimplePie_Item $feedEntry Feed entry
     */
    public function __construct(\SimplePie_Item $feedEntry)
    {
        $this->entry = $feedEntry;
    }

    /**
     * Get news item id, if feed provides no item use other unique value
     *
     * @return string
     */
    public function getNewsItemId()
    {
        return $this->entry->get_id();
    }

    /**
     * Get language
     *
     * @return string
     */
    public function getLanguage()
    {
        $feed = $this->entry->get_feed();

        return $feed->get_language();
    }

    /**
     * Get title
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->decodeString($this->entry->get_title());
    }

    /**
     * Get content
     *
     * @return string
     */
    public function getContent()
    {
        return $this->decodeString($this->entry->get_content(true));
    }

    /**
     * Get content
     *
     * @return string
     */
    public function getSummary()
    {
        return $this->decodeString($this->entry->get_description(true));
    }

    /**
     * Get created
     *
     * @return DateTime
     */
    public function getCreated()
    {
        return new \DateTime($this->entry->get_date());
    }

    /**
     * Get updated
     *
     * @return DateTime
     */
    public function getUpdated()
    {
        return new \DateTime($this->entry->get_updated_date());
    }

    /**
     * Get product (NewsProduct in \Article)
     *
     * @return string|null
     */
    public function getProduct()
    {
        $feed = $this->entry->get_feed();

        return $feed->get_title();
    }

    /**
     * Get categories as keywords
     *
     * @return array Each entry in the array should be a seperate keyword
     */
    public function getKeywords()
    {
        $return  = array();
        $keywords = $this->entry->get_categories();
        if (is_array($keywords) && count($keywords) > 0) {
            foreach ($keywords as $keyword) {
                $return[] = $this->decodeString($keyword->get_label());
            }
        }

        return $return;
    }

    /**
     * Get list of authors
     *
     * @return string|array
     */
    public function getAuthors()
    {
        $return  = array();
        $authors = $this->entry->get_authors();

        if (is_array($authors) && count($authors) > 0) {
            foreach ($authors as $author) {
                $authorName = $this->readName($this->decodeString($author->get_name()));
                $authors[] = array(
                    'firstname' => $authorName['firstname'],
                    'lastname' => $authorName['lastname'],
                    'email' => $author->get_email(),
                    'link' => $author->get_link(),
                );
            }
        }

        return $return;
    }

    /**
     * Get images
     *
     * @return array
     */
    public function getImages()
    {
        $enclosures = $this->entry->get_enclosures();
        // TODO: is this all?
        $imageTypes = array('image/jpeg', 'image/jpe', 'image/jpg', 'image/png', 'image/gif');
        $images = array();

        foreach ($enclosures as $enclosure) {

            if ($enclosure->get_medium() == 'image' || in_array($enclosure->get_type(), $imageTypes)) {

                // Filter owners and photographers
                $credits = $enclosure->get_credits();
                $owners = array();
                $photographers = array();
                if (is_array($credits)) {
                    foreach ($credits as $credit) {

                        if ($credits->getRole() == 'owner') {
                            $owners[] = $this->decodeString($credit->get_name());
                        } elseif ($credits->getRole() == 'photographer') {
                            $photographers[] = $this->decodeString($credit->get_name());
                        }
                    }
                }

                $images[] = array(
                    'location' => $enclosure->get_link(),
                    'description' => $this->decodeString(($enclosure->get_caption() != '') ?: $enclosure->get_title()),
                    'copyright' => $this->decodeString($enclosure->get_copyright()),
                    'photographer' => implode(', ', $photographers)
                );
            }
        }

        return $images;
    }

    /**
     * Returns link to the Article
     *
     * @return string|null
     */
    public function getLink()
    {
        return $this->entry->get_permalink();
    }

    /**
     * Get location
     *
     * @return array
     */
    public function getAttributeLocation()
    {
        return array('latitude' => $this->entry->get_latitude(), 'longitude' => $this->entry->get_longitude());
    }

    /**
     * Get source
     *
     * @return string
     */
    public function getAttributeSource()
    {
        $return  = array();
        $sources = $this->entry->get_source();

        if ($sources !== null) {
            foreach ($sources as $source) {
                $return[] = array(
                    'name' => $this->decodeString($source->get_title()),
                    'link' => $source->get_link()
                );
            }
        }

        return $return;
    }
}

<?php
/**
 * @package   Newscoop\IngestPluginBundle
 * @author    Mischa Gorinskat <mischa.gorinskat@sourcefabric.org>
 * @copyright 2013 Sourcefabric o.p.s.
 * @license   http://www.gnu.org/licenses/gpl-3.0.txt
 */

namespace Newscoop\IngestPluginBundle\Parsers;

/**
 * Abstract Parser class.
 */
abstract class AbstractParser
{
    /**
     * These constants define the proper integer values for the section handling
     * property. Use these to set and check the property.
     */
    const SECTION_HANDLING_NONE = 0;
    const SECTION_HANDLING_PARTIAL = 1;
    const SECTION_HANDLING_FULL = 2;

    /**
     * Parser name
     *
     * @var string
     */
    protected static $parserName;

    /**
     * Parser description
     *
     * @var string
     */
    protected static $parserDescription;

    /**
     * Parser domain, can use basic regexp for matching
     *
     * @var string
     */
    protected static $parserDomain;

    /**
     * Specify whether the user should enter a url or not
     *
     * @var boolean
     */
    protected static $parserRequiresURL = true;

    /**
     * Specify how the parser handles sections
     *
     * 0: Not at all (user must select one)
     * 1: Partial (parser tries to find one, uses user selection as fallback)
     * 2: Full (parser handles all, user cant select section)
     *
     * @var int
     */
    protected static $parserHandleSection = self::SECTION_HANDLING_NONE;

    /**
     * Contains all other data that needs to be stored about this feed entry.
     * Use a logical key, since those are also needed to get this data in a
     * template.
     *
     * @var array
     */
    private $_attributes;

    /**
     * Initialize variables
     */
    private function __construct()
    {
        $this->_attributes = array();
    }

    /**
     * Returns the parser name
     *
     * @return string
     */
    public static function getParserName()
    {
        return static::$parserName;
    }

    /**
     * Returns the parser description
     *
     * @return string
     */
    public static function getParserDescription()
    {
        return static::$parserDescription;
    }

    /**
     * Returns the parser domain
     *
     * @return string
     */
    public static function getParserDomain()
    {
        return static::$parserDomain;
    }

    /**
     * Returns wheter parser requires URL or not
     *
     * @return boolean
     */
    public static function getRequiresUrl()
    {
        return static::$parserRequiresURL;
    }

    /**
     * Returns parser section handling value
     *
     * @return int
     */
    public static function getHandlesSection()
    {
        return static::$parserHandleSection;
    }

    /**
     * Get individual entries from feed
     *
     * @param \Newscoop\IngestPluginBundle\Entity\Feed $feed Feed to substract entries from
     *
     * @return array() Return an array containing current parser instances
     */
    public static function getStories(\Newscoop\IngestPluginBundle\Entity\Feed $feed)
    {
        return array();
    }

    /**
     * Get ID for this entry (NewsItemIdentifier in \Article)
     *
     * @return string
     */
    public function getNewsItemId()
    {
        return uniqid();
    }

    /**
     * Get date id for this article
     *
     * @return string
     */
    public function getDateId()
    {
        return '';
    }

    /**
     * Get language
     *
     * @return string
     */
    public function getLanguage()
    {
        return '';
    }

    /**
     * Get title (HeadLine in \Article)
     *
     * @return string
     */
    public function getTitle()
    {
        return '';
    }

    /**
     * Get content (DataContent in \Article)
     *
     * @return string
     */
    public function getContent()
    {
        return '';
    }

    /**
     * Get catchline (NewsLineText in \Article)
     *
     * @return string|null
     */
    public function getCatchline()
    {
        return null;
    }

    /**
     * Get summary (DataLead in \Article)
     *
     * @return string|null
     */
    public function getSummary()
    {
        return null;
    }

    /**
     * Get created
     *
     * @return DateTime
     */
    public function getCreated()
    {
        return new \DateTime();
    }

    /**
     * Get updated
     *
     * @return DateTime
     */
    public function getUpdated()
    {
        return new \DateTime();
    }

    /**
     * Get lift embargo date
     *
     * @return DateTime|null
     */
    public function getLiftEmbargo()
    {
        return null;
    }

    /**
     * Get published
     *
     * @return DateTime|null
     */
    public function getPublished()
    {
        return null;
    }

    /**
     * Get product (NewsProduct in \Article)
     *
     * @return string|null
     */
    public function getProduct()
    {
        return null;
    }

    // TODO: Check valid instructions for NewsML
    /**
     * Get instruction, mainly for NewsML, but can be implemented in other feed
     * types aswell. Allowed values are (should all be in lowercase):
     *     update, rectify, delete or the null value
     *
     * @return null|string
     */
    public function getInstruction()
    {
        return null;
    }

    /**
     * Get status
     *
     * @return string (Defaults to: usable)
     */
    public function getStatus()
    {
        return 'usable';
    }

    /**
     * Get priority (Urgency in \Article)
     *
     * @return string|null
     */
    public function getPriority()
    {
        return null;
    }

    /**
     * Get keywords (Keywords in \Article)
     *
     * @return array Each entry in the array should be a seperate keyword
     */
    public function getKeywords()
    {
        return array();
    }

    /**
     * Get Section (Section in \Article). Through this method one can determine
     * the section for this entry by data in the feed. Return null if on needed,
     * then the secion will be determined on a higher level.
     *
     * @return null|\Newscoop\Entity\Section
     */
    public function getSection()
    {
        return null;
    }

    /**
     * Get authors
     *
     * @return array The array must have 4 keys: firstname, lastename, email, link
     */
    public function getAuthors()
    {
        return array();
    }

    /**
     * Get images
     *
     * @return array Each entry should be an array. The array must have at least
     *               one key: location (to the image). Possible other keys are:
     *               description, copyright, photographer.
     */
    public function getImages()
    {
        return array();
    }

    /**
     * Returns link to the Article
     *
     * @return string|null
     */
    public function getLink()
    {
        return null;
    }

    /**
     * Get attributes
     *
     * @param boolean $serialize When true array is serialized into a json string
     *
     * @return array|string
     */
    public function getAttributes($serialize = false)
    {
        return ($serialize) ? json_encode($this->_attributes) : $this->_attributes;
    }

    /**
     * Sets all attributes. Calls all methods that start with 'getAttribute'.
     *
     * @return self
     */
    public function setAllAttributes()
    {
        $methods            = get_class_methods($this);
        $attributeMethods   = array();

        // Get all attribute related getters
        foreach ($methods as $method) {
            if (strpos($method, 'getAttribute') !== false && $method !== 'getAttributes') {
                $attributeMethods[] = $method;
            }
        }

        // Call all attribute related getters
        foreach ($attributeMethods as $attributeMethod) {
            $name   = strtolower(str_replace('getAttribute', '', $attributeMethod));
            $value  = $this->$attributeMethod();

            $this->_setAttributeProperty($name, $value);
        }

        return $this;
    }

    /**
     * Set an attribute
     *
     * @param string $propertyName  Name of the attribute
     * @param string $propertyValue Value of the attribute
     *
     * @return self
     */
    private function _setAttributeProperty($propertyName, $propertyValue)
    {
        $this->_attributes[$propertyName] = $propertyValue;

        return $this;
    }

    /**
     * Try to get a first and last name from a name string
     *
     * @param string $p_name
     *
     * @return array Array with key 'firstname' and 'lastname'
     */
    protected function readName($name)
    {
        $name = trim($name);
        $firstName = NULL;
        $lastName = NULL;
        preg_match('/([^,]+),([^,]+)/', $name, $matches);
        if (count($matches) > 0) {
            $lastName = trim($matches[1]);
            $firstName = isset($matches[2]) ? trim($matches[2]) : '';
        } else {
            preg_match_all('/[^\s]+/', $name, $matches);
            if (isset($matches[0])) {
                $matches = $matches[0];
            }
            if (count($matches) > 1) {
                $lastName = array_pop($matches);
                $firstName = implode(' ', $matches);
            }
            if (count($matches) == 1) {
                $firstName = $matches[0];
            }
        }
        return array('firstname' => $firstName, 'lastname' => $lastName);
    }

    /**
     * Decodes html entities in string
     *
     * @param  string $stirng
     *
     * @return string
     */
    protected function decodeString($stirng)
    {
        return html_entity_decode($string, ENT_QUOTES, 'UTF-8');
    }
}

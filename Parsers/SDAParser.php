<?php
/**
 * @package Newscoop
 * @copyright 2011 Sourcefabric o.p.s.
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 */

namespace Newscoop\IngestPluginBundle\Parsers;

use Symfony\Component\Finder\Finder;

/**
 * NewsML parser
 */
class SDAParser extends Parser
{
    /**
     * Parser name
     *
     * @var string
     */
    public static $parserName = 'SDA';

    /**
     * Parser description
     *
     * @var string
     */
    public static $parserDescription = 'This parser can handle the SDA NewsML feed for Tageswoche.';

    /**
     * Parser domain, can use basic regexp for matching
     *
     * @var string
     */
    public static $parserDomain = '*';

    /** @var SimpleXMLElement */
    private $xml;

    /** @var string */
    private $dir;

    // Make sure imports don't happen too fast after file creation
    const IMPORT_DELAY = 180;

    // TODO: convert to configuration
    const FEEDS_PATH = '/../../../../ftp/sda/';

    /**
     * Get all feed entries as a parser instance
     *
     * @param \Newscoop\IngestPluginBundle\Entity\Feed $feed Feed entity
     *
     * @return array Array should contain feed entries
     */
    public static function getStories(\Newscoop\IngestPluginBundle\Entity\Feed $feed)
    {
        $finder = new Finder();
        $entries = array();

        $finder->files()->in(__DIR__ . self::FEEDS_PATH)->name('*.xml')->sortByName();

        foreach ($finder as $file) {

            $filePath = $file->getRealpath();

            if ($feed->getUpdated() && $feed->getUpdated()->getTimestamp() > filectime($filePath) + self::IMPORT_DELAY) {
                // echo "File timestamp is older then feed updated timestamp.\n";
                continue;
            }

            if (time() < filectime($filePath) + self::IMPORT_DELAY) {
                // echo "Waiting to import, will be imported on next call.\n";
                continue;
            }

            $handle = fopen($filePath, 'r');
            if (flock($handle, LOCK_EX | LOCK_NB)) {

                $parsedEntry = new SDAParser($filePath);

                if ($parsedEntry->isImage()) {
                    continue;
                }

                $entries[]= $parsedEntry;

                flock($handle, LOCK_UN);
                fclose($handle);
            }
        }

        return $entries;
    }

    /**
     * @param string $content
     */
    public function __construct($content)
    {
        $this->xml = @simplexml_load_file($content);
        $this->dir = dirname($content);
    }

    /**
     * Get news item id
     *
     * @return string
     */
    public function getNewsItemId()
    {
        return $this->getString($this->xml->xpath('//Identification/NewsIdentifier/NewsItemId'));
    }

    /**
     * Get date id
     *
     * @return string
     */
    public function getDateId()
    {
        return $this->getString($this->xml->xpath('//Identification/NewsIdentifier/DateId'));
    }

    /**
     * Get title
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->replaceQuotes($this->getString($this->xml->xpath('//HeadLine')));
    }

    /**
     * Get content
     *
     * @return string
     */
    public function getContent()
    {
        $content = array();
        foreach ($this->xml->xpath('//body.content/*[not(@lede)]') as $element) {
            $content[] = $element->asXML();
        }

        $content = implode("\n", $content);
        $content = str_replace('hl2>', 'h4>', $content);
        $content = $this->replaceQuotes($content);

        return $content;
    }

    /**
     * Get catch line
     *
     * @return string
     */
    public function getCatchline()
    {
        $catchLine = $this->xml->xpath('//NewsLines/NewsLine/NewsLineType[@FormalName="CatchLine"]');

        if (!empty($catchLine)) {
            return $this->replaceQuotes($this->getString(array_shift($catchLine)->xpath('following::NewsLineText')));
        }

        return  '';
    }

    /**
     * Get summary
     *
     * @return string
     */
    public function getSummary()
    {
        return $this->replaceQuotes($this->getString($this->xml->xpath('//p[@lede="true"]')));
    }

    /**
     * Get created
     *
     * @return DateTime
     */
    public function getCreated()
    {
        return new \DateTime($this->getString($this->xml->xpath('//FirstCreated')));
    }

    /**
     * Get updated
     *
     * @return DateTime
     */
    public function getUpdated()
    {
        return new \DateTime($this->getString($this->xml->xpath('//ThisRevisionCreated')));
    }

    /**
     * Get lift embargo
     *
     * @return DateTime|null
     */
    public function getLiftEmbargo()
    {
        $datetime = array_shift($this->xml->xpath('//StatusWillChange/DateAndTime'));
        if ((string) $datetime !== '') {
            return new \DateTime((string) $datetime);
        }
    }

    /**
     * Get service
     *
     * @return string
     */
    public function getService()
    {
        $service = array_shift($this->xml->xpath('//NewsService'));

        return (string) $service['FormalName'];
    }

    /**
     * Get news product
     *
     * @return string
     */
    public function getProduct()
    {
        $product = array_shift($this->xml->xpath('//NewsProduct'));

        return (string) $product['FormalName'];
    }

    /**
     * Get instruction
     *
     * @return string
     */
    public function getInstruction()
    {
        $instruction = array_shift($this->xml->xpath('//NewsManagement/Instruction'));

        return (string) $instruction['FormalName'];
    }

    /**
     * Get language
     *
     * @return string
     */
    public function getLanguage()
    {
        return 'de-DE';
    }

    /**
     * Get status
     *
     * @return string
     */
    public function getStatus()
    {
        $status = array_shift($this->xml->xpath('//Status'));

        return mb_strtolower((string) $status['FormalName']);
    }

    /**
     * Get priority
     *
     * @return int
     */
    public function getPriority()
    {
        $priority = array_shift($this->xml->xpath('//Priority'));

        return (int) $priority['FormalName'];
    }

    /**
     * Get keywords
     *
     * @return string
     */
    public function getKeywords()
    {
        $catchWord = $this->xml->xpath('//NewsLines/NewsLine/NewsLineType[@FormalName="CatchWord"]');

        return (empty($catchWord)) ? array() : array($this->getString(array_shift($catchWord)->xpath('following::NewsLineText')));
    }

    /**
     * Get list of authors
     *
     * @return array
     */
    public function getAuthors()
    {
        // Override authors to it alwasy is sda
        return array(array(
            'firstname' => $this->getAttributeProvider() === 'Si' ? 'SI' : 'sda',
            'lastname' => '',
            'email' => '',
            'link' => '',
        ));

        // Old code to retrieve author data
        /*
        $authors = array();
        foreach ($this->xml->xpath('//AdministrativeMetadata/Property[@FormalName="author"]') as $author) {
            $authorName = $this->readName((string) $author['Value']);
            $authors[] = array(
                'firstname' => $authorName['firstname'],
                'lastname' => $authorName['lastname'],
                'email' => '',
                'link' => '',
            );
        }

        return $authors;
        */
    }

    /**
     * Get location
     *
     * @return string
     */
    public function getAttributeLocation()
    {
        return $this->getString($this->xml->xpath('//NewsLines/DateLine'));
    }

    /**
     * Get country
     *
     * @return string
     */
    public function getAttributeCountry()
    {
        $country = array_shift($this->xml->xpath('//DescriptiveMetadata/Location/Property[@FormalName="Country"]'));

        return (string) $country['Value'];
    }

    /**
     * Get provider
     *
     * @return string
     */
    public function getAttributeProvider()
    {
        $provider = array_shift($this->xml->xpath('//AdministrativeMetadata/Provider/Party'));

        return (string) $provider['FormalName'];
    }

    /**
     * Get provider id
     *
     * @return string
     */
    public function getAttributeProviderId()
    {
        return $this->getString($this->xml->xpath('//Identification/NewsIdentifier/ProviderId'));
    }

    /**
     * Get revision id
     *
     * @return int
     */
    public function getAttributeRevisionId()
    {
        return (int) $this->getString($this->xml->xpath('//Identification/NewsIdentifier/RevisionId'));
    }

    /**
     * Get subject
     *
     * @return string
     */
    public function getAttributeSubject()
    {
        $subject = array_shift($this->xml->xpath('//DescriptiveMetadata/SubjectCode/Subject'));
        $subject = (string) $subject['FormalName'];
        $subject = $this->replaceQuotes($subject);

        return $subject;
    }

    /**
     * Get news item type
     *
     * @return string
     */
    public function getAttributeType()
    {
        $typeInfo = array_shift($this->xml->xpath('//NewsItemType'));

        return (string) $typeInfo['FormalName'];
    }

    /**
     * Get source
     *
     * @return string
     */
    public function getAttributeSource()
    {
        $sources = array();
        foreach ($this->xml->xpath('//AdministrativeMetadata/Source/Party') as $party) {
            $sources[] = (string) $party['FormalName'];
        }

        return implode(', ', $sources);
    }

    /**
     * Get sub title
     *
     * @return string
     */
    public function getAttributeSubTitle()
    {
        return $this->replaceQuotes($this->getString($this->xml->xpath('//NewsLines/SubHeadLine')));
    }

    /**
     * Get path
     *
     * @return string
     */
    public function getAttributePath()
    {
        $contentItem = array_shift($this->xml->xpath('//NewsComponent/ContentItem[@Href]'));
        $href = (string) $contentItem['Href'];

        return "$this->dir/$href";
    }

    /**
     * Get section number for entry
     *
     * @return int Section Number
     */
    public function getSection()
    {
        if (strpos($this->getNewsItemId(), 'lto') !== false) {
            return 41;
        }

        switch ($this->getAttributeSubject()) {
            case '15000000':
                return 40;
                break;

            case '1000000':
                return 50;
                break;
        }

        if ($this->getAttributeProvider() == 'Si') {
            return 40;
        }

        if ($this->getAttributeCountry() != 'CH')  {
            return 30;
        }

        if ($this->getProduct() == "Regionaldienst Nord") {
            return 10;
        }

        return 20;
    }

    /**
     * Test if is image
     *
     * @return bool
     */
    public function isImage()
    {
        $mainRole = array_pop($this->xml->xpath('//NewsComponent/Role[@FormalName="Main"]'));
        return $mainRole && $mainRole->xpath('following::ContentItem/MediaType[@FormalName="Photo"]');
    }

    /**
     * Get images
     *
     * @return array
     */
    public function getImages()
    {
        $images = array();
        $xmlImages = array();
        foreach ($this->xml->xpath('//NewsManagement/AssociatedWith') as $assoc) {
            list(,,,$dateId, $newsItemId) = explode(':', (string) $assoc['NewsItem']);
            foreach (glob("{$this->dir}/{$dateId}*_{$newsItemId}.xml") as $imageNewsMl) {
                $xmlImages[] = new self($imageNewsMl);
            }
        }

        if (count($xmlImages) > 0) {

            foreach ($xmlImages AS $image) {

                $images[] = array(
                    'location' => $image->getAttributePath(),
                    'description' => $image->getTitle(),
                    'copyright' => '',
                    'photographer' => $this->getAttributeProvider() === 'Si' ? 'SI' : 'sda',
                );
            }
        }

        return $images;
    }

    /**
     * Get string value of first matched element
     *
     * @param array $matches
     * @return string
     */
    private function getString(array $matches)
    {
        return (string) array_shift($matches);
    }

    /**
     * Convert lower and higher double quotes matches to « and »
     *
     * @param  string $text
     *
     * @return string
     */
    private function replaceQuotes($text)
    {
        return preg_replace('/[\x{22}\x{201c}\x{201d}](.*?)[\x{22}\x{201d}\x{201c}]/u', '«$1»', $text);
    }
}

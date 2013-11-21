<?php
/**
 * @category  IngestPlugin
 * @package   Newscoop\IngestPluginBundle
 * @author    Mischa Gorinskat <mischa.gorinskat@sourcefabric.org>
 * @copyright 2013 Sourcefabric o.p.s.
 * @license   http://www.gnu.org/licenses/gpl-3.0.txt  GNU GENERAL PUBLIC LICENSE Version 3
 */

namespace Newscoop\IngestPluginBundle\Services;

use Doctrine\ORM\EntityManager,
    Newscoop\IngestPluginBundle\Entity\Ingest\Feed,
    Newscoop\IngestPluginBundle\Entity\Ingest\Feed\Entry,
    Newscoop\IngestPluginBundle\Parser,
    Newscoop\IngestPluginBundle\Services\PublisherService;

/**
 * Ingest service
 */
class IngestService
{
    /**
     * Entity manager
     *
     * @var Doctrine\ORM\EntityManager
     */
    private $em;

    /**
     * Publisher service
     *
     * @var Newscoop\IngestPluginBundle\Services\PublisherService
     */
    private $publisher;

    /**
     * Initialize service
     *
     * @param EntityManager    $em        Entity manager
     * @param PublisherService $publisher Publisher
     */
    public function __construct(EntityManager $em, PublisherService $publisher)
    {
        $this->em = $em;
        $this->publisher = $publisher;
    }

    /**
     * Get all enabled feeds
     *
     * @return ArrayCollection List of enabled feeds
     */
    private function getFeeds()
    {
        return $this->em->getRepository('\Newscoop\IngestPluginBundle\Entity\Feed')->findAll();
    }

    /**
     * Ingest content of all feeds
     */
    public function ingestAllFeeds()
    {
        $feeds = $this->getFeeds();

        foreach ($feeds as $feed) {
            $this->updateFeed($feed);
        }
    }

    /**
     * Update a specific feed
     *
     * @param Newscoop\IngestPluginBundle\Entity\Feed $feed
     */
    public function updateFeed(\Newscoop\IngestPluginBundle\Entity\Feed $feed)
    {
        $parser  = $feed->getParser();
        $namespace = $parser->getNamespace();
        $unparsedEntries = $namespace::getStories($feed);

        $repository = $this->em
            ->getRepository('Newscoop\IngestPluginBundle\Entity\Feed\Entry');

        foreach ($unparsedEntries as $unparsedEntry) {

            $entry = $repository->findBy(
                array(
                    'feed' => $feed,
                    'newsItemid' => $unparsedEntry->getNewsItemId()
                )
            );

            if ($entry !== null && !empty($entry)) {

                // Todo: Handle other possible statuses
                if ($unparsedEntry->getStatus() == 'Deleted') {
                    // Delete entry
                    // Delete entry via publisher

                    $publisher->delete($entry);
                    $this->em->remove($entry);

                    continue;
                }
            } else {
                $entry = new \Newscoop\IngestPluginBundle\Entity\Feed\Entry();
                $entry->setFeed($feed);
                $entry->setNewsItemId($unparsedEntry->getNewsItemId());
            }

            $languageFound  = false;
            if ($unparsedEntry->getLanguage() !== null && $unparsedEntry->getLanguage() !== '') {
                // Determine locale
                $localeArray    = \Locale::parseLocale($unparsedEntry->getLanguage());
                if (array_key_exists('language', $localeArray) && array_key_exists('region', $localeArray)) {
                    $setLanguage    = $localeArray['language'].'-'.$localeArray['region'];
                    $languageFound  = true;
                } elseif (array_key_exists('language', $localeArray)) {
                    $setLanguage    = $localeArray['language'].'-'.$localeArray['language'];
                    $languageFound  = true;
                }
            }
            if (!$languageFound) {
                //Todo: Set to default language is language is not provided
                $setLanguage = 'en-GB';
            }
            $entry->setLanguage($setLanguage);

            $entry->setTitle($unparsedEntry->getTitle());
            $entry->setContent($unparsedEntry->getContent());
            $entry->setCatchLine($unparsedEntry->getCatchLine());
            $entry->setSummary($unparsedEntry->getSummary());
            $entry->setCreated($unparsedEntry->getCreated());
            $entry->setUpdated($unparsedEntry->getUpdated());

            // Todo: Set this when published automatically
            //$entry->setPublished($unparsedEntry->getPublished());

            $entry->setProduct($unparsedEntry->getProduct());
            $entry->setStatus($unparsedEntry->getStatus());
            $entry->setPriority($unparsedEntry->getPriority());
            $entry->setKeywords($unparsedEntry->getKeywords());
            $entry->setSection($unparsedEntry->getSection());
            $entry->setAuthors($unparsedEntry->getAuthors());
            $entry->setImages($unparsedEntry->getImages());

            //Todo: what to do with this
            //$entry->setEmbargoed($unparsedEntry->getEmbargoed());

            $entry->setLink($unparsedEntry->getLink());

            // Attributes
            $entry->setAttributes($unparsedEntry->setAllAttributes()->getAttributes());

            $this->em->persist($entry);

            // TODO: check after finishing publisher
            // Publish article
            // if ($feed->isAutoMode()) {
            //     $publisher->publish($entry);
            // }
        }

        $feed->setUpdated(new \DateTime());

        $this->em->persist($feed);
        $this->em->flush();
    }

    // private function updateSDAFeed(Feed $feed)
    // {
    //     foreach (glob($this->config['path'] . '/*.xml') as $file) {
    //         if ($feed->getUpdated() && $feed->getUpdated()->getTimestamp() > filectime($file) + self::IMPORT_DELAY) {
    //             continue;
    //         }

    //         if (time() < filectime($file) + self::IMPORT_DELAY) {
    //             continue;
    //         }

    //         $handle = fopen($file, 'r');
    //         if (flock($handle, LOCK_EX | LOCK_NB)) {
    //             $parser = new NewsMlParser($file);
    //             if (!$parser->isImage()) {
    //                 $entry = $this->getPrevious($parser, $feed);
    //                 switch ($parser->getInstruction()) {
    //                     case 'Rectify':
    //                     case 'Update':
    //                         $entry->update($parser);

    //                     case '':
    //                         if ($entry->isPublished()) {
    //                             $this->updatePublished($entry);
    //                         } else if ($feed->isAutoMode()) {
    //                             $this->publish($entry);
    //                         }
    //                         break;

    //                     case 'Delete':
    //                         $this->deletePublished($entry);
    //                         $this->em->remove($entry);
    //                         break;

    //                     default:
    //                         throw new \InvalidArgumentException("Instruction '{$parser->getInstruction()}' not implemented.");
    //                         break;
    //                 }
    //             }

    //             flock($handle, LOCK_UN);
    //             fclose($handle);
    //         } else {
    //             continue;
    //         }
    //     }

    //     $feed->setUpdated(new \DateTime());
    //     $this->getEntryRepository()->liftEmbargo();
    //     $this->em->flush();
    // }

    // public function publish(Entry $entry, $workflow = 'Y')
    // {
    //     $article = $this->publisher->publish($entry, $workflow);
    //     $entry->setPublished(new \DateTime());
    //     $this->em->persist($entry);
    //     $this->em->flush();
    //     return $article;
    // }

    // private function updatePublished(Entry $entry)
    // {
    //     if ($entry->isPublished()) {
    //         $this->publisher->update($entry);
    //     }
    // }

    // private function deletePublished(Entry $entry)
    // {
    //     if ($entry->isPublished()) {
    //         $this->publisher->delete($entry);
    //     }
    // }
}

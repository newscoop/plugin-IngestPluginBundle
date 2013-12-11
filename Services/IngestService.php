<?php
/**
 * @category  IngestPlugin
 * @package   Newscoop\IngestPluginBundle
 * @author    Mischa Gorinskat <mischa.gorinskat@sourcefabric.org>
 * @copyright 2013 Sourcefabric o.p.s.
 * @license   http://www.gnu.org/licenses/gpl-3.0.txt  GNU GENERAL PUBLIC LICENSE Version 3
 */

namespace Newscoop\IngestPluginBundle\Services;

use Doctrine\ORM\EntityManager;
use Newscoop\IngestPluginBundle\Entity\Ingest\Feed;
use Newscoop\IngestPluginBundle\Entity\Ingest\Feed\Entry;
use Newscoop\IngestPluginBundle\Parser;
use Newscoop\IngestPluginBundle\Services\PublisherService;
use Newscoop\IngestPluginBundle\Services\ArticleTypeConfigurationService;

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
     * List of all languages for current feed (based on sections)
     *
     * @var array
     */
    private $allowedLanguages = array();

    /**
     * Initialize service
     *
     * @param EntityManager                   $em                     Entity manager
     * @param PublisherService                $publisher              Publisher
     * @param ArticleTypeConfigurationService $articleTypeConfService Articletype helper
     */
    public function __construct(
        EntityManager $em,
        PublisherService $publisher,
        ArticleTypeConfigurationService $articleTypeConfService
    )
    {
        $this->em = $em;
        $this->publisher = $publisher;
        $this->articleTypeConfigurator = $articleTypeConfService;
    }

    /**
     * Get all enabled feeds
     *
     * @return ArrayCollection List of enabled feeds
     */
    private function getFeeds()
    {
        return $this->em
            ->getRepository('\Newscoop\IngestPluginBundle\Entity\Feed')
            ->findByEnabled(true);
    }

    /**
     * Ingest content of all feeds
     *
     * @return int Number of handled feeds
     */
    public function ingestAllFeeds()
    {
        $feeds = $this->getFeeds();

        if (count($feeds) > 0) {

            $updated = array();
            foreach ($feeds as $feed) {
                $updated[] = $this->updateFeed($feed, false);
            }

            $this->liftEmbargo();

            return count($updated);
        } else {
            return 0;
        }
    }

    /**
     * Update a specific feed
     *
     * @param Newscoop\IngestPluginBundle\Entity\Feed $feed
     * @param boolean                                 $liftEmbargo
     */
    public function updateFeed(\Newscoop\IngestPluginBundle\Entity\Feed $feed, $liftEmbargo=true)
    {
        if (!$feed->isEnabled()) {
            throw new \Exception('The feed '.$feed->getName().' is not enabled and will not be updated.', 1);
        }

        $parser  = $feed->getParser();
        $namespace = $parser->getNamespace();
        $unparsedEntries = $namespace::getStories($feed);

        $repository = $this->em
            ->getRepository('Newscoop\IngestPluginBundle\Entity\Feed\Entry');

        // Get possible languages based on feed and selected sections(s)
        $this->allowedLanguages = array();
        $sections = $feed->getSections();
        foreach ($sections as $section) {
            $this->allowedLanguages[] = $section->getLanguage();
        }

        foreach ($unparsedEntries as $unparsedEntry) {

            if ($unparsedEntry->getNewsItemId() === '' || $unparsedEntry->getNewsItemId() === null) {
                throw Exception('Skipped parsing feed entry. Method getNewsItemId returns invalid value.', 0);
                continue;
            }

            $entry = $repository->findOneBy(
                array(
                    'feed' => $feed,
                    'newsItemId' => $unparsedEntry->getNewsItemId()
                )
            );

            if ($entry === null) {
                $entry = new \Newscoop\IngestPluginBundle\Entity\Feed\Entry();
                $entry->setFeed($feed);
                $entry->setNewsItemId($unparsedEntry->getNewsItemId());
            }

            if ($unparsedEntry->getInstruction() == 'delete') {
                if ($entry->getId() !== null) {
                    $this->em->remove($entry);
                    if ($entry->getArticleId() !== null) {
                        $this->publisher->remove($entry);
                    }
                }
            } else {

                $languageEntity = $this->getLanguage(
                    $unparsedEntry->getLanguage(),
                    $feed->getPublication()->getDefaultLanguage()
                );

                // only add entry
                $entry->setLanguage($languageEntity);

                $sectionEntity  = null; // Default can be null, but not for autopublishing
                if ($unparsedEntry->getSection() instanceof \Newscoop\Entity\Section) {
                    $sectionEntity = $unparsedEntry->getSection();
                } elseif (is_int($unparsedEntry->getSection())) {
                    // Try to find entity
                    $sectionEntity = $this->em->getRepository('Newscoop\Entity\Section')
                        ->findOneById($unparsedEntry->getSection());
                }
                if ($sectionEntity === null) {
                    $sections = $feed->getSections();
                    foreach ($sections as $section) {
                        if ($section->getLanguage() == $languageEntity) {
                            $sectionEntity = $section;
                            break;
                        }
                    }
                }

                $entry->setSection($sectionEntity);
                $entry->setCreated($unparsedEntry->getCreated());

                $entry->setTitle($unparsedEntry->getTitle());
                $entry->setContent($unparsedEntry->getContent());
                $entry->setCatchLine($unparsedEntry->getCatchLine());
                $entry->setSummary($unparsedEntry->getSummary());

                $entry->setUpdated($unparsedEntry->getUpdated());
                $entry->setEmbargoed($unparsedEntry->getLiftEmbargo());

                $entry->setProduct($unparsedEntry->getProduct());
                $entry->setStatus($unparsedEntry->getStatus());
                $entry->setPriority($unparsedEntry->getPriority());
                $entry->setKeywords($unparsedEntry->getKeywords());
                $entry->setAuthors($unparsedEntry->getAuthors());
                $entry->setImages($unparsedEntry->getImages());

                $entry->setLink($unparsedEntry->getLink());

                $entry->setAttributes($unparsedEntry->setAllAttributes()->getAttributes());

                $this->em->persist($entry);

                if ($entry->isPublished()) {
                    $this->publisher->update($entry);
                } elseif ($feed->isAutoMode()) {
                    $this->publisher->publish($entry);
                }
            }

            // Flush each time to prevent inconsistencies
            $this->em->flush();
        }

        $feed->setUpdated(new \DateTime());

        $this->em->persist($feed);
        $this->em->flush();

        if ($liftEmbargo) {
            $this->liftEmbargo();
        }
    }

    /**
     * Checks if any embargoes need to be lifted
     */
    private function liftEmbargo()
    {
        $this->em
            ->getRepository('Newscoop\IngestPluginBundle\Entity\Feed\Entry')
            ->liftEmbargo();
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
    //                         } elseif ($feed->isAutoMode()) {
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

    private function getLanguage($language, $defaultLanguage=null)
    {
        $languageEntity = null;
        if ($language !== null && $language !== '') {
            if ($language instanceof \Newscoop\Entity\Language) {
                $languageEntity = $language;
            } else {
                $languageEntity = $this
                    ->em->getRepository('\Newscoop\Entity\Language')
                    ->findByRFC3066bis($language);
            }
        }
        // Use default language if no language is set or if the found
        // languages are not allowed in our selected languages
        if (!in_array($languageEntity, $this->allowedLanguages)) {
            $languageEntity = $defaultLanguage;
        }

        return $languageEntity;
    }
}

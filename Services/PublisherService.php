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
use Newscoop\IngestPluginBundle\Entity\Ingest\Feed\Entry;
use Newscoop\IngestPluginBundle\Services\ArticleTypeConfigurationService;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;

/**
 * Ingest publisher service
 */
class PublisherService
{
    /**
     * Initialize service
     *
     * @param EntityManager                   $em                     Entity manager
     * @param ArticleTypeConfigurationService $articleTypeConfService Articletype helper
     */
    public function __construct(EntityManager $em, ArticleTypeConfigurationService $articleTypeConfService)
    {
        $this->em = $em;
        $this->atcf = $articleTypeConfService;
    }

    /**
     * Currently just a future proof wrapper class which calls legacy publishing
     * code
     *
     * @param \Newscoop\IngestPluginBundle\Entity\Feed\Entry $entry Entry
     *
     * @return \Article Legacy article
     */
    public function publish(\Newscoop\IngestPluginBundle\Entity\Feed\Entry $entry)
    {
        return $this->publishLegacy($entry);
    }

    /**
     * Publishes an entry as an article
     * NOTE: Partially finished and partially tested, got stuck at Article Entity
     * TODO: Fix this correctly and replace publishLegacy() with this
     *
     * @param \NewscoopIngestPluginBundleEntityFeedEntry $entry Entry to be published
     */
    private function publishNew(\Newscoop\IngestPluginBundle\Entity\Feed\Entry $entry)
    {
        if ($this->getArticle($entry) !== null) {
            // Update article
            $this->update($entry);
        } else {

            $publication = $entry->getFeed()->getPublication();
            $latestIssue = $this->em
                ->getRepository('\Newscoop\Entity\Issue')
                ->getLatestBy(array(
                    'publication' => $publication,
                    'language' => $entry->getLanguage(),
                    'workflowStatus' => 'Y'
                ));
            $articleType = $this->em
                ->getRepository('\Newscoop\Entity\ArticleType')
                ->findOneBy(array('name', 'Newswire'));

            // Map data
            $mappingArray = $this->atcf->getArticleTypeMapping();
            $dataArray  = array();
            foreach ($mappingArray as $fieldID => $method) {
                $dataArray[$fieldID] = $entry->$method();
            }

            // Create new Article
            $article = new \Newscoop\Entity\Article(0, $entry->getLanguage());

            // Set title and typeData
            $article
                ->author($entry->getTitle(), $dataArray);

            // Main article settings
            $article
                ->setType($articleType)
                ->setPublication($publication)
                ->setIssue($latestIssue)
                ->setSection($entry->getSection())
                ->setKeywords($entry->getKeywords())
                ->setCommentsEnabled(0);

            $this->setArticleAuthors($article, $entry);
            $this->setArticleImages($article, $entry);

            // Publish article
            $article->publish();

            // Save changes
            $this->em->persist($article);
            $entry->setArticleId($article->getNumber());
            $this->em->persist($entry);
            $this->em->flush();
        }
    }

    /**
     * Publishes an entry as an article.
     * NOTE: Mixed new and legacy code. Legacy article API is used.
     *
     * @param \Newscoop\IngestPluginBundle\Entity\Feed\Entry $entry Entry to be published
     *
     * @return \Article Legacy article
     */
    private function publishLegacy(\Newscoop\IngestPluginBundle\Entity\Feed\Entry $entry)
    {
        $article = $this->createLegacy($entry);

        $article->setWorkflowStatus('Y');
        $entry->setPublished(new \DateTime());

        $article->commit();
        $this->em->persist($entry);
        $this->em->flush();

        return $article;
    }

    /**
     * Prepares an entry as a Newscoop Article, but doesn't publish it yet
     *
     * @param \Newscoop\IngestPluginBundle\Entity\Feed\Entry $entry
     */
    public function prepare(\Newscoop\IngestPluginBundle\Entity\Feed\Entry $entry)
    {
        $article = $this->createLegacy($entry);

        return $article;
    }

    /**
     * Updates the article linked with the given entry
     *
     * @param \Newscoop\IngestPluginBundle\Entity\Feed\Entry $entry
     */
    public function update(\Newscoop\IngestPluginBundle\Entity\Feed\Entry $entry)
    {
        $this->updateLegacy($entry);
    }

    /**
     * Removes an article linked with the given entry
     *
     * @param \Newscoop\IngestPluginBundle\Entity\Feed\Entry $entry
     */
    public function remove(\Newscoop\IngestPluginBundle\Entity\Feed\Entry $entry)
    {
        $this->removeLegacy($entry);
    }

    /**
     * Creates an legacy Article based on a feed entry
     *
     * @param \NewscoopIngestPluginBundle\Entity\Feed\Entry $entry
     *
     * @return \Article Returns legacy article opbject
     */
    private function createLegacy(\Newscoop\IngestPluginBundle\Entity\Feed\Entry $entry)
    {
        $publication = $entry->getFeed()->getPublication();
        $latestIssue = $this->em
            ->getRepository('\Newscoop\Entity\Issue')
            ->getLatestBy(array(
                'publication' => $publication,
                'language' => $entry->getLanguage(),
                'workflowStatus' => 'Y'
            ));
        $articleType = $this->em
            ->getRepository('\Newscoop\Entity\ArticleType')
            ->findOneByName('Newswire');

        $article = new \Article($entry->getLanguage()->getId());
        $article->create(
            $articleType->getName(),
            $entry->getTitle(),
            $publication->getId(),
            $latestIssue->getNumber(),
            $entry->getSection()->getNumber()
        );
        $article->setWorkflowStatus('N');
        $article->setKeywords(implode(',', $entry->getKeywords()));
        $article->setCommentsEnabled(0);

        // ArticleType data
        $this->setArticleDataLegacy($article, $entry);

        // Dates
        $article->setCreationDate($entry->getCreated()->format('Y-m-d H:i:s'));
        $article->setProperty('time_updated', $entry->getUpdated()->format('Y-m-d H:i:s'));

        // Author
        $this->setArticleAuthorsLegacy($article, $entry);
        $this->setArticleImagesLegacy($article, $entry);

        $entry->setArticleId($article->getArticleNumber());

        $article->commit();
        $this->em->persist($entry);
        $this->em->flush();

        return $article;
    }

    /**
     * Update an entry that is already published as article with legacy methods
     *
     * @param \Newscoop\IngestPluginBundle\Entity\Feed\Entry $entry
     *   The entity on which the article is based
     */
    private function updateLegacy(\Newscoop\IngestPluginBundle\Entity\Feed\Entry $entry)
    {
        if (!$entry->isPublished()) {
            return;
        }

        $article = getArticleLegacy($entry->getLanguage()->getId(), $entry->getArticleId());
        if (!$article->exists()) {
            return;
        }

        $article->setTitle($entry->getTitle());
        $article->setProperty('time_updated', $entry->getUpdated()->format('Y-m-d H:i:s'));
        $article->setKeywords(implode(',', $entry->getKeywords()));

        $this->setArticleDataLegacy($article, $entry);
        $this->setArticleAuthorsLegacy($article, $entry);
        $this->setArticleImagesLegacy($article, $entry);

        // Update published if entry already was published
        if ($entry->isPublished()) {
            $entry->setPublished(new \DateTime());
        }

        $article->commit();
        $this->em->persist($entry);
        $this->em->flush();
    }

    /**
     * Remove an article through entities.
     * TODO: Not supported yet since articles are not fully working with all
     * corresponding triggers through Entities
     *
     * @param \Newscoop\IngestPluginBundle\Entity\Feed\Entry $entry
     *   The entity on which the article is based
     */
    private function removeNew(\Newscoop\IngestPluginBundle\Entity\Feed\Entry $entry)
    {
        $article = $this->getArticle($entry);
        if ($article !== null) {
            $this->em->remove($article);
            $this->em->flush();
        }
    }

    /**
     * Removes an article the legacy way
     *
     * @param \Newscoop\IngestPluginBundle\Entity\Feed\Entry $entry
     *   The entity on which the article is based
     */
    private function removeLegacy(\Newscoop\IngestPluginBundle\Entity\Feed\Entry $entry)
    {
        $article = getArticleLegacy($entry->getLanguage()->getId(), $entry->getArticleId());
        if ($article->exists()) {
            $article->delete();
        }
    }

    /**
     * Get an article entity related to the given entry
     *
     * @param \Newscoop\IngestPluginBundle\Entity\Feed\Entry $entry
     *
     * @return \Newscoop\Entity\Article
     */
    private function getArticle(\Newscoop\IngestPluginBundle\Entity\Feed\Entry $entry)
    {
        if ($entry->getArticleId() === null) {
            return null;
        }

        $article = $this->em->getRepository('\Newscoop\Entity\Article')
            ->findOneByNumber($entry->getArticleId());

        return $article;
    }

    /**
     * Get a legacy article object related to the given entry
     *
     * @param int $languageId    Id of the article language
     * @param int $articleNumber Number of the article
     *
     * @return \Article
     */
    private function getArticleLegacy($languageId, $articleNumber)
    {
        return new \Article($languageId, $articleNumber);
    }

    /**
     * Set articletype specific data
     *
     * @param \Newscoop\Entity\Article                       $article Article
     * @param \Newscoop\IngestPluginBundle\Entity\Feed\Entry $entry   Entity
     */
    private function setArticleData(
            \Newscoop\Entity\Article $article,
            \Newscoop\IngestPluginBundle\Entity\Feed\Entry $entry
        )
    {
        $newswireType = $this->em
            ->getRepository('\Newscoop\Entity\ArticleType')
            ->findBy(array('name' => 'newswire'));

        // Get fields for
        $newswireFields = $this->em
            ->getRepository('\Newscoop\Entity\ArticleTypeField')
            ->findBy(array('type_id' => $newswireType->getId()));

        // TODO: Finish this
    }

    /**
     * Set articletype data the legacy way
     *
     * @param \Article                                       $article Article
     * @param \Newscoop\IngestPluginBundle\Entity\Feed\Entry $entry   Entity
     */
    private function setArticleDataLegacy(
        \Article $article,
        \Newscoop\IngestPluginBundle\Entity\Feed\Entry $entry
    ) {
        // Get mapping data from ArticleTypeConfiguration service
        $mappingArray = $this->atcf->getArticleTypeMapping();

        $data = $article->getArticleData();
        foreach ($mappingArray as $fieldID => $method) {
            if (method_exists($entry, $method)) {
                $propertySet = $data->setProperty("F{$fieldID}", $entry->$method());
            }
        }

        $data->create();
    }

    /**
     * Set authors for article, if author doesn't exist it gets created
     *
     * @param \Newscoop\Entity\Article                       $article Article
     * @param \Newscoop\IngestPluginBundle\Entity\Feed\Entry $entry   Entity
     */
    private function setArticleAuthors(
            \Newscoop\Entity\Article $article,
            \Newscoop\IngestPluginBundle\Entity\Feed\Entry $entry
        )
    {
        $repository = $this->em->getRepository('\Newscoop\Entity\Author');
        $doctrineCollection = new \Doctrine\Common\Collections\ArrayCollection();

        $authors    = $entry->getAuthors();
        if (count($authors) > 0) {
            foreach ($authors as $author) {

                $authorEntity = $repository
                    ->findBy(array(
                        'first_name' => $author['firstname'],
                        'last_name' => $author['lastname']
                    ));

                if ($authorEntity === null) {
                    $authorEntity = new \Newscoop\Entity\Author($author['firstname'], $author['lastname']);
                }

                $doctrineCollection->add($authorEntity);
            }

            $article->setArticleAuthors($doctrineCollection);
        }
    }

    /**
     * Set authors for an article, uses legacy classes
     *
     * @param Article                                   $article
     * @param \Newscoop\IngestPluginBundle\Entity\Entry $entry
     */
    private function setArticleAuthorsLegacy(
        \Article $article,
        \Newscoop\IngestPluginBundle\Entity\Feed\Entry $entry
    ) {
        $authors    = $entry->getAuthors();
        $order      = 0;
        if (count($authors) > 0) {
            foreach ($authors as $author) {
                $name = trim($author['firstname'] .' '. $author['lastname']);
                $author = new \Author($name);
                if (!$author->exists()) {
                    $author->create();
                }
                $article->setAuthor($author, $order++);
            }
        } else {
            $name = $entry->getProduct() ?: $entry->getFeed()->getName();
            $author = new \Author($name);
            if (!$author->exists()) {
                $author->create();
            }
            $article->setAuthor($author);
        }
    }

    /**
     * Set images for the article
     *
     * @param \Article                                  $article
     * @param \Newscoop\IngestPluginBundle\Entity\Entry $entry
     */
    private function setArticleImagesLegacy(
        \Article $article,
        \Newscoop\IngestPluginBundle\Entity\Feed\Entry $entry
    ) {
        $images = $entry->getImages();
        if (!is_array($images) || empty($images)) {
            return;
        }

        $oldImages = \ArticleImage::GetImagesByArticleNumber($article->getArticleNumber());
        if (is_array($oldImages)) {
            foreach ($oldImages as $image) {
                $image->delete();
            }
        }

        $filesystem = new Filesystem();

        foreach ($images as $image) {

            if (!array_key_exists('location', $image) || !$image['location']) {
                continue;
            }

            $imagePath = '';
            if ($filesystem->exists($image['location'])) {
                $basename = basename($image['location']);
                $imagePath = $image['location'];
            } else {
                $tmpPath = tempnam(sys_get_temp_dir(), 'NWSIMG');

                try {
                    $filesystem->copy($image['location'], $tmpPath, true);
                } catch(IOExceptionInterface $e) {
                    echo '<pre>$e:'; var_dump($e); echo '<hr>'; exit;
                    continue;
                }
                $imagePath = $tmpPath;
                $basename = basename($image['location']);
            }

            $imagesize = getimagesize($imagePath);
            $info = array(
                'name' => $basename,
                'type' => $imagesize['mime'],
                'tmp_name' => $imagePath,
                'size' => filesize($imagePath),
                'error' => 0,
            );

            $attributes = array(
                'Photographer' => array_key_exists('photographer', $image) ? $image['photographer'] : '',
                'Description' => array_key_exists('description', $image) ? $image['description'] : '',
                'Source' => 'newsfeed',
                'Status' => 'approved',
            );

            try {
                $image = \Image::OnImageUpload($info, $attributes, null, null, true);
                \ArticleImage::AddImageToArticle($image->getImageId(), $article->getArticleNumber(), null);
            } catch (\Exception $e) {
                var_dump($e);
                exit;
            }
        }
    }
}

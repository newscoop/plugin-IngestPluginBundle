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
    Doctrine\Common\Collections\Collection,
    Newscoop\IngestPluginBundle\Entity\Ingest\Feed\Entry;

/**
 * Ingest publisher service
 */
class PublisherService
{
    /**
     * Initialize service
     *
     * @param EntityManager $em Entity manager
     */
    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    /**
     * Publishes an entry as an article
     *
     * @param \NewscoopIngestPluginBundleEntityFeedEntry $entry Entry to be published
     */
    public function publish(\Newscoop\IngestPluginBundle\Entity\Feed\Entry $entry)
    {
        if ($this->getArticle($entry) !== null) {
            // Update article
            $this->update($entry);
        } else {
            // Create new Article
            $article = new \Newscoop\Entity\Article($entry->getLanguage());

            $article
                ->setPublication($entry->getPublication())
                ->setIssue($this->getLatestIssue($entry->getPublication()))
                ->setSection($entry->getSection())
                // TODO: check what this is
                ->setWorkflowStatus('Y')
                ->setKeywords($entry->getKeywords())
                ->setCommentsEnabled(false)
                ->author($entry->getName());

/*
        ingest_publisher:
    article_type: "newswire"
    section_other: "20"
    field:
        NewsItemIdentifier: "getNewsItemId"
        NewsProduct: "getProduct"
        Status: "getStatus"
        Urgency: "getPriority"
        HeadLine: "getTitle"
        NewsLineText: "getCatchLine"
        DataLead: "getSummary"
        DataContent: "getContent"
        AuthorNames: "getAuthors"
    image_path: ""
*/
            // TODO: Create article type and store data there


            $this->setArticleAuthors($article, $entry);

            // TODO: set images

            // Publish article
            $article->publish();

            // Save changes
            $this->em->persist($article);
            $entry->setArticleNumber($article->getArticleNumber());
            $this->em->persis($entry);
            $this->em->flush();

            // $article->setWorkflowStatus(strpos($entry->getTitle(), self::PROGRAM_TITLE) === 0 ? 'N' : $status);
            // $article->setKeywords($entry->getCatchWord());
            // $article->setCommentsEnabled(TRUE);
            $this->setArticleData($article, $entry);
            // $this->setArticleDates($article, $entry);
            // $this->setArticleAuthors($article, $entry);
            // $this->setArticleImages($article, $entry);
            // $entry->setArticleNumber($article->getArticleNumber());
            // $article->commit();
            // return $article;
        }
    }

    public function update(\Newscoop\IngestPluginBundle\Entity\Feed\Entry $entry)
    {
        // update
    }

    public function delete(\Newscoop\IngestPluginBundle\Entity\Feed\Entry $entry)
    {
        $article = $this->getArticle($entry);
        if ($article !== null) {
            $this->em->remove($article);
            $this->em->flush();
        }
    }

    private function getArticle(\Newscoop\IngestPluginBundle\Entity\Feed\Entry $entry)
    {
        $article = $this->em->getRepository('\Newscoop\Entity\Article')
            ->find($entry->getArticleNumber());

        return $article;
    }

    private function getLatestIssue(\Newscoop\Entity\Publication $publication)
    {
        $issue  = $this->em
            ->getRepository('\Newscoop\Entity\Issie')
            ->findOneBy();

        return $issue;
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
        $doctrineCollection = new \Doctrine\Common\Collections\Collection();

        $authors    = $entry->getAuthors();
        if (count($authors) > 0) {
            foreach ($authors as $author) {

                $authorEntity = $repository
                    ->findBy(array(
                        'firstname' => $author['firstname'],
                        'lastname' => $author['lastname']
                    ));

                if ($authorEntity === null) {
                    $authorEntity = new \Newscoop\Entity\Author($author['firstname'], $author['lastname']);
                }

                $doctrineCollection->add($authorEntity);
            }

            $article->setArticleAuthors($doctrineCollection);
        }
    }
}

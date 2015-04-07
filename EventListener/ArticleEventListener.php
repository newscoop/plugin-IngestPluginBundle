<?php
/**
 * @package Newscoop\IngestPluginBundle
 * @author Mischa Gorinskat <mischa.gorinskat@sourcefabric.org>
 * @copyright 2014 Sourcefabric o.p.s.
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 */

namespace Newscoop\IngestPluginBundle\EventListener;

use Doctrine\ORM\EntityManager;
use Newscoop\EventDispatcher\Events\GenericEvent;

class ArticleEventListener
{
    /**
     * @var Doctrine\ORM\EntityManager
     */
    private $em;

    /**
     * @param Doctrine\ORM\EntityManager $em
     */
    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    /**
     * Handles publish and published event for articles. Will update entry
     * published date with article published date.
     *
     * @param  Newscoop\EventDispatcher\Events\GenericEvent $event
     */
    public function publish(GenericEvent $event)
    {
        $legacyArticle = $event->getSubject();
        $arguments = $event->getArguments();

        if ($legacyArticle instanceof \Article) {
            $entry = $this->em
                ->getRepository('Newscoop\IngestPluginBundle\Entity\Feed\Entry')
                ->findOneByArticleId($legacyArticle->getArticleNumber());
            if ($entry !== null) {
                $entry->setPublished(new \DateTime($legacyArticle->getPublishDate()));
                $this->em->persist($entry);
                $this->em->flush();
            }
        }
    }

    /**
     * Handles delete event for articles. Unlink article from entry and
     * unpublishes entry.
     *
     * @param  Newscoop\EventDispatcher\Events\GenericEvent $event
     */
    public function delete(GenericEvent $event)
    {
        $eventCaller = $event->getSubject();
        $arguments = $event->getArguments();

        if ($eventCaller instanceof \Article) {
            $entry = $this->em
                ->getRepository('Newscoop\IngestPluginBundle\Entity\Feed\Entry')
                ->findOneByArticleId($eventCaller->getArticleNumber());
            if ($entry !== null) {
                $entry->setArticleId(NULL);
                $entry->setPublished(NULL);
                $this->em->persist($entry);
                $this->em->flush();
            }
        }
    }
}

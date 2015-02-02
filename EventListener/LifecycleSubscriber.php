<?php
/**
 * @package Newscoop\IngestPluginBundle
 * @author Mischa Gorinskat <mischa.gorinskat@sourcefabric.org>
 * @copyright 2013 Sourcefabric o.p.s.
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 */

namespace Newscoop\IngestPluginBundle\EventListener;

use Doctrine\ORM\EntityManager;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Newscoop\EventDispatcher\Events\GenericEvent;
use Newscoop\Entity\ArticleType;
use Newscoop\Entity\ArticleTypeField;
use Newscoop\IngestPluginBundle\Services\ArticleTypeConfigurationService;

/**
 * Event lifecycle management
 */
class LifecycleSubscriber implements EventSubscriberInterface
{
    private $em;

    public function __construct(
        EntityManager $em,
        ArticleTypeConfigurationService $articleTypeConfigurationService
    ) {
        $this->em = $em;
        $this->articleTypeConfigurationService = $articleTypeConfigurationService;
    }

    public function install(GenericEvent $event)
    {
        $tool = new \Doctrine\ORM\Tools\SchemaTool($this->em);
        $tool->updateSchema($this->getClasses(), true);

        // Generate proxies for entities
        $this->em->getProxyFactory()->generateProxyClasses($this->getClasses(), __DIR__ . '/../../../../library/Proxy');

        // Create articletype
        $this->articleTypeConfigurationService->create();
    }

    public function update(GenericEvent $event)
    {
        $tool = new \Doctrine\ORM\Tools\SchemaTool($this->em);
        $tool->updateSchema($this->getClasses(), true);

        // Generate proxies for entities
        $this->em->getProxyFactory()->generateProxyClasses($this->getClasses(), __DIR__ . '/../../../../library/Proxy');

        // Update articletype
        $this->articleTypeConfigurationService->update();
    }

    public function remove(GenericEvent $event)
    {
        $tool = new \Doctrine\ORM\Tools\SchemaTool($this->em);
        $tool->dropSchema($this->getClasses(), true);

        // Remove articletype
        $this->articleTypeConfigurationService->remove();
    }

    public static function getSubscribedEvents()
    {
        return array(
            'plugin.install.m038_ingest_plugin_bundle' => array('install', 1),
            'plugin.update.m038_ingest_plugin_bundle' => array('update', 1),
            'plugin.remove.m038_ingest_plugin_bundle' => array('remove', 1),
        );
    }

    private function getClasses()
    {
        return array(
            $this->em->getClassMetadata('Newscoop\IngestPluginBundle\Entity\Feed'),
            $this->em->getClassMetadata('Newscoop\IngestPluginBundle\Entity\Feed\Entry'),
            $this->em->getClassMetadata('Newscoop\IngestPluginBundle\Entity\Parser'),
        );
    }
}

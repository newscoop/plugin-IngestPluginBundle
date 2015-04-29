<?php
/**
 * @package Newscoop\IngestPluginBundle
 * @author Mischa Gorinskat <mischa.gorinskat@sourcefabric.org>
 * @copyright 2013 Sourcefabric o.p.s.
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 */

namespace Newscoop\IngestPluginBundle\EventListener;

use Doctrine\ORM\EntityManager;
use Symfony\Component\Translation\Translator;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Newscoop\EventDispatcher\Events\GenericEvent;
use Newscoop\Entity\ArticleType;
use Newscoop\Entity\ArticleTypeField;
use Newscoop\IngestPluginBundle\Event\IngestParsersEvent;
use Newscoop\IngestPluginBundle\Services\ArticleTypeConfigurationService;
use Newscoop\IngestPluginBundle\Controller\SettingsController;
use Newscoop\Services\Plugins\PluginsService;

/**
 * Event lifecycle management
 */
class LifecycleSubscriber implements EventSubscriberInterface
{
    /**
     * @var Doctrine\ORM\EntityManager
     */
    private $em;

    /**
     * @var Newscoop\IngestPluginBundle\Services\ArticleTypeConfigurationService
     */
    private $articleTypeConfigurationService;

    private $dispatcher;

    /**
     * @var Newscoop\Services\SchedulerService
     */
    private $scheduler;

    /**
     * @var Array
     */
    private $cronjobs;

    /**
     * @var \Newscoop\Services\Plugins\PluginsService
     */
    private $pluginsService;

    /**
     * @var \Symfony\Component\Translation\Translator
     */
    private $translator;

    public function __construct(
        EntityManager $em,
        ArticleTypeConfigurationService $articleTypeConfigurationService,
        $dispatcher,
        $scheduler,
        PluginsService $pluginsService,
        Translator $translator
    ) {
        $this->em = $em;
        $this->articleTypeConfigurationService = $articleTypeConfigurationService;
        $this->dispatcher = $dispatcher;
        $this->scheduler = $scheduler;
        $this->pluginsService = $pluginsService;
        $this->translator = $translator;

        $appDirectory = realpath(__DIR__.'/../../../../application/console');
        $cronName = SettingsController::INGEST_CRON_NAME;
        $this->cronjobs = array(
            $cronName => array(
                'command' => $appDirectory . ' ingest:update all',
                'schedule' => '*/15 * * * *',
            )
        );
    }

    public function install(GenericEvent $event)
    {
        $tool = new \Doctrine\ORM\Tools\SchemaTool($this->em);
        $tool->updateSchema($this->getClasses(), true);

        // Generate proxies for entities
        $this->em->getProxyFactory()->generateProxyClasses($this->getClasses(), __DIR__ . '/../../../../library/Proxy');

        // Create articletype
        $this->articleTypeConfigurationService->create();

        // Register parsers
        $this->dispatcher->dispatch('newscoop_ingest.parser.register', new IngestParsersEvent($this, array()));

        // Avoid duplicates
        $this->removeJobs();
        $this->addJobs();

        // Set persissions
        $this->setPermissions();
    }

    public function update(GenericEvent $event)
    {
        $tool = new \Doctrine\ORM\Tools\SchemaTool($this->em);
        $tool->updateSchema($this->getClasses(), true);

        // Generate proxies for entities
        $this->em->getProxyFactory()->generateProxyClasses($this->getClasses(), __DIR__ . '/../../../../library/Proxy');

        // Update articletype
        $this->articleTypeConfigurationService->update();

        // Register parsers
        $this->dispatcher->dispatch('newscoop_ingest.parser.register', new IngestParsersEvent($this, array()));

        // Only add if the job doesn't exist
        $this->updateJobs();

        // Set persissions
        $this->setPermissions();
    }

    public function remove(GenericEvent $event)
    {
        $tool = new \Doctrine\ORM\Tools\SchemaTool($this->em);
        $tool->dropSchema($this->getClasses(), true);

        // Remove articletype
        $this->articleTypeConfigurationService->remove();

        // Remove jobs
        $this->removeJobs();

        // Remove persissions
        $this->removePermissions();
    }

    public static function getSubscribedEvents()
    {
        return array(
            'plugin.install.newscoop_ingest_plugin_bundle' => array('install', 1),
            'plugin.update.newscoop_ingest_plugin_bundle' => array('update', 1),
            'plugin.remove.newscoop_ingest_plugin_bundle' => array('remove', 1),
        );
    }

    /**
     * Add plugin cron jobs
     */
    private function addJobs()
    {
        foreach ($this->cronjobs as $jobName => $jobConfig) {
            $this->addJob($jobName, $jobConfig);
        }
    }

    private function addJob($jobName, $jobConfig)
    {
        $this->scheduler->registerJob($jobName, $jobConfig);
    }

    private function updateJobs()
    {
        foreach ($this->cronjobs as $jobName => $jobConfig) {
            $jobs = $this->em->getRepository('Newscoop\Entity\CronJob')->findBy(array('name' => $jobName));
            if (empty($jobs)) {
                $this->addJob($jobName, $jobConfig);
            }
        }
    }

    /**
     * Remove plugin cron jobs
     */
    private function removeJobs()
    {
        foreach ($this->cronjobs as $jobName => $jobConfig) {
            // remove schedule in case it changed after being installed
            unset($jobConfig['schedule']);
            $this->scheduler->removeJob($jobName, $jobConfig);
        }
    }

    private function getClasses()
    {
        return array(
            $this->em->getClassMetadata('Newscoop\IngestPluginBundle\Entity\Feed'),
            $this->em->getClassMetadata('Newscoop\IngestPluginBundle\Entity\Feed\Entry'),
            $this->em->getClassMetadata('Newscoop\IngestPluginBundle\Entity\Parser'),
        );
    }

    /**
     * Save plugin permissions into database
     */
    private function setPermissions()
    {
        $this->pluginsService->savePluginPermissions($this->pluginsService->collectPermissions($this->translator->trans('plugin.ingest.permissions.label')));
    }

    /**
     * Remove plugin permissions
     */
    private function removePermissions()
    {
        $this->pluginsService->removePluginPermissions($this->pluginsService->collectPermissions($this->translator->trans('plugin.ingest.permissions.label')));
    }
}

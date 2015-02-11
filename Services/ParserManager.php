<?php
/**
 * @package Newscoop\IngestPluginBundle
 * @author Mischa Gorinskat <mischa.gorinskat@sourcefabric.org>
 * @copyright 2015 Sourcefabric o.p.s.
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 */

namespace Newscoop\IngestPluginBundle\Services;

use Doctrine\ORM\EntityManager;
// use Newscoop\PaywallBundle\Services\PaywallService;
use Newscoop\IngestPluginBundle\Events\IngestParsersEvent;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * ParsersManager class manages paywall adapters
 */
class ParsersManager
{
    /** @var EntityManager */
    private $em;

    /** @var PaywallService */
    private $subscriptionService;

    /** @var EventDispatcher */
    private $dispatcher;

    /**
     * Apply entity manager and injected services
     *
     * @param EntityManager       $em
     * @param PaywallService $subscriptionService
     */
    public function __construct(EntityManager $em, EventDispatcher $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    /**
     * Trigger an up
     *
     * @return [type] [description]
     */
    public function updateAdapters()
    {
        $adaptersEvent = $this->dispatcher->dispatch('newscoop_paywall.adapters.register', new AdaptersEvent($this, array()));
    }
}

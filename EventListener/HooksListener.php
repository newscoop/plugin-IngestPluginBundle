<?php

namespace Newscoop\IngestPluginBundle\EventListener;

use Symfony\Component\HttpFoundation\Request;
use Newscoop\EventDispatcher\Events\PluginHooksEvent;

class HooksListener
{
    private $container;

    public function __construct($container)
    {
        $this->container = $container;
    }

    public function sidebar(PluginHooksEvent $event)
    {
        $response = $this->container->get('templating')->renderResponse(
            'NewscoopIngestPluginBundle:Hooks:sidebar.html.twig',
            array(
                'pluginName' => 'IngestPluginBundle',
                'info' => 'This is response from ingest plugin hook!'
            )
        );

        $event->addHookResponse($response);
    }

    // Services?
}
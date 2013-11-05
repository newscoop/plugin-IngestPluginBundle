<?php

namespace Newscoop\IngestPluginBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;

class DefaultController extends Controller
{
    public function indexAction(Request $request)
    {
    }

    /**
     * @Route("/admin/ingest")
     * @Template()
     */
    public function adminAction(Request $request)
    {
        $em = $this->container->get('em');
        $feeds = $em->getRepository('Newscoop\IngestPluginBundle\Entity\Feed')
            ->createQueryBuilder('p')
            ->getQuery()
            ->getResult();

        return array(
            'feeds' => $feeds
        );
    }
}

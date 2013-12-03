<?php

namespace Newscoop\IngestPluginBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Finder\Finder;

use Newscoop\IngestPluginBundle\Form\Type\FeedType;
use Newscoop\IngestPluginBundle\Entity\Feed;
use Newscoop\IngestPluginBundle\Entity\Feed\Entry;
use Newscoop\IngestPluginBundle\Entity\Parser;
use Newscoop\EventDispatcher\Events\GenericEvent;

/**
 * @Route("/admin/ingest/feed")
 */
class FeedController extends Controller
{
    /**
     * @Route("/list")
     * @Template()
     */
    public function listAction(Request $request)
    {
        $em = $this->container->get('em');

        $feeds = $em->getRepository('Newscoop\IngestPluginBundle\Entity\Feed')
            ->createQueryBuilder('f')
            ->getQuery()
            ->getResult();

        return array(
            'feeds' => $feeds
        );
    }

    /**
     * @Route("/add")
     * @Template()
     */
    public function addAction(Request $request)
    {
        $feed = new Feed();

        $form = $this->createForm(new FeedType(), $feed);

        // Handle updates in form
        if ($request->isXmlHttpRequest()) {
            $form->handleRequest($request);

            return new JsonResponse(array(
                'html' => htmlentities($this-> renderView('NewscoopIngestPluginBundle:Feed:ajaxForm.html.twig', array(
                    'form'   => $form->createView(),
                ))),
            ));
        }

        if ($request->getMethod() == 'POST') {
            $form->handleRequest($request);

            if ($form->isValid()) {
                $em = $this->getDoctrine()->getManager();
                $em->persist($feed);
                $em->flush();

                $this->get('session')->getFlashBag()->add(
                    'notice',
                    'Feed added!'
                );

                return $this->redirect($this->generateUrl('newscoop_ingestplugin_feed_list'));
            }
        }

        return array(
            'form' => $form
        );
    }

    /**
     * @Route("/edit/{id}")
     * @ParamConverter("get")
     * @Template()
     */
    public function editAction(Request $request, Feed $feed)
    {
        $em = $this->container->get('em');

        $form = $this->createForm(new FeedType(), $feed, array('type' => 'edit'));

        // Handles updates in form
        if ($request->isXmlHttpRequest()) {
            $form->handleRequest($request);

            return new JsonResponse(array(
                'html' => htmlentities($this-> renderView('NewscoopIngestPluginBundle:Feed:ajaxForm.html.twig', array(
                    'form'   => $form->createView(),
                ))),
            ));
        }

        if ($request->getMethod() == 'POST') {
            $form->handleRequest($request);

            if ($form->isValid()) {
                $em->persist($feed);
                $em->flush();

                $this->get('session')->getFlashBag()->add(
                    'notice',
                    'Feed updated!'
                );

                return $this->redirect($this->generateUrl('newscoop_ingestplugin_feed_list'));
            }
        }

        return array(
            'form' => $form
        );
    }

    /**
     * @Route("/delete/{id}")
     * @ParamConverter("get")
     */
    public function deleteAction(Request $request, Feed $feed)
    {
        $em                     = $this->container->get('em');
        $publisherService       = $this->container->get('newscoop_ingest_plugin.publisher');
        $deleteRelatedEntries   = (bool) $request->query->get('delete_entries');

        if ($deleteRelatedEntries) {

            $entries = $em
                ->getRepository('Newscoop\IngestPluginBundle\Entity\Feed\Entry')
                ->findByFeed($feed);

            foreach ($entries as $entry) {
                if ($entry->getArticleId() !== null) {
                    $publisherService->remove($entry);
                }
                $em->remove($entry);
            }
        }

        $em     = $this->getDoctrine()->getManager();
        $em->remove($feed);
        $em->flush();

        $this->get('session')->getFlashBag()->add(
            'notice',
            'Feed deleted!'
        );

        return $this->redirect($this->generateUrl('newscoop_ingestplugin_feed_list'));
    }

    /**
     * @Route("/update/all")
     */
    public function updateAllAction(Request $request)
    {
        $ingestService = $this->container->get('newscoop_ingest_plugin.ingester');
        $updatedFeedCount = $ingestService->ingestAllFeeds();

        $this->get('session')->getFlashBag()->add(
            'notice',
            'Number of feeds updated: '.$updatedFeedCount
        );

        return $this->redirect($this->generateUrl('newscoop_ingestplugin_feed_list'));
    }

    /**
     * @Route("/update/{id}")
     * @ParamConverter("get")
     */
    public function updateAction(Request $request, Feed $feed)
    {
        $ingestService = $this->container->get('newscoop_ingest_plugin.ingester');
        $ingestService->updateFeed($feed);

        $this->get('session')->getFlashBag()->add(
            'notice',
            $feed->getName() .' has been updated.'
        );

        return $this->redirect($this->generateUrl('newscoop_ingestplugin_feed_list'));
    }
}

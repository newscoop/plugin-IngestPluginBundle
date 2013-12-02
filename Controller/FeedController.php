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
        $em = $this->getDoctrine()->getManager();

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
                $em = $this->getDoctrine()->getManager();
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
        $em     = $this->getDoctrine()->getManager();
        $em->remove($feed);
        $em->flush();

        $this->get('session')->getFlashBag()->add(
            'notice',
            'Feed deleted!'
        );

        return $this->redirect($this->generateUrl('newscoop_ingestplugin_feed_list'));
    }
}

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
 * @Route("/admin/ingest/entry")
 */
class EntryController extends Controller
{
    /**
     * @Route("/list")
     * @Template()
     */
    public function listAction(Request $request)
    {
        $em = $this->container->get('em');

        // Todo: remove after debugging is done
        // Debug to install entities
        $tool = new \Doctrine\ORM\Tools\SchemaTool($em);
        $tool->updateSchema(array(
            $em->getClassMetadata('Newscoop\IngestPluginBundle\Entity\Feed'),
            $em->getClassMetadata('Newscoop\IngestPluginBundle\Entity\Feed\Entry'),
            $em->getClassMetadata('Newscoop\IngestPluginBundle\Entity\Parser'),
        ), true);

        // $dispatcher = $this->get('event_dispatcher');
        // $dispatcher->dispatch('plugin.install.newscoop_ingest_plugin', new GenericEvent());
        // $dispatcher->dispatch('plugin.remove.newscoop_ingest_plugin', new GenericEvent());

        // End of debug code
        $queryBuilder = $em
            ->getRepository('Newscoop\IngestPluginBundle\Entity\Feed\Entry')
            ->createQueryBuilder('e');

        $defaultData = array('feed' => '', 'published' => '');
        $filterForm = $this->createFormBuilder($defaultData)
            ->setMethod('GET')
            ->add('feed', 'entity', array(
                'class' => 'Newscoop\IngestPluginBundle\Entity\Feed',
                'property' => 'name',
                'empty_value' => 'plugin.ingest.entries.filter.all_feeds',
                'required' => false,
            ))
            ->add('published', 'choice', array(
                'choices'   => array(
                    'Y' => 'plugin.ingest.entries.filter.yes',
                    'N' => 'plugin.ingest.entries.filter.no',
                ),
                'empty_value' => 'plugin.ingest.entries.filter.all',
                'label' => 'plugin.ingest.entries.filter.published',
                'required' => false,
            ))
            ->add('view', 'choice', array(
                'choices'   => array(
                    'slim' => 'plugin.ingest.entries.filter.slim',
                    'expanded' => 'plugin.ingest.entries.filter.expanded',
                ),
                'label' => 'plugin.ingest.entries.filter.view',
            ))
            ->add('filter', 'submit', array(
                'label' => 'plugin.ingest.entries.filter.filter'
            ))
            ->getForm();

        $filterForm->handleRequest($request);

        if ($filterForm->isValid()) {
            $formData = $filterForm->getData();
            $query = $queryBuilder
                ->where('1=1');
            if (!empty($formData['feed'])) {
                $query = $query
                    ->andWhere($queryBuilder->expr()->in('e.feed', '?1'))
                    ->setParameter(1, $formData['feed']);
            }
            if (!empty($formData['published'])) {
                if ($formData['published'] == 'Y') {
                    $expression = $queryBuilder->expr()->isNotNull('e.published');
                } else {
                    $expression = $queryBuilder->expr()->isNull('e.published');
                }
                $query = $query
                    ->andWhere($expression);
            }
        }
        $queryBuilder->orderBy('e.created', 'desc');

        $paginator  = $this->get('knp_paginator');
        $pagination = $paginator->paginate(
            $queryBuilder->getQuery(),
            $request->query->get('knp_page', 1),
            10
        );
        $pagination->setTemplate('NewscoopIngestPluginBundle:Pagination:pagination_bootstrap3.html.twig');

        return array(
            'filterForm' => $filterForm->createView(),
            'pagination' => $pagination,
            'view'       => $formData['view']
        );
    }

    /**
     * @Route("/publish/{id}")
     * @ParamConverter("get")
     */
    public function publishAction(Request $request, Entry $entry)
    {
        $publisherService = $this->container->get('newscoop_ingest_plugin.publisher');
        $publisherService->publish($entry);

        $this->get('session')->getFlashBag()->add(
            'notice',
            'Entry succesfully published!'
        );

        return $this->redirect($this->generateUrl('newscoop_ingestplugin_entry_list'));
    }

    /**
     * @Route("/prepare/{id}")
     * @ParamConverter("get")
     */
    public function prepareAction(Request $request, Entry $entry)
    {
        $publisherService = $this->container->get('newscoop_ingest_plugin.publisher');
        $legacyArticle = $publisherService->prepare($entry);

        return $this->redirect($this->generateUrl('newscoop_ingestplugin_entry_redirecttoarticle'));
    }

    /**
     * @Route("/delete/{id}")
     * @ParamConverter("get")
     * @Template()
     */
    public function deleteAction(Request $request, Entry $entry)
    {
        if ($entry->getArticleId() !== null) {
            $publisherService = $this->container->get('newscoop_ingest_plugin.publisher');
            $publisherService->remove($entry);
        }

        $em = $this->container->get('em');

        $em->remove($entry);
        $em->flush();

        $this->get('session')->getFlashBag()->add(
            'notice',
            'Entry removed succesfully!'
        );

        return $this->redirect($this->generateUrl('newscoop_ingestplugin_entry_list'));
    }

    /**
     * @Route("/redirect/{languageId}/{articleNumber}")
     */
    public function redirectToArticleAction($languageId, $articleNumber, Request $request)
    {
        $legacyArticleLink = '';

        // find article
        $article = new \Article($languageId, $articleNumber);
        if (!$article->exists()) {
            throw new \Exception("Article could not be found. (Language: $languageId, ArticleNumber: $articleNumber)", 1);
        }

        $legacyArticleLink = '/admin/articles/edit.php?f_publication_id=' . $article->getPublicationId()
            . '&f_issue_number=' . $article->getIssueNumber() . '&f_section_number=' . $article->getSectionNumber()
            . '&f_article_number=' . $article->getArticleNumber() . '&f_language_id=' . $article->getLanguageId()
            . '&f_language_selected=' . $article->getLanguageId();

        return $this->redirect($legacyArticleLink);
    }
}

<?php

namespace Newscoop\IngestPluginBundle\Form\DataTransformer;

use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

class TopicTransformer implements DataTransformerInterface
{
    private $om;

    private $repo;

    public function __construct(ObjectManager $om)
    {
        $this->om = $om;
        $this->repo = $this->om->getRepository('Newscoop\Entity\Topic');
    }

    public function transform($topics)
    {
        if (null === $topics) {
            return "";
        }

        $ids = array();
        foreach ($topics as $topic) {
            $ids[] = sprintf('%s:%s', $topic->getTopicId(), $topic->getName());
        }

        return implode(',', $ids);
    }

    public function reverseTransform($string)
    {
        if (!$string) {
            return new \Doctrine\Common\Collections\ArrayCollection();
        }

        $topics = explode(',', $string);

        $res = new \Doctrine\Common\Collections\ArrayCollection();
        $repo = $this->repo;

        foreach ($topics as $topic) {

            $id = preg_replace('/^([0-9]+)\:.*/', '$1', $topic);
            $topic = $repo->findOneById((int) $id);

            if ($topic instanceof \Newscoop\Entity\Topic) {
                $res->add($topic);
            }
        }

        return $res;
    }
}

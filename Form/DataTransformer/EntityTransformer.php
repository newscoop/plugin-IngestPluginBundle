<?php

namespace Newscoop\IngestPluginBundle\Form\DataTransformer;

use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

class EntityTransformer implements DataTransformerInterface
{
    private $om;

    private $repository;

    private $options;

    public function __construct(ObjectManager $om, $repository, $fieldOptions)
    {
        $this->om = $om;
        $this->repository = $repository;
        $this->options = $fieldOptions;
    }

    public function transform($items)
    {
        if (null === $items) {
            return "";
        }

        if ($this->options['multiple']) {

            $ids = array();
            foreach ($items as $item) {
                $ids[] = sprintf('%s:%s', $this->getItemId($item), $this->getItemLabel($item));
            }
            $value = implode(',', $ids);
        } else {

            $value = sprintf('%s:%s', $this->getItemId($items), $this->getItemLabel($items));
        }

        return $value;
    }

    public function reverseTransform($string)
    {
        if ($this->options['multiple']) {

            if (!$string) {
                $result = new \Doctrine\Common\Collections\ArrayCollection();
            } else {

                $items = explode(',', $string);

                $result = new \Doctrine\Common\Collections\ArrayCollection();
                $repo = $this->repository;

                foreach ($items as $item) {

                    $id = preg_replace('/^([0-9]+)\:.*/', '$1', $item);
                    $itemEntity = $repo->findOneById((int) $id);

                    if (is_object($itemEntity)) {
                        $result->add($itemEntity);
                    }
                }
            }
        } else {

            if (!$string) {
                $result = null;
            } else {

                $id = preg_replace('/^([0-9]+)\:.*/', '$1', $string);
                $itemEntity = $this->repository->findOneById((int) $id);

                if (is_object($itemEntity)) {
                    $result = $itemEntity;
                }
            }
        }

        return $result;
    }

    private function getItemId($item)
    {
        // TODO: Fix for 4.4 topic
        if ($item instanceof \Newscoop\Entity\Topic) {
            return $item->getTopicId();
        } else {
            return $item->getId();
        }
    }

    private function getItemLabel($item)
    {
        // TODO: Fix for 4.4 topic
        if ($item instanceof \Newscoop\Entity\Topic) {
            return sprintf('%s', $item->getName());
        } else {
            return sprintf('%d %s (%s)', $item->getNumber(), $item->getName(), $item->getLanguage()->getCode());
        }
    }
}

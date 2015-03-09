<?php

namespace  Newscoop\IngestPluginBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Newscoop\IngestPluginBundle\Form\DataTransformer\EntityTransformer;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class EntitySelectorType extends AbstractType
{
    /**
     * @var ObjectManager
     */
    protected $om;

    protected $repository;

    protected $name;

    public function __construct($name, ObjectManager $om, $repository)
    {
        $this->name = $name;
        $this->om = $om;
        $this->repository = $repository;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $transformer = new EntityTransformer($this->om, $this->repository, $options);
        $builder->addModelTransformer($transformer);
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'invalid_message' => 'The selected item does not exist',
            'multiple' => true,
        ));
    }

    public function getParent()
    {
        return 'hidden';
    }

    public function getName()
    {
        return $this->name;
    }
}

<?php

namespace Maci\OrderBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class SpeditionType extends AbstractType
{
	public function setDefaultOptions(OptionsResolverInterface $resolver)
	{
		$resolver->setDefaults(array(
			'data_class' => 'Maci\OrderBundle\Entity\Order',
			'cascade_validation' => true
		));
	}

	public function buildForm(FormBuilderInterface $builder, array $options)
	{
		$builder
			->add('spedition', 'choice', array(
                'choices' => $builder->getData()->getSpeditionArray(),
                'expanded' => true
            ))
			->add('cancel', 'reset')
			->add('send', 'submit')
		;
	}

	public function getName()
	{
		return 'order_spedition';
	}
}

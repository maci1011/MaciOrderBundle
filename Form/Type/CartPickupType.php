<?php

namespace Maci\OrderBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class CartPickupType extends AbstractType
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
			->add('spedition', 'hidden', array(
				'data' => 'pickup'
            ))
			->add('payment', 'hidden', array(
				'data' => 'cash'
            ))
			->add('checkout', 'hidden', array(
				'data' => 'pickup'
            ))
			->add('cart_pickup', 'submit')
		;
	}

	public function getName()
	{
		return 'cart_pickup';
	}
}

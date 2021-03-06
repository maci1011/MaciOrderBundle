<?php

namespace Maci\OrderBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

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
			->add('spedition', HiddenType::class, array(
				'data' => 'pickup'
            ))
			->add('payment', HiddenType::class, array(
				'data' => 'cash'
            ))
			->add('checkout', HiddenType::class, array(
				'data' => 'pickup'
            ))
			->add('cart_pickup', SubmitType::class)
		;
	}

	public function getName()
	{
		return 'cart_pickup';
	}
}

<?php

namespace Maci\OrderBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

class CartBookingType extends AbstractType
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
				'data' => 'none'
            ))
			->add('payment', HiddenType::class, array(
				'data' => 'none'
            ))
			->add('checkout', HiddenType::class, array(
				'data' => 'booking'
            ))
			->add('cart_booking', SubmitType::class)
		;
	}

	public function getName()
	{
		return 'cart_booking';
	}
}

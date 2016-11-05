<?php

namespace Maci\OrderBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

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
			->add('spedition', 'hidden', array(
				'data' => 'none'
            ))
			->add('payment', 'hidden', array(
				'data' => 'none'
            ))
			->add('checkout', 'hidden', array(
				'data' => 'booking'
            ))
			->add('cart_booking', 'submit')
		;
	}

	public function getName()
	{
		return 'cart_booking';
	}
}

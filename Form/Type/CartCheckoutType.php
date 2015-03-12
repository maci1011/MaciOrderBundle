<?php

namespace Maci\OrderBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class CartCheckoutType extends AbstractType
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
                'choices' => array(
                	'standard' => 'Standard'
                ),
                'expanded' => true,
				'data' => 'standard'
            ))
			->add('payment', 'choice', array(
                'choices' => array(
                	'paypal' => 'PayPal',
                	'delivery' => 'Cash On Delivery'
                ),
                'expanded' => true,
				'data' => 'paypal'
            ))
			->add('checkout', 'hidden', array(
				'data' => 'checkout'
            ))
			->add('proceed', 'submit')
		;
	}

	public function getName()
	{
		return 'cart_checkout';
	}
}

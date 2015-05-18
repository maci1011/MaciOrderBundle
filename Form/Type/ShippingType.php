<?php

namespace Maci\OrderBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class ShippingType extends AbstractType
{
	protected $orders;

	public function __construct($orders)
	{
		$this->orders = $orders;
	}

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
			->add('shipping', 'choice', array(
                'choices' => $this->getChoices($this->orders->getShippingsArray()),
                'expanded' => true
            ))
			->add('cancel', 'reset')
			->add('send', 'submit')
		;
	}

	public function getChoices($array)
	{
		$result = array();
		foreach ($array as $key => $value) {
			$result[$key] = ( $value['label'] . ( $value['cost'] ? ( ' ( ' . number_format($value['cost'], 2, '.', ',') . ' EUR )' ) : null ) );
		}
		return $result;
	}

	public function getName()
	{
		return 'order_shipping';
	}
}

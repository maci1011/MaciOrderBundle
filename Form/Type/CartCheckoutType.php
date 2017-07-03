<?php

namespace Maci\OrderBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

class CartCheckoutType extends AbstractType
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
		if ($builder->getData()->checkShipment()) {
			$builder
				->add('shipping', ChoiceType::class, array(
	                'choices' => $this->getChoices($this->orders->getShippingsArray()),
	                'preferred_choices' => (is_string($str = $this->orders->getCartShippingCountry()) ? array($str) : array())
	            ))
	        ;
		}
		$builder
			->add('payment', ChoiceType::class, array(
                'choices' => $this->getChoices($this->orders->getPaymentsArray()),
                'expanded' => true
            ))
			->add('checkout', HiddenType::class, array(
				'data' => 'checkout'
            ))
			->add('proceed', SubmitType::class)
		;
	}

	public function getChoices($array)
	{
		$result = array();
		foreach ($array as $key => $value) {
			if (array_key_exists('label', $value)) {
				$label = $value['label'];
			} else {
				$label = ucfirst($key);
			}
			if (array_key_exists('country', $value)) {
				$label = $this->orders->getCountryName($value['country']) . ' - ' . $label;
			}
			if ($value['cost']) {
				$label .= ( ' ( ' . number_format($value['cost'], 2, '.', ',') . ' EUR )' );
			}
			$result[$label] = $key;
		}
		return $result;
	}

	public function getName()
	{
		return 'cart_checkout';
	}
}

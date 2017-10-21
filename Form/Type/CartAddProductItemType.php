<?php

namespace Maci\OrderBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Maci\TranslatorBundle\Controller\TranslatorController;

use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;


class CartAddProductItemType extends AbstractType
{
	protected $translator;

	public function __construct(TranslatorController $translator)
	{
		$this->translator = $translator;
	}

	public function setDefaultOptions(OptionsResolverInterface $resolver)
	{
		$resolver->setDefaults(array(
			'data_class' => 'Maci\OrderBundle\Entity\Item',
			'cascade_validation' => true
		));
	}

	public function buildForm(FormBuilderInterface $builder, array $options)
	{
		$builder
			->add('quantity', IntegerType::class, array(
				'label' => ($this->translator->getLabel('cart_add_product_item.label', 'Select Quantity')),
                'attr' => array('class' => 'edit-quantity-field')
            ))
			->add('add_to_cart', SubmitType::class)
		;
	}

	public function getName()
	{
		return 'cart_add_product_item';
	}
}

<?php

namespace Maci\OrderBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class CartAddProductItemType extends AbstractType
{
	public function setDefaultOptions(OptionsResolverInterface $resolver)
	{
		$resolver->setDefaults(array(
			'data_class' => 'Maci\OrderBundle\Entity\Item',
			'cascade_validation' => true
		));
	}

	public function buildForm(FormBuilderInterface $builder, array $options)
	{
		$product = $builder->getData()->getProduct();
		if (is_object($product)) {
			if (count($product->getVariants())) {
				$builder->add('variants', 'entity', array(
					'class' => 'MaciProductBundle:Variant',
					'choices' => $product->getVariantsChildren()
				));
			}
		}
		$builder
			->add('quantity', 'integer', array(
                'attr' => array('class' => 'edit-quantity-field')
            ))
			->add('add_to_cart', 'submit')
		;
	}

	public function getName()
	{
		return 'cart_add_product_item';
	}
}

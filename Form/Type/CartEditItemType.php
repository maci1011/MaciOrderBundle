<?php

namespace Maci\OrderBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class CartEditItemType extends AbstractType
{
	public function buildForm(FormBuilderInterface $builder, array $options)
	{
		$builder
            ->add('quantity', 'integer', array(
                'label_attr' => array('class' => 'sr-only'),
                'attr' => array('class' => 'edit-quantity-field')
            ))
            ->add('edit', 'submit', array(
                'attr' => array('class' => 'btn-success')
            ))
		;
	}

	public function getName()
	{
		return 'cart_edit_item';
	}
}

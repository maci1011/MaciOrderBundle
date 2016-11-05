<?php

namespace Maci\OrderBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class CartRemoveItemType extends AbstractType
{
	public function buildForm(FormBuilderInterface $builder, array $options)
	{
		$builder
            ->add('remove', 'submit', array(
                'attr' => array('class' => 'btn-danger')
            ))
		;
	}

	public function getName()
	{
		return 'cart_remove_item';
	}
}

<?php

namespace Maci\OrderBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

use Symfony\Component\Form\Extension\Core\Type\SubmitType;

use EWZ\Bundle\RecaptchaBundle\Form\Type\EWZRecaptchaType;
use EWZ\Bundle\RecaptchaBundle\Validator\Constraints\IsTrue as RecaptchaTrue;

class CheckoutConfirmType extends AbstractType
{
	public function buildForm(FormBuilderInterface $builder, array $options)
	{
		$builder
	        ->add('recaptcha', EWZRecaptchaType::class, array(
	        	'label_attr'  => array('class'=> 'sr-only'),
    	        'mapped'      => false,
				'constraints' => array(
				    new RecaptchaTrue()
				)
	        ))
			->add('confirm', SubmitType::class, [
				'label' => 'Confirm',
				'attr' => ['class' => 'btn btn-primary']
			])
		;
	}

	public function getName()
	{
		return 'cart_checkout_confirm';
	}
}

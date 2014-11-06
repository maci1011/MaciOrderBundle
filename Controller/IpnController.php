<?php

namespace Orderly\PayPalIpnBundle\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Orderly\PayPalIpnBundle\Ipn;
use Orderly\PayPalIpnBundle\Event as Events;


class IpnController extends Controller
{
    
    public $paypal_ipn;

    public function indexAction()
    {
		//getting ipn service registered in container
		$this->paypal_ipn = $this->get('orderly_pay_pal_ipn');

		//validate ipn (generating response on PayPal IPN request)
		if ($this->paypal_ipn->validateIPN())
		{
			// Succeeded, now let's extract the order
			$this->paypal_ipn->extractOrder();

			// And we save the order now (persist and extract are separate because you might only want to persist the order in certain circumstances).
			$this->paypal_ipn->saveOrder();

			// Now let's check what the payment status is and act accordingly
			if ($this->paypal_ipn->getOrderStatus() == Ipn::PAID)
			{

		        $id = $this->paypal_ipn->getCustom();

		        $order = $this->getDoctrine()->getManager()
		        	->getRepository('MaciOrderBundle:Order')->findOneById($id);

		        if ($order) {

					$message = \Swift_Message::newInstance()
						->setSubject('Order Confirmation')
						->setFrom($this->get('service_container')->getParameter('server_email'), $this->get('service_container')->getParameter('server_email_int'))
						->setTo($order->getBilling()->getEmail(), $order->getBilling()->getName() .' '. $order->getBilling()->getSurname())
						->setBody($this->renderView('MaciOrderBundle:Email:confirmation_email.html.twig', array('order' => $order)), 'text/html')
					;
					//send message
					$this->get('mailer')->send($message);

					$notify = \Swift_Message::newInstance()
						->setSubject('Order Notify')
						->setFrom($this->get('service_container')->getParameter('server_email'), $this->get('service_container')->getParameter('server_email_int'))
						->setTo($this->get('service_container')->getParameter('order_email'))
						->setBody($this->renderView('MaciOrderBundle:Email:notify_email.html.twig',array('order' => $order())), 'text/html')
					;
					//send notify
					$this->get('mailer')->send($notify);

				}
			}
		}
		else // Just redirect to the root URL
		{
			return $this->redirect('/');
		}
		$this->triggerEvent(Events\PayPalEvents::RECEIVED);

		$response = new Response();
		$response->setStatusCode(200);

		return $response;
    }

	private function triggerEvent($event_name) {
		$dispatcher = $this->container->get('event_dispatcher');
		$dispatcher->dispatch($event_name, new Events\PayPalEvent($this->paypal_ipn));
	}
}

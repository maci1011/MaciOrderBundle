<?php

namespace Orderly\PayPalIpnBundle\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Orderly\PayPalIpnBundle\Ipn;
use Orderly\PayPalIpnBundle\Event as Events;

use Maci\OrderBundle\Entity\Order;

/*
 * Copyright 2012 Orderly Ltd 
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

/**
 *  Sample listener controller for IPN messages with twig email notification
 */
class TwigNotificationEmailController extends Controller
{
    
    public $paypal_ipn;
    /**
     * @Template()
     */
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
                $id = $this->paypal_ipn->getOrder()->getCustom();

                $order = $this->getDoctrine()->getManager()
                    ->getRepository('MaciOrderBundle:Order')->findOneById($id);

                if ($order) {

                    if ($order->getUser()) {
                        $to = $order->getUser()->getEmail();
                        $toint = $order->getUser()->getUsername();
                    } else {
                        $to = $order->getBilling()->getMail();
                        $toint = $order->getBilling()->getName() .' '. $order->getBilling()->getSurname();
                    }

                    $message = \Swift_Message::newInstance()
                        ->setSubject('Order Confirmation')
                        ->setFrom($this->get('service_container')->getParameter('server_email'), $this->get('service_container')->getParameter('server_email_int'))
                        ->setTo($to, $toint)
                        ->setBcc($this->get('service_container')->getParameter('order_email'))
                        ->setBody($this->renderView('MaciOrderBundle:Email:confirmation_email.html.twig', array('order' => $order)), 'text/html')
                    ;

                    if (!$order->getUser()) {
                        $documents = $order->getOrderDocuments();
                        if (count($documents)) {
                            foreach ($documents as $doc) {
                                $message->attach(Swift_Attachment::fromPath( $doc->getAbsoluthPath() ));
                            }
                        }
                    }

                    //send message
                    $this->get('mailer')->send($message);

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

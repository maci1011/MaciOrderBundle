<?php

namespace Maci\OrderBundle\Event;

use Orderly\PayPalIpnBundle\Event\PayPalEvent;
use Doctrine\Common\Persistence\ObjectManager;

use Maci\OrderBundle\Entity\Transaction;
use Maci\MediaBundle\Entity\Permission;


class OrderPayPalListener {

    private $om;

    public function __construct(ObjectManager $om) {

        $this->om = $om;

    }

    public function onIPNReceive(PayPalEvent $event) {

        $ipn = $event->getIPN();

        $ipnOrder = $ipn->getOrder();

        $id = $ipn->getCustom();

        $order = $this->om->getRepository('MaciOrderBundle:Order')->findOneById($id);

        if ($order) {

            $tx = new Transaction;

            $tx->setTx($ipnOrder->getTxnId());

            $tx->setAmount($ipnOrder->getMcGross());

            $tx->setOrder($order);

            $order->addTransaction($tx);

            $this->om->persist($tx);

            $order->completeOrder();

            $docs = $order->getOrderDocuments();

            if (count($docs)) {
                foreach ($docs as $id => $document) {
                    $permission = $this->om->getRepository('MaciMediaBundle:Permission')->findBy(array(
                        'user' => $this->getUser(),
                        'media' => $document
                    ));

                    if ($permission) {
                        $permission->setStatus('active');
                    } else {
                        $permission = new Permission;
                        $permission->setUser($this->getUser());
                        $permission->setMedia($document);
                        $permission->setStatus('end');
                        $permission->setNote('Created by Order ['.$this->getId().'], Item: ['.$item->getId().'].');
                        $this->om->persist($permission);
                    }

                }
            }

            $message = \Swift_Message::newInstance()
                ->setSubject('Order Confirmation')
                ->setFrom($this->get('service_container')->getParameter('server_email'), $this->get('service_container')->getParameter('server_email_int'))
                ->setTo($this->paypal_ipn->getOrder()->getPayerEmail(), $this->paypal_ipn->getOrder()->getFirstName() .' '. $this->paypal_ipn->getOrder()->getLastName())
                ->setBody($this->renderView('MaciOrderBundle:Email:confirmation_email.html.twig',
                        array('order' => $order)
                        ), 'text/html')
            ;
            //send message
            $this->get('mailer')->send($message);

            $notify = \Swift_Message::newInstance()
                ->setSubject('Order Notify')
                ->setFrom($this->get('service_container')->getParameter('server_email'), $this->get('service_container')->getParameter('server_email_int'))
                ->setTo($this->get('service_container')->getParameter('order_email'))
                ->setBody($this->renderView('MaciOrderBundle:Email:notify_email.html.twig',
                        array('order' => $order())
                        ), 'text/html')
            ;
            //send notify
            $this->get('mailer')->send($notify);

        }

        $this->om->flush();

    }
}

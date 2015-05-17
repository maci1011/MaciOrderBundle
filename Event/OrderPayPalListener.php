<?php

namespace Maci\OrderBundle\Event;

use Orderly\PayPalIpnBundle\Event\PayPalEvent;
use Doctrine\Common\Persistence\ObjectManager;

use Maci\OrderBundle\Entity\Transaction;


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

        if (!$order) {
            $order = new Order;
            $order->setAmount( $ipnOrder->getMcGross() );
            $order->setStatus('paid');
            $em->persist($order);
        }

        $tx = new Transaction;

        $tx->setTx( $ipnOrder->getTxnId() );

        $tx->setAmount( $ipnOrder->getMcGross() );

        $tx->setGateway( 'PayPal' );

        $tx->setOrder( $order );

        $this->om->persist( $tx );

        $order->addTransaction( $tx );

        $order->completeOrder();

        $this->om->getRepository('MaciMediaBundle:Permission')->setDocumentsPermissions(
            $order->getOrderDocuments(),
            $order->getUser(),
            'Created by Order: '.$order->getCode()
        );

        $this->om->flush();

    }
}

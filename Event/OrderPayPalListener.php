<?php

namespace Maci\OrderBundle\Event;

use Orderly\PayPalIpnBundle\Event\PayPalEvent;
use Doctrine\Common\Persistence\ObjectManager;

use Maci\OrderBundle\Entity\Transaction;
use Maci\OrderBundle\Entity\Order;


class OrderPayPalListener {

    private $om;

    public function __construct(ObjectManager $om) {

        $this->om = $om;

    }

    public function onIPNReceive(PayPalEvent $event) {

        $ipn = $event->getIPN();

        $ipnOrder = $ipn->getOrder();

        $id = intval( $ipnOrder->getCustom() );

        $order = $this->om->getRepository('MaciOrderBundle:Order')->findOneById( $id );

        if (!$order) {
            $order = new Order;
            $order->setName('SAVED IPN ORDER');
            $order->setAmount( $ipnOrder->getMcGross() );
            $order->setStatus('paid');
            $this->om->persist($order);
        }

        $tx = new Transaction;

        $tx->setTx( $ipnOrder->getTxnId() );

        $tx->setAmount( $ipnOrder->getMcGross() );

        $tx->setGateway( 'PayPal' );

        $tx->setOrder( $order );

        $this->om->persist( $tx );

        $order->addTransaction( $tx );

        $order->completeOrder();

        if ( $order->getUser() && count( $documents = $order->getOrderDocuments() ) ) {

            $this->om->getRepository('MaciMediaBundle:Permission')->setDocumentsPermissions(
                $documents,
                $order->getUser(),
                'Created by Order: '.$order->getCode()
            );

        }

        $this->om->flush();

    }
}

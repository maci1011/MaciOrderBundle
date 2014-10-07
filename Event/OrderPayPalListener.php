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
                        $permission->setStatus('active');
                        $permission->setNote('Created by Order ['.$this->getId().'], Item: ['.$item->getId().'].');
                        $this->om->persist($permission);
                    }

                }
            }

        }

        $this->om->flush();

    }
}

<?php

namespace Maci\OrderBundle\Event;

use Orderly\PayPalIpnBundle\Event\PayPalEvent;
use Doctrine\Common\Persistence\ObjectManager;


class OrderPayPalListener {

    private $om;

    public function __construct(ObjectManager $om) {

        $this->om = $om;

    }

    public function onIPNReceive(PayPalEvent $event) {

        $ipn = $event->getIPN();

        $ipn_items = $ipn->getOrderItems();

        foreach ($ipn_items as $ipn_item) {

        	$id = $ipn_item->getItemNumber();

        }

        // other stuff

    }
}

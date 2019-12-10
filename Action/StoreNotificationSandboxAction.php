<?php

namespace Maci\OrderBundle\Action;

use Doctrine\Common\Persistence\ObjectManager;
use Payum\Core\Bridge\Guzzle\HttpClientFactory;
use Maci\OrderBundle\Action\StoreNotificationAction;

use Http\Message\MessageFactory\GuzzleMessageFactory;

class StoreNotificationSandboxAction extends StoreNotificationAction
{

    public function __construct(
    	ObjectManager $om,
    	\Http\Message\MessageFactory $messageFactory
    ) {
        $this->om = $om;
        $this->client = HttpClientFactory::create();
        $this->messageFactory = new GuzzleMessageFactory();
        $this->sandbox = true;

    }

}

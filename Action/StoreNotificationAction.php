<?php

namespace Maci\OrderBundle\Controller;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

use Payum\Core\Action\ActionInterface;
use Payum\Core\Action\GatewayAwareAction;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\Request\Notify;

use Payum\Paypal\Ipn\Api;

class StoreNotificationAction extends GatewayAwareAction
{
    protected $om;

    protected $client;

    protected $messageFactory;

    protected $sandbox;

    public function __construct(
    	ObjectManager $om,
    	\Payum\Core\HttpClientInterface $client,
    	\Http\Message\MessageFactory $messageFactory,
    	bool $sandbox
    ) {
        $this->om = $om;
        $this->client = $client;
        $this->messageFactory = $messageFactory;
        $this->sandbox = $sandbox;
    }

    public function execute($request)
    {
		/** @var $request NotifyRequest */
		if (false == $this->supports($request)) {
		    throw RequestNotSupportedException::createActionNotSupported($this, $request);
		}

		// TODO: read sandbox attribute from config
		$api = new Api(['sandbox' => $this->sandbox], $this->client, $this->messageFactory);

		// Verify the IPN via PayPal
		if (Api::NOTIFY_VERIFIED !== $api->notifyValidate($request->getNotification())) {
		    throw new NotFoundHttpException('Invalid IPN');
		}

		$notification   = $request->getNotification();
		$model          = $request->getModel();

		// Additional Checks
		if (!$this->checkEquality(
		    array(
		        'payer_id' => 'PAYERID',
		        'mc_gross' => 'PAYMENTREQUEST_0_AMT',
		        // maybe more
				// 'item_name' => '';
				// 'item_number' => '';
				// 'payment_status' => '';
				// 'payment_amount' => '';
				// 'payment_currency' => '';
				// 'txn_id' => '';
				// 'receiver_email' => '';
				// 'payer_email' => '';
		    ),
		    $notification,
		    $model
		)) {
		    throw new NotFoundHttpException('Malformed IPN');
		}


		$previousState  = $model['PAYMENTREQUEST_0_PAYMENTSTATUS'];
		$currentState   = $notification['payment_status'];

		if ($previousState !== $currentState) {

		    // ... do something with that state change

		    $model['PAYMENTREQUEST_0_PAYMENTSTATUS'] = $currentState;

		    $this->om->persist($model);
		    $this->om->flush();

		}// else { no state change. Maybe no need to do something. }
    }

    protected function checkEquality($array, $notification, $model)
    {
        foreach ($array as $key => $value) {
            if ($notification[$key] !== $model[$value]) {
                return false;
            }
        }
        return true;
    }

    public function supports($request)
    {
        return
            $request instanceof Notify &&
            $request->getModel() instanceof \ArrayAccess
        ;
    }
}

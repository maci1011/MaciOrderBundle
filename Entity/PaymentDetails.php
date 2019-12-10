<?php

namespace Maci\OrderBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Payum\Core\Model\ArrayObject;

class PaymentDetails extends ArrayObject
{
    /**
     * @var integer
     */
    private $id;

    /**
     * @var \Maci\OrderBundle\Entity\Payment
     */
    private $payment;

    /**
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }

    public function setDetails(array $details)
    {
        return $this->details = $details;
    }

    public function getDetails()
    {
        return $this->details;
    }

    /**
     * Set payment
     *
     * @param \Maci\OrderBundle\Entity\Payment $payment
     * @return Item
     */
    public function setPayment(\Maci\OrderBundle\Entity\Payment $payment = null)
    {
        $this->payment = $payment;

        return $this;
    }

    /**
     * Get payment
     *
     * @return \Maci\OrderBundle\Entity\Payment 
     */
    public function getPayment()
    {
        return $this->payment;
    }

    /**
     * __toString()
     */
    public function __toString()
    {
        return 'PaymentDetails_'.($this->id ? $this->id : 'New');
    }
}

<?php

namespace Maci\OrderBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Payum\Core\Model\Payment as BasePayment;
use Symfony\Component\Intl\Currencies;

class Payment extends BasePayment
{
    /**
     * @var integer
     */
    private $id;

    /**
     * @var \DateTime
     */
    private $created;

    /**
     * @var ArrayCollection
     */
    private $paymentDetails;

    /**
     * @var \Maci\OrderBundle\Entity\Order
     */
    private $order;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->paymentDetails = new \Doctrine\Common\Collections\ArrayCollection();
    }


    /**
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }

    public function getTotalamountLabel()
    {
        return number_format($this->getTotalamount() / 100, 2, '.', ',') . " " . ucfirst(Currencies::getName($this->getCurrencyCode()));
    }

    /**
     * Set created
     *
     * @param \DateTime $created
     * @return Item
     */
    public function setCreated($created)
    {
        $this->created = $created;

        return $this;
    }

    /**
     * Get created
     *
     * @return \DateTime 
     */
    public function getCreated()
    {
        return $this->created;
    }

    public function setCreatedValue()
    {
        $this->created = new \DateTime();
    }

    /**
     * Add paymentDetails
     *
     * @param \Maci\OrderBundle\Entity\PaymentDetails $paymentDetails
     * @return Order
     */
    public function addPaymentDetails(\Maci\OrderBundle\Entity\PaymentDetails $paymentDetails)
    {
        $this->paymentDetails[] = $paymentDetails;

        return $this;
    }

    /**
     * Remove paymentDetails
     *
     * @param \Maci\OrderBundle\Entity\PaymentDetails $paymentDetails
     */
    public function removePaymentDetails(\Maci\OrderBundle\Entity\PaymentDetails $paymentDetails)
    {
        $this->paymentDetails->removeElement($paymentDetails);
    }

    /**
     * Get paymentDetails
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getPaymentDetails()
    {
        return $this->paymentDetails;
    }

    /**
     * Set order
     *
     * @param \Maci\OrderBundle\Entity\Order $order
     * @return Item
     */
    public function setOrder(\Maci\OrderBundle\Entity\Order $order = null)
    {
        $this->order = $order;

        return $this;
    }

    /**
     * Get order
     *
     * @return \Maci\OrderBundle\Entity\Order 
     */
    public function getOrder()
    {
        return $this->order;
    }

    /**
     * __toString()
     */
    public function __toString()
    {
        return 'Payment_'.($this->id ? $this->id : 'New');
    }
}

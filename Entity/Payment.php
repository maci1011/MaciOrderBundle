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
     * @var \Maci\OrderBundle\Entity\Order
     */
    private $order;


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

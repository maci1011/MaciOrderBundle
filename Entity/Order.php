<?php

namespace Maci\OrderBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Order
 */
class Order
{
    /**
     * @var integer
     */
    private $id;

    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $description;

    /**
     * @var string
     */
    private $mail;

    /**
     * @var string
     */
    private $status;

    /**
     * @var string
     */
    private $checkout;

    /**
     * @var string
     */
    private $type;

    /**
     * @var string
     */
    private $shipping;

    /**
     * @var string
     */
    private $payment;

    /**
     * @var string
     */
    private $shipping_cost;

    /**
     * @var string
     */
    private $payment_cost;

    /**
     * @var string
     */
    private $code;

    /**
     * @var string
     */
    private $amount;

    /**
     * @var string
     */
    private $token;

    /**
     * @var \DateTime
     */
    private $invoice;

    /**
     * @var \DateTime
     */
    private $paid;

    /**
     * @var \DateTime
     */
    private $due;

    /**
     * @var \DateTime
     */
    private $created;

    /**
     * @var \DateTime
     */
    private $updated;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $items;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $transactions;

    /**
     * @var \Maci\UserBundle\Entity\User
     */
    private $user;

    /**
     * @var \Maci\UserBundle\Entity\Address
     */
    private $billing_address;

    /**
     * @var \Maci\UserBundle\Entity\Address
     */
    private $shipping_address;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->items = new \Doctrine\Common\Collections\ArrayCollection();
        $this->transactions = new \Doctrine\Common\Collections\ArrayCollection();
        $this->token = md5(
            'MaciOrderBundle_Entity_Order-' . rand(10000, 99999) . '-' . 
            date('h') . date('i') . date('s') . date('m') . date('d') . date('Y')
        );
        $this->type = 'order';
        $this->status = 'new';
        $this->checkout = 'checkout';
        $this->shipping = null;
        $this->payment = null;
        $this->amount = 0;
        $this->shipment = false;
        $this->invoice = null;
        $this->paid = null;
        $this->due = null;
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

    /**
     * Set name
     *
     * @param string $name
     * @return Order
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string 
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set description
     *
     * @param string $description
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description
     *
     * @return string 
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set mail
     *
     * @param string $mail
     * @return Address
     */
    public function setMail($mail)
    {
        $this->mail = $mail;

        return $this;
    }

    /**
     * Get mail
     *
     * @return string 
     */
    public function getMail()
    {
        return $this->mail;
    }

    /**
     * Set type
     *
     * @param string $type
     * @return Order
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type
     *
     * @return string 
     */
    public function getType()
    {
        return $this->type;
    }

    public function getTypeArray()
    {
        return array(
            'cart' => 'Cart',
            'order' => 'Order',
            'booking' => 'Booking',
            'none' => 'None'
        );
    }

    public function getTypeLabel()
    {
        $array = $this->getTypeArray();
        if (array_key_exists($this->type, $array)) {
            return $array[$this->type];
        }
        $str = str_replace('_', ' ', $this->type);
        return ucwords($str);
    }

    public function setPayment($payment)
    {
        $this->payment = $payment;

        return $this;
    }

    public function getPayment()
    {
        return $this->payment;
    }

    public function setShipping($shipping)
    {
        $this->shipping = $shipping;

        return $this;
    }

    public function getShipping()
    {
        return $this->shipping;
    }

    public function setPaymentCost($payment_cost)
    {
        $this->payment_cost = $payment_cost;

        return $this;
    }

    public function getPaymentCost()
    {
        return $this->payment_cost;
    }

    public function setShippingCost($shipping_cost)
    {
        $this->shipping_cost = $shipping_cost;

        return $this;
    }

    public function getShippingCost()
    {
        return $this->shipping_cost;
    }

    /**
     * Set status
     *
     * @param string $status
     * @return Order
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status
     *
     * @return string 
     */
    public function getStatus()
    {
        return $this->status;
    }

    public function getStatusArray()
    {
        return array(
            'new' => 'New',
            'wishlist' => 'Wish List',
            'current' => 'Current',
            'confirm' => 'Confirm',
            'complete' => 'Complete',
            'paid' => 'Paid',
            'refuse' => 'Refuse',
            'foo' => 'Foo'
        );
    }

    public function getOrderProgression()
    {
        $i = 0;
        foreach ($this->getStatusArray() as $key => $value) {
            if ($key === $this->status) {
                return $i;
            }
            $i++;
        }
        return null;
    }

    public function getStatusLabel()
    {
        $array = $this->getStatusArray();
        if (array_key_exists($this->status, $array)) {
            return $array[$this->status];
        }
        $str = str_replace('_', ' ', $this->status);
        return ucwords($str);
    }

    public function setCheckout($checkout)
    {
        $this->checkout = $checkout;

        return $this;
    }

    public function getCheckout()
    {
        return $this->checkout;
    }

    public function getCheckoutArray()
    {
        return array(
            'full_checkout' => 'Full Checkout',
            'checkout' => 'Checkout',
            'fast_checkout' => 'Fast Checkout',
            'pickup' => 'Pickup In Store',
            'booking' => 'Booking',
            'foo' => 'Foo'
        );
    }

    public function getCheckoutLabel()
    {
        $array = $this->getCheckoutArray();
        if (array_key_exists($this->checkout, $array)) {
            return $array[$this->checkout];
        }
        $str = str_replace('_', ' ', $this->checkout);
        return ucwords($str);
    }

    /**
     * Set token
     *
     * @param string $token
     * @return Order
     */
    public function setToken($token)
    {
        $this->token = $token;

        return $this;
    }

    /**
     * Get token
     *
     * @return string 
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * Set code
     *
     * @param string $code
     * @return Order
     */
    public function setCode($code)
    {
        $this->code = $code;

        return $this;
    }

    /**
     * Get code
     *
     * @return string 
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * Set amount
     *
     * @param string $amount
     * @return Order
     */
    public function setAmount($amount)
    {
        $this->amount = $amount;

        return $this;
    }

    /**
     * Get amount
     *
     * @return string 
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * Set invoice
     *
     * @param \DateTime $invoice
     * @return Order
     */
    public function setInvoice($invoice)
    {
        $this->invoice = $invoice;

        return $this;
    }

    /**
     * Get invoice
     *
     * @return \DateTime 
     */
    public function getInvoice()
    {
        return $this->invoice;
    }

    /**
     * Set paid
     *
     * @param \DateTime $paid
     * @return Order
     */
    public function setPaid($paid)
    {
        $this->paid = $paid;

        return $this;
    }

    /**
     * Get paid
     *
     * @return \DateTime 
     */
    public function getPaid()
    {
        return $this->paid;
    }

    /**
     * Set due
     *
     * @param \DateTime $due
     * @return Order
     */
    public function setDue($due)
    {
        $this->due = $due;

        return $this;
    }

    /**
     * Get due
     *
     * @return \DateTime 
     */
    public function getDue()
    {
        return $this->due;
    }

    /**
     * Set created
     *
     * @param \DateTime $created
     * @return Order
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

    /**
     * Set updated
     *
     * @param \DateTime $updated
     * @return Order
     */
    public function setUpdated($updated)
    {
        $this->updated = $updated;

        return $this;
    }

    /**
     * Get updated
     *
     * @return \DateTime 
     */
    public function getUpdated()
    {
        return $this->updated;
    }

    /**
     * Add items
     *
     * @param \Maci\OrderBundle\Entity\Item $items
     * @return Order
     */
    public function addItem(\Maci\OrderBundle\Entity\Item $items)
    {
        $this->items[] = $items;

        return $this;
    }

    /**
     * Remove items
     *
     * @param \Maci\OrderBundle\Entity\Item $items
     */
    public function removeItem(\Maci\OrderBundle\Entity\Item $items)
    {
        $this->items->removeElement($items);
    }

    /**
     * Get items
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getItems()
    {
        return $this->items;
    }

    /**
     * Add transactions
     *
     * @param \Maci\OrderBundle\Entity\Transaction $transactions
     * @return Order
     */
    public function addTransaction(\Maci\OrderBundle\Entity\Transaction $transactions)
    {
        $this->transactions[] = $transactions;

        return $this;
    }

    /**
     * Remove transactions
     *
     * @param \Maci\OrderBundle\Entity\Transaction $transactions
     */
    public function removeTransaction(\Maci\OrderBundle\Entity\Transaction $transactions)
    {
        $this->transactions->removeElement($transactions);
    }

    /**
     * Get transactions
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getTransactions()
    {
        return $this->transactions;
    }

    /**
     * Set user
     *
     * @param \Maci\UserBundle\Entity\User $user
     * @return Order
     */
    public function setUser(\Maci\UserBundle\Entity\User $user = null)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get user
     *
     * @return \Maci\UserBundle\Entity\User 
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Set billing_address
     *
     * @param \Maci\UserBundle\Entity\Address $billing_address
     * @return Order
     */
    public function setBillingAddress(\Maci\AddressBundle\Entity\Address $billing_address = null)
    {
        $this->billing_address = $billing_address;

        return $this;
    }

    /**
     * Get billing_address
     *
     * @return \Maci\UserBundle\Entity\Address 
     */
    public function getBillingAddress()
    {
        return $this->billing_address;
    }

    /**
     * Set shipping_address
     *
     * @param \Maci\UserBundle\Entity\Address $shipping_address
     * @return Order
     */
    public function setShippingAddress(\Maci\AddressBundle\Entity\Address $shipping_address = null)
    {
        $this->shipping_address = $shipping_address;

        return $this;
    }

    /**
     * Get shipping_address
     *
     * @return \Maci\UserBundle\Entity\Address 
     */
    public function getShippingAddress()
    {
        return $this->shipping_address;
    }

    /**
     * __toString()
     */
    public function __toString()
    {
        return $this->getName();
    }

    /**
     * @ORM\PrePersist
     */
    public function setUpdatedValue()
    {
        $this->updated = new \DateTime();
    }

    /**
     * @ORM\PrePersist
     */
    public function setCreatedValue()
    {
        $this->created = new \DateTime();
    }

    public function getTransactionsAmount()
    {
        $amount = 0;

        foreach ($this->transactions as $item) {
            $amount += $item->getAmount();
        }

        return $amount;
    }

    public function getBalance()
    {
        return ( $this->getTransactionsAmount() - $this->getAmount() );
    }

    public function getArrayLabel($array, $key)
    {
        if (array_key_exists($key, $array)) {
            return $array[$key];
        }
        $str = str_replace('_', ' ', $key);
        return ucwords($str);
    }

    public function getOrderDocuments()
    {
        $documents = array();
        foreach ($this->items as $item) {
            if (count($docs = $item->getPrivateDocuments())) {
                foreach ($docs as $id => $doc) {
                    $documents[$id] = $doc;
                }
            }
        }

        return $documents;
    }

    public function checkOrder()
    {
        foreach ($this->items as $item) {
            if ( !$item->checkAvailability() ) {
                return false;
            }
        }

        return true;
    }


    public function checkShipment()
    {
        $shipment = false;

        foreach ($this->items as $item) {
            $product = $item->getProduct();
            if ( $product && $product->getShipment() ) {
                $shipment = true;
                break;
            }
        }

        return $shipment;
    }

    public function refreshAmount()
    {
        $amounts = $this->getItems()->map(function($e){
            $e->refreshAmount();
            return $e->getAmount();
        });
        $tot = 0;
        foreach ($amounts as $amount) {
            $tot += $amount;
        }
        if ( $this->getShippingCost() ) {
            $tot += $this->getShippingCost();
        }
        if ( $this->getPaymentCost() ) {
            $tot += $this->getPaymentCost();
        }
        return $this->amount = $tot;
    }

    public function subItemsQuantity()
    {
        foreach ($this->items as $item) {
            if ($product = $item->getProduct()) {
                $product->subQuantity($item->getQuantity());
            }
            if (count($item->getVariants())) {
                foreach ($item->getVariants() as $variant) {
                    $variant->subQuantity($item->getQuantity());
                }
            }
        }
    }

    public function confirmOrder()
    {
        if ( 2 < $this->getOrderProgression() ) {
            return false;
        }

        $this->status = 'confirm';

        $this->subItemsQuantity();

        $this->invoice = new \DateTime();

        $this->due = new \DateTime();

        $this->due->modify('+1 month');

        return true;
    }

    public function completeOrder()
    {
        if ($this->status === 'complete' || $this->status !== 'confirm') {
            return false;
        }

        $this->status = 'complete';

        return true;
    }
}

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
    private $sub_amount;

    /**
     * @var string
     */
    private $token;

    /**
     * @var string
     */
    private $locale;

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
     * @var boolean
     */
    private $removed;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $items;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $transactions;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $payments;

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
        $this->payments = new \Doctrine\Common\Collections\ArrayCollection();
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
        $this->sub_amount = 0;
        $this->shipment = false;
        $this->invoice = null;
        $this->paid = null;
        $this->due = null;
        $this->removed = false;
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
            'Cart' => 'cart',
            'Order' => 'order',
            'Booking' => 'booking',
            'None' => 'none'
        );
    }

    public function getTypeLabel()
    {
        $array = $this->getTypeArray();
        $key = array_search($this->type, $array);
        if ($key) return $key;
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

    public function getpayment_cost()
    {
        return $this->getPaymentCost();
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

    public function getshipping_cost()
    {
        return $this->getShippingCost();
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
            'New' => 'new',
            'Wish List' => 'wishlist',
            'Current' => 'current',
            'Confirm' => 'confirm',
            'Complete' => 'complete',
            'Paid' => 'paid',
            'Refuse' => 'refuse',
            'Foo' => 'foo'
        );
    }

    public function getProgression()
    {
        $i = 0;
        foreach ($this->getStatusArray() as $key => $value) {
            if ($value === $this->status) {
                return $i;
            }
            $i++;
        }
        return -1;
    }

    public function getStatusLabel()
    {
        $array = $this->getStatusArray();
        $key = array_search($this->status, $array);
        if ($key) return $key;
        $str = str_replace('_', ' ', $this->type);
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
            'Full Checkout' => 'full_checkout',
            'Checkout' => 'checkout',
            'Fast Checkout' => 'fast_checkout',
            'Pickup In Store' => 'pickup',
            'Booking' => 'booking',
            'Foo' => 'foo'
        );
    }

    public function getCheckoutLabel()
    {
        $array = $this->getCheckoutArray();
        $key = array_search($this->type, $array);
        if ($key) return $key;
        $str = str_replace('_', ' ', $this->type);
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

    public function setLocale($locale)
    {
        $this->locale = $locale;

        return $this;
    }

    public function getLocale()
    {
        return $this->locale;
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

    public function getSubAmount()
    {
        if (!$this->sub_amount) {
            $this->refreshAmount();
        }
        return $this->sub_amount;
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

    public function setRemoved($removed)
    {
        $this->removed = $removed;

        return $this;
    }

    public function getRemoved()
    {
        return $this->removed;
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
     * Add payments
     *
     * @param \Maci\OrderBundle\Entity\Payment $payments
     * @return Order
     */
    public function addPayment(\Maci\OrderBundle\Entity\Payment $payments)
    {
        $this->payments[] = $payments;

        return $this;
    }

    /**
     * Remove payments
     *
     * @param \Maci\OrderBundle\Entity\Payment $payments
     */
    public function removePayment(\Maci\OrderBundle\Entity\Payment $payments)
    {
        $this->payments->removeElement($payments);
    }

    /**
     * Get payments
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getPayments()
    {
        return $this->payments;
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
    public function setBillingAddress(\Maci\UserBundle\Entity\Address $billing_address = null)
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
    public function setShippingAddress(\Maci\UserBundle\Entity\Address $shipping_address = null)
    {
        if($this->shipping_address && $this->shipping) {
            if($this->shipping_address->getCountry() !== $shipping_address->getCountry()) {
                $this->shipping = null;
            }
        }

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

    public function setUpdatedValue()
    {
        $this->updated = new \DateTime();
    }

    public function setCreatedValue()
    {
        $this->created = new \DateTime();
    }

    public function setInvoiceValue()
    {
        if (!$this->invoice) {
            $this->invoice = new \DateTime();
        }
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
            $docs = $item->getPrivateDocuments();
            if (is_array($docs) && count($docs)) {
                foreach ($docs as $id => $doc) {
                    $documents[$id] = $doc;
                }
            }
        }

        return $documents;
    }

    public function checkOrder()
    {
        if(!count($this->items)) {
            return false;
        }

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

        $this->sub_amount = $tot;

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
        }
    }

    public function reverseOrder()
    {
        foreach ($this->items as $item) {
            if ($product = $item->getProduct()) {
                $product->addQuantity($item->getQuantity());
            }
        }
    }

    public function getItemsNumber()
    {
        return count($this->items);
    }

    public function getTotalItemsQuantity()
    {
        $tot = 0;
        foreach ($this->items as $item) {
            $tot += $item->getQuantity();
        }
        return $tot;
    }

    public function checkConfirmation()
    {
        if ( 2 < $this->getProgression() || !$this->checkOrder() ) {
            return false;
        }

        return true;
    }

    public function confirmOrder()
    {
        $this->status = 'confirm';

        $this->subItemsQuantity();

        $this->invoice = new \DateTime();

        $this->due = new \DateTime();

        $this->due->modify('+1 month');

        return true;
    }

    public function completeOrder()
    {
        if ( 4 < $this->getProgression() ) {
            return false;
        }

        if ( ! $this->getBalance() < 0 ) {
            $this->status = 'complete';
        } else {
            $this->status = 'paid';
        }

        return true;
    }

    /**
     * __toString()
     */
    public function __toString()
    {
        return 'Order_'.($this->id ? $this->id : 'New');
    }
}

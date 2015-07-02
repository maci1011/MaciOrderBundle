<?php

namespace Maci\OrderBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Item
 */
class Item
{
    /**
     * @var integer
     */
    private $id;

    /**
     * @var string
     */
    private $info;

    /**
     * @var string
     */
    private $details;

    /**
     * @var string
     */
    private $quantity;

    /**
     * @var string
     */
    private $amount;

    /**
     * @var \DateTime
     */
    private $created;

    /**
     * @var \DateTime
     */
    private $updated;

    /**
     * @var \Maci\OrderBundle\Entity\Order
     */
    private $order;

    /**
     * @var \Maci\ProductBundle\Entity\Product
     */
    private $product;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $variants;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->quantity = 1;
        $this->variants = new \Doctrine\Common\Collections\ArrayCollection();
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
     * Set info
     *
     * @param string $info
     * @return Item
     */
    public function setInfo($info)
    {
        $this->info = $info;

        return $this;
    }

    /**
     * Get info
     *
     * @return string 
     */
    public function getInfo()
    {
        return $this->info;
    }

    /**
     * Set details
     *
     * @param string $details
     * @return Item
     */
    public function setDetails($details)
    {
        $this->details = $details;

        return $this;
    }

    /**
     * Get details
     *
     * @return string 
     */
    public function getDetails()
    {
        return $this->details;
    }

    /**
     * Set quantity
     *
     * @param string $quantity
     * @return Item
     */
    public function setQuantity($quantity)
    {
        $this->quantity = $quantity;

        return $this;
    }

    /**
     * Get quantity
     *
     * @return string 
     */
    public function getQuantity()
    {
        return $this->quantity;
    }

    /**
     * Set amount
     *
     * @param string $amount
     * @return Item
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

    /**
     * Set updated
     *
     * @param \DateTime $updated
     * @return Item
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
     * Set product
     *
     * @param \Maci\ProductBundle\Entity\Product $product
     * @return Item
     */
    public function setProduct(\Maci\ProductBundle\Entity\Product $product = null)
    {
        $this->product = $product;

        return $this;
    }

    /**
     * Get product
     *
     * @return \Maci\ProductBundle\Entity\Product 
     */
    public function getProduct()
    {
        return $this->product;
    }

    /**
     * Add variants
     *
     * @param \Maci\ProductBundle\Entity\Variant $variants
     * @return Item
     */
    public function addVariant(\Maci\ProductBundle\Entity\Variant $variants)
    {
        $this->variants[] = $variants;

        return $this;
    }

    /**
     * Remove variants
     *
     * @param \Maci\ProductBundle\Entity\Variant $variants
     */
    public function removeVariant(\Maci\ProductBundle\Entity\Variant $variants)
    {
        $this->variants->removeElement($variants);
    }

    /**
     * Get variants
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getVariants()
    {
        return $this->variants;
    }

    public function getVariantsId()
    {
        $list = $this->getVariants();
        $res = array();
        foreach ($list as $el) {
            array_push($res, $el->getId());
        }
        return $res;
    }

    /**
     * __toString()
     */
    public function __toString()
    {
        return 'MaciOrderItem_' . $this->getId();
    }

    public function setUpdatedValue()
    {
        $this->updated = new \DateTime();
    }

    public function setCreatedValue()
    {
        $this->created = new \DateTime();
    }

    public function getPrivateDocuments()
    {
        $documents = array();

        if ($this->product) {
            foreach ($this->product->getPrivateDocuments() as $item) {
                $document = $item->getMedia();
                $documents[$document->getId()] = $document;
            }
        }

        if (count($documents)) {
            return $documents;
        }

        return false;
    }

    public function checkProduct($quantity = false)
    {
        if ($this->product) {
            if ( !$this->product->isAvailable() ) {
                return false;
            }
            $quantity = $quantity ? $quantity : $this->quantity;
            if ( !$this->product->checkQuantity($quantity) ) {
                return false;
            }
        }
        return true;
    }

    public function checkVariants($quantity = false)
    {
        $quantity = $quantity ? $quantity : $this->quantity;
        foreach ($this->variants as $variant) {
            if ( !$variant->checkQuantity($quantity) && $this->product->isAvalaible() ) {
                return false;
            }
        }
        return true;
    }

    public function checkAvailability($quantity = false)
    {
        if ( $this->product ) {
            if ( !$this->product->isAvailable() || !$this->checkProduct($quantity) || !$this->checkVariants($quantity) ) {
                return false;
            }
        }
        return true;
    }

    public function refreshAmount()
    {
        $tot = 0;
        if ($this->product) {
            $tot += ( $this->product->getPrice() * $this->quantity );
        }
        $this->amount = $tot;
    }
}

<?php

namespace Maci\OrderBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Security\Core\SecurityContext;

use Maci\OrderBundle\Entity\Order;
use Maci\OrderBundle\Entity\Item;

class OrderController extends Controller
{
	private $em;

	private $securityContext;

	private $user;

    private $session;

    private $cart;

	public function __construct(EntityManager $doctrine, SecurityContext $securityContext, Session $session)
	{
    	$this->em = $doctrine;
	    $this->securityContext = $securityContext;
	    $this->user = $securityContext->getToken()->getUser();
		$this->session = $session;
        $this->cart = false;
    }

    public function getDefaultSession()
    {
        $order_arr = $this->session->get('order');
        if (!is_array($order_arr)) {
            if (true === $this->securityContext->isGranted('ROLE_USER')) {
                $status = 'current';
            } else {
                $status = 'session';
            }
            $this->session->set('order', $order_arr = array(
                'name' => 'My Cart',
                'code' => 'CRT-' . rand(10000, 99999) . '-' . 
                    date('h') . date('i') . date('s') . date('m') . date('d') . date('Y'),
                'status' => $status,
                'type' => 'cart',
                'shipping' => null,
                'billing' => null,
                'amount' => 0
            ));
            $this->session->set('order_items', array());
        }
        return $order_arr;
    }

    public function setCart($cart)
    {
        $order_arr = $this->getDefaultSession();
        $cart->setName( $order_arr['name'] );
        $cart->setCode( $order_arr['code'] );
        $cart->setStatus( $order_arr['status'] );
        $cart->setType( $order_arr['type'] );
        return $cart;
    }

    public function getCurrentCart()
    {
        if ($this->cart) {
            return $this->cart;
        }

        if (true === $this->securityContext->isGranted('ROLE_USER')) {

            $cart = $this->em->getRepository('MaciOrderBundle:Order')
                ->findOneBy(array( 'user' => $this->user, 'type' => 'cart', 'status' => 'current' ));

            $order_arr = $this->getDefaultSession();

            if (!$cart) {
                if ( array_key_exists('id', $order_arr) ) {
                    $this->session->set('order', false);
                    $this->session->set('order_items', array());
                }
                $cart = $this->setCart(new Order);
                $this->em->persist($cart);
            }

            if ($order_arr['status'] === 'session') {
                $cart = $this->loadCartFromSession($cart);
                $cart->setStatus('current');
                $cart->setUser($this->user);
                $this->refreshSession($cart);
            }

            $cart->refreshAmount();
            $this->em->flush();

        } else {

            $cart = $this->setCart(new Order);
            $cart = $this->loadCartFromSession($cart);

        }

        return $this->cart = $cart;
    }

    public function loadCartFromSession($cart)
    {
        $order_arr = $this->getDefaultSession();
        $items = $this->session->get('order_items', array());
        if (count($items)) {
            foreach ($items as $info) {
                $available = true;
                $quantity = $info['quantity'];
                $variants = array();
                $product = $this->em->getRepository('MaciProductBundle:Product')
                    ->findOneById($info['id']);
                if ($product && $product->isAvailable() && $product->checkQuantity($quantity)) {
                    if (count($info['variants'])) {
                        foreach ($info['variants'] as $varinfo) {
                            $vid = $varinfo['id'];
                            $variant = $this->em->getRepository('MaciProductBundle:Variant')
                                ->findOneById($vid);
                            if ($variant && $variant->isAvailable() && $variant->checkQuantity($quantity)) {
                                array_push($variants, $variant);
                            } else {
                                $available = false;
                            }
                        }
                    }
                } else {
                    $available = false;
                }
                if ($available) {
                    $this->addProduct($cart, $product, $quantity, $variants);
                }
            }
        }

        if (array_key_exists('shipping', $order_arr) && $id = intval($order_arr['shipping'])) {
            $address = $this->em->getRepository('MaciAddressBundle:Address')
                ->findOneById($id);
            if ($address && !$address->getUser()) {
                $cart->setShipping($address);
            }
        }

        if (array_key_exists('billing', $order_arr) && $id = intval($order_arr['billing'])) {
            $address = $this->em->getRepository('MaciAddressBundle:Address')
                ->findOneById($id);
            if ($address && !$address->getUser()) {
                $cart->setBilling($address);
            }
        }

        $cart->refreshAmount();

        return $cart;
    }

    public function refreshSession($order)
    {
        $info =array(
            'name' => $order->getName(),
            'code' => $order->getCode(),
            'status' => $order->getStatus(),
            'type' => $order->getType(),
            'amount' => $order->getAmount()
        );

        if ($order->getShipping()) {
            $info['shipping'] = $order->getShipping()->getId();
        }

        if ($order->getBilling()) {
            $info['billing'] = $order->getBilling()->getId();
        }

        $this->session->set('order', $info);

        $items = array();

        foreach ($order->getItems() as $item) {
            if (is_object($product = $item->getProduct())) {
                $variants_info = array();
                foreach ($item->getVariants() as $variant) {
                    array_push($variants_info, array(
                        'id' => $variant->getId(),
                        'name' => $variant->getName()
                    ));
                }
                array_push($items, array(
                    'id' => $product->getId(),
                    'name' => $product->getName(),
                    'sale' => $product->getSale(),
                    'price' => $product->getPrice(),
                    'quantity' => $item->getQuantity(),
                    'variants' => $variants_info
                ));
            }
        }

        $this->session->set('order_items', $items);
    }

    public function addProductToCart($product, $quantity = 1, $variants = array())
    {
        $cart = $this->getCurrentCart();

        $item = $this->addProduct($cart, $product, $quantity, $variants);

        if (!$item) {
            return false;
        }

        $cart->refreshAmount();

        if (true === $this->securityContext->isGranted('ROLE_USER')) {
            $this->em->flush();
        }

        $this->refreshSession($cart);

        return true;
    }

    public function addProduct($order, $product, $quantity = 1, $variants = array())
    {
        $same_item = false;
        foreach ($order->getItems() as $item) {
            $same = true;
            $item_product = $item->getProduct();
            if (is_object($item_product) && $item_product->getId() === $product->getId() && count($variants) == count($item->getVariants())) {
                foreach ($variants as $variant) {
                    if (!in_array($variant->getId(), $item->getVariantsId())) {
                        $same = false;
                    }
                }
            } else {
                $same = false;
            }
            if ($same) {
                $same_item = $item;
                break;
            }
        }

        if ($same_item) {
            $item = $same_item;
            $item->setQuantity( $item->getQuantity() + $quantity );
        } else {
            $item = new Item;
            $item->setQuantity($quantity);
            $item->setProduct($product);
            $item->setOrder($order);
            $order->addItem($item);
            foreach ($variants as $variant) {
                $item->addVariant($variant);
            }
        }

        if (!$item->checkAvailability()) {
            return false;
        }

        if ( $order->getId() ) {
            $this->em->persist($item);
            $this->em->flush();
        }

        return $item;
    }

    public function editItemQuantity($id, $quantity)
    {
        if (true === $this->securityContext->isGranted('ROLE_USER')) {
            $item = $this->em->getRepository('MaciOrderBundle:Item')
                ->findOneById($id);

            if (!$item) {
                return false;
            }

            $item->setQuantity($quantity);

            if (!$item->checkProductQuantity($quantity) || !$item->checkVariantsQuantity($quantity)) {
                return false;
            }

            $item->getOrder()->refreshAmount();

            $this->em->flush();
        } else {
            $items = $this->session->get('order_items');

            if (!is_array($items) || !count($items) || count($items) <= $id) {
                return false;
            }

            $items[$id]['quantity'] = $quantity;

            $this->session->set('order_items', $items);
        }

        return true;
    }

    public function removeItem($id)
    {
        if (true === $this->securityContext->isGranted('ROLE_USER')) {

            $cart = $this->getCurrentCart();
            $item = false;

            foreach ($cart->getItems() as $_item) {
                if ($_item->getId() === $id) {
                    $item = $_item;
                    break;
                }
            }

            if (!$item) {
                return false;
            }

            if (
                !$item ||
                ( $item->getOrder()->getUser() && $item->getOrder()->getUser()->getId() !== $this->user->getId() ) &&
                false === $this->securityContext->isGranted('ROLE_SUPER_ADMIN')
            ) {
                return false;
            }

            $this->em->remove($item);

            $this->em->flush();
        } else {
            $items = $this->session->get('order_items');

            if (!is_array($items) || !count($items) || count($items) <= $id) {
                return false;
            }

            array_splice($items, $id);

            $this->session->set('order_items', $items);
        }

        return true;
    }

    public function setCartPayment($payment)
    {
        $cart = $this->getCurrentCart();
        $cart->setPayment($payment);
        $this->saveCart();
    }

    public function setCartSpedition($spedition)
    {
        $cart = $this->getCurrentCart();
        $cart->setSpedition($spedition);
        $this->saveCart();
    }

    public function setCartShipping($address)
    {
        $cart = $this->getCurrentCart();
        $cart->setShipping($address);
        $this->saveCart();
    }

    public function setCartBilling($address)
    {
        $cart = $this->getCurrentCart();
        $cart->setBilling($address);
        $this->saveCart();
    }

    public function setCartCheckout($checkout)
    {
        $cart = $this->getCurrentCart();
        $cart->setCheckout($checkout);
        $this->saveCart();
    }

    public function saveCart()
    {
        if (true === $this->securityContext->isGranted('ROLE_USER')) {
            $this->em->flush();
        } else {
            $this->refreshSession($this->getCurrentCart());
        }
    }

    public function confirmCart()
    {
        $cart = $this->getCurrentCart();
        if ($cart->confirmOrder()) {
            return $cart;
        }
        return false;
    }
}

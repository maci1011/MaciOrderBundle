<?php

namespace Maci\OrderBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

use Maci\OrderBundle\Entity\Order;
use Maci\OrderBundle\Entity\Item;

class DefaultController extends Controller
{
    public function indexAction()
    {
        $list = $this->getDoctrine()->getManager()
            ->getRepository('MaciOrderBundle:Order')
            ->findBy(array( 'user' => $this->getUser()));

        return $this->render('MaciOrderBundle:Default:index.html.twig', array(
            'list' => $list
        ));
    }

    public function cartAction(Request $request)
    {
        return $this->render('MaciOrderBundle:Default:cart.html.twig', array(
            'cart' => $this->get('maci.orders')->getCurrentCart()
        ));
    }

    public function addToCartAction(Request $request)
    {
        $om = $this->getDoctrine()->getManager();

        $quantity = $request->get('quantity', 1);

        $product_id = $request->get('product');

        $product = $om->getRepository('MaciProductBundle:Product')
            ->findOneById($product_id);

        if (!$product || !$product->isAvailable() || !$product->checkQuantity($quantity)) {
            return $this->redirect($this->generateUrl('maci_order_cart', array('error' => 'error.noquantity')));
        }

        $variants_id = $request->get('variants', array());

        $variants = array();

        foreach ($variants_id as $id) {
            if (in_array($id, $product->getVariantsId())) {
                $variant = $om->getRepository('MaciProductBundle:Variant')
                    ->findOneById($id);
                if ($variant && $variant->isAvailable() && $variant->checkQuantity($quantity)) {
                    array_push($variants, $variant);
                } else {
                    return $this->redirect($this->generateUrl('maci_order_cart', array('error' => 'error.noquantity')));
                }
            }
        }

        if ( $this->get('maci.orders')->addProductToCart($product, $quantity, $variants) ) {
            return $this->redirect($this->generateUrl('maci_order_cart'));
        } else {
            return $this->redirect($this->generateUrl('maci_order_cart', array('error' => 'error.notavailable')));
        }
    }

    public function editCartItemAction(Request $request, $id)
    {
        if ( $this->get('maci.orders')->editItemQuantity($id, intval($request->get('quantity', 1)) ) ) {
            return $this->redirect($this->generateUrl('maci_order_cart', array('edited' => true)));
        } else {
            return $this->redirect($this->generateUrl('maci_order_cart', array('error' => true)));
        }
    }

    public function removeCartItemAction(Request $request, $id)
    {
        if ( $this->get('maci.orders')->removeItem($id) ) {
            return $this->redirect($this->generateUrl('maci_order_cart', array('removed' => true)));
        } else {
            return $this->redirect($this->generateUrl('maci_order_cart', array('error' => true)));
        }
    }

    public function cartGoCheckoutAction(Request $request, $option)
    {
        if ($option === 'fast_checkout') {
            $this->get('maci.orders')->setCartSpedition('standard');
            $this->get('maci.orders')->setCartPayment('paypal');
            $this->get('maci.orders')->setCartCheckout('fast_checkout');
        } else if ($option === 'booking') {
            $this->get('maci.orders')->setCartSpedition('pickup');
            $this->get('maci.orders')->setCartPayment('cash');
            $this->get('maci.orders')->setCartCheckout('booking');
        } else {
            $this->get('maci.orders')->setCartCheckout('checkout');
        }

        if (true === $this->get('security.context')->isGranted('ROLE_USER')) {
            return $this->redirect($this->generateUrl('maci_order_checkout'));
        }

        return $this->redirect($this->generateUrl('fos_user_registration_register'));
        // return $this->render('MaciOrderBundle:Default:gocheckout.html.twig');
    }

    public function cartCheckoutAction(Request $request)
    {
        $cart = $this->get('maci.orders')->getCurrentCart();

        if (!$cart) {
            return $this->redirect($this->generateUrl('maci_order_notfound'));
        }

        if ($cart->getStatus() === 'complete' || $cart->getStatus() === 'confirm') {
            return $this->redirect($this->generateUrl('maci_order_invoice', array('id'=>$cart->getId())));
        }

        $type = $cart->getCheckout();

        $checkout = array();

        if ($type) {

            $edit = $request->get('checkout');

            $set = false;

            if ($cart->getBilling() && $edit !== 'billing') {
                $checkout['billing'] = 'setted';
            } else {
                if ($set) {
                    $checkout['billing'] = 'toset';
                } else {
                    $checkout['billing'] = 'set';
                    $set = true;
                }
            }

            if ($type === 'checkout' || $type === 'fast_checkout') {
                if ($cart->getShipping() && $edit !== 'shipping') {
                    $checkout['shipping'] = 'setted';
                } else {
                    if ($set) {
                        $checkout['shipping'] = 'toset';
                    } else {
                        $checkout['shipping'] = 'set';
                        $set = true;
                    }
                }
            } else {
                $checkout['shipping'] = 'disabled';
            }

            if ($type === 'checkout') {
                if ($cart->getSpedition() && $edit !== 'spedition') {
                    $checkout['spedition'] = 'setted';
                } else {
                    if ($set) {
                        $checkout['spedition'] = 'toset';
                    } else {
                        $checkout['spedition'] = 'set';
                        $set = true;
                    }
                }
            } else {
                $checkout['spedition'] = 'disabled';
            }

            if ($type === 'checkout') {
                if ($cart->getPayment() && $edit !== 'payment') {
                    $checkout['payment'] = 'setted';
                } else {
                    if ($set) {
                        $checkout['payment'] = 'toset';
                    } else {
                        $checkout['payment'] = 'set';
                        $set = true;
                    }
                }
            } else {
                $checkout['payment'] = 'disabled';
            }

            if ($set) {
                $checkout['confirm'] = 'toset';
            } else {
                $checkout['confirm'] = 'set';
            }

        } else {
            $checkout = false;
        }

        $cart->refreshAmount();

        if (true === $this->get('security.context')->isGranted('ROLE_USER')) {
            $this->getDoctrine()->getManager()->flush();
        }

        return $this->render('MaciOrderBundle:Default:checkout.html.twig', array(
            'checkout' => $checkout,
            'order' => $cart
        ));
    }

    public function cartConfirmAction(Request $request)
    {
        if ( $cart = $this->get('maci.orders')->confirmCart() ) {
            return $this->render('MaciOrderBundle:Default:confirm.html.twig', array('order' => $cart));
        } else {
            return $this->redirect($this->generateUrl('maci_order_checkout', array('error' => true)));
        }
    }

    public function cartSetAddressAction(Request $request, $option)
    {
        $address = $this->getDoctrine()->getManager()->getRepository('MaciAddressBundle:Address')
                ->findOneBy(array('user' => $this->getUser(), 'id' => $request->get('address')));
        if (!$address) {
            return $this->redirect($this->generateUrl('maci_order_checkout', array('error' => 'address.notfound')));
        }
        if ( $this->setAddress($option, $address) ) {
            return $this->redirect($this->generateUrl('maci_order_checkout', array('setted' => $option)));
        }
        return $this->redirect($this->generateUrl('maci_order_checkout', array('error' => 'checkout.nothingsetted')));
    }

    public function cartSetPaymentAction(Request $request)
    {
        $cart = $this->get('maci.orders')->getCurrentCart();
        $form = $this->createForm('order_payment', $cart);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $this->getDoctrine()->getManager()->flush();
            return $this->redirect($this->generateUrl('maci_order_checkout', array('setted' => 'payment')));
        }

        return $this->render('MaciOrderBundle:Default:_order_payment.html.twig', array(
            'form' => $form->createView()
        ));
    }

    public function cartSetSpeditionAction(Request $request)
    {
        $cart = $this->get('maci.orders')->getCurrentCart();
        $form = $this->createForm('order_spedition', $cart);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $this->getDoctrine()->getManager()->flush();
            return $this->redirect($this->generateUrl('maci_order_checkout', array('setted' => 'spedition')));
        }

        return $this->render('MaciOrderBundle:Default:_order_spedition.html.twig', array(
            'form' => $form->createView()
        ));
    }

    public function setAddress($option, $address)
    {
        if ($option === 'both') {
            $this->get('maci.orders')->setCartShipping($address);
            $this->get('maci.orders')->setCartBilling($address);
            return $this->redirect($this->generateUrl('maci_order_checkout', array('setted' => 'both')));
        } else if ($option === 'shipping') {
            $this->get('maci.orders')->setCartShipping($address);
            return $this->redirect($this->generateUrl('maci_order_checkout', array('setted' => 'shipping')));
        } else if ($option === 'billing') {
            $this->get('maci.orders')->setCartBilling($address);
            return $this->redirect($this->generateUrl('maci_order_checkout', array('setted' => 'billing')));
        }
        return false;
    }

    public function setPayment($option)
    {
        if ( in_array($option, array('paypal', 'prenotation')) ) {
            $this->get('maci.orders')->setCartPayment($option);
            return true;
        }
        return false;
    }

    public function setSpedition($option)
    {
        if ( in_array($option, array('standard')) ) {
            $this->get('maci.orders')->setCartSpedition($option);
            return true;
        }
        return false;
    }

    public function notfoundAction()
    {
        return $this->render('MaciOrderBundle:Default:notfound.html.twig');
    }

    public function invoiceAction($id)
    {
        $order = $this->getDoctrine()->getManager()
            ->getRepository('MaciOrderBundle:Order')
            ->findOneById($id);

        if (!$order) {
            return $this->redirect($this->generateUrl('maci_order_notfound'));
        }

        if (
            ( $order->getUser() && $order->getUser()->getId() !== $this->getUser()->getId() ) &&
            false === $this->get('security.context')->isGranted('ROLE_SUPER_ADMIN')
        ) {
            return $this->redirect($this->generateUrl('maci_order_homepage', array('error' => 'order.nomap')));
        }

        return $this->render('MaciOrderBundle:Default:invoice.html.twig', array(
            'order' => $order
        ));
    }

    public function paypalPayAction()
    {
        $cart = $this->get('maci.orders')->getCurrentCart();

        if (!$cart || $cart->getStatus() !== 'confirmed') {
            return $this->redirect($this->generateUrl('maci_order_cart'));
        }


    }

    public function paypalCompleteAction()
    {
        $om = $this->getDoctrine()->getManager();

        $id = $tx = $this->getRequest()->get('cm');

        $order = $om->getRepository('MaciOrderBundle:Order')
            ->findOneById($id);

        $tx = $this->getRequest()->get('tx');

        if (!$order || !$tx) {
            return $this->redirect($this->generateUrl('maci_order_notfound'));
        }

        $pdt = $this->get('orderly_pay_pal_pdt');
        $pdtArray = $pdt->getPdt($tx);

        $status = 'unknown';

        if (isset($pdtArray['payment_status'])) {
            $status = $pdtArray['payment_status'];
        } else if ($this->getRequest()->get('st')) {
            $status = $this->getRequest()->get('st');
        }

        if ($status === 'Completed') {
            $order->setStatus('paid');
            $om->flush();
        }

        $page = $this->getDoctrine()->getManager()
            ->getRepository('MaciPageBundle:Page')
            ->findOneByPath('order-complete');

        if ($page) {
            return $this->render('MaciPageBundle:Default:page.html.twig', array('page' => $page));
        }

        return $this->render('MaciOrderBundle:Default:complete.html.twig');
    }

    public function paypalFormAction($order)
    {
        $form = $this->createFormBuilder($order);

        if ($this->get('service_container')->getParameter('paypal_islive')) {

            $form = $form->setAction('https://www.paypal.com/cgi-bin/webscr')
                ->add('business', 'hidden', array('mapped' => false, 'data' => $this->get('service_container')->getParameter('maciorder_paypalform_business')));

        } else {

            $form = $form->setAction('https://sandbox.paypal.com/cgi-bin/webscr')
                ->add('business', 'hidden', array('mapped' => false, 'data' => $this->get('service_container')->getParameter('maciorder_paypalform_business_fac')));

        }

        $form = $form->add('cmd', 'hidden', array('mapped' => false, 'data' => '_xclick'))
            ->add('lc', 'hidden', array('mapped' => false, 'data' => 'IT'))
            ->add('item_name', 'hidden', array('mapped' => false, 'data' => $order->getName()))
            ->add('item_number', 'hidden', array('mapped' => false, 'data' => 1))
            ->add('custom', 'hidden', array('mapped' => false, 'data' => $order->getId()))
            ->add('amount', 'hidden', array('mapped' => false, 'data' => number_format($order->getAmount(), 2, '.', '')))
            ->add('currency_code', 'hidden', array('mapped' => false, 'data' => 'EUR'))
            ->add('button_subtype', 'hidden', array('mapped' => false, 'data' => 'services'))
            ->add('no_note', 'hidden', array('mapped' => false, 'data' => '1'))
            ->add('no_shipping', 'hidden', array('mapped' => false, 'data' => '1'))
            ->add('rm', 'hidden', array('mapped' => false, 'data' => '1'))
            ->add('return', 'hidden', array('mapped' => false, 'data' => $this->generateUrl('maci_order_paypal_complete', array(), true)))
            ->add('cancel_return', 'hidden', array('mapped' => false, 'data' => $this->generateUrl('maci_order', array(), true)))
            ->add('notify_url', 'hidden', array('mapped' => false, 'data' => $this->generateUrl('orderly_paypalipn_twignotificationemail_index', array(), true)))
            ->getForm();

        return $this->render('MaciOrderBundle:Default:_paypal.html.twig', array(
            'form' => $form->createView(),
        ));
    }

    public function testAction(Request $request, $id)
    {
        $om = $this->getDoctrine()->getManager();

        $order = $om->getRepository('MaciOrderBundle:Order')
            ->findOneById($id);

        return $this->render('MaciOrderBundle:Default:invoice.html.twig', array(
            'order' => $order
        ));
    }
}

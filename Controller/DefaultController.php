<?php

namespace Maci\OrderBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

use Maci\OrderBundle\Entity\Order;
use Maci\OrderBundle\Entity\Item;

use Maci\OrderBundle\Form\Type\CartAddProductItemType;
use Maci\OrderBundle\Form\Type\CartBillingAddressType;
use Maci\OrderBundle\Form\Type\CartBookingType;
use Maci\OrderBundle\Form\Type\CartCheckoutType;
use Maci\OrderBundle\Form\Type\CartEditItemType;
use Maci\OrderBundle\Form\Type\CartPickupType;
use Maci\OrderBundle\Form\Type\CartRemoveItemType;
use Maci\OrderBundle\Form\Type\CartShippingAddressType;
use Maci\OrderBundle\Form\Type\MailType;
use Maci\OrderBundle\Form\Type\PaymentType;
use Maci\OrderBundle\Form\Type\ShippingType;

use Maci\MailerBundle\Entity\Mail;

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

    public function confirmedAction()
    {
        $list = $this->getDoctrine()->getManager()
            ->getRepository('MaciOrderBundle:Order')
            ->getConfirmed();

        return $this->render('MaciOrderBundle:Default:confirmed.html.twig', array(
            'list' => $list
        ));
    }

    public function adminShowAction($id)
    {
        $order = $this->getDoctrine()->getManager()
            ->getRepository('MaciOrderBundle:Order')
            ->findOneById($id);

        return $this->render('MaciOrderBundle:Default:show.html.twig', array(
            'order' => $order,
            'edit' => false
        ));
    }

    public function userShowAction($id)
    {
        $order = $this->getDoctrine()->getManager()
            ->getRepository('MaciOrderBundle:Order')
            ->findOneBy(array('id'=>$id,'user'=>$this->getUser()));

        return $this->render('MaciOrderBundle:Default:preview.html.twig', array(
            'order' => $order,
            'edit' => false
        ));
    }

    public function cartAction()
    {
        return $this->render('MaciOrderBundle:Default:cart.html.twig', array(
            'cart' => $this->get('maci.orders')->getCurrentCart()
        ));
    }

    public function showOrderAction($order, $edit = false)
    {
        return $this->render('MaciOrderBundle:Default:_show.html.twig', array(
            'order' => $order,
            'edit' => $edit
        ));
    }

    public function notfoundAction()
    {
        return $this->render('MaciOrderBundle:Default:notfound.html.twig');
    }

    public function addToCartAction(Request $request, $_product = false)
    {
        $form = $this->createForm(CartAddProductItemType::class, null, array(
            'product' => $_product
        ));

        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {

                $product = intval($form['product']->getData());

                if (is_numeric($product)) {
                    $product = $this->getDoctrine()->getManager()->getRepository('MaciProductBundle:Product')
                        ->findOneById(intval($product));
                }

                if (!is_object($product) || !$product->isAvailable()) {
                    return $this->redirect($this->generateUrl('maci_order_cart', array('error' => 'error.notAvailable')));
                }

                $quantity = intval($form['quantity']->getData());

                if ($quantity < 1 || !$product->checkQuantity($quantity)) {
                    return $this->redirect($this->generateUrl('maci_order_cart', array('error' => 'error.notAvailable')));
                }

                if ( $this->get('maci.orders')->addToCart($product, $quantity) ) {
                    return $this->redirect($this->generateUrl('maci_order_cart'));
                }

            } else {
                return $this->redirect($this->generateUrl('maci_order_cart', array('error' => 'error.formNoValid')));
            }
        }

        return $this->render('MaciOrderBundle:Default:_order_cart_add_product.html.twig', array(
            'product' => $_product,
            'form' => $form->createView()
        ));
    }

    public function editCartItemAction(Request $request, $id, $quantity = 1)
    {
        $form = $this->createForm(CartEditItemType::class);
        $form['quantity']->setData(intval($quantity));
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ( $this->get('maci.orders')->editItemQuantity(intval($id), intval($form['quantity']->getData()) ) ) {
                return $this->redirect($this->generateUrl('maci_order_cart', array('edited' => true)));
            } else {
                return $this->redirect($this->generateUrl('maci_order_cart', array('error' => true)));
            }
        }

        return $this->render('MaciOrderBundle:Default:_order_cart_edit_item.html.twig', array(
            'id' => $id,
            'form' => $form->createView()
        ));
    }

    public function removeCartItemAction(Request $request, $id)
    {
        $form = $this->createForm(CartRemoveItemType::class);
        $form->handleRequest($request);

        if ($form->isValid()) {
            if ( $this->get('maci.orders')->removeItem(intval($id)) ) {
                return $this->redirect($this->generateUrl('maci_order_cart', array('removed' => true)));
            } else {
                return $this->redirect($this->generateUrl('maci_order_cart', array('error' => true)));
            }
        }

        return $this->render('MaciOrderBundle:Default:_order_cart_remove_item.html.twig', array(
            'id' => $id,
            'form' => $form->createView()
        ));
    }

    public function cartGoCheckoutAction(Request $request, $option)
    {
        if (true === $this->get('security.authorization_checker')->isGranted('ROLE_USER')) {
            return $this->redirect($this->generateUrl('maci_order_checkout'));
        }

        $edit = $request->get('edit', false);
        $cart = $this->get('maci.orders')->getCurrentCart();

        if ($cart->getMail() !== null && !$edit) {
            return $this->redirect($this->generateUrl('maci_order_checkout'));
        }

        return $this->render('MaciOrderBundle:Default:gocheckout.html.twig');
    }

    public function cartCheckoutAction(Request $request)
    {
        $cart = $this->get('maci.orders')->getCurrentCart();

        if ( !$cart ) {
            return $this->redirect($this->generateUrl('maci_order_cart'));
        }

        if ($cart->getStatus() === 'complete' || $cart->getStatus() === 'confirm') {
            return $this->redirect($this->generateUrl('maci_order_invoice', array('id'=>$cart->getId())));
        }

        if ( $this->get('service_container')->getParameter('registration_required') && false === $this->get('security.authorization_checker')->isGranted('ROLE_USER') ) {
            return $this->redirect($this->generateUrl('maci_order_gocheckout'));
        }

        $checkout = array();
        $type = $cart->getCheckout();
        $type_array = $cart->getCheckoutArray();

        if ( !$type || !in_array($type, $type_array) ) {
            return $this->redirect($this->generateUrl('maci_order_cart'));
        }

        $edit = $request->get('checkout');
        $set = false;

        if ($cart->getBillingAddress() && $edit !== 'billingAddress') {
            $checkout['billingAddress'] = 'setted';
        } else {
            if ($set) {
                $checkout['billingAddress'] = 'toset';
            } else {
                $checkout['billingAddress'] = 'set';
                $set = true;
            }
        }

        if ($cart->checkShipment() && ( $type === 'full_checkout' || $type === 'checkout' || $type === 'fast_checkout' ) ) {
            if ($cart->getShippingAddress() && $edit !== 'shippingAddress') {
                $checkout['shippingAddress'] = 'setted';
            } else {
                if ($set) {
                    $checkout['shippingAddress'] = 'toset';
                } else {
                    $checkout['shippingAddress'] = 'set';
                    $set = true;
                }
            }
        } else {
            $checkout['shippingAddress'] = false;
        }

        if ($type === 'full_checkout') {
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
            $checkout['shipping'] = false;
        }

        if ($type === 'full_checkout') {
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
            $checkout['payment'] = false;
        }

        if ($set) {
            $checkout['confirm'] = 'toset';
        } else {
            $checkout['confirm'] = 'set';
        }

        $this->get('maci.orders')->setCartLocale( $request->getLocale() );
        $this->get('maci.orders')->refreshCartAmount();

        return $this->render('MaciOrderBundle:Default:checkout.html.twig', array(
            'checkout' => $checkout,
            'order' => $cart
        ));
    }

    public function cartSetCheckoutAction(Request $request, $checkout = null)
    {
        $cart = $this->get('maci.orders')->getCurrentCart();

        if ($checkout === null || !in_array($checkout, $cart->getCheckoutArray())) {
            $checkout = 'checkout';
        }

        if ($checkout === 'pickup') {
            $form = CartPickupType::class;
        } else if ($checkout === 'booking') {
            $form = CartBookingType::class;
        } else {
            $form = CartCheckoutType::class;
        }

        $form = $this->createForm($form, $cart);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $this->get('maci.orders')->setCartCheckout( $checkout );
            $payment = $form['payment']->getData();
            $payments = $this->get('maci.orders')->getPaymentsArray();
            $this->get('maci.orders')->setCartPayment( $payment, $payments[$payment]['cost'] );
            if ( $form->has('shipping') ) {
                $this->get('maci.orders')->setCartShipping( $form['shipping']->getData() );
            }
            return $this->redirect($this->generateUrl('maci_order_gocheckout', array('setted' => 'checkout')));
        }

        return $this->render('MaciOrderBundle:Default:_order_cart_checkout.html.twig', array(
            'checkout' => $checkout,
            'form' => $form->createView()
        ));
    }

    public function cartSetMailAction(Request $request)
    {
        $cart = $this->get('maci.orders')->getCurrentCart();
        $form = $this->createForm(MailType::class, $cart);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $this->get('maci.orders')->setCartMail( $form['mail']->getData() );
            return $this->redirect($this->generateUrl('maci_order_gocheckout', array('setted' => 'mail')));
        }

        return $this->render('MaciOrderBundle:Default:_order_mail.html.twig', array(
            'form' => $form->createView()
        ));
    }

    public function cartSetPaymentAction(Request $request)
    {
        $cart = $this->get('maci.orders')->getCurrentCart();
        $form = $this->createForm(PaymentType::class, $cart);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $payments = $this->get('maci.orders')->getPaymentsArray();
            $payment = $form['payment']->getData();
            $this->get('maci.orders')->setCartPayment( $payment, $payments[$payment]['cost'] );
            return $this->redirect($this->generateUrl('maci_order_gocheckout', array('setted' => 'payment')));
        }

        return $this->render('MaciOrderBundle:Default:_order_payment.html.twig', array(
            'form' => $form->createView()
        ));
    }

    public function cartSetShippingAction(Request $request)
    {
        $cart = $this->get('maci.orders')->getCurrentCart();
        $form = $this->createForm(ShippingType::class, $cart);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $this->get('maci.orders')->setCartShipping( $form['shipping']->getData() );
            return $this->redirect($this->generateUrl('maci_order_gocheckout', array('setted' => 'shipping')));
        }

        return $this->render('MaciOrderBundle:Default:_order_shipping.html.twig', array(
            'form' => $form->createView()
        ));
    }

    public function cartSetBillingAddressAction(Request $request)
    {
        $cart = $this->get('maci.orders')->getCurrentCart();
        $form = $this->createForm(CartBillingAddressType::class, $cart);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $address = $this->get('maci.addresses')->getAddress($form['billing_address']->getData());
            $this->get('maci.orders')->setCartBillingAddress($address);
            return $this->redirect($this->generateUrl('maci_order_gocheckout', array('setted' => 'billing')));
        }

        return $this->render('MaciOrderBundle:Default:_order_billing_address.html.twig', array(
            'form' => $form->createView()
        ));
    }

    public function cartSetShippingAddressAction(Request $request)
    {
        $cart = $this->get('maci.orders')->getCurrentCart();
        $form = $this->createForm(CartShippingAddressType::class, $cart);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $address = $this->get('maci.addresses')->getAddress($form['shipping_address']->getData());
            $this->get('maci.orders')->setCartShippingAddress($address);
            return $this->redirect($this->generateUrl('maci_order_gocheckout', array('setted' => 'shipping')));
        }

        return $this->render('MaciOrderBundle:Default:_order_shipping_address.html.twig', array(
            'form' => $form->createView()
        ));
    }

    public function cartConfirmAction(Request $request)
    {
        $cart = $this->get('maci.orders')->getCurrentCart();

        if (!$cart->checkConfirmation()) {

            return $this->redirect($this->generateUrl('maci_order_checkout', array('error' => 'error.order_not_valid')));

        } else if ($cart->getPayment() === 'paypal') {

            return $this->paypalForm($cart);

        } else if ( $cart->confirmOrder() ) {

            if ($cart->getUser()) {
                $to = $cart->getUser()->getEmail();
                $toint = $cart->getUser()->getUsername();
            } else {
                $to = $cart->getMail();
                $toint = $cart->getBillingAddress()->getName() .' '. $cart->getBillingAddress()->getSurname();
            }

            $em = $this->getDoctrine()->getManager();

            $mail = new Mail();

            $mail
                ->setName('Order Confirmation: ' . $cart->getCode())
                ->setType('notify')
                ->setSubject('Order Confirmation')
                ->setFrom($this->get('service_container')->getParameter('server_email'), $this->get('service_container')->getParameter('server_email_int'))
                ->addTo($to, $toint)
                ->setLocale($request->getLocale())
                ->setContent($this->renderView('MaciOrderBundle:Email:confirmation_email.html.twig', array('mail' => $mail, 'order' => $cart)), 'text/html')
            ;

            $message = $this->get('maci.mailer')->getSwiftMessage($mail);

            $notify = clone $message;

            if ($cart->getUser()) {
                $mail->setUser($cart->getUser());
            }

            $mail->end();

            // ---> send message
            if ($this->container->get('kernel')->getEnvironment() == "prod") $this->get('mailer')->send($message);

            $notify->addTo($this->get('service_container')->getParameter('order_email'));

            // ---> send notify
            if ($this->container->get('kernel')->getEnvironment() == "prod") $this->get('mailer')->send($notify);

            $em->persist($mail);

            $em->flush();

            $page = $em->getRepository('MaciPageBundle:Page')
                ->findOneByPath('order-complete');

            if ($page) {
                return $this->redirect($this->generateUrl('maci_page', array('path' => 'order-complete')));
            }

            return $this->redirect($this->generateUrl('maci_order_checkout_complete'));

        }

        return $this->redirect($this->generateUrl('maci_order_checkout', array('error' => true)));
    }

    public function cartCompleteAction(Request $request)
    {
        return $this->render('MaciOrderBundle:Default:complete.html.twig');
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

        $page = $this->getDoctrine()->getManager()
            ->getRepository('MaciPageBundle:Page')
            ->findOneByPath('order-complete-paypal');

        if ($page) {
            return $this->redirect($this->generateUrl('maci_page', array('path' => 'order-complete-paypal')));
        }

        $page = $this->getDoctrine()->getManager()
            ->getRepository('MaciPageBundle:Page')
            ->findOneByPath('order-complete');

        if ($page) {
            return $this->redirect($this->generateUrl('maci_page', array('path' => 'order-complete')));
        }

        return $this->redirect($this->generateUrl('maci_order_checkout_complete'));
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
            false === $this->get('security.authorization_checker')->isGranted('ROLE_SUPER_ADMIN')
        ) {
            return $this->redirect($this->generateUrl('maci_order_homepage', array('error' => 'order.nomap')));
        }

        return $this->render('MaciOrderBundle:Default:invoice.html.twig', array(
            'order' => $order
        ));
    }

    public function paypalForm($order)
    {
        $form = $this->createFormBuilder($order);

        if ($this->get('service_container')->getParameter('shop_is_live')) {

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
            ->add('notify_url', 'hidden', array('mapped' => false, 'data' => $this->generateUrl('maci_paypal_ipn', array(), true)))
            ->getForm();

        return $this->render('MaciOrderBundle:Default:_paypal.html.twig', array(
            'form' => $form->createView(),
        ));
    }
}

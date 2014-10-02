<?php

namespace Maci\OrderBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

use Maci\OrderBundle\Entity\Order;
use Maci\OrderBundle\Entity\Item;

use Maci\AddressBundle\Entity\Address;

class DefaultController extends Controller
{
    public function indexAction()
    {
        if (false === $this->get('security.context')->isGranted('ROLE_USER')) {
            throw new AccessDeniedException();
        }

        $list = $this->getDoctrine()->getManager()
            ->getRepository('MaciOrderBundle:Order')
            ->findBy(array( 'user' => $this->getUser(), 'status' => array('new', 'confirmed')));

        return $this->render('MaciOrderBundle:Default:index.html.twig', array('list' => $list));
    }

    public function cartAction()
    {
        if (false === $this->get('security.context')->isGranted('ROLE_USER')) {
            throw new AccessDeniedException();
        }

        $cart = $this->getDoctrine()->getManager()
            ->getRepository('MaciOrderBundle:Order')
            ->findOneBy(array( 'user' => $this->getUser(), 'type' => 'cart', 'status' => 'current' ));

        return $this->render('MaciOrderBundle:Default:cart.html.twig', array('cart' => $cart));
    }

    public function invoiceAction($id)
    {
        if (false === $this->get('security.context')->isGranted('ROLE_USER')) {
            throw new AccessDeniedException();
        }

        $order = $this->getDoctrine()->getManager()
            ->getRepository('MaciOrderBundle:Order')
            ->findOneById($id);

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

    public function buyFormAction(Request $request)
    {
        $order = new Order();

        return $this->renderForm($request, $order, array(
            'order' => $order,
            'label' => 'Buy',
            'action' => $this->generateUrl('maci_order_preview')
        ));
    }

    public function previewAction(Request $request)
    {
        $order = new Order();

        return $this->renderForm($request, $order, array(
            'order' => $order,
            'label' => 'Checkout Page',
            'preview' => 'preview',
            'action' => $this->generateUrl('maci_order_save_and_pay')
        ));
    }

    public function saveAndPayAction(Request $request)
    {
        $order = new Order();



        $this->editAction($request, $order, array(
            'order' => $order,
            'action' => '#'
        ));

        return $this->redirect($this->generateUrl('maci_order_checkout', array('id' => $order->getId())));
    }

    public function checkoutAction(Request $request, $id)
    {
        if (false === $this->get('security.context')->isGranted('ROLE_USER')) {
            throw new AccessDeniedException();
        }

        $order = $this->getDoctrine()->getManager()
            ->getRepository('MaciOrderBundle:Order')
            ->findOneById($id);

        if ( !$order ) {
            return $this->redirect($this->generateUrl('maci_order_homepage', array('error' => 'order.notfound')));
        }

        $checkout = 'checkout';

        return $this->editAction($request, null, array(
            'order' => $order,
            'checkout' => $checkout,
            'label' => 'Confirm',
            'action' => $this->generateUrl('maci_order_checkout', array('id' => $order->getId()))
        ));
    }

    public function cartFormAction(Request $request)
    {
        if (true === $this->get('security.context')->isGranted('ROLE_USER')) {

            $cart = $this->getDoctrine()->getManager()
                ->getRepository('MaciOrderBundle:Order')
                ->findOneBy(array( 'user' => $this->getUser(), 'type' => 'cart', 'status' => 'current' ));

            if (!$cart) {

                $cart = new Order();
                $cart->setName('My Cart');
                $cart->setCode(
                    'CRT-' . rand(10000, 99999) . '-' . 
                    date('h') . date('i') . date('s') . date('m') . date('d') . date('Y')
                );
                $cart->setStatus('current');
                $cart->setType('cart');
                $cart->setUser($this->getUser());

            }

        } else {

            $cart = new Order();

        }

        return $this->editAction($request, null, array(
            'order' => $cart,
            'add' => true,
            'label' => 'Add to Cart',
            'action' => $this->generateUrl('maci_order_cart_form')
        ));
    }

    public function cartCheckoutAction(Request $request)
    {
        if (false === $this->get('security.context')->isGranted('ROLE_USER')) {
            throw new AccessDeniedException();
        }

        $order = $this->getDoctrine()->getManager()
            ->getRepository('MaciOrderBundle:Order')
            ->findOneBy(array( 'user' => $this->getUser(), 'type' => 'cart', 'status' => 'current' ));

        if ( !$order ) {
            return $this->redirect($this->generateUrl('maci_order_homepage', array('error' => 'order.nocart')));
        }

        $req_address = $request->get('address');
        $address = false;

        if ($req_address && is_numeric($req_address)) {

            $address = $this->getDoctrine()->getManager()
                ->getRepository('MaciAddressBundle:Address')
                ->findOneBy(array( 'user' => $this->getUser(), 'id' => $req_address ));

        }

        $checkout = 'checkout';

        if ($checkout === 'checkout' && !$order->getShipping()) { // || $request->getParameter('edit') === 'shipping'

            if ($address) {
                $order->setShipping($address);
            } else {
                $checkout = 'shipping';
            }

        }

        if ($checkout === 'checkout' && !$order->getBilling()) { // || $request->getParameter('edit') === 'billing'

            if ($address) {
                $order->setBilling($address);
            } else {
                $checkout = 'billing';
            }

        }

        return $this->editAction($request, null, array(
            'order' => $order,
            'add' => false,
            'checkout' => $checkout,
            'label' => 'Confirm',
            'action' => $this->generateUrl('maci_order_cart_checkout')
        ));
    }

    public function editAction(Request $request, $id = 0, $options = array())
    {

        if ( array_key_exists('order', $options) && $options['order'] ) {
            $order = $options['order'];
        } elseif ($id) {
            $order = $this->getDoctrine()->getManager()
                ->getRepository('MaciOrderBundle:Order')
                ->findOneById($id);
        }

        if (
            ( $order->getUser() && $order->getUser()->getId() !== $this->getUser()->getId() ) &&
            false === $this->get('security.context')->isGranted('ROLE_SUPER_ADMIN')
        ) {
            return $this->redirect($this->generateUrl('maci_order_homepage', array('error' => 'order.security_exception')));
        }

        if ( !array_key_exists('label', $options) || !strlen($options['label']) ) {
            $options['label'] = 'Add To Item';
        }

        if ( !array_key_exists('action', $options) || !strlen($options['action']) ) {
            $options['action'] = $this->generateUrl('maci_order_checkout', array('id' => $order->getId()));
        }

        $action = $this->renderForm($request, $order, $options);

        if (true === $this->get('security.context')->isGranted('ROLE_USER')) {

            if (!$order->getUser()) {
                $order->setUser($this->getUser());
            }

            if (!$order->getCode()) {
                $order->setCode(
                    'ORD-' . rand(10000, 99999) . '-' . 
                    date('h') . date('i') . date('s') . date('m') . date('d') . date('Y')
                );
            }

            if (!$order->getStatus()) {
                $order->setStatus('new');
            }

            if (!$order->getType()) {
                $order->setType('order');
            }

            if (!$order->getName()) {
                $order->setName('Order ' . date('m') . date('d') . date('Y'));
            }

            $em = $this->getDoctrine()->getManager();

            foreach ($order->getItems() as $item) {
                $em->persist($item);
            }

            $em->persist($order);

            $em->flush();

        }

        return $action;
    }

    public function setAddressAction(Request $request, $id)
    {
        if (false === $this->get('security.context')->isGranted('ROLE_USER')) {
            throw new AccessDeniedException();
        }

        $order = $this->getDoctrine()->getManager()
            ->getRepository('MaciOrderBundle:Order')
            ->findOneById($id);

        if ( !$order ) {
            return $this->redirect($this->generateUrl('maci_order_cart_checkout', array('error' => 'order.noorder')));
        }

        if (
            ( $order->getUser() && $order->getUser()->getId() !== $this->getUser()->getId() ) &&
            false === $this->get('security.context')->isGranted('ROLE_SUPER_ADMIN')
        ) {
            return $this->redirect($this->generateUrl('maci_order_cart_checkout', array('error' => 'order.security_exception')));
        }

        return $this->redirect($this->generateUrl('maci_order_cart_checkout', array('added' => true)));
    }

    public function renderForm(Request $request, Order $order, $options = array())
    {
        $isNew = false;

        $renderPage = false;

        $addresses = false;

        $address_form = $this->createForm('address', new Address());

        if ( $order->getId() === null ) {
            $isNew = true;
        }

        if (array_key_exists('action', $options)) {
            $action = $options['action'];
        } else {
            $action = '#';
        }

        if (array_key_exists('checkout', $options)) {
            $checkout = $options['checkout'];
        } else {
            $checkout = 'checkout';
        }

        if (array_key_exists('label', $options)) {
            $label = $options['label'];
        } else {
            $label = 'Buy';
        }

        $form = $this->createFormBuilder($order)
            ->setAction($action)
            ->add('map', 'hidden', array('mapped' => false, 'required' => false))
            ->add('add', 'submit', array('label' => $label))
            ->getForm();

        $form->handleRequest($request);

        if ($form->isValid()) {

            if (false === $this->get('security.context')->isGranted('ROLE_USER')) {
                throw new AccessDeniedException();
            }

            $map = $form->get('map')->getData();

            if ( !$map ) {
                return $this->redirect($this->generateUrl('maci_order_homepage', array('error' => 'order.nomap')));
            }

            $order = $this->setOrderFromMap($map, $order);

            $items = $order->getItems();

            if ( !$items ) {
                return $this->redirect($this->generateUrl('maci_order_homepage', array('error' => 'order.noitems')));
            }

            if (array_key_exists('add', $options) && $options['add']) {

                if ($order->getType() === 'cart') {
                    return $this->redirect($this->generateUrl('maci_order_cart'));
                }

                return $this->redirect($this->generateUrl('maci_order_show', array('id' => $order->getId())));
            }

            if (array_key_exists('confirm', $map) && $map['confirm']) {

                $order->setStatus('confirmed');

                if ( $isNew ) {

                    $em = $this->getDoctrine()->getManager();

                    foreach ($items as $item) {
                        $em->persist($item);
                    }

                    $em->persist($order);

                    $em->flush();

                }

                return $this->redirect($this->generateUrl('maci_order_invoice', array('id' => $order->getId())));

            }

            $renderPage = true;
        }

        if (!$renderPage && array_key_exists('checkout', $options) && $options['checkout']) {

            $renderPage = true;

        }

        if ($renderPage) {

            if (array_key_exists('preview', $options) && $options['preview']) {

                return $this->render('MaciOrderBundle:Default:preview.html.twig', array(
                    'form' => $form->createView(),
                    'order' => $order,
                ));

            }

            if (!$addresses && true === $this->get('security.context')->isGranted('ROLE_USER')) {

                $addresses = $this->getDoctrine()->getManager()
                    ->getRepository('MaciAddressBundle:Address')
                    ->findByUser($this->getUser());

            }

            return $this->render('MaciOrderBundle:Default:checkout.html.twig', array(
                'addresses' => $addresses,
                'address_form' => $address_form->createView(),
                'form' => $form->createView(),
                'checkout' => $checkout,
                'order' => $order,
            ));

        }

        return $this->render('MaciOrderBundle:Default:_form.html.twig', array(
            'form' => $form->createView(),
            'order' => $order,
        ));
    }

    public function setOrderFromMap($map, $order)
    {
        if (array_key_exists('name', $map)) {
            $order->setName( $map['name'] );
        }

        if (array_key_exists('products', $map)) {

            foreach ($map['products'] as $product) {

                if ( is_numeric($id = $product['id']) ) {

                    $product = $this->getDoctrine()->getManager()
                        ->getRepository('MaciProductBundle:Product')
                        ->findOneById($id);

                    if ($product) {

                        $item = new Item();

                        $item->setProduct($product);

                        $item->setOrder($order);
                        $order->addItem($item);

                    }
                    
                }

            }

        }

        return $order;
    }
/*
    public function paypalCompleteAction() {
        $tx = $this->getRequest()->get('tx');
        if (!$tx) {
            return $this->redirect($this->generateUrl('notFound'));
        }

        $pdt = $this->get('orderly_pay_pal_pdt');
        $pdtArray = $pdt->getPdt($tx);

        $status = 'unknown';
        if (isset($pdtArray['payment_status'])) {
            $status = $pdtArray['payment_status'];
        }

        return $this->render('YourOwnBundle:YourPaypal:complete.html.twig',
            array('status' => $status)
        );
    }
*/
    public function paypalFormAction($order)
    {
        $form = $this->createFormBuilder($order)
            ->setAction('https://www.paypal.com/cgi-bin/webscr')
            ->add('cmd', 'hidden', array('mapped' => false, 'data' => '_xclick'))
            ->add('lc', 'hidden', array('mapped' => false, 'data' => 'IT'))
            ->add('business', 'hidden', array('mapped' => false, 'data' => $this->get('service_container')->getParameter('maciorder_paypalform_business')))
            ->add('item_name', 'hidden', array('mapped' => false, 'data' => $order->getName()))
            ->add('item_number', 'hidden', array('mapped' => false, 'data' => $order->getId()))
            ->add('custom', 'hidden', array('mapped' => false, 'data' => $order->getId()))
            ->add('amount', 'hidden', array('mapped' => false, 'data' => number_format($order->getAmount(), 2, '.', '')))
            ->add('currency_code', 'hidden', array('mapped' => false, 'data' => 'EUR'))
            ->add('button_subtype', 'hidden', array('mapped' => false, 'data' => 'services'))
            ->add('no_note', 'hidden', array('mapped' => false, 'data' => '1'))
            ->add('no_shipping', 'hidden', array('mapped' => false, 'data' => '1'))
            ->add('rm', 'hidden', array('mapped' => false, 'data' => '1'))
            ->add('return', 'hidden', array('mapped' => false, 'data' => $this->generateUrl('maci_order_paypal_notification', array('id' => $order->getId(), 'payment' => 'complete'))))
            ->add('cancel_return', 'hidden', array('mapped' => false, 'data' => $this->generateUrl('maci_order_homepage')))
            ->add('notify_url', 'hidden', array('mapped' => false, 'data' => $this->generateUrl('maci_order_paypal_notification')))
            ->add('add', 'submit')
            ->getForm();

        return $this->render('MaciOrderBundle:Default:_paypal.html.twig', array(
            'form' => $form->createView(),
        ));
    }
}

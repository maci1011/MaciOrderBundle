<?php

namespace Maci\OrderBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

use Maci\OrderBundle\Entity\Order;
use Maci\OrderBundle\Entity\Item;
use Maci\MediaBundle\Entity\Permission;

use Maci\AddressBundle\Entity\Address;

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

    public function cartAction()
    {
        $om = $this->getDoctrine()->getManager();
        $cart = $om->getRepository('MaciOrderBundle:Order')
            ->findOneBy(array( 'user' => $this->getUser(), 'type' => 'cart', 'status' => 'current' ));

        if ($cart) {
            $cart->refreshAmount();
            $om->flush();
        }

        return $this->render('MaciOrderBundle:Default:cart.html.twig', array(
            'cart' => $cart
        ));
    }

    public function checkoutAction(Request $request, $id)
    {
        $om = $this->getDoctrine()->getManager();

        $order = $om->getRepository('MaciOrderBundle:Order')
            ->findOneById($id);

        if (!$order) {
            return $this->redirect($this->generateUrl('maci_order_notfound'));
        }

        if ($request->get('set')) {
            return $this->set($order, $request);
        }

        $checkout = ( $request->get('checkout') ? $request->get('checkout') : 'checkout' );

        if ( $checkout === 'checkout' ) {
            if ( $order->checkShipment() && !$order->getShipping() ) {
                $checkout = 'shipping';
            } else if (!$order->getBilling() ) {
                $checkout = 'billing';
            } else {
                $checkout = 'confirm';
            }
        }

        $order->refreshAmount();

        $om->flush();

        return $this->render('MaciOrderBundle:Default:checkout.html.twig', array(
            'checkout' => $checkout,
            'order' => $order
        ));
    }

    public function devAction(Request $request, $id)
    {
        $om = $this->getDoctrine()->getManager();
        $order = $om->getRepository('MaciOrderBundle:Order')
            ->findOneById($id);

        $order->completeOrder();

        $docs = $order->getOrderDocuments();

        if (count($docs)) {
            foreach ($docs as $id => $document) {
                $permission = $om->getRepository('MaciMediaBundle:Permission')->findOneBy(array(
                    'user' => $this->getUser(),
                    'media' => $document
                ));

                if ($permission) {
                    $permission->setStatus('active');
                } else {
                    $permission = new Permission;
                    $permission->setUser($this->getUser());
                    $permission->setMedia($document);
                    $permission->setStatus('active');
                    $permission->setNote('Created by Order ['.$order->getId().'].');
                    $om->persist($permission);
                }

            }
        }

        $om->flush();

        // return $this->redirect($this->generateUrl('maci_order'));

        return $this->render('MaciOrderBundle:Default:invoice.html.twig', array(
            'order' => $order
        ));
    }

    public function paypalCompleteAction($id)
    {
        $order = $this->getDoctrine()->getManager()
            ->getRepository('MaciOrderBundle:Order')
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
        }

        $page = $this->getDoctrine()->getManager()
            ->getRepository('MaciPageBundle:Page')
            ->findOneByPath('order-complete');

        if ($page) {
            return $this->render('MaciPageBundle:Default:page.html.twig', array('page' => $page));
        }

        return $this->render('MaciOrderBundle:Default:complete.html.twig');
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

    public function addToCartAction(Request $request)
    {
        $om = $this->getDoctrine()->getManager();

        $product_id = $request->get('product');

        $product = $om->getRepository('MaciProductBundle:Product')
            ->findOneById($product_id);

        if (!$product) {
            return $this->redirect($this->generateUrl('maci_order_notfound'));
        }

        $cart = $om->getRepository('MaciOrderBundle:Order')
            ->findOneBy(array( 'user' => $this->getUser(), 'type' => 'cart', 'status' => 'current' ));

        if (!$cart) {

            $cart = new Order;
            $cart->setName('My Cart');
            $cart->setCode(
                'CRT-' . rand(10000, 99999) . '-' . 
                date('h') . date('i') . date('s') . date('m') . date('d') . date('Y')
            );
            $cart->setStatus('current');
            $cart->setType('cart');
            $cart->setUser($this->getUser());

            $om->persist($cart);

        }

        $item = new Item;

        $variants_id = $request->get('variants');

        if ($quantity = $request->get('quantity')) {
            $item->setQuantity($quantity);
        }

        $item->setProduct($product);

        foreach ($variants_id as $id) {
            if (in_array($id, $product->getVariantsId())) {
                $variant = $om->getRepository('MaciProductBundle:Variant')
                    ->findOneById($id);
                if ($variant) {
                    $item->addVariant($variant);
                }
            }
        }

        if (!$item->checkProductQuantity($quantity) || !$item->checkVariantsQuantity($quantity)) {
            return $this->redirect($this->generateUrl('maci_order_cart', array('error' => 'error.noquantity')));
        }

        $item->setOrder($cart);

        $cart->refreshAmount();

        $om->persist($item);

        $om->flush();

        return $this->redirect($this->generateUrl('maci_order_cart'));
    }

    public function removeCartItemAction(Request $request, $id)
    {
        if ($this->removeItem($request, $id)) {
            return $this->redirect($this->generateUrl('maci_order_cart', array('removed' => true)));
        } else {
            return $this->redirect($this->generateUrl('maci_order_cart', array('error' => true)));
        }
    }

    public function removeItem(Request $request, $id)
    {
        $item = $this->getDoctrine()->getEntityManager()->getRepository('MaciOrderBundle:Item')
            ->findOneBy(array(
                'id' => $id
            ))
        ;

        if (
            !$item ||
            ( $item->getOrder()->getUser() && $item->getOrder()->getUser()->getId() !== $this->getUser()->getId() ) &&
            false === $this->get('security.context')->isGranted('ROLE_SUPER_ADMIN')
        ) {
            return false;
        }

        $em = $this->getDoctrine()->getEntityManager();

        $em->remove($item);

        $em->flush();

        return true;
    }

    public function set($order, $request)
    {
        $set = $request->get('set');
        $om = $this->getDoctrine()->getManager();
        $address = $om->getRepository('MaciAddressBundle:Address')
                ->findOneBy(array('user' => $this->getUser(), 'id' => $request->get('address')));
        if (!$address) {
            return $this->redirect($this->generateUrl('maci_order_checkout', array('error' => 'address.notfound')));
        }
        if ($set === 'shipping') {
            $order->setShipping($address);
            $om->flush();
            return $this->redirect($this->generateUrl('maci_order_checkout', array('id' => $order->getId(), 'setted' => 'shipping')));
        } else if ($set === 'billing') {
            $order->setBilling($address);
            $om->flush();
            return $this->redirect($this->generateUrl('maci_order_checkout', array('id' => $order->getId(), 'setted' => 'billing')));
        }
        return $this->redirect($this->generateUrl('maci_order_checkout', array('error' => 'checkout.nothingsetted')));
    }

    public function notfoundAction($order)
    {
        return $this->render('MaciOrderBundle:Default:notfound.html.twig');
    }

    public function paypalFormAction($order)
    {
        $form = $this->createFormBuilder($order)
            // ->setAction('https://www.paypal.com/cgi-bin/webscr')
            ->setAction('https://sandbox.paypal.com/cgi-bin/webscr')
            ->add('cmd', 'hidden', array('mapped' => false, 'data' => '_xclick'))
            ->add('lc', 'hidden', array('mapped' => false, 'data' => 'IT'))
            // ->add('business', 'hidden', array('mapped' => false, 'data' => $this->get('service_container')->getParameter('maciorder_paypalform_business')))
            ->add('business', 'hidden', array('mapped' => false, 'data' => $this->get('service_container')->getParameter('maciorder_paypalform_business_fac')))
            ->add('item_name', 'hidden', array('mapped' => false, 'data' => $order->getName()))
            ->add('item_number', 'hidden', array('mapped' => false, 'data' => 1))
            ->add('custom', 'hidden', array('mapped' => false, 'data' => $order->getId()))
            ->add('amount', 'hidden', array('mapped' => false, 'data' => number_format($order->getAmount(), 2, '.', '')))
            ->add('currency_code', 'hidden', array('mapped' => false, 'data' => 'EUR'))
            ->add('button_subtype', 'hidden', array('mapped' => false, 'data' => 'services'))
            ->add('no_note', 'hidden', array('mapped' => false, 'data' => '1'))
            ->add('no_shipping', 'hidden', array('mapped' => false, 'data' => '1'))
            ->add('rm', 'hidden', array('mapped' => false, 'data' => '1'))
            ->add('return', 'hidden', array('mapped' => false, 'data' => $this->generateUrl('maci_order_paypal_complete', array('id' => $order->getId()))))
            ->add('cancel_return', 'hidden', array('mapped' => false, 'data' => $this->generateUrl('maci_order')))
            ->add('notify_url', 'hidden', array('mapped' => false, 'data' => '/ipn/ipn-no-notification'))
            ->getForm();

        return $this->render('MaciOrderBundle:Default:_paypal.html.twig', array(
            'form' => $form->createView(),
        ));
    }
}

<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Purchase;
use App\Cart\CartService;
use App\Stripe\StripeService;
use App\Form\CartConfirmationType;
use App\Event\PurchaseSuccessEvent;
use App\Purchase\PurchasePersister;
use App\Repository\PurchaseRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class PurchaseController extends AbstractController
{
    protected $cartService;
    protected $em;
    protected $persister;

    public function __construct(CartService $cartService, EntityManagerInterface $em, PurchasePersister $persister)
    {
        $this->cartService = $cartService;
        $this->em = $em;
        $this->persister = $persister;
    }

    #[Route('/profile/purchases', name: 'purchase_list')]
    public function list()
    {
        /** @var User */
        $user = $this->getUser();

        return $this->render('purchase/list.html.twig', [
            'purchases' => $user->getPurchases(),
        ]);
    }


    // NUMERO 2
    #[Route("profile/purchase/confirm", name: "purchase_confirm")]
    public function confirm(Request $request)
    {
        //
        $form = $this->createForm(CartConfirmationType::class);

        $form->handleRequest($request);

        if (!$form->isSubmitted()) {
            $this->addFlash('danger', 'Attention vous n\'avez pas remplie le formulaire');
            return $this->redirectToRoute('cart_show');
        }

        //
        $user = $this->getUser();

        //
        $cartItems = $this->cartService->getDetailedCart();

        if (count($cartItems) === 0) {
            $this->addFlash('warning', 'Vous ne pouvez pas confirmer une commande avec un panier vide');
            return $this->redirectToRoute('cart_show');
        }

        /** @var Purchase */
        $purchase = $form->getData();

        $this->persister->storePurchase($purchase);

        return $this->redirectToRoute('purchase_payment_form', [
            'id' => $purchase->getId()
        ]);
    }

    //
    #[Route("/purchase/pay/{id}", name: "purchase_payment_form")]
    public function showCardForm($id, PurchaseRepository $purchaseRepository, StripeService $stripeService)
    {
        $purchase = $purchaseRepository->find($id);

        if (
            !$purchase ||
            ($purchase && $purchase->getUser() !== $this->getUser()) ||
            ($purchase && $purchase->getStatus() === Purchase::STATUS_PAID)
        ) {
            return $this->redirectToRoute('card_show');
        }

        $intent = $stripeService->getPaymentIntent($purchase);

        return $this->render('purchase/payment.html.twig', [
            'clientSecret' => $intent->client_secret,
            'purchase' => $purchase,
            'stripePublicKey' => $stripeService->getPublicKey(),
        ]);
    }

    #[Route("/purchase/success/{id}", name: "purchase_payment_success")]
    public function success($id, PurchaseRepository $purchaseRepository, EntityManagerInterface $em, CartService $cartService, EventDispatcherInterface $dispatcher)
    {
        // 1
        $purchase = $purchaseRepository->find($id);

        // 2
        if (
            !$purchase ||
            ($purchase && $purchase->getUser() !== $this->getUser()) ||
            ($purchase && $purchase->getStatus() === Purchase::STATUS_PAID)
        ) {
            $this->addFlash("warning", "La commande n'existe pas.");
            return $this->redirectToRoute('purchase_list');
        }

        $purchase->setStatus(Purchase::STATUS_PAID);
        $em->flush();

        // 3
        $cartService->empty();

        // 4
        $purchaseEvent = new PurchaseSuccessEvent($purchase);
        $dispatcher->dispatch($purchaseEvent, 'purchase.success');

        // 5
        $this->addFlash('success', 'La commande à été payer et confirmer');
        return $this->redirectToRoute('purchase_list');
    }
}

<?php

namespace App\Stripe;

use App\Entity\Purchase;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class StripeService extends AbstractController
{
    protected $publicKey;
    protected $secretKey;

    public function __construct()
    {
        $this->publicKey = "pk_test_Oeztmtu3IMryLYsD5BbOxWAy";
        $this->secretKey = "sk_test_GQpe2lgnrf1J5Ne3DgewdZip";
    }

    public function getPublicKey(): string
    {
        return $this->publicKey;
    }

    public function getPaymentIntent(Purchase $purchase)
    {
        \Stripe\Stripe::setApiKey($this->secretKey);

        return \Stripe\PaymentIntent::create([
            'amount' => $purchase->getTotal(),
            'currency' => 'eur',
        ]);
    }
}

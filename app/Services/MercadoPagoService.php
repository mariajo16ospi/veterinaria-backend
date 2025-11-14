<?php

namespace App\Services;

class MercadoPagoService
{
    protected $accessToken;
    protected $publicKey;

    public function __construct()
    {
        $this->accessToken = config('services.mercadopago.access_token');
        $this->publicKey = config('services.mercadopago.public_key');
    }

    public function getAccessToken()
    {
        return $this->accessToken;
    }

    public function getPublicKey()
    {
        return $this->publicKey;
    }
}

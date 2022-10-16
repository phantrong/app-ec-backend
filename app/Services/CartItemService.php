<?php

namespace App\Services;

use App\Repositories\CartItem\CartItemRepository;

class CartItemService
{
    private CartItemRepository $cartItemRepository;

    public function __construct(CartItemRepository $cartItemRepository)
    {
        $this->cartItemRepository = $cartItemRepository;
    }

    public function deleteCateItemByProduct($productId)
    {
        return $this->cartItemRepository->deleteCateItemByProduct($productId);
    }
}

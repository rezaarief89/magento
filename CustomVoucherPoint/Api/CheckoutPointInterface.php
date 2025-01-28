<?php

namespace Fef\CustomVoucherPoint\Api;

interface CheckoutPointInterface
{
    /**
     * Adds a reward points to a specified cart.
     *
     * @param int $cartId The cart ID.
     * @param float $points
     *
     * @return mixed
     */
    public function set($cartId, $points);
    
    /**
     * Deletes a points from a specified cart.
     *
     * @param int $cartId The cart ID.
     * @return bool
     */
    public function remove($cartId);
}

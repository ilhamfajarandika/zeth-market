<?php

namespace App\Exceptions;

use Exception;

/**
 * Dilempar saat tidak ada akun dengan status available untuk sebuah produk.
 */
class OutOfStockException extends Exception {}

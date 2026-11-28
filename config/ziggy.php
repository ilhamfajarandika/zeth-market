<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Routes Exposed to the Storefront
    |--------------------------------------------------------------------------
    |
    | `@routes` prints route names and URIs in the page source, so only the
    | storefront routes used by the Vue pages are listed. Admin (Filament),
    | webhook and Livewire routes stay hidden.
    |
    */

    'only' => [
        'home',
        'game.show',
        'checkout.store',
        'nickname.check',
        'invoice.show',
        'orders.index',
        'login',
        'login.store',
        'register',
        'register.store',
        'logout',
    ],

    /*
    | The route() function ships in the Vite bundle (`ziggy-js`), so `@routes`
    | only prints the route list.
    */

    'skip-route-function' => true,

];

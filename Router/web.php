<?php

return [
    'auth/login' => [
        'controller' => 'Auth',
        'action' => 'showLogin',
    ],
    'auth/login.submit' => [
        'controller' => 'Auth',
        'action' => 'login',
    ],
    'auth/register' => [
        'controller' => 'Auth',
        'action' => 'showRegister',
    ],
    'auth/register.submit' => [
        'controller' => 'Auth',
        'action' => 'register',
    ],
    'auth/logout' => [
        'controller' => 'Auth',
        'action' => 'logout',
    ],
    'home' => [
        'controller' => 'Home',
        'action' => 'index',
    ],
    'vehicles' => [
        'controller' => 'Vehicle',
        'action' => 'index',
    ],
    'vehicles/create' => [
        'controller' => 'Vehicle',
        'action' => 'create',
    ],
    'vehicles/store' => [
        'controller' => 'Vehicle',
        'action' => 'store',
    ],
    'vehicles/edit' => [
        'controller' => 'Vehicle',
        'action' => 'edit',
    ],
    'vehicles/show' => [
        'controller' => 'Vehicle',
        'action' => 'show',
    ],
    'vehicles/share' => [
        'controller' => 'Vehicle',
        'action' => 'share',
    ],
    'vehicles/update' => [
        'controller' => 'Vehicle',
        'action' => 'update',
    ],
    'vehicles/delete' => [
        'controller' => 'Vehicle',
        'action' => 'delete',
    ],
    'maintenance' => [
        'controller' => 'Maintenance',
        'action' => 'index',
    ],
    'maintenance/create' => [
        'controller' => 'Maintenance',
        'action' => 'create',
    ],
    'maintenance/store' => [
        'controller' => 'Maintenance',
        'action' => 'store',
    ],
    'maintenance/edit' => [
        'controller' => 'Maintenance',
        'action' => 'edit',
    ],
    'maintenance/update' => [
        'controller' => 'Maintenance',
        'action' => 'update',
    ],
    'maintenance/delete' => [
        'controller' => 'Maintenance',
        'action' => 'delete',
    ],
    'rentals' => [
        'controller' => 'Rental',
        'action' => 'index',
    ],
    'rentals/create' => [
        'controller' => 'Rental',
        'action' => 'create',
    ],
    'rentals/store' => [
        'controller' => 'Rental',
        'action' => 'store',
    ],
    'rentals/edit' => [
        'controller' => 'Rental',
        'action' => 'edit',
    ],
    'rentals/update' => [
        'controller' => 'Rental',
        'action' => 'update',
    ],
    'rentals/delete' => [
        'controller' => 'Rental',
        'action' => 'delete',
    ],
];

<?php

// Mapa sencillo de rutas => controlador/acción.
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
    'auth/denied' => [
        'controller' => 'Auth',
        'action' => 'accessDenied',
    ],
    'home' => [
        'controller' => 'Home',
        'action' => 'index',
    ],
    'audit' => [
        'controller' => 'Audit',
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
    'vehicles/status' => [
        'controller' => 'Vehicle',
        'action' => 'updateStatus',
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
    'maintenance/plans' => [
        'controller' => 'MaintenancePlan',
        'action' => 'index',
    ],
    'maintenance/plans/create' => [
        'controller' => 'MaintenancePlan',
        'action' => 'create',
    ],
    'maintenance/plans/store' => [
        'controller' => 'MaintenancePlan',
        'action' => 'store',
    ],
    'maintenance/plans/edit' => [
        'controller' => 'MaintenancePlan',
        'action' => 'edit',
    ],
    'maintenance/plans/update' => [
        'controller' => 'MaintenancePlan',
        'action' => 'update',
    ],
    'maintenance/plans/delete' => [
        'controller' => 'MaintenancePlan',
        'action' => 'delete',
    ],
    'obligations' => [
        'controller' => 'VehicleObligation',
        'action' => 'index',
    ],
    'obligations/create' => [
        'controller' => 'VehicleObligation',
        'action' => 'create',
    ],
    'obligations/store' => [
        'controller' => 'VehicleObligation',
        'action' => 'store',
    ],
    'obligations/edit' => [
        'controller' => 'VehicleObligation',
        'action' => 'edit',
    ],
    'obligations/update' => [
        'controller' => 'VehicleObligation',
        'action' => 'update',
    ],
    'obligations/delete' => [
        'controller' => 'VehicleObligation',
        'action' => 'delete',
    ],
    'rentals' => [
        'controller' => 'Rental',
        'action' => 'index',
    ],
    'rentals/history' => [
        'controller' => 'Rental',
        'action' => 'history',
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

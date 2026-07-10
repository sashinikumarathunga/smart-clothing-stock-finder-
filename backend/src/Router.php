<?php

declare(strict_types=1);

require_once __DIR__ . '/handlers/HealthAuthDashboard.php';
require_once __DIR__ . '/handlers/BranchesUsers.php';
require_once __DIR__ . '/handlers/Inventory.php';
require_once __DIR__ . '/handlers/Sales.php';
require_once __DIR__ . '/handlers/SettingsReports.php';

final class Router
{
    public static function dispatch(): never
    {
        $method = requestMethod();
        $path = requestPath();

        if ($path === '/api/health' && $method === 'GET') {
            handleHealth();
        }

        if ($path === '/api/auth/login' && $method === 'POST') {
            handleAuthLogin();
        }

        if ($path === '/api/auth/logout' && $method === 'POST') {
            handleAuthLogout();
        }

        if ($path === '/api/auth/me' && $method === 'GET') {
            handleAuthMe();
        }

        if ($path === '/api/dashboard' && $method === 'GET') {
            handleDashboard();
        }

        if ($path === '/api/branches' && $method === 'GET') {
            handleBranchesList();
        }

        if ($path === '/api/branches' && $method === 'POST') {
            handleBranchesCreate();
        }

        if (preg_match('#^/api/branches/(\d+)$#', $path, $m) === 1) {
            $id = (int) $m[1];
            if ($method === 'GET') {
                handleBranchesGet($id);
            }
            if ($method === 'PUT') {
                handleBranchesUpdate($id);
            }
            if ($method === 'DELETE') {
                handleBranchesDelete($id);
            }
        }

        if ($path === '/api/users' && $method === 'GET') {
            handleUsersList();
        }

        if ($path === '/api/users' && $method === 'POST') {
            handleUsersCreate();
        }

        if (preg_match('#^/api/users/(\d+)$#', $path, $m) === 1) {
            $id = (int) $m[1];
            if ($method === 'GET') {
                handleUsersGet($id);
            }
            if ($method === 'PUT') {
                handleUsersUpdate($id);
            }
            if ($method === 'DELETE') {
                handleUsersDelete($id);
            }
        }

        if ($path === '/api/products' && $method === 'GET') {
            handleProductsList();
        }

        if ($path === '/api/products' && $method === 'POST') {
            handleProductsCreate();
        }

        if ($path === '/api/products/search' && $method === 'GET') {
            handleProductsSearch();
        }

        if ($path === '/api/products/lookup' && $method === 'GET') {
            handleProductsLookup();
        }

        if (preg_match('#^/api/products/(\d+)$#', $path, $m) === 1) {
            $id = (int) $m[1];
            if ($method === 'GET') {
                handleProductsGet($id);
            }
            if ($method === 'PUT') {
                handleProductsUpdate($id);
            }
            if ($method === 'DELETE') {
                handleProductsDelete($id);
            }
        }

        if ($path === '/api/suppliers' && $method === 'GET') {
            handleSuppliersList();
        }

        if ($path === '/api/suppliers' && $method === 'POST') {
            handleSuppliersCreate();
        }

        if (preg_match('#^/api/suppliers/(\d+)$#', $path, $m) === 1) {
            $id = (int) $m[1];
            if ($method === 'GET') {
                handleSuppliersGet($id);
            }
            if ($method === 'PUT') {
                handleSuppliersUpdate($id);
            }
            if ($method === 'DELETE') {
                handleSuppliersDelete($id);
            }
        }

        if ($path === '/api/purchase-orders' && $method === 'GET') {
            handlePurchaseOrdersList();
        }

        if ($path === '/api/purchase-orders' && $method === 'POST') {
            handlePurchaseOrdersCreate();
        }

        if (preg_match('#^/api/purchase-orders/(\d+)/deliver$#', $path, $m) === 1 && $method === 'POST') {
            handlePurchaseOrdersDeliver((int) $m[1]);
        }

        if (preg_match('#^/api/purchase-orders/(\d+)$#', $path, $m) === 1 && $method === 'GET') {
            handlePurchaseOrdersGet((int) $m[1]);
        }

        if ($path === '/api/reservations' && $method === 'GET') {
            handleReservationsList();
        }

        if ($path === '/api/reservations' && $method === 'POST') {
            handleReservationsCreate();
        }

        if (preg_match('#^/api/reservations/(\d+)/cancel$#', $path, $m) === 1 && $method === 'POST') {
            handleReservationsCancel((int) $m[1]);
        }

        if ($path === '/api/sales' && $method === 'GET') {
            handleSalesList();
        }

        if ($path === '/api/sales' && $method === 'POST') {
            handleSalesCreate();
        }

        if (preg_match('#^/api/sales/(\d+)$#', $path, $m) === 1 && $method === 'GET') {
            handleSalesGet((int) $m[1]);
        }

        if ($path === '/api/customers' && $method === 'GET') {
            handleCustomersList();
        }

        if ($path === '/api/customers' && $method === 'POST') {
            handleCustomersCreate();
        }

        if ($path === '/api/returns' && $method === 'POST') {
            handleReturnsCreate();
        }

        if ($path === '/api/returns/lookup' && $method === 'GET') {
            handleReturnsSaleLookup();
        }

        if ($path === '/api/settings' && $method === 'GET') {
            handleSettingsGet();
        }

        if ($path === '/api/settings' && $method === 'PUT') {
            handleSettingsUpdate();
        }

        if ($path === '/api/reports' && $method === 'GET') {
            handleReports();
        }

        Response::notFound('Route not found: ' . $method . ' ' . $path);
    }
}

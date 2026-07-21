<?php

declare(strict_types=1);

namespace SmartStock;

use SmartStock\Controllers\AuthLoginController;
use SmartStock\Controllers\AuthLogoutController;
use SmartStock\Controllers\AuthMeController;
use SmartStock\Controllers\BranchesCreateController;
use SmartStock\Controllers\BranchesDeleteController;
use SmartStock\Controllers\BranchesGetController;
use SmartStock\Controllers\BranchesListController;
use SmartStock\Controllers\BranchesUpdateController;
use SmartStock\Controllers\CustomersCreateController;
use SmartStock\Controllers\CustomersListController;
use SmartStock\Controllers\DashboardController;
use SmartStock\Controllers\HealthController;
use SmartStock\Controllers\LoyaltySettingsController;
use SmartStock\Controllers\ProductsCreateController;
use SmartStock\Controllers\ProductsDeleteController;
use SmartStock\Controllers\ProductsGetController;
use SmartStock\Controllers\ProductsListController;
use SmartStock\Controllers\ProductsLookupController;
use SmartStock\Controllers\ProductsSearchController;
use SmartStock\Controllers\ProductsUpdateController;
use SmartStock\Controllers\PurchaseOrdersCreateController;
use SmartStock\Controllers\PurchaseOrdersDeliverController;
use SmartStock\Controllers\PurchaseOrdersGetController;
use SmartStock\Controllers\PurchaseOrdersListController;
use SmartStock\Controllers\ReportsController;
use SmartStock\Controllers\ReservationsCancelController;
use SmartStock\Controllers\ReservationsCreateController;
use SmartStock\Controllers\ReservationsListController;
use SmartStock\Controllers\ReturnsCreateController;
use SmartStock\Controllers\ReturnsLookupController;
use SmartStock\Controllers\SalesCreateController;
use SmartStock\Controllers\SalesGetController;
use SmartStock\Controllers\SalesListController;
use SmartStock\Controllers\SettingsGetController;
use SmartStock\Controllers\SettingsUpdateController;
use SmartStock\Controllers\SuppliersCreateController;
use SmartStock\Controllers\SuppliersDeleteController;
use SmartStock\Controllers\SuppliersGetController;
use SmartStock\Controllers\SuppliersListController;
use SmartStock\Controllers\SuppliersUpdateController;
use SmartStock\Controllers\UsersCreateController;
use SmartStock\Controllers\UsersDeleteController;
use SmartStock\Controllers\UsersGetController;
use SmartStock\Controllers\UsersListController;
use SmartStock\Controllers\UsersUpdateController;
use SmartStock\Http\JsonResponse;
use SmartStock\Http\Request;
use SmartStock\Routing\Router;

final class Application
{
    public function run(): never
    {
        $response = new JsonResponse();
        $router = new Router($response);
        $router->add('GET', '/api/health', new HealthController($response));
        $router->add('POST', '/api/auth/login', new AuthLoginController($response));
        $router->add('POST', '/api/auth/logout', new AuthLogoutController($response));
        $router->add('GET', '/api/auth/me', new AuthMeController($response));
        $router->add('GET', '/api/dashboard', new DashboardController($response));
        $router->add('GET', '/api/branches', new BranchesListController($response));
        $router->add('POST', '/api/branches', new BranchesCreateController($response));
        $router->add('GET', '/api/branches/{id}', new BranchesGetController($response));
        $router->add('PUT', '/api/branches/{id}', new BranchesUpdateController($response));
        $router->add('DELETE', '/api/branches/{id}', new BranchesDeleteController($response));
        $router->add('GET', '/api/users', new UsersListController($response));
        $router->add('POST', '/api/users', new UsersCreateController($response));
        $router->add('GET', '/api/users/{id}', new UsersGetController($response));
        $router->add('PUT', '/api/users/{id}', new UsersUpdateController($response));
        $router->add('DELETE', '/api/users/{id}', new UsersDeleteController($response));
        $router->add('GET', '/api/products', new ProductsListController($response));
        $router->add('POST', '/api/products', new ProductsCreateController($response));
        $router->add('GET', '/api/products/search', new ProductsSearchController($response));
        $router->add('GET', '/api/products/lookup', new ProductsLookupController($response));
        $router->add('GET', '/api/products/{id}', new ProductsGetController($response));
        $router->add('PUT', '/api/products/{id}', new ProductsUpdateController($response));
        $router->add('DELETE', '/api/products/{id}', new ProductsDeleteController($response));
        $router->add('GET', '/api/suppliers', new SuppliersListController($response));
        $router->add('POST', '/api/suppliers', new SuppliersCreateController($response));
        $router->add('GET', '/api/suppliers/{id}', new SuppliersGetController($response));
        $router->add('PUT', '/api/suppliers/{id}', new SuppliersUpdateController($response));
        $router->add('DELETE', '/api/suppliers/{id}', new SuppliersDeleteController($response));
        $router->add('GET', '/api/purchase-orders', new PurchaseOrdersListController($response));
        $router->add('POST', '/api/purchase-orders', new PurchaseOrdersCreateController($response));
        $router->add('GET', '/api/purchase-orders/{id}', new PurchaseOrdersGetController($response));
        $router->add('POST', '/api/purchase-orders/{id}/deliver', new PurchaseOrdersDeliverController($response));
        $router->add('GET', '/api/reservations', new ReservationsListController($response));
        $router->add('POST', '/api/reservations', new ReservationsCreateController($response));
        $router->add('POST', '/api/reservations/{id}/cancel', new ReservationsCancelController($response));
        $router->add('GET', '/api/loyalty/settings', new LoyaltySettingsController($response));
        $router->add('GET', '/api/sales', new SalesListController($response));
        $router->add('POST', '/api/sales', new SalesCreateController($response));
        $router->add('GET', '/api/sales/{id}', new SalesGetController($response));
        $router->add('GET', '/api/customers', new CustomersListController($response));
        $router->add('POST', '/api/customers', new CustomersCreateController($response));
        $router->add('POST', '/api/returns', new ReturnsCreateController($response));
        $router->add('GET', '/api/returns/lookup', new ReturnsLookupController($response));
        $router->add('GET', '/api/settings', new SettingsGetController($response));
        $router->add('PUT', '/api/settings', new SettingsUpdateController($response));
        $router->add('GET', '/api/reports', new ReportsController($response));
        $router->dispatch(Request::capture());
    }
}

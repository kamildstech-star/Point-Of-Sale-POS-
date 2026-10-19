<?php

use App\Http\Controllers\AboutController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\anuallySaleReportController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\dailySaleReportController;
use App\Http\Controllers\BusinessDealerController;
use App\Http\Controllers\DailyAccountController;
use App\Http\Controllers\FrontendController;
use App\Http\Controllers\ledgerController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\returnSaleReportController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\SizeController;
use App\Http\Controllers\SchoolController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\returnOrderController;
use App\Http\Controllers\StockController;
use App\Http\Controllers\SettingController;
use App\Http\Middleware\AlreadyLogin;
use App\Http\Middleware\RoleMiddleware;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Http\Middleware\Validuser;
use App\Http\Controllers\EmailController;
use App\Http\Controllers\SalesReportcontroller;
use App\Http\Controllers\StockReportcontroller;
use App\Http\Controllers\ExpensesController;
use App\Http\Middleware\AdminMiddleware;

Route::middleware(Validuser::class)->group(function() {
    Route::middleware(AdminMiddleware::class)->group(function() {
        Route::middleware(RoleMiddleware::class . ':13')->group(function() {
                Route::get('/', [UserController::class, 'dashboard'])->name('dashboard');
                Route::post('/todolist', [UserController::class, 'todolist'])->name('todolist');
                Route::get('/getTasks', [UserController::class, 'getTasks'])->name('getTasks');
                Route::put('/updateTaskStatus/{id}', [UserController::class, 'updateTask'])->name('updateTaskStatus');
                Route::post('/update-task-status', [UserController::class, 'updateStatus'])->name('task.updateStatus');
        });
        Route::middleware(RoleMiddleware::class . ':12')->group(function() {
                Route::get('/role',[AdminController::class, 'show'])->name('roles.show');
                Route::post('role/create',[AdminController::class, 'create'])->name('role.create');
                Route::post('role/permission',[AdminController::class, 'rolePermission'])->name('role.permission');
                Route::get('/roles',[AdminController::class, 'index'])->name('roles.index');
                Route::get('/role/edit/{id}', [AdminController::class, "edit"])->name('roles.edit');
                Route::post('/roles/{id}', [AdminController::class, "update"])->name("roles.update");
                Route::get('/roles/{id}', [AdminController::class,  'destroy'])->name('roles.destroy');
                Route::get('/getPermission/{id}', [AdminController::class, 'getPermission'])->name('getPermission');
        });
        Route::middleware(RoleMiddleware::class . ':1')->group(function() {
                Route::get('/user', [HomeController::class, 'show'])->name('user.show');
                Route::post('user/create', [HomeController::class, 'create'])->name('user.create');
                Route::get('/users', [HomeController::class, 'index'])->name('users.index');
                Route::get('/user/edit/{id}', [HomeController::class, 'edit'])->name('user.edit');
                Route::post('user/{id}', [HomeController::class, 'update'])->name('user.update');
                Route::get('/user/{id}', [HomeController::class, 'destroy'])->name('user.destroy');
        });
        Route::middleware(RoleMiddleware::class . ':10')->group(function() {
                Route::get('/businessDealer', [BusinessDealerController::class, 'showBusinessDealer'])->name('businessDealer.show');
                Route::get('/businessDealers', [BusinessDealerController::class, 'businessDealerindex'])->name('businessDealer.index');
                Route::get('/businessDealer/edit/{id}', [BusinessDealerController::class, 'editbusinessDealer'])->name('businessDealer.edit');
                Route::post('/businessDealer/{id}', [BusinessDealerController::class, 'updatebusinessdealer'])->name('businessDealer.update');
                Route::post('/businessDealers/create', [BusinessDealerController::class, 'createBusinessDealer'])->name('businessDealer.create');
                Route::get('businessDealer/{id}', [BusinessDealerController::class, 'deleteBusinessDealer'])->name('businessDealer.destroy');
        });
        Route::middleware(RoleMiddleware::class . ':3')->group(function() {
                Route::get('/size',[SizeController::class, 'sizes'])->name('size.show');
                Route::post('/size/create', [SizeController::class, 'create'])->name('size.create');
                Route::get('/sizes', [SizeController::class, 'index'])->name('sizes.index');
                Route::get('/size/edit/{id}', [SizeController::class, 'editsize'])->name('size.edit');
                Route::post('/size/{id}', [SizeController::class, 'updatesize'])->name('size.update');
                Route::get('/size/{id}', [SizeController::class, 'destory'])->name('size.destory');
        });
        Route::middleware(RoleMiddleware::class . ':2')->group(function() {
                Route::get('/product', [ProductController::class, 'showproduct'])->name('product.show');
                Route::post('/product/create', [ProductController::class, 'createproduct'])->name('product.create');
                Route::get('/products', [ProductController::class, 'productindex'])->name('product.index');
                Route::get('product/edit/{id}', [ProductController::class, 'editproduct'])->name('product.edit');
                Route::post('product/{id}', [ProductController::class, 'updateproduct'])->name('product.update');
                Route::get('/product/{id}', [ProductController::class, 'deleteProduct'])->name('product.destroy');
        });
        Route::middleware(RoleMiddleware::class . ':14')->group(function() {
                Route::get('/permission', [PermissionController::class, 'show'])->name('permission.show');
                Route::post('/permission/create', [PermissionController::class, 'createpermission'])->name('permission.create');
                Route::get('/permissions', [PermissionController::class, 'permissionindex'])->name('permission.index');
                Route::get('permission/edit/{id}', [PermissionController::class, 'editpermission'])->name('permission.edit');
                Route::post('permission/{id}', [PermissionController::class, 'updatepermission'])->name('permission.update');
                Route::get('/permission/{id}', [PermissionController::class, 'deletepermission'])->name('permission.destroy');
        });
        Route::middleware(RoleMiddleware::class . ':4')->group(function() {
                Route::get('/school',[SchoolController::class, 'showschool'])->name('school.show');
                Route::post('/school.create', [SchoolController::class, 'createschool'])->name('school.create');
                Route::get('/schools', [SchoolController::class, 'schoolindex'])->name('school.index');
                Route::get('school/edit/{id}', [SchoolController::class, 'editschool'])->name('school.edit');
                Route::post('/school/{id}',[SchoolController::class, 'updateschool'])->name('school.update');
                Route::get('/school/{id}', [SchoolController::class, 'destroy'])->name('school.destroy');
        });
        Route::middleware(RoleMiddleware::class . ':5')->group(function() {
                Route::get('/stock', [StockController::class, 'showstock'])->name('stock.show');
                Route::post('/stock/create', [StockController::class, 'createstock'])->name('stock.create');
                Route::get('/stock/edit/{id}', [StockController::class, 'editstock'])->name('stock.edit');
                Route::post('/stock/{id}', [StockController::class, 'updatestock'])->name('stock.update');
                Route::get('/stock/{id}', [StockController::class, 'destroystock'])->name('stock.destroy');
                Route::get('/stocks', [StockController::class, 'stockindex'])->name('stocks.index');
        });
        Route::middleware(RoleMiddleware::class . ':15')->group(function() {
                Route::get('/ledger/{id?}', [ledgerController::class, 'ledgerindex'])->name('ledger.index');
                Route::post('/ledger/create/', [ledgerController::class, 'create'])->name('ledger.create');
        });
        Route::middleware(RoleMiddleware::class . ':11')->group(function() {
                Route::get('/setting', [SettingController::class, 'showsetting'])->name('setting.show');
                Route::post('/setting/create', [SettingController::class, 'createsetting'])->name('setting.create');
                Route::get('/settings', [SettingController::class, 'settingindex'])->name('settings.index');
                Route::get('/setting/edit/{id}', [SettingController::class, 'editsetting'])->name('setting.edit');
                Route::post('/setting/{id}', [SettingController::class, 'updatesetting'])->name('setting.update');
                Route::get('setting/{id}', [SettingController::class, 'deleteSetting'])->name('setting.destroy');
        });
        Route::middleware(RoleMiddleware::class . ':6')->group(function() {
                Route::get('/order', [OrderController::class, 'showorder'])->name('order.show');
                Route::get('/schoolmargin',[OrderController::class, 'schoolmargin'])->name('schoolmargin');
                Route::post('/order/create', [OrderController::class, 'createorder'])->name('order.create');
                Route::get('/orders', [OrderController::class, 'orderindex'])->name('orders.index');
                Route::get('/check/stock', [OrderController::class, 'checkstock'])->name('check.stock');
                Route::get('/order/edit/{id}', [OrderController::class, 'editorder'])->name('order.edit');
                Route::post('/order/{id}', [OrderController::class, 'updateorder'])->name('order.update');
                Route::get('/order/{id}', [OrderController::class, 'deleteorder'])->name('order.destroy');
                Route::get('/view/order/{id}', [OrderController::class, 'viewOrder'])->name('order.view');
                Route::post('/phoneCheck', [OrderController::class, 'phoneCheck'])->name('phoneCheck');
                Route::post('/nameCheck', [OrderController::class, 'nameCheck'])->name('nameCheck');
                Route::get('/cancel/order/{id}', [OrderController::class, 'cancelOrder'])->name('order.cancel');
                Route::get('/invoice/{orderId}', [OrderController::class, 'showInvoice'])->name('invoice');
        });
        Route::middleware(RoleMiddleware::class . ':7')->group(function() {
                Route::get('/return', [returnOrderController::class, 'returnorder'])->name('return.show');
                Route::get('/fetchprice', [returnOrderController::class, 'fetchPrice'])->name('fetchPrice');
                Route::post('/return/create', [returnOrderController::class, 'createreturn'])->name('return.create');
                Route::get('/getOrder/{id}', [returnOrderController::class, 'getOrder'])->name('getOrder');
                Route::get('/returns', [returnOrderController::class, 'returnindex'])->name('returns.index');
                Route::get('/returns/edit/{id}',[returnOrderController::class, 'editreturn'])->name('return.edit');
                Route::post('/return/{id}', [returnOrderController::class, 'updatereturn'])->name('return.update');
                Route::get('/return/{id}', [returnOrderController::class, 'deletereturn'])->name('return.destroy');
        });
        Route::middleware(RoleMiddleware::class . ':16')->group(function() {
                Route::get('/dailyAccount', [DailyAccountController::class, 'show'])->name('dailyAccount');
                Route::post('/dailyIncome', [DailyAccountController::class, 'dailyIncome'])->name('dailyIncome');
                Route::post('/dailyOutcome', [DailyAccountController::class, 'dailyOutcome'])->name('dailyOutcome');
                Route::post('/dailyAccount/create', [DailyAccountController::class, 'create'])->name('dailyAccount.create');
                Route::get('/ledger/destroy/{id}', [DailyAccountController::class, 'deleteledger'])->name('ledger.destroy');
        });
        Route::middleware(RoleMiddleware::class . ':17')->group(function() {
                Route::get('/expenses/show', [ExpensesController::class, 'show'])->name('expenses.show');
                Route::post('/expenses/create', [ExpensesController::class, 'create'])->name('expenses.create');
                Route::get('/expenses', [ExpensesController::class, 'index'])->name('expenses.index');
                Route::get('/expenses/edit/{id}', [ExpensesController::class, 'edit'])->name('expenses.edit');
                Route::post('/expenses/{id}', [ExpensesController::class, 'update'])->name('expenses.update');
                Route::get('expenses/{id}', [ExpensesController::class, 'destroy'])->name('expenses.delete');
        });
        Route::middleware(RoleMiddleware::class . ':18')->group(function() {
                Route::get('/Stocks/report', [StockReportcontroller::class, 'show'])->name('Stocks.report');
                Route::get('/Stocks/report/index', [StockReportcontroller::class, 'stockReport'])->name('stocks.report.index');
                Route::post('/stocks/download', [StockReportcontroller::class, 'stockReportpdf'])->name('stocks.download');
        });
        Route::middleware(RoleMiddleware::class . ':19')->group(function() {
                Route::get('/monthlySales', [SalesReportcontroller::class, 'show'])->name('monthlySales');
                Route::get('/monthlySales/fetch', [SalesReportcontroller::class, 'fetchSales'])->name('monthlySales.fetch');
                Route::post('/monthlySales/download', [SalesReportcontroller::class, 'salesReportPdf'])->name('monthlySales.download');
                Route::get('/anuallySales', [anuallySaleReportController::class, 'show'])->name('anuallysales');
                Route::get('/dailySales', [dailySaleReportController::class, 'show'])->name('dailysales');
                Route::get('/dailySales/fetch', [dailySaleReportController::class, 'fetchDailySales'])->name('dailySales.fetch');
                Route::post('/dailySales/download', [dailySaleReportController::class, 'dailySalesReportPdf'])->name('dailySales.download');
                Route::get('/annuallySales/fetch', [anuallySaleReportController::class, 'fetchAnnuallySales'])->name('annuallySales.fetch');
                Route::post('/annuallySales/download', [anuallySaleReportController::class, 'anuallysalesReportPdf'])->name('anuuallySales.download');
        });
        Route::middleware(RoleMiddleware::class . ':20')->group(function() {
                Route::get('/returnSales', [returnSaleReportController::class, 'show'])->name('returnSales');
                Route::get('/returnSales/fetch', [returnSaleReportController::class, 'fetchReturnSales'])->name('returnSales.fetch');
                Route::post('/sales/download', [returnSaleReportController::class, 'returnSalesReportPdf'])->name('returnSales.download');
        });

    });
});
Auth::routes();
// Route::get('/frontend', [FrontendController::class, 'frontendIndex'])->name('frontend');
// Route::get('/all-products', [FrontendController::class, 'allProducts'])->name('frontend.allProducts');
// Route::get('/frontend/about', [AboutController::class, 'aboutIndex'])->name('about');
// Route::get('/frontend/login', [LoginController::class, 'loginIndex'])->name('frontend-login');
// Route::get('/frontend/cart', [CartController::class, 'cartIndex'])->name('frontend-cart');
Route::get('/emailsend', [EmailController::class, 'sendEmail'])->name('emailsend');
Route::get('/low/stockMail', [Emailcontroller::class, 'lowstock'])->name('low.StockEmail');
Route::get('/login', [UserController::class, 'login'])->middleware(AlreadyLogin::class)->name('login');
Route::post('/loginpost', [UserController::class, 'loginpost'])->name('loginpost');
Route::get('/logoutpost', [UserController::class, 'logoutpost'])->name('logoutpost');
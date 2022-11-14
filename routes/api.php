<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\Api\BankController;
use App\Http\Controllers\Api\BookingController;
use App\Http\Controllers\Api\BrandController;
use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\ManagerProductController;
use App\Http\Controllers\Api\MessengerController;
use App\Http\Controllers\Api\PayoutController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\ProvinceController;
use App\Http\Controllers\Api\Staff\AuthController;
use App\Http\Controllers\Api\Staff\CalendarStaffController;
use App\Http\Controllers\Api\Staff\StoreController as StaffStoreController;
use App\Http\Controllers\Api\Staff\SubOrderController;
use App\Http\Controllers\Api\StoreController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\HomePageController;
use App\Http\Controllers\ManagerLiveStreamController;
use App\Http\Controllers\ManagerMessageLivestreamController;
use App\Http\Controllers\SettingAccountStaffController;
use App\Http\Controllers\StripeWebhookController;
use App\Http\Controllers\UploadController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::get('/server-status', function () {
    return response()->json(env('APP_PATH'));
    return response()->json([
        'success' => true,
        'message' => 'server running',
    ]);
});

Route::prefix('cart')
    ->controller(CartController::class)
    ->group(function () {
        Route::post('add', 'addCart');
        Route::get('list-product', 'listProduct');
        Route::delete('delete', 'deleteProduct');
        Route::post('create-order', 'createOrder');
        Route::get('view-sort', 'viewSortCart');
        Route::get('list-product', 'listProduct');
    });

Route::prefix('products')
    ->controller(ProductController::class)
    ->group(function () {
        Route::get('', 'searchProduct');
        Route::get('best-sale', 'getProductBestSaleByCategory');
        Route::get('{id}', 'getProductDetail');
        Route::get('{id}/reference', 'getProductReference');
        Route::get('best-sale/store/{storeId}', 'getProductBestSaleByStore');
    });

Route::prefix('categories')
    ->controller(CategoryController::class)
    ->group(function () {
        Route::get('count-product', 'getCategoryProductCount');
        Route::get('', 'getAllCategory');
        Route::get('best-sale', 'getCategoryBestSale');
        Route::get('store/{storeId}', 'getCategoryByStore');
    });

Route::prefix('brands')
    ->controller(BrandController::class)
    ->group(function () {
        Route::get('list', 'getAllBrand');
        Route::get('count-product', 'getBrandProductCount');
    });

Route::prefix('provinces')
    ->controller(ProvinceController::class)
    ->group(function () {
        Route::get('/', 'getAll');
        Route::get('count-store', 'countStoreByFilter');
    });

Route::prefix('stores')
    ->controller(StoreController::class)
    ->group(function () {
        Route::get('', 'searchStore');
        Route::get('{storeId}', 'getStoreInfo');
    });

Route::prefix('home-page')
    ->controller(HomePageController::class)
    ->group(function () {
        Route::get('live-stream', 'getListLiveStreamHomePage');
        Route::get('video', 'getListVideoHomePage');
        Route::get('instagram', 'getListInstagram');
    });

Route::controller(CustomerController::class)
    ->group(function () {
        Route::get('list-user-chat/{groupId}', 'getListUserChat');
    });

Route::controller(BookingController::class)
    ->group(function () {
        Route::get('booking/join/{id}', 'userJoinCall');
    });

// file
Route::controller(UploadController::class)
    ->group(function () {
        Route::post('upload-file', 'uploadSingleFile');
        Route::post('delete-file', 'deleteFile');
    });

Route::controller(ManagerLiveStreamController::class)
    ->group(function () {
        Route::get('livestream/{id}', 'getDetailLivestream');
        Route::post('livestream/record/{id}', 'recordLivestreamTimeOut');
    });

// CUSTOMER

Route::prefix('customers')
    ->controller(CustomerController::class)
    ->group(function () {
        Route::post('store', 'store');
        Route::get('verify/{token}/{id}', 'verifyCustomer');
        Route::post('login', 'login');
        Route::post('reset-password', 'sendMailResetPassword');
        Route::post('validate-link/reset-password', 'validateLinkResetPassword');
        Route::post('validate-link/setting-email', 'validateLinkSettingEmail');
        Route::post('resend-mail', 'reSendMail');
        Route::put('reset-password/{token}', 'resetPassword');
        Route::post('check-email', 'checkEmail');
        Route::get('change-email/{token}/{oldEmail}/{newEmail}', 'changeEmail');

        //banks
        Route::prefix('banks')
            ->controller(BankController::class)
            ->group(function () {
                Route::get('', 'getBankList');
                Route::get('branch/{bankId}', 'getBankBranchList');
            });

        Route::middleware(['auth:sanctum', 'customers'])->group(function () {

            //me
            Route::get('me', 'getUserInformation');
            Route::get('profile', 'getProfile');
            Route::post('update/profile', 'updateProfile');
            Route::put('update/address', 'updateAddress');
            Route::post('upgrade-account', 'upgradeAccount');
            Route::post('logout', 'logout');
            Route::get('group-chat', 'getGroupChatInformation');

            //order
            Route::prefix('order')->group(function () {
                Route::get('list', 'getListOrder');
                Route::get('{orderId}', 'getDetailOrderSiteUser');
                Route::post('confirm/{orderId}', 'confirmOrder');
            });

            //booking
            Route::prefix('booking')->group(function () {
                Route::controller(BookingController::class)->group(function () {
                    Route::get('/list', 'getBookingList');
                    Route::get('/{id}', 'getBookingDetail')->where(['id' => '[0-9]+']);
                    Route::get('/check-booking', 'checkBooking');
                    Route::post('/create', 'createBooking');
                    Route::put('/update', 'updateBooking');
                    Route::get('/history', 'getReservationHistory');
                    Route::get('/history/filter/booking-status', 'getBookingStatusFilter');
                    Route::get('/history/filter/video-call-type-customer', 'getVideoCallTypeCustomerFilter');
                    Route::put('/{id}/cancel', 'cancelBooking');
                    Route::get('/chat-history/{calendarStaffId}', 'getChatHistory');
                    Route::post('/chat', 'addChatComment');
                    Route::post('{bookingId}/end-call', 'endVideoCall');
                });
            });

            //setting
            Route::prefix('setting')->group(function () {
                Route::post('email/send-mail', 'sendMailSettingEmail');
                Route::post('password', 'settingPassword');
                Route::post('notify', 'settingNotify');
            });

            //product
            Route::prefix('products')
                ->controller(ProductController::class)
                ->group(function () {
                    Route::get("", 'searchProduct');
                    Route::get('{id}', 'getProductDetail')->where(['id' => '[0-9]+']);
                    Route::post('like', 'likeProduct');
                    Route::get('{id}/reference', 'getProductReference')->where(['id' => '[0-9]+']);
                    Route::get('favorite', 'getProductFavorite');
                });

            //messenger
            Route::prefix('messenger')
                ->controller(MessengerController::class)
                ->group(function () {
                    Route::post('create-group', 'createGroupChatUser');
                    Route::get("/list", 'getListGroupChat');
                    Route::get("/chat-history/{groupId}", 'getHistoryChat');
                    Route::post("/chat/{groupId}", 'addMessage');
                    Route::delete("/delete/{groupId}/{messageId}", 'deleteMessage');
                    Route::post('read/{groupId}', 'updateMessageRead');
                    Route::get('group', 'getGroupChatUser');
                });

            //chat livestream
            Route::prefix('livestream')
                ->controller(ManagerMessageLivestreamController::class)
                ->group(function () {
                    Route::post('chat/{livestreamId}', 'chatMessage');
                    Route::get('{livestreamId}/message', 'getMessageOfChannel');
                });
        });
    });

// STAFF
Route::prefix('staff')->group(function () {
    Route::controller(AuthController::class)->group(function () {
        Route::post('/login', 'login');
        Route::post('logout', 'logout');
        Route::post('reset-password', 'sendMailResetPassword');
        Route::put('reset-password/{token}', 'resetPassword');
        Route::post('resend-mail', 'reSendMail');
        Route::post('check-email', 'checkEmail');
        Route::post('validate-link/reset-password', 'validateLinkResetPassword');
        Route::get('change-email/{token}/{oldEmail}/{newEmail}', 'changeEmail');
        Route::post('check-mail-signup', 'sendMailSignUp');
        Route::post('validate-link/sign-up', 'validateLinkSignUp');
        Route::post('sign-up', 'signUpStore');
    });

    Route::middleware(['auth:sanctum', 'is_staff'])->group(function () {
        Route::controller(AuthController::class)->group(function () {
            Route::get('/list', 'getListStaff');
            Route::get('/list/count-status', 'countStatusListStaff');
            Route::get('/list-active', 'getListActiveStaff');
            Route::get('/me', 'getInfomation');
            Route::post('/create', 'createStaff');
            Route::put('/{id}/update', 'updateStaff');
            Route::delete('/{id}/delete', 'deleteStaff');
            Route::get('group-chat', 'getGroupChatInformation');
        });

        Route::prefix('my-store')->group(function () {
            Route::controller(StaffStoreController::class)->group(function () {
                Route::get('/', 'getStore');
                Route::post('', 'updateShop');
                Route::get('bank/detail', 'getDetailBank');
                Route::post('update/bank-account', 'updateBankAccount');
            });

            Route::controller(SubOrderController::class)->group(function () {
                Route::post('/count-sub-order', 'getCountSubOrderByStatus');
                Route::get('/list-sub-order', 'getListSubOrder');
                Route::get('/detail-sub-order/{id}', 'getDetailSubOrder');
                Route::post('/sub-order/change-status', 'changeStatusSubOrder');
                Route::post('/sub-order/change-note', 'changeNoteSubOrder');
                Route::get('/export-csv/sub-order', 'exportCsv');
                Route::get('export-pdf/{id}', 'exportPdf');
            });
        });

        Route::prefix('booking')->group(function () {
            Route::controller(CalendarStaffController::class)->group(function () {
                Route::get('/list', 'getCalendarList');
                Route::get('/{id}', 'getBookedCalendarDetail')->where(['id' => '[0-9]+']);
                Route::post('/add-calendar', 'addCalendar');
                Route::get('/calendar', 'getCalendarOfStaff');
                Route::put('/edit-calendar', 'editCalendar');
                Route::get('/history', 'getReservationHistory');
                Route::get('/history/filter/booking-status', 'getBookingStatusFilter');
                Route::get('/history/filter/video-call-type', 'getVideoCallTypeFilter');
                Route::get('/history/filter/video-call-type-customer', 'getVideoCallTypeCustomerFilter');
                Route::put('/{bookingId}/confirm-video-call-type', 'confirmVideoCallType');
                Route::post('/{bookingId}/join-video-call', 'joinVideoCall');
                Route::get('/chat-history/{calendarStaffId}', 'getChatHistory');
                Route::post('/chat', 'addChatComment');
                Route::delete('/chat/{id}', 'deleteChatComment');
                Route::post('{bookingId}/end-call', 'endVideoCall');
            });
        });

        // manager product
        Route::prefix('product')
            ->controller(ManagerProductController::class)
            ->group(function () {
                Route::post('', 'createProduct');
                Route::get('{id}', 'getInfoProduct')->where(['id' => '[0-9]+']);
                Route::get('list', 'getAllProduct');
                Route::post('update/{id}', 'updateProduct');
                Route::delete('{id}', 'deleteProduct');
            });

        //messenger
        Route::prefix('messenger')
            ->controller(MessengerController::class)
            ->group(function () {
                Route::post('create-group', 'createGroupChatShop');
                Route::get("/list", 'getListGroupChat');
                Route::get("/chat-history/{groupId}", 'getHistoryChat');
                Route::post("/chat/{groupId}", 'addMessage');
                Route::delete("/delete/{groupId}/{messageId}", 'deleteMessage');
                Route::post('read/{groupId}', 'updateMessageRead');
                Route::get('group', 'getGroupChatShop');
            });
        // setting account
        Route::prefix('setting')
            ->controller(SettingAccountStaffController::class)
            ->group(function () {
                Route::post('email/send-mail', 'sendMailSettingEmail');
                Route::post('password', 'settingPassword');
            });

        //chat livestream
        Route::prefix('livestream')
            ->controller(ManagerMessageLivestreamController::class)
            ->group(function () {
                Route::get('{livestreamId}/message', 'getMessageOfChannel');
                Route::post('chat/{livestreamId}', 'chatMessage');
                Route::delete('{livestreamId}/{messageId}', 'deleteMessage');
            });

        //get user information
        Route::prefix('user-information')
            ->controller(CustomerController::class)
            ->group(function () {
                Route::get('{id}', 'getUserInformation');
            });

        // withdraw, history payout stripe
        Route::prefix('payouts')
            ->controller(PayoutController::class)
            ->group(function () {
                Route::get('history', 'getPayoutHistory');
                Route::get('retrieve', 'getPayoutRetrieve');
            });

        // manager revenue by owner
        Route::prefix('manager-revenue')
            ->controller(StoreController::class)
            ->group(function () {
                Route::get('', 'getRevenue');
                Route::get('order', 'getRevenueOrderByStore');
                Route::get('order/export', 'exportRevenueOfStoreByOrder')
                    ->middleware('language');
                Route::get('product', 'getRevenueOfStoreByProduct');
                Route::get('product/export', 'exportRevenueOfStoreByProduct')
                    ->middleware('language');
                Route::get('age', 'statisticRevenueOfStoreByAge');
                Route::get('age/export', 'exportRevenueOfStoreByAge')
                    ->middleware('language');
            });
    });


    // manager live stream
    Route::prefix('live-stream')
        ->middleware(['auth:sanctum', 'is_staff'])
        ->controller(ManagerLiveStreamController::class)
        ->group(function () {
            Route::get('list', 'getListSchedule');
            Route::post('', 'createSchedule');
            Route::post('{id}', 'updateSchedule')->where(['id' => '[0-9]+']);
            Route::delete('{id}', 'deleteSchedule');
            Route::post('check-delete', 'checkStaffDeleteLivestream');
            Route::post('start/{id}', 'startLivestream');
            Route::post('end/{id}', 'endLivestream');
            Route::post('disconnect/{id}', 'disconnectLivestream');
            Route::post('now', 'livestreamNow');
            Route::get('check-staff', 'checkStaffIsLivestream');
            Route::post('start-record/{id}', 'startRecord');
            Route::post('stop-record/{id}', 'stopRecord');
            Route::get('check-record/{id}', 'checkLivestreamRecorded');
        });

    //banks
    Route::prefix('banks')
        ->controller(BankController::class)
        ->group(function () {
            Route::get('', 'getBankList');
            Route::get('branch/{bankId}', 'getBankBranchList');
        });
});

// CMS
Route::prefix('cms')
    ->group(function () {
        Route::middleware(['auth:sanctum', 'is_admin'])
            ->group(function () {
                // manager categories
                Route::prefix('categories')
                    ->controller(CategoryController::class)
                    ->group(function () {
                        Route::get('list', 'getListCategoryCMS');
                        Route::get('detail/{id}', 'getInfoCategory');
                        Route::post('create', 'createCategory');
                        Route::post('{id}', 'updateCategory');
                        Route::delete('{id}', 'deleteCategory');
                    });

                // manager brands
                Route::prefix('brands')
                    ->controller(BrandController::class)
                    ->group(function () {
                        Route::post('create', 'createBrand');
                        Route::put('{id}', 'updateBrand');
                        Route::delete('{id}', 'deleteBrand');
                    });

                Route::prefix('store')
                    ->controller(StoreController::class)
                    ->group(function () {
                        Route::get('list', 'getListStoreCMS');
                        Route::get('{id}', 'getStoreDetailCMS');
                        Route::put('{id}/setting-commission', 'settingCommission');
                        Route::get('{id}/bank/detail', 'getDetailBank');
                    });

                Route::prefix('product')
                    ->controller(ManagerProductController::class)
                    ->group(function () {
                        Route::get('list', 'getList');
                        Route::put('{id}/mark-violation', 'markViolation');
                        Route::put('{id}/unmark-violation', 'unmarkViolation');
                    });

                // manager upgrade account
                Route::prefix('upgrade-account')
                    ->controller(AdminController::class)
                    ->group(function () {
                        Route::get('list', 'getListAccountUpgrade');
                        Route::get('list/count-status', 'countStatusListAccount');
                        Route::get('{id}', 'detailAccountUpgrade')->where(['id' => '[0-9]+']);
                        Route::post('approve', 'approveRequestUpgrade');
                        Route::post('cancel', 'cancelRequestUpgrade');
                    });

                // Manage customer
                Route::prefix('customer')
                    ->controller(CustomerController::class)
                    ->group(function () {
                        Route::get('list', 'getCustomerListCMS');
                        Route::get('{id}', 'getCustomerDetailCMS');
                    });

                // Manage order
                Route::prefix('order')
                    ->controller(SubOrderController::class)
                    ->group(function () {
                        Route::get('list', 'getOrderListCMS');
                        Route::get('{id}', 'getOrderDetailCMS');
                        Route::get('export-pdf/{id}', 'exportPdf');
                    });

                // manager revenue ̥
                Route::prefix('manager-revenue')
                    ->controller(AdminController::class)
                    ->group(function () {
                        Route::get('order', 'getRevenueOrderByStore');
                        Route::get('order/export', 'exportRevenueOfStoreByOrder')
                            ->middleware('language');
                        Route::get('product', 'getRevenueOfStoreByProduct');
                        Route::get('product/export', 'exportRevenueOfStoreByProduct')
                            ->middleware('language');
                        Route::get('age', 'statisticRevenueOfStoreByAge');
                        Route::get('age/export', 'exportRevenueOfStoreByAge')
                            ->middleware('language');
                    });

                // manager livestream
                Route::prefix('livestream')
                    ->controller(ManagerLiveStreamController::class)
                    ->group(function () {
                        Route::get('list', 'getListScheduleCMS');
                        Route::put('{id}', 'markViolation')->where(['id' => '[0-9]+']);
                        Route::delete('{id}', 'deleteLivestreamCms');
                    });
                // withdraw, history payout stripe
                Route::prefix('payouts')
                    ->controller(PayoutController::class)
                    ->group(function () {
                        Route::get('history', 'getPayoutHistoryCMS');
                        Route::get('history/count-status', 'countStatusPayout');
                        Route::get('history/detail/{payoutId}', 'getPayoutHistoryDetailCMS');
                        Route::get('retrieve/{storeId}', 'getPayoutRetrieveCMS');
                    });
            });

        // manager account
        Route::controller(AdminController::class)
            ->group(function () {
                Route::post('login', 'login');
                Route::post('reset-password', 'sendMailResetPassword');
                Route::put('reset-password/{token}', 'resetPassword');
                Route::post('resend-mail', 'reSendMail');
                Route::post('validate-link/reset-password', 'validateLinkResetPassword');
                Route::middleware(['auth:sanctum', 'is_admin'])
                    ->group(function () {
                        Route::get('me', 'getInfoAdmin');
                        Route::post('logout', 'logout');
                        Route::put('change-password', 'changePassword');
                        Route::get('group-chat', 'getGroupChatInformation');
                    });
            });
    });

Route::controller(StripeWebhookController::class)
    ->group(function () {
        Route::post('stripe-webhook/account', 'handelEventStripeAccount');
        Route::post('stripe-webhook/payouts', 'handelEventStripePayout');
        Route::post('stripe-webhook/checkout/expired', 'handelEventStripeCheckoutExpired');
    });

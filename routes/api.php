<?php
/**
 * @author Xanders
 * @see https://www.linkedin.com/in/xanders-samoth-b2770737/
 */
use App\Models\LegalInfoSubject;
use App\Http\Controllers\API\BaseController;
use App\Http\Resources\LegalInfoSubject as ResourcesLegalInfoSubject;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Default API resource
|--------------------------------------------------------------------------
 */
Route::middleware(['auth:api', 'localization'])->group(function () {

    Route::apiResource('legal_info_subject', 'App\Http\Controllers\API\LegalInfoSubjectController');
    Route::apiResource('legal_info_title', 'App\Http\Controllers\API\LegalInfoTitleController');
    Route::apiResource('legal_info_content', 'App\Http\Controllers\API\LegalInfoContentController');
    Route::apiResource('group', 'App\Http\Controllers\API\GroupController');
    Route::apiResource('type', 'App\Http\Controllers\API\TypeController');
    Route::apiResource('image', 'App\Http\Controllers\API\ImageController');
    Route::apiResource('address', 'App\Http\Controllers\API\AddressController');
    Route::apiResource('role', 'App\Http\Controllers\API\RoleController');
    Route::apiResource('role_user', 'App\Http\Controllers\API\RoleUserController');
    Route::apiResource('password_reset', 'App\Http\Controllers\API\PasswordResetController');
    Route::apiResource('message', 'App\Http\Controllers\API\MessageController');
    Route::apiResource('news', 'App\Http\Controllers\API\NewsController');
});
/*
|--------------------------------------------------------------------------
| Custom API resource
|--------------------------------------------------------------------------
 */
Route::group(['middleware' => ['api', 'localization']], function () {
    Route::resource('status', 'App\Http\Controllers\API\StatusController');
    Route::resource('country', 'App\Http\Controllers\API\CountryController');
    Route::resource('user', 'App\Http\Controllers\API\UserController');
    Route::resource('notification', 'App\Http\Controllers\API\NotificationController');
    Route::resource('offer', 'App\Http\Controllers\API\OfferController');
    Route::resource('payment', 'App\Http\Controllers\API\PaymentController');

    // Status
    Route::get('status/search/{data}', 'App\Http\Controllers\API\StatusController@search')->name('status.api.search');
    Route::get('status/find_by_group/{group_name}', 'App\Http\Controllers\API\StatusController@findByGroup')->name('status.api.find_by_group');
    // Country
    Route::get('country', 'App\Http\Controllers\API\CountryController@index')->name('country.api.index');
    // User
    Route::get('user/{id}', 'App\Http\Controllers\API\UserController@show')->name('user.api.show');
    Route::get('user/get_api_token/{phone}', 'App\Http\Controllers\API\UserController@getApiToken')->name('user.api.get_api_token');
    Route::post('user/login', 'App\Http\Controllers\API\UserController@login')->name('user.api.login');
    // Notification
    Route::post('notification/store', 'App\Http\Controllers\API\NotificationController@store')->name('notification.api.store');
    // Offer
    Route::post('offer/store', 'App\Http\Controllers\API\OfferController@store')->name('offer.api.store');
    // Payment
    Route::post('payment/store', 'App\Http\Controllers\API\PaymentController@store')->name('payment.api.store');
});
Route::group(['middleware' => ['api', 'auth:api', 'localization']], function () {
    Route::resource('legal_info_title', 'App\Http\Controllers\API\LegalInfoTitleController');
    Route::resource('legal_info_content', 'App\Http\Controllers\API\LegalInfoContentController');
    Route::resource('group', 'App\Http\Controllers\API\GroupController');
    Route::resource('status', 'App\Http\Controllers\API\StatusController');
    Route::resource('type', 'App\Http\Controllers\API\TypeController');
    Route::resource('country', 'App\Http\Controllers\API\CountryController');
    Route::resource('role', 'App\Http\Controllers\API\RoleController');
    Route::resource('status', 'App\Http\Controllers\API\StatusController');
    Route::resource('user', 'App\Http\Controllers\API\UserController');
    Route::resource('password_reset', 'App\Http\Controllers\API\PasswordResetController');
    Route::resource('message', 'App\Http\Controllers\API\MessageController');
    Route::resource('notification', 'App\Http\Controllers\API\NotificationController');
    Route::resource('news', 'App\Http\Controllers\API\NewsController');
    Route::resource('offer', 'App\Http\Controllers\API\OfferController');
    Route::resource('payment', 'App\Http\Controllers\API\PaymentController');

    // LegalInfoTitle
    Route::get('legal_info_title/search/{data}', 'App\Http\Controllers\API\LegalInfoTitleController@search')->name('legal_info_title.api.search');
    // LegalInfoContent
    Route::get('legal_info_content/search/{data}', 'App\Http\Controllers\API\LegalInfoContentController@search')->name('legal_info_content.api.search');
    Route::put('legal_info_content/add_image/{id}', 'App\Http\Controllers\API\LegalInfoContentController@addImage')->name('legal_info_content.api.add_image');
    // Group
    Route::get('group/search/{data}', 'App\Http\Controllers\API\GroupController@search')->name('group.api.search');
    // Status
    Route::get('status', 'App\Http\Controllers\API\StatusController@index')->name('status.api.index');
    Route::get('status/{id}', 'App\Http\Controllers\API\StatusController@show')->name('status.api.show');
    Route::put('status/{id}', 'App\Http\Controllers\API\StatusController@update')->name('status.api.update');
    Route::delete('status/{id}', 'App\Http\Controllers\API\StatusController@destroy')->name('status.api.destroy');
    // Type
    Route::get('type/search/{data}', 'App\Http\Controllers\API\TypeController@search')->name('type.api.search');
    Route::get('type/find_by_group/{group_name}', 'App\Http\Controllers\API\TypeController@findByGroup')->name('type.api.find_by_group');
    // Country
    Route::post('country', 'App\Http\Controllers\API\CountryController@store')->name('country.api.store');
    Route::get('country/{id}', 'App\Http\Controllers\API\CountryController@show')->name('country.api.show');
    Route::put('country/{id}', 'App\Http\Controllers\API\CountryController@update')->name('country.api.update');
    Route::delete('country/{id}', 'App\Http\Controllers\API\CountryController@destroy')->name('country.api.destroy');
    Route::get('country/search/{data}', 'App\Http\Controllers\API\CountryController@search')->name('country.api.search');
    // Address
    Route::get('address/search/{type_name}/{user_id}', 'App\Http\Controllers\API\AddressController@search')->name('address.api.search');
    // Role
    Route::get('role/search/{data}', 'App\Http\Controllers\API\RoleController@search')->name('role.api.search');
    // User
    Route::get('user', 'App\Http\Controllers\API\UserController@index')->name('user.api.index');
    Route::get('user/{id}', 'App\Http\Controllers\API\UserController@show')->name('user.api.show');
    Route::put('user/{id}', 'App\Http\Controllers\API\UserController@update')->name('user.api.update');
    Route::delete('user/{id}', 'App\Http\Controllers\API\UserController@destroy')->name('user.api.destroy');
    Route::get('user/search/{data}', 'App\Http\Controllers\API\UserController@search')->name('user.api.search');
    Route::get('user/find_by_not_role/{role_name}', 'App\Http\Controllers\API\UserController@findByNotRole')->name('user.api.find_by_not_role');
    Route::get('user/find_by_role/{role_name}', 'App\Http\Controllers\API\UserController@findByRole')->name('user.api.find_by_role');
    Route::get('user/find_by_status/{status_id}', 'App\Http\Controllers\API\UserController@findByStatus')->name('user.api.find_by_status');
    Route::put('user/switch_status/{id}/{status_id}', 'App\Http\Controllers\API\UserController@switchStatus')->name('user.api.switch_status');
    Route::put('user/update_role/{id}', 'App\Http\Controllers\API\UserController@updateRole')->name('user.api.update_role');
    Route::put('user/update_password/{id}', 'App\Http\Controllers\API\UserController@updatePassword')->name('user.api.update_password');
    Route::put('user/update_api_token/{phone}', 'App\Http\Controllers\API\UserController@updateApiToken')->name('user.api.update_api_token');
    Route::put('user/update_avatar_picture/{id}', 'App\Http\Controllers\API\UserController@updateAvatarPicture')->name('user.api.update_avatar_picture');
    Route::put('user/add_image/{id}', 'App\Http\Controllers\API\UserController@addImage')->name('user.add_image');
    // PasswordReset
    Route::get('password_reset/search_by_email/{data}', 'App\Http\Controllers\API\PasswordResetController@searchByEmail')->name('password_reset.api.search_by_email');
    Route::get('password_reset/search_by_phone/{data}', 'App\Http\Controllers\API\PasswordResetController@searchByPhone')->name('password_reset.api.search_by_phone');
    // Message
    Route::get('message/search/{data}', 'App\Http\Controllers\API\MessageController@search')->name('message.api.search');
    Route::get('message/inbox/{entity}', 'App\Http\Controllers\API\MessageController@inbox')->name('message.api.inbox');
    Route::get('message/outbox/{user_id}', 'App\Http\Controllers\API\MessageController@outbox')->name('message.api.outbox');
    Route::get('message/answers/{message_id}', 'App\Http\Controllers\API\MessageController@answers')->name('message.api.answers');
    // Notification
    Route::get('notification', 'App\Http\Controllers\API\NotificationController@index')->name('notification.api.index');
    Route::get('notification/{id}', 'App\Http\Controllers\API\NotificationController@show')->name('notification.api.show');
    Route::put('notification/{id}', 'App\Http\Controllers\API\NotificationController@update')->name('notification.api.update');
    Route::delete('notification/{id}', 'App\Http\Controllers\API\NotificationController@destroy')->name('notification.api.destroy');
    Route::get('notification/select_by_user/{user_id}', 'App\Http\Controllers\API\NotificationController@selectByUser')->name('notification.api.select_by_user');
    Route::put('notification/switch_status/{id}/{status_id}', 'App\Http\Controllers\API\NotificationController@switchStatus')->name('notification.api.switch_status');
    Route::put('notification/mark_all_read/{user_id}', 'App\Http\Controllers\API\NotificationController@markAllRead')->name('notification.api.mark_all_read');
    // News
    Route::get('news/select_by_type/{type_id}', 'App\Http\Controllers\API\NewsController@selectByType')->name('news.api.select_by_type');
    Route::get('news/select_by_not_type/{type_id}', 'App\Http\Controllers\API\NewsController@selectByNotType')->name('news.api.select_by_not_type');
    Route::put('news/add_image/{id}', 'App\Http\Controllers\API\NewsController@addImage')->name('news.api.add_image');
    // Offer
    Route::get('offer', 'App\Http\Controllers\API\OfferController@index')->name('offer.api.index');
    Route::get('offer/{id}', 'App\Http\Controllers\API\OfferController@show')->name('offer.api.show');
    Route::put('offer/{id}', 'App\Http\Controllers\API\OfferController@update')->name('offer.api.update');
    Route::delete('offer/{id}', 'App\Http\Controllers\API\OfferController@destroy')->name('offer.api.destroy');
    // Payment
    Route::get('payment', 'App\Http\Controllers\API\PaymentController@index')->name('payment.api.index');
    Route::get('payment/find_by_phone/{phone_number}', 'App\Http\Controllers\API\PaymentController@findByPhone')->name('payment.api.find_by_phone');
    Route::get('payment/find_by_order_number/{order_number}', 'App\Http\Controllers\API\PaymentController@findByOrderNumber')->name('payment.api.find_by_order_number');
    Route::get('payment/find_by_order_number_user/{order_number}/{user_id}', 'App\Http\Controllers\API\PaymentController@findByOrderNumberUser')->name('payment.api.find_by_order_number_user');
    Route::put('payment/switch_status/{status_id}/{id}', 'App\Http\Controllers\API\PaymentController@switchStatus')->name('payment.api.switch_status');

    // Functions created directly here
    Route::get('about_subject/about_foundation', function () {
        $baseController = new BaseController();
        $legal_info_subject = LegalInfoSubject::where('subject_name', 'A propos de la fondation Jean Pierre Tshienda')->first();

        if (is_null($legal_info_subject)) {
            return $baseController->handleError(__('notifications.find_legal_info_subject_404'));
        }

        return $baseController->handleResponse(new ResourcesLegalInfoSubject($legal_info_subject), __('notifications.find_legal_info_subject_success'));

    });
    Route::get('about_subject/about_app', function () {
        $baseController = new BaseController();
        $legal_info_subject = LegalInfoSubject::where('subject_name', 'L\'application JPTShienda')->first();

        if (is_null($legal_info_subject)) {
            return $baseController->handleError(__('notifications.find_legal_info_subject_404'));
        }

        return $baseController->handleResponse(new ResourcesLegalInfoSubject($legal_info_subject), __('notifications.find_legal_info_subject_success'));

    });
    Route::get('about_subject/help_center', function () {
        $baseController = new BaseController();
        $legal_info_subject = LegalInfoSubject::where('subject_name', 'Centre d\'aide')->first();

        if (is_null($legal_info_subject)) {
            return $baseController->handleError(__('notifications.find_legal_info_subject_404'));
        }

        return $baseController->handleResponse(new ResourcesLegalInfoSubject($legal_info_subject), __('notifications.find_legal_info_subject_success'));

    });
    Route::get('about_subject/faq', function () {
        $baseController = new BaseController();
        $legal_info_subject = LegalInfoSubject::where('subject_name', 'FAQ')->first();

        if (is_null($legal_info_subject)) {
            return $baseController->handleError(__('notifications.find_legal_info_subject_404'));
        }

        return $baseController->handleResponse(new ResourcesLegalInfoSubject($legal_info_subject), __('notifications.find_legal_info_subject_success'));

    });
    Route::get('about_subject/terms', function () {
        $baseController = new BaseController();
        $legal_info_subject = LegalInfoSubject::where('subject_name', 'Conditions d\'utilisation')->first();

        if (is_null($legal_info_subject)) {
            return $baseController->handleError(__('notifications.find_legal_info_subject_404'));
        }

        return $baseController->handleResponse(new ResourcesLegalInfoSubject($legal_info_subject), __('notifications.find_legal_info_subject_success'));

    });
    Route::get('about_subject/privacy_policy', function () {
        $baseController = new BaseController();
        $legal_info_subject = LegalInfoSubject::where('subject_name', 'Politique de confidentialité')->first();

        if (is_null($legal_info_subject)) {
            return $baseController->handleError(__('notifications.find_legal_info_subject_404'));
        }

        return $baseController->handleResponse(new ResourcesLegalInfoSubject($legal_info_subject), __('notifications.find_legal_info_subject_success'));

    });
});

<?php
/**
 * @author Xanders
 * @see https://www.linkedin.com/in/xanders-samoth-b2770737/
 */
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Default API resource
|--------------------------------------------------------------------------
 */
Route::middleware(['auth:sanctum', 'localization'])->group(function () {
    Route::apiResource('group', 'App\Http\Controllers\API\GroupController');
    Route::apiResource('order', 'App\Http\Controllers\API\OrderController');
    Route::apiResource('pricing', 'App\Http\Controllers\API\PricingController')->except(['index']);
    Route::apiResource('role', 'App\Http\Controllers\API\RoleController')->except(['search']);
    Route::apiResource('password_reset', 'App\Http\Controllers\API\PasswordResetController')->except(['searchByEmailOrPhone', 'searchByEmail', 'searchByPhone', 'checkToken']);
    Route::apiResource('personal_access_token', 'App\Http\Controllers\API\PersonalAccessTokenController');
    Route::apiResource('notification', 'App\Http\Controllers\API\NotificationController');
    Route::apiResource('donation', 'App\Http\Controllers\API\DonationController');
    Route::apiResource('payment', 'App\Http\Controllers\API\PaymentController');
});
/*
|--------------------------------------------------------------------------
| Custom API resource
|--------------------------------------------------------------------------
 */
Route::group(['middleware' => ['api', 'localization']], function () {
    Route::resource('legal_info_subject', 'App\Http\Controllers\API\LegalInfoSubjectController');
    Route::resource('legal_info_title', 'App\Http\Controllers\API\LegalInfoTitleController');
    Route::resource('status', 'App\Http\Controllers\API\StatusController');
    Route::resource('type', 'App\Http\Controllers\API\TypeController');
    Route::resource('category', 'App\Http\Controllers\API\CategoryController');
    Route::resource('country', 'App\Http\Controllers\API\CountryController');
    Route::resource('book', 'App\Http\Controllers\API\BookController');
    Route::resource('media', 'App\Http\Controllers\API\MediaController');
    Route::resource('pricing', 'App\Http\Controllers\API\PricingController');
    Route::resource('role', 'App\Http\Controllers\API\RoleController');
    Route::resource('user', 'App\Http\Controllers\API\UserController');
    Route::resource('password_reset', 'App\Http\Controllers\API\PasswordResetController');
    Route::resource('payment', 'App\Http\Controllers\API\PaymentController');

    // LegalInfoSubject
    Route::get('legal_info_subject', 'App\Http\Controllers\API\LegalInfoSubjectController@index')->name('legal_info_subject.api.index');
    Route::get('legal_info_subject/{id}', 'App\Http\Controllers\API\LegalInfoSubjectController@show')->name('legal_info_subject.api.show');
    Route::get('legal_info_subject/search/{locale}/{data}', 'App\Http\Controllers\API\LegalInfoSubjectController@search')->name('legal_info_subject.api.search');
    // LegalInfoTitle
    Route::get('legal_info_title', 'App\Http\Controllers\API\LegalInfoTitleController@index')->name('legal_info_title.api.index');
    Route::get('legal_info_title/{id}', 'App\Http\Controllers\API\LegalInfoTitleController@show')->name('legal_info_title.api.show');
    Route::get('legal_info_title/search/{locale}/{data}', 'App\Http\Controllers\API\LegalInfoSubjectController@search')->name('legal_info_title.api.search');
    // LegalInfoContent
    Route::get('legal_info_content', 'App\Http\Controllers\API\LegalInfoContentController@index')->name('legal_info_content.api.index');
    Route::get('legal_info_content/{id}', 'App\Http\Controllers\API\LegalInfoContentController@show')->name('legal_info_content.api.show');
    Route::get('legal_info_content/search/{locale}/{data}', 'App\Http\Controllers\API\LegalInfoContentController@search')->name('legal_info_content.api.search');
    // Status
    Route::get('status', 'App\Http\Controllers\API\StatusController@index')->name('status.api.index');
    Route::get('status/{id}', 'App\Http\Controllers\API\StatusController@show')->name('status.api.show');
    Route::get('status/search/{locale}/{data}', 'App\Http\Controllers\API\StatusController@search')->name('status.api.search');
    Route::get('status/find_by_group/{locale}/{group_name}', 'App\Http\Controllers\API\StatusController@findByGroup')->name('status.api.find_by_group');
    // Type
    Route::get('type', 'App\Http\Controllers\API\TypeController@index')->name('type.api.index');
    Route::get('type/{id}', 'App\Http\Controllers\API\TypeController@show')->name('type.api.show');
    Route::get('type/search/{locale}/{data}', 'App\Http\Controllers\API\TypeController@search')->name('type.api.search');
    Route::get('type/find_by_group/{locale}/{group_name}', 'App\Http\Controllers\API\TypeController@findByGroup')->name('type.api.find_by_group');
    // Category
    Route::get('category', 'App\Http\Controllers\API\CategoryController@index')->name('category.api.index');
    Route::get('category/{id}', 'App\Http\Controllers\API\CategoryController@show')->name('category.api.show');
    Route::get('category/all_used_categories/{for_youth}', 'App\Http\Controllers\API\CategoryController@allUsedCategories')->name('category.api.all_used_categories');
    Route::get('category/search/{locale}/{data}', 'App\Http\Controllers\API\CategoryController@search')->name('category.api.search');
    // Country
    Route::get('country', 'App\Http\Controllers\API\CountryController@index')->name('country.api.index');
    Route::get('country/{id}', 'App\Http\Controllers\API\CountryController@show')->name('country.api.show');
    Route::get('country/search/{data}', 'App\Http\Controllers\API\CountryController@search')->name('country.api.search');
    // Book
    Route::get('book', 'App\Http\Controllers\API\BookController@index')->name('book.api.index');
    Route::get('book/{id}', 'App\Http\Controllers\API\BookController@show')->name('book.api.show');
    Route::get('book/search/{data}', 'App\Http\Controllers\API\BookController@search')->name('book.api.search');
    Route::get('book/find_all_by_age/{for_youth}', 'App\Http\Controllers\API\BookController@findAllByAge')->name('book.api.find_all_by_age');
    Route::get('book/find_all_by_age_type/{for_youth}/{type_id}', 'App\Http\Controllers\API\BookController@findAllByAgeType')->name('book.api.find_all_by_age_type');
    // Media
    Route::get('media', 'App\Http\Controllers\API\MediaController@index')->name('media.api.index');
    Route::get('media/trends/{year}', 'App\Http\Controllers\API\MediaController@trends')->name('media.api.trends');
    Route::get('media/{id}', 'App\Http\Controllers\API\MediaController@show')->name('media.api.show');
    Route::get('media/search/{data}', 'App\Http\Controllers\API\MediaController@search')->name('media.api.search');
    Route::get('media/find_live/{for_youth}', 'App\Http\Controllers\API\MediaController@findLive')->name('media.api.find_live');
    Route::get('media/find_all_by_type/{locale}/{type_name}', 'App\Http\Controllers\API\MediaController@findAllByType')->name('media.api.find_all_by_type');
    Route::get('media/find_all_by_age_type/{for_youth}/{type_id}', 'App\Http\Controllers\API\MediaController@findAllByAgeType')->name('media.api.find_all_by_age_type');
    Route::get('media/find_views/{media_id}', 'App\Http\Controllers\API\MediaController@findViews')->name('media.api.find_views');
    Route::get('media/find_likes/{media_id}', 'App\Http\Controllers\API\MediaController@findLikes')->name('media.api.find_likes');
    Route::post('media/filter_by_categories', 'App\Http\Controllers\API\MediaController@filterByCategories')->name('media.api.filter_by_categories');
    Route::put('media/switch_view/{media_id}', 'App\Http\Controllers\API\MediaController@switchView')->name('media.api.switch_view');
    // Pricing
    Route::get('pricing', 'App\Http\Controllers\API\PricingController@index')->name('pricing.api.index');
    // Role
    Route::get('role/search/{data}', 'App\Http\Controllers\API\RoleController@search')->name('role.api.search');
    // User
    Route::post('user', 'App\Http\Controllers\API\UserController@store')->name('user.api.store');
    Route::post('user/login', 'App\Http\Controllers\API\UserController@login')->name('user.api.login');
    // PasswordReset
    Route::get('password_reset/search_by_email_or_phone/{data}', 'App\Http\Controllers\API\PasswordResetController@searchByEmailOrPhone')->name('password_reset.api.search_by_email_or_phone');
    Route::get('password_reset/search_by_email/{data}', 'App\Http\Controllers\API\PasswordResetController@searchByEmail')->name('password_reset.api.search_by_email');
    Route::get('password_reset/search_by_phone/{data}', 'App\Http\Controllers\API\PasswordResetController@searchByPhone')->name('password_reset.api.search_by_phone');
    Route::post('password_reset/check_token', 'App\Http\Controllers\API\PasswordResetController@checkToken')->name('password_reset.api.check_token');
    // Payment
    Route::post('payment/store', 'App\Http\Controllers\API\PaymentController@store')->name('payment.api.store');
});
Route::group(['middleware' => ['api', 'auth:sanctum', 'localization']], function () {
    Route::resource('legal_info_subject', 'App\Http\Controllers\API\LegalInfoSubjectController')->except(['index', 'show', 'search']);
    Route::resource('legal_info_title', 'App\Http\Controllers\API\LegalInfoTitleController')->except(['index', 'show', 'search']);
    Route::resource('legal_info_content', 'App\Http\Controllers\API\LegalInfoContentController')->except(['index', 'show', 'search']);
    Route::resource('status', 'App\Http\Controllers\API\StatusController')->except(['index', 'show', 'search', 'findByGroup']);
    Route::resource('type', 'App\Http\Controllers\API\TypeController')->except(['index', 'show', 'search', 'findByGroup']);
    Route::resource('category', 'App\Http\Controllers\API\CategoryController')->except(['index', 'show', 'allUsedCategories', 'search']);
    Route::resource('country', 'App\Http\Controllers\API\CountryController')->except(['index', 'show', 'search']);
    Route::resource('book', 'App\Http\Controllers\API\BookController')->except(['index', 'show', 'search', 'findAllByAge', 'findAllByAgeType']);
    Route::resource('media', 'App\Http\Controllers\API\MediaController')->except(['index', 'show', 'trends', 'search', 'findLive', 'findAllByType', 'findAllByAgeType', 'findViews', 'findLikes', 'switchView', 'filterByCategories']);
    Route::resource('cart', 'App\Http\Controllers\API\CartController');
    Route::resource('user', 'App\Http\Controllers\API\UserController')->except(['store', 'login']);
    Route::resource('notification', 'App\Http\Controllers\API\NotificationController');
    Route::resource('payment', 'App\Http\Controllers\API\PaymentController');

    // LegalInfoSubject
    Route::post('legal_info_subject', 'App\Http\Controllers\API\LegalInfoSubjectController@store')->name('legal_info_subject.api.store');
    Route::put('legal_info_subject/{id}', 'App\Http\Controllers\API\LegalInfoSubjectController@update')->name('legal_info_subject.api.update');
    Route::delete('legal_info_subject/{id}', 'App\Http\Controllers\API\LegalInfoSubjectController@destroy')->name('legal_info_subject.api.destroy');
    Route::post('legal_info_subject/register_subject/{subject}', 'App\Http\Controllers\API\LegalInfoSubjectController@registerSubject')->name('legal_info_subject.api.register_subject');
    // LegalInfoTitle
    Route::post('legal_info_title', 'App\Http\Controllers\API\LegalInfoTitleController@store')->name('legal_info_title.api.store');
    Route::put('legal_info_title/{id}', 'App\Http\Controllers\API\LegalInfoTitleController@update')->name('legal_info_title.api.update');
    Route::delete('legal_info_subject/{id}', 'App\Http\Controllers\API\LegalInfoTitleController@destroy')->name('legal_info_subject.api.destroy');
    // LegalInfoContent
    Route::post('legal_info_content', 'App\Http\Controllers\API\LegalInfoContentController@store')->name('legal_info_content.api.store');
    Route::put('legal_info_content/{id}', 'App\Http\Controllers\API\LegalInfoContentController@update')->name('legal_info_content.api.update');
    Route::delete('legal_info_content/{id}', 'App\Http\Controllers\API\LegalInfoContentController@destroy')->name('legal_info_content.api.destroy');
    Route::put('legal_info_content/add_image/{id}', 'App\Http\Controllers\API\LegalInfoContentController@addImage')->name('legal_info_content.api.add_image');
    // Status
    Route::post('status', 'App\Http\Controllers\API\StatusController@store')->name('status.api.store');
    Route::put('status/{id}', 'App\Http\Controllers\API\StatusController@update')->name('status.api.update');
    Route::delete('status/{id}', 'App\Http\Controllers\API\StatusController@destroy')->name('status.api.destroy');
    // Type
    Route::post('type', 'App\Http\Controllers\API\TypeController@store')->name('type.api.store');
    Route::put('type/{id}', 'App\Http\Controllers\API\TypeController@update')->name('type.api.update');
    Route::delete('type/{id}', 'App\Http\Controllers\API\TypeController@destroy')->name('type.api.destroy');
    // Category
    Route::post('category', 'App\Http\Controllers\API\CategoryController@store')->name('category.api.store');
    Route::put('category/{id}', 'App\Http\Controllers\API\CategoryController@update')->name('category.api.update');
    Route::delete('category/{id}', 'App\Http\Controllers\API\CategoryController@destroy')->name('category.api.destroy');
    // Country
    Route::post('country', 'App\Http\Controllers\API\CountryController@store')->name('country.api.store');
    Route::put('country/{id}', 'App\Http\Controllers\API\CountryController@update')->name('country.api.update');
    Route::delete('country/{id}', 'App\Http\Controllers\API\CountryController@destroy')->name('country.api.destroy');
    // Book
    Route::post('book', 'App\Http\Controllers\API\BookController@store')->name('book.api.store');
    Route::put('book/{id}', 'App\Http\Controllers\API\BookController@update')->name('book.api.update');
    Route::delete('book/{id}', 'App\Http\Controllers\API\BookController@destroy')->name('book.api.destroy');
    Route::put('book/add_image/{id}', 'App\Http\Controllers\API\BookController@addImage')->name('book.api.add_image');
    // Media
    Route::post('media', 'App\Http\Controllers\API\MediaController@store')->name('media.api.store');
    Route::put('media/{id}', 'App\Http\Controllers\API\MediaController@update')->name('media.api.update');
    Route::delete('media/{id}', 'App\Http\Controllers\API\MediaController@destroy')->name('media.api.destroy');
    Route::put('media/set_approbation/{user_id}/{media_id}/{status_id}', 'App\Http\Controllers\API\MediaController@setApprobation')->name('media.api.set_approbation');
    Route::put('media/switch_like/{user_id}/{media_id}', 'App\Http\Controllers\API\MediaController@switchLike')->name('media.api.switch_like');
    Route::put('media/add_image/{id}', 'App\Http\Controllers\API\MediaController@addImage')->name('media.api.add_image');
    // Cart
    Route::get('cart', 'App\Http\Controllers\API\CartController@index')->name('cart.api.index');
    Route::post('cart', 'App\Http\Controllers\API\CartController@store')->name('cart.api.store');
    Route::get('cart/{id}', 'App\Http\Controllers\API\CartController@show')->name('cart.api.show');
    Route::put('cart/{id}', 'App\Http\Controllers\API\CartController@update')->name('cart.api.update');
    Route::delete('cart/{id}', 'App\Http\Controllers\API\CartController@destroy')->name('cart.api.destroy');
    Route::get('cart/find_by_type/{user_id}/{type_id}', 'App\Http\Controllers\API\CartController@findByType')->name('cart.api.find_by_type');
    // User
    Route::get('user', 'App\Http\Controllers\API\UserController@index')->name('user.api.index');
    Route::get('user/{id}', 'App\Http\Controllers\API\UserController@show')->name('user.api.show');
    Route::put('user/{id}', 'App\Http\Controllers\API\UserController@update')->name('user.api.update');
    Route::delete('user/{id}', 'App\Http\Controllers\API\UserController@destroy')->name('user.api.destroy');
    Route::get('user/profile/{username}', 'App\Http\Controllers\API\UserController@profile')->name('user.api.profile');
    Route::get('user/find_by_role/{locale}/{role_name}', 'App\Http\Controllers\API\UserController@findByRole')->name('user.api.find_by_role');
    Route::get('user/find_by_not_role/{locale}/{role_name}', 'App\Http\Controllers\API\UserController@findByNotRole')->name('user.api.find_by_not_role');
    Route::get('user/find_by_status/{status_id}', 'App\Http\Controllers\API\UserController@findByStatus')->name('user.api.find_by_status');
    Route::get('user/find_by_parental_code/{parental_code}/{user_id}', 'App\Http\Controllers\API\UserController@findByParentalCode')->name('user.api.find_by_parental_code');
    Route::put('user/switch_status/{id}/{status_id}', 'App\Http\Controllers\API\UserController@switchStatus')->name('user.api.switch_status');
    Route::put('user/update_role/{id}', 'App\Http\Controllers\API\UserController@updateRole')->name('user.api.update_role');
    Route::put('user/update_password/{id}', 'App\Http\Controllers\API\UserController@updatePassword')->name('user.api.update_password');
    Route::put('user/update_avatar_picture/{id}', 'App\Http\Controllers\API\UserController@updateAvatarPicture')->name('user.api.update_avatar_picture');
    Route::put('user/add_image/{id}', 'App\Http\Controllers\API\UserController@addImage')->name('user.api.add_image');
    // Notification
    Route::get('notification', 'App\Http\Controllers\API\NotificationController@index')->name('notification.api.index');
    Route::post('notification/store', 'App\Http\Controllers\API\NotificationController@store')->name('notification.api.store');
    Route::get('notification/{id}', 'App\Http\Controllers\API\NotificationController@show')->name('notification.api.show');
    Route::put('notification/{id}', 'App\Http\Controllers\API\NotificationController@update')->name('notification.api.update');
    Route::delete('notification/{id}', 'App\Http\Controllers\API\NotificationController@destroy')->name('notification.api.destroy');
    Route::get('notification/select_by_user/{user_id}', 'App\Http\Controllers\API\NotificationController@selectByUser')->name('notification.api.select_by_user');
    Route::put('notification/switch_status/{id}/{status_id}', 'App\Http\Controllers\API\NotificationController@switchStatus')->name('notification.api.switch_status');
    Route::put('notification/mark_all_read/{user_id}', 'App\Http\Controllers\API\NotificationController@markAllRead')->name('notification.api.mark_all_read');
    // Payment
    Route::get('payment', 'App\Http\Controllers\API\PaymentController@index')->name('payment.api.index');
    Route::post('payment/store', 'App\Http\Controllers\API\PaymentController@store')->name('payment.api.store');
    Route::get('payment/{id}', 'App\Http\Controllers\API\PaymentController@show')->name('payment.api.show');
    Route::put('payment/{id}', 'App\Http\Controllers\API\PaymentController@update')->name('payment.api.update');
    Route::delete('payment/{id}', 'App\Http\Controllers\API\PaymentController@destroy')->name('payment.api.destroy');
    Route::get('payment/find_by_phone/{phone_number}', 'App\Http\Controllers\API\PaymentController@findByPhone')->name('payment.api.find_by_phone');
    Route::get('payment/find_by_order_number/{order_number}', 'App\Http\Controllers\API\PaymentController@findByOrderNumber')->name('payment.api.find_by_order_number');
    Route::get('payment/find_by_order_number_user/{order_number}/{user_id}', 'App\Http\Controllers\API\PaymentController@findByOrderNumberUser')->name('payment.api.find_by_order_number_user');
    Route::put('payment/switch_status/{status_id}/{id}', 'App\Http\Controllers\API\PaymentController@switchStatus')->name('payment.api.switch_status');
});

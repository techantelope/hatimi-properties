<?php

namespace Config;

// Create a new instance of our RouteCollection class.
$routes = Services::routes();

// Load the system's routing file first, so that the app and ENVIRONMENT
// can override as needed.
if (is_file(SYSTEMPATH . 'Config/Routes.php')) {
    require SYSTEMPATH . 'Config/Routes.php';
}

/*
 * --------------------------------------------------------------------
 * Router Setup
 * --------------------------------------------------------------------
 */
$routes->setDefaultNamespace('App\Controllers');
$routes->setDefaultController('Home');
$routes->setDefaultMethod('index');
$routes->setTranslateURIDashes(false);
$routes->set404Override();
// The Auto Routing (Legacy) is very dangerous. It is easy to create vulnerable apps
// where controller filters or CSRF protection are bypassed.
// If you don't want to define all routes, please use the Auto Routing (Improved).
// Set `$autoRoutesImproved` to true in `app/Config/Feature.php` and set the following to true.
// $routes->setAutoRoute(false);

/*
 * --------------------------------------------------------------------
 * Route Definitions
 * --------------------------------------------------------------------
 */

// We get a performance increase by specifying the default
// route since we don't have to scan directories.
/*$routes->get('/', 'Home::index');
$routes->get('/login', 'Auth::login');*/
/* $routes->group("", ["namespace" => "App\Controllers\Api", "filter" => "basicauth"] , function($routes){
});*/
$routes->get('/', 'Home::index');
// Common
$routes->post('uploadfile', 'UploadFile::upload',["namespace" => "App\Controllers", "filter" => "basicauth"]);
$routes->post('deleteimage', 'Property::deleteImage',["namespace" => "App\Controllers", "filter" => "basicauth"]);

// Admin
$routes->post('devuserlogin', 'DevAuth::login',["namespace" => "App\Controllers", "filter" => "basicauth"]);

$routes->post('adminlogin', 'Auth::login',["namespace" => "App\Controllers", "filter" => "basicauth"]);
$routes->post('adminprofile', 'Admin::profile',["namespace" => "App\Controllers", "filter" => "basicauth"]);
$routes->post('adminupdateprofile', 'Admin::updateProfile',["namespace" => "App\Controllers", "filter" => "basicauth"]);
$routes->post('adminadashboard', 'Admin::dashboard',["namespace" => "App\Controllers", "filter" => "basicauth"]);
$routes->post('adminchangepassword', 'Admin::changePassword',["namespace" => "App\Controllers", "filter" => "basicauth"]);

// Bookings
$routes->post('admincurrentbookings', 'Admin::currentBookings',["namespace" => "App\Controllers", "filter" => "basicauth"]);
$routes->post('adminallbookings', 'Admin::allBookings',["namespace" => "App\Controllers", "filter" => "basicauth"]);
$routes->post('adminaddbookings', 'Booking::adminAddBooking',["namespace" => "App\Controllers", "filter" => "basicauth"]);
$routes->post('adminsearchrooms', 'Booking::adminSearchRooms',["namespace" => "App\Controllers", "filter" => "basicauth"]);
$routes->post('admincancelbooking', 'Booking::adminCancelBooking',["namespace" => "App\Controllers", "filter" => "basicauth"]);
$routes->post('admingetbookings', 'Booking::adminGetBookings',["namespace" => "App\Controllers", "filter" => "basicauth"]);
$routes->post('admingetbookingdetails', 'Booking::adminGetBookingDetails',["namespace" => "App\Controllers", "filter" => "basicauth"]);
$routes->post('adminapprovebooking', 'Booking::adminApproveBooking',["namespace" => "App\Controllers", "filter" => "basicauth"]);
$routes->post('adminapproverefund', 'Booking::adminApproveRefund',["namespace" => "App\Controllers", "filter" => "basicauth"]);

// Property Admin
$routes->post('adminpropertylist', 'Property::list',["namespace" => "App\Controllers", "filter" => "basicauth"]);
$routes->post('adminupdatepropertystatus', 'Property::updateStatus',["namespace" => "App\Controllers", "filter" => "basicauth"]);
$routes->post('adminaddproperty', 'Property::add',["namespace" => "App\Controllers", "filter" => "basicauth"]);
$routes->post('adminactivepropertywithrooms', 'Property::activeListWithRooms',["namespace" => "App\Controllers", "filter" => "basicauth"]);
$routes->post('adminpropertydetails', 'Property::details',["namespace" => "App\Controllers", "filter" => "basicauth"]);

// Rooms
$routes->post('adminallrooms', 'Room::list',["namespace" => "App\Controllers", "filter" => "basicauth"]);
$routes->post('adminaroomsbyproperty', 'Room::listByProperty',["namespace" => "App\Controllers", "filter" => "basicauth"]);
$routes->post('adminupdateroomstatus', 'Room::updateStatus',["namespace" => "App\Controllers", "filter" => "basicauth"]);
$routes->post('adminaddroom', 'Room::add',["namespace" => "App\Controllers", "filter" => "basicauth"]);
$routes->post('adminassignedroomnumber', 'Room::assignedNumbersByProperty',["namespace" => "App\Controllers", "filter" => "basicauth"]);
$routes->post('adminroomdetails', 'Room::details',["namespace" => "App\Controllers", "filter" => "basicauth"]);

// Amenity
$routes->post('adminactiveamenities', 'Amenity::list',["namespace" => "App\Controllers", "filter" => "basicauth"]);
$routes->post('adminaddamenities', 'Amenity::add',["namespace" => "App\Controllers", "filter" => "basicauth"]);
$routes->post('adminupdateamenitystatus', 'Amenity::updateStatus',["namespace" => "App\Controllers", "filter" => "basicauth"]);

// Customer
$routes->post('admincustomerbyits', 'Customer::detailByITS',["namespace" => "App\Controllers", "filter" => "basicauth"]);
$routes->post('adminallcustomers', 'Customer::list',["namespace" => "App\Controllers", "filter" => "basicauth"]);
$routes->post('adminaaddcustomer', 'Customer::add',["namespace" => "App\Controllers", "filter" => "basicauth"]);
$routes->post('adminallguests', 'Customer::guestList',["namespace" => "App\Controllers", "filter" => "basicauth"]);
$routes->post('adminallcontactus', 'Customer::contactUsList',["namespace" => "App\Controllers", "filter" => "basicauth"]);

// Payments
$routes->post('adminallpayments', 'Payments::paymentsList',["namespace" => "App\Controllers", "filter" => "basicauth"]);

// Razorpay
$routes->post('getpaymenturl', 'Frontend::getPaymentURL',["namespace" => "App\Controllers", "filter" => "basicauth"]);
$routes->post('fetchtransfer', 'Frontend::fetchTransfer',["namespace" => "App\Controllers", "filter" => "basicauth"]);
$routes->post('normalrefund', 'Frontend::normalRefund',["namespace" => "App\Controllers", "filter" => "basicauth"]);

// Promo Code
$routes->post('adminallpromocodes', 'PromoCode::list',["namespace" => "App\Controllers", "filter" => "basicauth"]);
$routes->post('adminupdatepromocodes', 'PromoCode::updatePromoCode',["namespace" => "App\Controllers", "filter" => "basicauth"]);
$routes->post('adminupdatepromocodestatus', 'PromoCode::updateStatus',["namespace" => "App\Controllers", "filter" => "basicauth"]);
$routes->post('adminpromocodedetails', 'PromoCode::details',["namespace" => "App\Controllers", "filter" => "basicauth"]);
$routes->post('checkpromocode', 'FrontPromoCode::details',["namespace" => "App\Controllers", "filter" => "basicauth"]);
$routes->post('admincheckpromocode', 'PromoCode::detailsByCode',["namespace" => "App\Controllers", "filter" => "basicauth"]);

// Idara
$routes->post('adminidaralist', 'AdminIdara::list',["namespace" => "App\Controllers", "filter" => "basicauth"]);
$routes->post('adminidaraupdate', 'AdminIdara::updateIdara',["namespace" => "App\Controllers", "filter" => "basicauth"]);
$routes->post('adminidaradelete', 'AdminIdara::deleteIdara',["namespace" => "App\Controllers", "filter" => "basicauth"]);
$routes->post('adminidaradetails', 'AdminIdara::detailsByUid',["namespace" => "App\Controllers", "filter" => "basicauth"]);
$routes->post('admincheckidaracustomer', 'AdminIdara::checkIdaraCustomer',["namespace" => "App\Controllers", "filter" => "basicauth"]);

//Frontend
$routes->post('customerlogin', 'Its_auth::login',["namespace" => "App\Controllers", "filter" => "basicauth"]);
$routes->post('customerprofile', 'Frontend::profile',["namespace" => "App\Controllers", "filter" => "basicauth"]);
$routes->post('customerupdateprofile', 'Frontend::updateProfile',["namespace" => "App\Controllers", "filter" => "basicauth"]);
$routes->post('customersearchrooms', 'Frontend::searchRooms',["namespace" => "App\Controllers", "filter" => "basicauth"]);
$routes->post('customeraddbookings', 'Frontend::addBooking',["namespace" => "App\Controllers", "filter" => "basicauth"]);
$routes->post('customercancelbooking', 'Frontend::cancelBooking',["namespace" => "App\Controllers", "filter" => "basicauth"]);
$routes->post('customerallbookings', 'Frontend::getBookings',["namespace" => "App\Controllers", "filter" => "basicauth"]);
$routes->post('customerbookingdetails', 'Frontend::getBookingDetails',["namespace" => "App\Controllers", "filter" => "basicauth"]);
$routes->post('customergetallactiveproperties', 'Frontend::getAllActiveProperties',["namespace" => "App\Controllers", "filter" => "basicauth"]);
$routes->post('customergetproperty', 'Frontend::getProperty',["namespace" => "App\Controllers", "filter" => "basicauth"]);
$routes->post('customergetroom', 'Frontend::getRoom',["namespace" => "App\Controllers", "filter" => "basicauth"]);
$routes->post('customercontactus', 'Frontend::contactUs',["namespace" => "App\Controllers", "filter" => "basicauth"]);
$routes->post('customeraddreview', 'Frontend::addReview',["namespace" => "App\Controllers", "filter" => "basicauth"]);
$routes->post('customergetallreviews', 'Frontend::getAllReviews',["namespace" => "App\Controllers", "filter" => "basicauth"]);
$routes->post('propertydetails', 'Frontend::getPropertyInfo',["namespace" => "App\Controllers", "filter" => "basicauth"]);


$routes->post('checkidaracustomer', 'Frontend::checkIdaraCustomer',["namespace" => "App\Controllers", "filter" => "basicauth"]);
$routes->post('ccavenue-generate-pay-url', 'CcavenueController::verifyTransaction',["namespace" => "App\Controllers", "filter" => "basicauth"]);
/*
 * --------------------------------------------------------------------
 * Additional Routing
 * --------------------------------------------------------------------
 *
 * There will often be times that you need additional routing and you
 * need it to be able to override any defaults in this file. Environment
 * based routes is one such time. require() additional route files here
 * to make that happen.
 *
 * You will have access to the $routes object within that file without
 * needing to reload it.
 */
if (is_file(APPPATH . 'Config/' . ENVIRONMENT . '/Routes.php')) {
    require APPPATH . 'Config/' . ENVIRONMENT . '/Routes.php';
}

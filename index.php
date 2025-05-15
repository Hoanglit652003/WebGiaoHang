<?php
require_once("./component/header.php");

if (!isset($_GET['url'])) {
    require_once("./public/trangchu.php");
} else {
    switch ($_GET['url']) {
        case 'trangchu':
            require_once("./public/trangchu.php");
            break;
        case 'contact':
            require_once("./public/view/contact.php");
            break;
        case 'create-shipment':
            require_once("./public/view/create-shipment.php");
            break;
        case 'success-create-shipment':
            require_once("./public/view/success.php");
            break;
        case 'manager-shipment':
            require_once("./public/view/QL_donGiao.php");
            break;
        case 'login':
            require_once("./public/view/login.php");
            break;
        case 'register':
            require_once("./public/view/register.php");
            break;
        case 'logout':
            require_once("./public/view/logout.php");
            break;
        case 'tracking':
            require_once("./public/view/tracking.php");
            break;
        case 'detail-orders':
            require_once("./public/view/detail_donGiao.php");
            break;
        case 'profile':
            require_once("./public/view/user_info.php");
            break;
        case 'update-order':
            require_once("./public/view/update_order.php");
            break;
        case 'forgot_password':
            require_once("./public/view/forgot_password.php");
            break;
        case 'verify_otp':
            require_once("./public/view/verify_otp.php");
            break;
        case 'reset_password':
            require_once("./public/view/reset_password.php");
            break;
    }
}

require_once("./component/footer.php");

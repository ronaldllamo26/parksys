<?php
// config/constants.php

define('APP_NAME',    'ParkSys Pro');
define('APP_VERSION', '1.0.0');
define('BASE_URL',    'http://localhost/parksys');
define('UPLOAD_PATH', __DIR__ . '/../uploads/');

// Session keys
define('SESSION_USER_ID',   'ps_user_id');
define('SESSION_USER_ROLE', 'ps_user_role');
define('SESSION_USER_NAME', 'ps_user_name');

// Role constants
define('ROLE_SUPERADMIN', 'superadmin');
define('ROLE_ADMIN',      'admin');
define('ROLE_CUSTOMER',   'customer');

// Parking status
define('STATUS_AVAILABLE',   'available');
define('STATUS_OCCUPIED',    'occupied');
define('STATUS_RESERVED',    'reserved');
define('STATUS_MAINTENANCE', 'maintenance');
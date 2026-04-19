<?php
// includes/security_headers.php — Iron Dome Security Headers

// 1. Content-Security-Policy (CSP)
// Only allow scripts from trusted sources and the same origin.
// Note: We allow inline styles for some of our dynamic components.
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://unpkg.com https://cdn.jsdelivr.net; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com; img-src 'self' data: https:; frame-ancestors 'none';");

// 2. Prevent Clickjacking
header("X-Frame-Options: SAMEORIGIN");

// 3. Prevent MIME Sniffing
header("X-Content-Type-Options: nosniff");

// 4. Referrer Policy
header("Referrer-Policy: strict-origin-when-cross-origin");

// 5. Permissions Policy
header("Permissions-Policy: camera=(), microphone=(), geolocation=()");

// 6. XSS Protection (Legacy but still useful)
header("X-XSS-Protection: 1; mode=block");

// 7. Strict-Transport-Security (HSTS)
// Uncomment this if you have SSL/HTTPS configured
// header("Strict-Transport-Security: max-age=31536000; includeSubDomains");

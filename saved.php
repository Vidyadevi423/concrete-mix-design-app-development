<?php
/**
 * Combined endpoint for saved designs.
 * - Browser visit (normal Accept header): redirects to saved.html page.
 * - API calls (JSON / fetch): proxies to save_design.php handlers.
 */

// Detect if the caller expects JSON (API) or HTML (page view)
$accept = strtolower($_SERVER['HTTP_ACCEPT'] ?? '');
$wantsHtml = str_contains($accept, 'text/html');
$isJsonRequest =
    $_SERVER['REQUEST_METHOD'] !== 'GET' ||                // non-GET => API
    !$wantsHtml ||                                         // fetch/axios default */* (no html) => API
    str_contains($accept, 'application/json') ||           // explicit JSON Accept
    str_contains($accept, 'text/json') ||                  // alternate JSON Accept
    (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') ||
    (isset($_GET['api']) && $_GET['api'] === '1');

if ($isJsonRequest) {
    // Reuse the core CRUD logic
    require __DIR__ . '/save_design.php';
    exit;
}

// Otherwise, treat as page request and send to the Saved Designs UI
header('Location: saved.html', true, 302);
exit;
?>

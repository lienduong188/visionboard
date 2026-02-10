<?php

/**
 * Laravel - Shared hosting fallback
 *
 * Forward all requests to public/index.php when document root
 * is set to project root instead of public/.
 */

// Forward to public/index.php
require __DIR__.'/public/index.php';

<?php
/**
 * Configuración de Entorno - GENIALISIS
 */

// Base de datos
define('DB_HOST', '92.205.2.161');
define('DB_NAME', 'genialisis-portal-prod');
define('DB_USERNAME', 'admin-genialisis-portal-prod');
define('DB_PASSWORD', 'diCPi@SZ{8pr');
define('DB_CHARSET', 'utf8mb4');
define('DB_DSN', 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET);

// Zona horaria
define('TIMEZONE', 'America/Bogota');

// URLs
define('API_URL', 'http://localhost:9997');
define('FRONTEND_URL', 'http://localhost:4700');

// CORS permitidos
define('ALLOWED_ORIGINS', [
    'http://localhost:4700',
    'https://genialisis.com',
    'https://www.genialisis.com'
]);

// Rate limiting
define('RATE_LIMIT_ENABLED', true);
define('RATE_LIMIT_MAX_REQUESTS', 5);
define('RATE_LIMIT_TIME_WINDOW', 3600); // 1 hora en segundos
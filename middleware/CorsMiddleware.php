<?php
/**
 * Middleware de CORS
 * Controla qué dominios pueden acceder a la API
 */

class CorsMiddleware
{
    public static function handle()
    {
        // Definir orígenes permitidos
        $allowedOrigins = [
            'http://localhost:4400',  // Angular dev
            'http://localhost:4200',  // Angular dev alternativo
            'https://genialisis.co',  // Producción
            'https://www.genialisis.co', // Producción con www
        ];
        
        // Si existe la constante ALLOWED_ORIGINS, usarla
        if (defined('ALLOWED_ORIGINS')) {
            $allowedOrigins = array_merge($allowedOrigins, ALLOWED_ORIGINS);
        }
        
        // Obtener el origen de la petición
        $origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';
        
        // Verificar si el origen está en la lista de permitidos
        if (in_array($origin, $allowedOrigins)) {
            header("Access-Control-Allow-Origin: $origin");
        }
        
        // Headers permitidos
        header("Access-Control-Allow-Headers: X-API-KEY, Origin, X-Requested-With, Content-Type, Accept, Access-Control-Request-Method");
        header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");
        header("Allow: GET, POST, OPTIONS, PUT, DELETE");
        header("Access-Control-Allow-Credentials: true");
        
        // Manejar preflight requests
        $method = $_SERVER['REQUEST_METHOD'];
        if ($method == "OPTIONS") {
            http_response_code(200);
            exit();
        }
    }
}
<?php
/**
 * Servicio de Contactos para GENIALISIS
 * Maneja el registro de leads desde la landing page
 */
class GenialisisContactosService
{
    /**
     * Crear nuevo contacto desde el formulario de la landing
     */
    public static function crearContacto()
    {
        try {
            $db = Flight::db();
            
            // Obtener datos del request
            $datos = Flight::request()->data;
            
            // Validar datos
            $errores = self::validarDatos($datos);
            if (!empty($errores)) {
                UtilsService::responderJSON([
                    'success' => false,
                    'errores' => $errores
                ], 400);
                return;
            }
            
            // Obtener IP y User Agent
            $ipCliente = UtilsService::obtenerIPCliente();
            $userAgent = UtilsService::obtenerUserAgent();
            
            // Verificar rate limit (5 por hora)
            $limitePorHora = ConfiguracionService::obtenerConfiguracion('genialisis_contacto_limite_hora', 5);
            if (!UtilsService::verificarRateLimit('genialisis_contactos', $ipCliente, 1, $limitePorHora)) {
                UtilsService::log("Rate limit excedido para IP: $ipCliente", 'warning');
                UtilsService::responderJSON([
                    'success' => false,
                    'message' => 'Has excedido el límite de solicitudes. Por favor intenta más tarde.'
                ], 429);
                return;
            }
            
            // Sanitizar datos
            $nombreEstablecimiento = UtilsService::sanitizarTexto($datos->nombre_establecimiento);
            $nombreContacto = UtilsService::sanitizarTexto($datos->nombre_contacto);
            $email = strtolower(trim($datos->email));
            $telefono = UtilsService::sanitizarTexto($datos->telefono);
            $mensaje = !empty($datos->mensaje) ? UtilsService::sanitizarTexto($datos->mensaje) : null;
            
            // Insertar contacto
            $stmt = $db->prepare("
                INSERT INTO genialisis_contactos (
                    nombre_establecimiento,
                    nombre_contacto,
                    email,
                    telefono,
                    mensaje,
                    ip_address,
                    user_agent,
                    origen
                ) VALUES (?, ?, ?, ?, ?, ?, ?, 'landing')
            ");
            
            $stmt->execute([
                $nombreEstablecimiento,
                $nombreContacto,
                $email,
                $telefono,
                $mensaje,
                $ipCliente,
                $userAgent
            ]);
            
            $contactoId = $db->lastInsertId();
            
            UtilsService::log("Nuevo contacto GENIALISIS creado", 'info', [
                'contacto_id' => $contactoId,
                'email' => $email,
                'establecimiento' => $nombreEstablecimiento
            ]);
            
            // TODO: Enviar email de notificación al equipo de ventas
            // TODO: Enviar email de bienvenida al contacto
            
            // Obtener URL de Calendly si está configurada
            $calendlyUrl = ConfiguracionService::obtenerConfiguracion('genialisis_calendly_url', '');
            
            UtilsService::responderJSON([
                'success' => true,
                'message' => '¡Gracias por tu interés! Nos pondremos en contacto contigo pronto.',
                'contacto_id' => $contactoId,
                'calendly_url' => $calendlyUrl
            ]);
            
        } catch (Exception $e) {
            UtilsService::log("Error en crearContacto GENIALISIS: " . $e->getMessage(), 'error');
            UtilsService::responderJSON([
                'success' => false,
                'message' => 'Error al procesar la solicitud. Por favor intenta nuevamente.'
            ], 500);
        }
    }
    
    /**
     * Validar datos del formulario
     */
    private static function validarDatos($datos)
    {
        $errores = [];
        
        // Nombre del establecimiento
        if (empty($datos->nombre_establecimiento) || strlen(trim($datos->nombre_establecimiento)) < 3) {
            $errores[] = 'El nombre del establecimiento es requerido (mínimo 3 caracteres)';
        }
        
        // Nombre del contacto
        if (empty($datos->nombre_contacto) || strlen(trim($datos->nombre_contacto)) < 3) {
            $errores[] = 'El nombre de contacto es requerido (mínimo 3 caracteres)';
        }
        
        // Email
        if (empty($datos->email) || !UtilsService::validarEmail($datos->email)) {
            $errores[] = 'Email inválido';
        }
        
        // Teléfono
        if (empty($datos->telefono) || !UtilsService::validarTelefonoColombia($datos->telefono)) {
            $errores[] = 'Teléfono inválido (debe tener entre 7 y 10 dígitos)';
        }
        
        return $errores;
    }
    
    /**
     * Obtener estadísticas de contactos (para dashboard interno)
     */
    public static function obtenerEstadisticas()
    {
        try {
            $db = Flight::db();
            
            // Total de contactos
            $stmt = $db->query("SELECT COUNT(*) as total FROM genialisis_contactos");
            $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Por estado
            $stmt = $db->query("
                SELECT estado, COUNT(*) as cantidad 
                FROM genialisis_contactos 
                GROUP BY estado
            ");
            $porEstado = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Últimos 7 días
            $stmt = $db->query("
                SELECT DATE(created_at) as fecha, COUNT(*) as cantidad
                FROM genialisis_contactos
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                GROUP BY DATE(created_at)
                ORDER BY fecha ASC
            ");
            $ultimos7Dias = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            UtilsService::responderJSON([
                'success' => true,
                'estadisticas' => [
                    'total' => $total,
                    'por_estado' => $porEstado,
                    'ultimos_7_dias' => $ultimos7Dias
                ]
            ]);
            
        } catch (Exception $e) {
            UtilsService::log("Error en obtenerEstadisticas: " . $e->getMessage(), 'error');
            UtilsService::responderJSON([
                'success' => false,
                'message' => 'Error al obtener estadísticas'
            ], 500);
        }
    }
}

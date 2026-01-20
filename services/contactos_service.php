<?php

class ContactosService
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
            
            // Verificar rate limit (3 por hora según configuración)
            $limitePorHora = ConfiguracionService::obtenerConfiguracion('contacto_limite_por_hora', 3);
            if (!UtilsService::verificarRateLimit('contactos', $ipCliente, 1, $limitePorHora)) {
                UtilsService::log("Rate limit excedido para IP: $ipCliente", 'warning');
                UtilsService::responderJSON([
                    'success' => false,
                    'message' => 'Has excedido el límite de solicitudes. Por favor intenta más tarde.'
                ], 429);
                return;
            }
            
            // Sanitizar datos
            $nombreEstablecimiento = UtilsService::sanitizarTexto($datos->nombreEstablecimiento);
            $nombreContacto = UtilsService::sanitizarTexto($datos->nombreContacto);
            $email = strtolower(trim($datos->email));
            $telefono = UtilsService::sanitizarTexto($datos->telefono);
            $mensaje = !empty($datos->mensaje) ? UtilsService::sanitizarTexto($datos->mensaje) : null;
            $idTamanoEstablecimiento = !empty($datos->tamanoEstablecimiento) ? (int)$datos->tamanoEstablecimiento : null;
            $idTipoConsulta = !empty($datos->tipoConsulta) ? (int)$datos->tipoConsulta : null;
            $idComoConocio = !empty($datos->comoConocio) ? (int)$datos->comoConocio : null;
            $comoConocioDetalle = !empty($datos->comoConocioDetalle) ? UtilsService::sanitizarTexto($datos->comoConocioDetalle) : null;
            
            // Insertar contacto
            $stmt = $db->prepare("
                INSERT INTO contactos (
                    nombre_establecimiento,
                    nombre_contacto,
                    email,
                    telefono,
                    mensaje,
                    id_tamano_establecimiento,
                    id_tipo_consulta,
                    id_como_conocio,
                    como_conocio_detalle,
                    ip_address,
                    user_agent
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $nombreEstablecimiento,
                $nombreContacto,
                $email,
                $telefono,
                $mensaje,
                $idTamanoEstablecimiento,
                $idTipoConsulta,
                $idComoConocio,
                $comoConocioDetalle,
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
            
            UtilsService::responderJSON([
                'success' => true,
                'message' => '¡Gracias por tu interés! Nos pondremos en contacto contigo pronto.',
                'contacto_id' => $contactoId
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
     * Obtener tamaños de establecimiento disponibles
     */
    public static function obtenerTamanosEstablecimiento()
    {
        try {
            $db = Flight::db();
            
            $stmt = $db->query("
                SELECT id, nombre, rango_inicio, rango_fin 
                FROM tamanos_establecimiento 
                WHERE activo = TRUE 
                ORDER BY orden ASC
            ");
            $tamanos = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            UtilsService::responderJSON([
                'success' => true,
                'tamanos' => $tamanos
            ]);
            
        } catch (Exception $e) {
            UtilsService::log("Error en obtenerTamanosEstablecimiento: " . $e->getMessage(), 'error');
            UtilsService::responderJSON([
                'success' => false,
                'message' => 'Error al obtener tamaños'
            ], 500);
        }
    }
    
    /**
     * Obtener tipos de consulta disponibles
     */
    public static function obtenerTiposConsulta()
    {
        try {
            $db = Flight::db();
            
            $stmt = $db->query("
                SELECT id, nombre 
                FROM tipos_consulta 
                WHERE activo = TRUE 
                ORDER BY orden ASC
            ");
            $tipos = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            UtilsService::responderJSON([
                'success' => true,
                'tipos' => $tipos
            ]);
            
        } catch (Exception $e) {
            UtilsService::log("Error en obtenerTiposConsulta: " . $e->getMessage(), 'error');
            UtilsService::responderJSON([
                'success' => false,
                'message' => 'Error al obtener tipos de consulta'
            ], 500);
        }
    }
    
    /**
     * Obtener cómo nos conoció (canales)
     */
    public static function obtenerComoConocio()
    {
        try {
            $db = Flight::db();
            
            $stmt = $db->query("
                SELECT id, nombre, pide_detalle, placeholder_detalle 
                FROM tipos_como_conocio 
                WHERE activo = TRUE 
                ORDER BY id ASC
            ");
            $canales = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            UtilsService::responderJSON([
                'success' => true,
                'canales' => $canales
            ]);
            
        } catch (Exception $e) {
            UtilsService::log("Error en obtenerComoConocio: " . $e->getMessage(), 'error');
            UtilsService::responderJSON([
                'success' => false,
                'message' => 'Error al obtener canales'
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
        if (empty($datos->nombreEstablecimiento) || strlen(trim($datos->nombreEstablecimiento)) < 3) {
            $errores[] = 'El nombre del establecimiento es requerido (mínimo 3 caracteres)';
        }
        
        // Nombre del contacto
        if (empty($datos->nombreContacto) || strlen(trim($datos->nombreContacto)) < 3) {
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
            $stmt = $db->query("SELECT COUNT(*) as total FROM contactos");
            $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Por estado
            $stmt = $db->query("
                SELECT ec.nombre as estado, COUNT(c.id) as cantidad 
                FROM estados_contacto ec
                LEFT JOIN contactos c ON ec.id = c.id_estado
                GROUP BY ec.id, ec.nombre
                ORDER BY ec.orden ASC
            ");
            $porEstado = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Últimos 7 días
            $stmt = $db->query("
                SELECT DATE(created_at) as fecha, COUNT(*) as cantidad
                FROM contactos
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                GROUP BY DATE(created_at)
                ORDER BY fecha ASC
            ");
            $ultimos7Dias = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Por tamaño de establecimiento
            $stmt = $db->query("
                SELECT te.nombre, COUNT(c.id) as cantidad
                FROM tamanos_establecimiento te
                LEFT JOIN contactos c ON te.id = c.id_tamano_establecimiento
                GROUP BY te.id, te.nombre
                ORDER BY te.orden ASC
            ");
            $porTamano = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            UtilsService::responderJSON([
                'success' => true,
                'estadisticas' => [
                    'total' => $total,
                    'por_estado' => $porEstado,
                    'ultimos_7_dias' => $ultimos7Dias,
                    'por_tamano' => $porTamano
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
<?php
/**
 * Rutas para contactos de GENIALISIS
 */

// Crear nuevo contacto desde la landing
Flight::route('POST /api/genialisis/contactos', [GenialisisContactosService::class, 'crearContacto']);

// Obtener estadísticas (requiere autenticación - TODO: agregar middleware)
Flight::route('GET /api/genialisis/estadisticas', [GenialisisContactosService::class, 'obtenerEstadisticas']);

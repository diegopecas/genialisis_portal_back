<?php
/**
 * Rutas para contactos de GENIALISIS
 */

// Crear nuevo contacto desde la landing
Flight::route('POST /api/genialisis/contactos', [ContactosService::class, 'crearContacto']);

// Obtener tamaños de establecimiento disponibles
Flight::route('GET /api/genialisis/tamanos-establecimiento', [ContactosService::class, 'obtenerTamanosEstablecimiento']);

// Obtener tipos de consulta
Flight::route('GET /api/genialisis/tipos-consulta', [ContactosService::class, 'obtenerTiposConsulta']);

// Obtener cómo nos conoció
Flight::route('GET /api/genialisis/como-conocio', [ContactosService::class, 'obtenerComoConocio']);

// Obtener estadísticas (requiere autenticación - TODO: agregar middleware)
Flight::route('GET /api/genialisis/estadisticas', [ContactosService::class, 'obtenerEstadisticas']);
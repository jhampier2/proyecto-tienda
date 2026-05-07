const express = require('express');
const router = express.Router();
const productoController = require('../controllers/productoController');
const ventaController = require('../controllers/ventaController');
const authController = require('../controllers/authController');

// Productos
router.get('/productos', productoController.obtenerProductos);
router.get('/categorias', productoController.obtenerCategorias);

// Auth
router.post('/auth/registrar', authController.registrar);
router.post('/auth/login', authController.login);
router.get('/auth/perfil/:id', authController.perfil);
router.put('/auth/perfil/:id', authController.actualizarPerfil);

// Pedidos
router.get('/pedidos/:id', authController.misPedidos);
router.get('/pedidos/detalle/:idVenta', authController.detallePedido);

// Ventas
router.post('/comprar', ventaController.procesarCompra);
router.post('/pagar-ficticio', ventaController.procesarPagoFicticio);
router.get('/estado-pago/:idVenta', ventaController.consultarEstadoPago);
router.post('/webhook-yape', ventaController.aprobarPagoYape);

module.exports = router;
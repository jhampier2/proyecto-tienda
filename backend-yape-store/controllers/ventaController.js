const db = require('../config/db');

// Crear venta pendiente y mostrar modal
exports.procesarCompra = async (req, res) => {
    const { carrito, total, idCliente } = req.body;
    console.log('=== COMPRAR ===', { carritoLength: carrito?.length, total, idCliente });
    
    const idClienteReal = idCliente || 1; // Usar el ID del cliente logueado o default 1
    const connection = await db.getConnection();

    try {
        await connection.beginTransaction();
        
        // Obtener el último ID y generar uno nuevo
        const [maxIdResult] = await connection.query('SELECT MAX(id_venta) as maxId FROM ventas');
        const nuevoId = (maxIdResult[0].maxId || 0) + 1;
        
        // Crear venta como Pendiente con el ID del cliente
        await connection.query(
            'INSERT INTO ventas (id_venta, id_cliente, total, metodo_pago, estado) VALUES (?, ?, ?, ?, ?)',
            [nuevoId, idClienteReal, total, 'Yape', 'Pendiente']
        );
        
        console.log('Venta creada con ID:', nuevoId);

        // Insertar detalles de venta
        for (let item of carrito) {
            const precio = item.precio_oferta || item.precio;
            const cantidad = item.cantidad || 1;
            const subtotal = precio * cantidad;
            
            await connection.query(
                'INSERT INTO detalle_venta (id_venta, id_producto, cantidad, subtotal) VALUES (?, ?, ?, ?)',
                [nuevoId, item.id_producto, cantidad, subtotal]
            );
        }

        await connection.commit();
        res.status(200).json({ mensaje: 'Orden creada', idVenta: nuevoId });
    } catch (error) {
        await connection.rollback();
        console.error('Error en comprar:', error);
        res.status(500).json({ error: 'Error al crear la orden: ' + error.message });
    } finally {
        connection.release();
    }
};

// Procesar pago cuando usuario hace click en "Ya pagué"
exports.procesarPagoFicticio = async (req, res) => {
    const { idVenta } = req.body;
    console.log('=== PAGAR FICTICIO ===', { idVenta, tipo: typeof idVenta });

    if (!idVenta) {
        return res.status(400).json({ error: 'ID de venta requerido' });
    }

    const connection = await db.getConnection();

    try {
        await connection.beginTransaction();

        // Actualizar estado a Pagado
        const [updateResult] = await connection.query(
            'UPDATE ventas SET estado = "Pagado" WHERE id_venta = ?',
            [idVenta]
        );
        console.log('Rows actualizados:', updateResult.affectedRows);

        // Obtener detalles de la venta para actualizar stock
        const [detalles] = await connection.query(
            'SELECT id_producto, cantidad FROM detalle_venta WHERE id_venta = ?',
            [idVenta]
        );
        console.log('Detalles encontrados:', detalles.length);

        // Reducir stock
        for (const detalle of detalles) {
            await connection.query(
                'UPDATE producto SET stock = stock - ? WHERE id_producto = ?',
                [detalle.cantidad, detalle.id_producto]
            );
        }

        await connection.commit();

        res.json({
            success: true,
            message: 'Pago procesado exitosamente',
            idVenta: idVenta,
            venta: { id: idVenta, estado: 'Pagado' }
        });
    } catch (error) {
        await connection.rollback();
        console.error('Error en pagar-ficticio:', error);
        res.status(500).json({ success: false, error: 'Error interno del servidor: ' + error.message });
    } finally {
        connection.release();
    }
};

// Consultar estado de pago
exports.consultarEstadoPago = async (req, res) => {
    const { idVenta } = req.params;
    try {
        const [rows] = await db.query('SELECT estado FROM ventas WHERE id_venta = ?', [idVenta]);
        if (rows.length > 0) {
            return res.status(200).json(rows);
        } else {
            return res.status(404).json({ error: 'Venta no encontrada' });
        }
    } catch (error) {
        return res.status(500).json({ error: 'Error al consultar estado' });
    }
};

// Webhook simulado para aprobar pago
exports.aprobarPagoYape = async (req, res) => {
    const { idVenta } = req.body;
    try {
        await db.query('UPDATE ventas SET estado = "Pagado" WHERE id_venta = ?', [idVenta]);
        res.json({ mensaje: `Pago aprobado para la venta ${idVenta}` });
    } catch (error) {
        res.status(500).json({ error: 'Error al aprobar el pago' });
    }
};
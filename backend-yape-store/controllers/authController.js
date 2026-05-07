const db = require('../config/db');

// Registro de usuario
exports.registrar = async (req, res) => {
    const { nombres, apellidos, telefono, email, password, direccion } = req.body;

    if (!nombres || !apellidos || !telefono || !email || !password) {
        return res.status(400).json({ error: 'Todos los campos son requeridos' });
    }

    try {
        // Verificar si el email ya existe
        const [existing] = await db.query('SELECT id_cliente FROM clientes WHERE email = ?', [email]);
        if (existing.length > 0) {
            return res.status(400).json({ error: 'El email ya está registrado' });
        }

        // Insertar nuevo cliente (la contraseña se guarda como texto plano para simplificar)
        // En producción, usar bcrypt para hash
        const [result] = await db.query(
            'INSERT INTO clientes (nombres, apellidos, telefono, email, password, direccion) VALUES (?, ?, ?, ?, ?, ?)',
            [nombres, apellidos, telefono, email, password, direccion || '']
        );

        res.status(201).json({
            success: true,
            mensaje: 'Usuario registrado exitosamente',
            usuario: {
                id_cliente: result.insertId,
                nombres,
                apellidos,
                email
            }
        });
    } catch (error) {
        console.error('Error en registro:', error);
        res.status(500).json({ error: 'Error al registrar usuario' });
    }
};

// Login de usuario
exports.login = async (req, res) => {
    const { email, password } = req.body;

    if (!email || !password) {
        return res.status(400).json({ error: 'Email y password son requeridos' });
    }

    try {
        const [rows] = await db.query(
            'SELECT id_cliente, nombres, apellidos, telefono, email, direccion FROM clientes WHERE email = ? AND password = ?',
            [email, password]
        );

        if (rows.length === 0) {
            return res.status(401).json({ error: 'Email o contraseña incorrectos' });
        }

        const usuario = rows[0];
        
        res.json({
            success: true,
            mensaje: 'Login exitoso',
            usuario: {
                id_cliente: usuario.id_cliente,
                nombres: usuario.nombres,
                apellidos: usuario.apellidos,
                telefono: usuario.telefono,
                email: usuario.email,
                direccion: usuario.direccion
            }
        });
    } catch (error) {
        console.error('Error en login:', error);
        res.status(500).json({ error: 'Error al iniciar sesión' });
    }
};

// Obtener perfil del usuario
exports.perfil = async (req, res) => {
    const { id } = req.params;

    try {
        const [rows] = await db.query(
            'SELECT id_cliente, nombres, apellidos, telefono, email, direccion, created_at FROM clientes WHERE id_cliente = ?',
            [id]
        );

        if (rows.length === 0) {
            return res.status(404).json({ error: 'Usuario no encontrado' });
        }

        res.json({ success: true, usuario: rows[0] });
    } catch (error) {
        console.error('Error al obtener perfil:', error);
        res.status(500).json({ error: 'Error al obtener perfil' });
    }
};

// Obtener pedidos del usuario
exports.misPedidos = async (req, res) => {
    const { id } = req.params;

    try {
        const [pedidos] = await db.query(
            'SELECT id_venta, fecha, total, metodo_pago, estado FROM ventas WHERE id_cliente = ? ORDER BY fecha DESC',
            [id]
        );

        res.json({ success: true, pedidos });
    } catch (error) {
        console.error('Error al obtener pedidos:', error);
        res.status(500).json({ error: 'Error al obtener pedidos' });
    }
};

// Obtener detalle de un pedido
exports.detallePedido = async (req, res) => {
    const { idVenta } = req.params;
    const { idCliente } = req.query;

    try {
        // Verificar que el pedido pertenece al usuario
        const [venta] = await db.query(
            'SELECT id_venta, fecha, total, metodo_pago, estado FROM ventas WHERE id_venta = ? AND id_cliente = ?',
            [idVenta, idCliente]
        );

        if (venta.length === 0) {
            return res.status(404).json({ error: 'Pedido no encontrado' });
        }

        const [detalles] = await db.query(
            `SELECT dv.id_detventa, dv.cantidad, dv.subtotal, p.nombre, p.imagen_url 
             FROM detalle_venta dv 
             LEFT JOIN producto p ON dv.id_producto = p.id_producto 
             WHERE dv.id_venta = ?`,
            [idVenta]
        );

        res.json({
            success: true,
            pedido: {
                ...venta[0],
                detalles
            }
        });
    } catch (error) {
        console.error('Error al obtener detalle:', error);
        res.status(500).json({ error: 'Error al obtener detalle del pedido' });
    }
};

// Actualizar perfil
exports.actualizarPerfil = async (req, res) => {
    const { id } = req.params;
    const { nombres, apellidos, telefono, direccion } = req.body;

    try {
        await db.query(
            'UPDATE clientes SET nombres = ?, apellidos = ?, telefono = ?, direccion = ? WHERE id_cliente = ?',
            [nombres, apellidos, telefono, direccion, id]
        );

        res.json({ success: true, mensaje: 'Perfil actualizado correctamente' });
    } catch (error) {
        console.error('Error al actualizar perfil:', error);
        res.status(500).json({ error: 'Error al actualizar perfil' });
    }
};
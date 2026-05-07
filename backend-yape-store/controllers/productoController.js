const db = require('../config/db');

exports.obtenerProductos = async (req, res) => {
    try {
        const [rows] = await db.query(`
            SELECT p.*, c.descripcion as categoria_nombre 
            FROM producto p 
            LEFT JOIN categoria c ON p.id_categoria = c.id_categoria
        `);
        res.json(rows);
    } catch (error) {
        console.error(error);
        res.status(500).json({ mensaje: 'Error al obtener los productos' });
    }
};

exports.obtenerCategorias = async (req, res) => {
    try {
        const [rows] = await db.query('SELECT id_categoria, descripcion FROM categoria ORDER BY descripcion');
        res.json(rows);
    } catch (error) {
        console.error(error);
        res.status(500).json({ mensaje: 'Error al obtener las categorías' });
    }
};
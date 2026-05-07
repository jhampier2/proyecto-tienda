const mysql = require('mysql2'); // Usamos la librería base

// Creamos el pool de forma tradicional
const pool = mysql.createPool({
    host: process.env.DB_HOST,
    user: process.env.DB_USER,
    password: process.env.DB_PASSWORD,
    database: process.env.DB_NAME,
    port: process.env.DB_PORT,
    waitForConnections: true,
    connectionLimit: 10,
    queueLimit: 0,
    ssl: {
        rejectUnauthorized: false
    }
});

// ESTO ES LO MÁS IMPORTANTE: Convertimos el pool a promesas manualmente
const promisePool = pool.promise();

module.exports = promisePool; // Exportamos el pool que SI tiene .query()

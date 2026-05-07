const mysql = require('mysql2/promise');
require('dotenv').config();

const pool = mysql.createPool({
    host: 'mysql-118092dd-jortizvento-9349.b.aivencloud.com',
    user: 'avnadmin',
    password: 'TU_PASSWORD_REAL_AQUÍ',
    database: 'defaultdb',
    port: 28944,
    ssl: { rejectUnauthorized: false }
});

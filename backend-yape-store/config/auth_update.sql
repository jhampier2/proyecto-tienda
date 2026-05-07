-- Agregar campos de autenticación a clientes
ALTER TABLE clientes ADD COLUMN email VARCHAR(100) UNIQUE DEFAULT NULL;
ALTER TABLE clientes ADD COLUMN password VARCHAR(255) DEFAULT NULL;
ALTER TABLE clientes ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP;

-- Actualizar cliente 1 con datos de ejemplo
UPDATE clientes SET email = 'maryori@example.com', password = '$2a$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi' WHERE id_cliente = 1;
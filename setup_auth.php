<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "mibd";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Agregar columnas
$conn->query("ALTER TABLE clientes ADD COLUMN IF NOT EXISTS email VARCHAR(100) DEFAULT NULL");
$conn->query("ALTER TABLE clientes ADD COLUMN IF NOT EXISTS password VARCHAR(255) DEFAULT NULL");
$conn->query("ALTER TABLE clientes ADD COLUMN IF NOT EXISTS created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP");

echo "Columnas agregadas\n";

// Actualizar clientes con email/password para prueba
$conn->query("UPDATE clientes SET email = 'maryori@example.com', password = '123456' WHERE id_cliente = 1");
$conn->query("UPDATE clientes SET email = 'luis@example.com', password = '123456' WHERE id_cliente = 2");

echo "Clientes actualizados\n";

// Verificar ventas
echo "\n=== VENTAS DEL CLIENTE 1 ===\n";
$result = $conn->query("SELECT id_venta, fecha, total, estado FROM ventas WHERE id_cliente = 1 ORDER BY fecha DESC LIMIT 10");
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "ID: {$row['id_venta']}, Fecha: {$row['fecha']}, Total: {$row['total']}, Estado: {$row['estado']}\n";
    }
} else {
    echo "No hay ventas para cliente 1\n";
}

echo "\n=== VENTAS TOTALES ===\n";
$result = $conn->query("SELECT COUNT(*) as total FROM ventas");
echo "Total ventas: " . $result->fetch_assoc()['total'] . "\n";

$conn->close();
echo "\nListo!";
?>
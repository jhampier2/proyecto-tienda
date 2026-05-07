<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "mibd";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Ver clientes con email/password
echo "=== CLIENTES CON EMAIL/PASSWORD ===\n";
$result = $conn->query("SELECT id_cliente, nombres, email, password FROM clientes WHERE email IS NOT NULL");
while ($row = $result->fetch_assoc()) {
    echo "ID: {$row['id_cliente']}, Nombre: {$row['nombres']}, Email: {$row['email']}, Pass: {$row['password']}\n";
}

// Ver ventas del cliente 1
echo "\n=== VENTAS DEL CLIENTE 1 ===\n";
$result = $conn->query("SELECT id_venta, fecha, total, estado FROM ventas WHERE id_cliente = 1 ORDER BY fecha DESC LIMIT 20");
while ($row = $result->fetch_assoc()) {
    echo "ID: {$row['id_venta']}, Fecha: {$row['fecha']}, Total: {$row['total']}, Estado: {$row['estado']}\n";
}

$conn->close();
?>
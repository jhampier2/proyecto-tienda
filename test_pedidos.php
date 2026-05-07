<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "mibd";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Simular el query del backend para pedidos
$id = 1; // cliente ID
$result = $conn->query("SELECT id_venta, fecha, total, metodo_pago, estado FROM ventas WHERE id_cliente = $id ORDER BY fecha DESC");

echo "=== PEDIDOS DEL CLIENTE 1 (simulando backend) ===\n";
while ($row = $result->fetch_assoc()) {
    echo json_encode($row) . "\n";
}

$conn->close();
?>
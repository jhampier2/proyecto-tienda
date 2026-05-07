<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "mibd";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Add columns if they don't exist
$sql1 = "ALTER TABLE clientes ADD COLUMN IF NOT EXISTS email VARCHAR(100) DEFAULT NULL";
$sql2 = "ALTER TABLE clientes ADD COLUMN IF NOT EXISTS password VARCHAR(255) DEFAULT NULL";
$sql3 = "ALTER TABLE clientes ADD COLUMN IF NOT EXISTS created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP";

if ($conn->query($sql1) === TRUE) {
    echo "Column 'email' added or already exists<br>";
} else {
    echo "Error adding email: " . $conn->error . "<br>";
}

if ($conn->query($sql2) === TRUE) {
    echo "Column 'password' added or already exists<br>";
} else {
    echo "Error adding password: " . $conn->error . "<br>";
}

if ($conn->query($sql3) === TRUE) {
    echo "Column 'created_at' added or already exists<br>";
} else {
    echo "Error adding created_at: " . $conn->error . "<br>";
}

// Update existing user with test credentials
$sql4 = "UPDATE clientes SET email = 'maryori@example.com', password = '123456' WHERE id_cliente = 1";
if ($conn->query($sql4) === TRUE) {
    echo "Test user updated<br>";
}

$conn->close();
echo "Done!";
?>
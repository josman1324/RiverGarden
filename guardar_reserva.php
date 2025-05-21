<?php
$host = "localhost";
$dbname = "hotelreservas";
$username = "root";
$password = "j1075792008";

try {
    // Encabezado para manejar caracteres especiales
    header('Content-Type: text/html; charset=utf-8');

    // Conexión con PDO
    $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Sanitización de entrada del usuario
    $nombre = htmlspecialchars($_POST['nombre'], ENT_QUOTES, 'UTF-8');
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $telefono = htmlspecialchars($_POST['telefono'], ENT_QUOTES, 'UTF-8');
    $fecha_entrada = $_POST['fecha_entrada'];
    $fecha_salida = $_POST['fecha_salida'];
    $id_habitacion = (int) $_POST['habitacion'];

    // Validar correo electrónico
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception("El correo electrónico no es válido.");
    }

    // Generar un ID de reserva único
    do {
        $id_reserva = str_pad(rand(100000, 999999), 6, '0', STR_PAD_LEFT);
        $stmtCheck = $conn->prepare("SELECT 1 FROM reservas WHERE id_reserva = :id_reserva");
        $stmtCheck->bindParam(':id_reserva', $id_reserva);
        $stmtCheck->execute();
        $existe = $stmtCheck->fetchColumn();
    } while ($existe);

    // Insertar la reserva en la base de datos
    $stmt = $conn->prepare("INSERT INTO reservas 
        (nombre_cliente, email_cliente, telefono_cliente, fecha_entrada, fecha_salida, id_habitacion, id_reserva)
        VALUES 
        (:nombre, :email, :telefono, :fecha_entrada, :fecha_salida, :id_habitacion, :id_reserva)");
    
    $stmt->bindParam(':nombre', $nombre);
    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':telefono', $telefono);
    $stmt->bindParam(':fecha_entrada', $fecha_entrada);
    $stmt->bindParam(':fecha_salida', $fecha_salida);
    $stmt->bindParam(':id_habitacion', $id_habitacion);
    $stmt->bindParam(':id_reserva', $id_reserva);
    $stmt->execute();

    // Preparar mensaje para WhatsApp
    $telefonoHotel = "573152528546";
    $mensaje = "Hola, deseo confirmar mi reserva:\n\nNombre: ${nombre}\nEntrada: ${fecha_entrada}\nSalida: ${fecha_salida}\nNumero de reserva: ${id_reserva}\n\n¿Podrían confirmarme la disponibilidad?";
    $mensajeCodificado = rawurlencode($mensaje);
    $linkWhatsApp = "https://wa.me/$telefonoHotel?text=$mensajeCodificado";

    // Mostrar respuesta en HTML
    echo "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <title>Reserva Confirmada</title>
        <style>
            body {
                font-family: 'Segoe UI', sans-serif;
                background: #f0f4f8;
                display: flex;
                justify-content: center;
                align-items: center;
                height: 100vh;
            }
            .card {
                background: #fff;
                padding: 40px;
                border-radius: 15px;
                box-shadow: 0 8px 20px rgba(0,0,0,0.1);
                text-align: center;
                max-width: 500px;
            }
            h2 {
                color: #0d47a1;
                margin-bottom: 20px;
            }
            p {
                font-size: 16px;
                color: #333;
            }
            .reserva-numero {
                font-size: 20px;
                color: #007BFF;
                font-weight: bold;
                margin: 10px 0;
            }
            .btn {
                display: inline-block;
                margin-top: 20px;
                padding: 12px 20px;
                font-size: 16px;
                background-color: #25D366;
                color: white;
                text-decoration: none;
                border-radius: 8px;
                transition: background 0.3s ease;
            }
            .btn:hover {
                background-color: #1ebe5d;
            }
        </style>
    </head>
    <body>
        <div class='card'>
            <h2>¡Reserva Registrada!</h2>
            <p>Gracias, <strong>$nombre</strong>. Tu número de reserva es:</p>
            <div class='reserva-numero'>#$id_reserva</div>
            <p>Si deseas confirmación por WhatsApp, pulsa el botón a continuación:</p>
            <a class='btn' href='$linkWhatsApp' target='_blank'>Solicitar confirmación por WhatsApp</a>
        </div>
    </body>
    </html>";
    
} catch (PDOException $e) {
    echo "Error en la base de datos: " . $e->getMessage();
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
} finally {
    $conn = null;
}
?>



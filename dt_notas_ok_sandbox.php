<?php 

header('Content-Type: application/json; charset=utf-8'); // 🔹 Indicamos que responderemos en JSON

require("acces_ic.php"); // 🔹 Archivo donde haces la conexión a la BD (debe definir $conex_i)

// 1️⃣ Leemos el cuerpo del POST en formato JSON
$rawData = file_get_contents("php://input");

// 2️⃣ Lo convertimos a arreglo PHP
$data = json_decode($rawData, true);

// 3️⃣ Validamos que se haya recibido algo válido
if (!$data) {
    echo json_encode([
        'success' => false,
        'error' => 'No se recibieron datos válidos.'
    ]);
    exit;
}

// 4️⃣ Extraemos los campos del JSON
/* $comentario = mysqli_real_escape_string($conex_i, $data['comentario'] ?? '');
$usuario    = mysqli_real_escape_string($conex_i, $data['usuario'] ?? '');
$date_time  = mysqli_real_escape_string($conex_i, $data['date_time'] ?? '');
$menciones  = $data['menciones'] ?? []; */

$comentario = $data['comentario'];
$fecha  = $data['date_time'];
$dot_rts    = $data['dot_rts'];
$id_rts     = $data['id_rts'];
$usuario    = $data['usuario'];
$menciones  = $data['menciones'];

/* echo json_encode([
    'success' => true,
    'received' => [
        'comentario' => $comentario,
        'usuario'    => $usuario,
        'date_time'  => $date_time,
        'dot_rts'    => $dat_rts,
        'id_rts'     => $id_rts,
        'menciones'  => $menciones
    ]
]);
 */

$insert =  false;

foreach ($menciones as $index => $mencion) {
   
    $query_inseert = mysqli_query($conex_i, "INSERT INTO dt_notas_ok (dot_nota_tag, id_rts_tag, inicales_tag, usuario_tag,fecha_tag, estatus_tag)
    VALUES('$dot_rts', '$id_rts', '$mencion', '$usuario', '$fecha', 'Enviado') ");

    if ($query_inseert) {
        $insert = true;

        echo json_encode([
            'success' => true,
            'message' => 'Mención insertada correctamente.',
            'mencion' => $mencion
        ]);

    } else {
        $insert = false;
        break; // Si una inserción falla, salimos del bucle

        echo json_encode([
            'success' => false,
            'error' => 'Error al insertar la mención: ' . mysqli_error($conex_i),
            'mencion' => $mencion,
            "query"   => "INSERT INTO dt_notas_ok (dot_nota_tag, id_rts_tag, inicales_tag, usuario_tag,fecha_tag, estatus_tag)
                            VALUES('$dot_rts', '$id_rts', '$mencion', '$usuario', '$fecha', 'Enviado')"
        ]);
    }

}
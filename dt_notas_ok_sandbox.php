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
        'menciones'  => $menciones,
        'query'      => "INSERT INTO notificaciones (dot_nota_tag, id_rts_tag, inicales_tag, usuario_tag,fecha_tag, estatus_tag)
    VALUES('$dot_rts', '$id_rts', 'JSG', '$usuario', '$fecha', 'Enviado') "
    ]
]);
 */



$insert =  false;

if (empty($menciones)) {
   
    /* inseetar el comentario*/

    $razon_nota = "Renta";
    $tipo_nota  = "Operaciones";

    // Obtener la empresa y la sucursal del contrato

    $query_conrato = mysqli_query($conex_i, "SELECT sucursal_rts , empresa_rts FROM rentas WHERE dot_rts = '$dot_rts'");

    $rows = mysqli_fetch_array($query_conrato);

    $sucursal_nota = $rows['sucursal_rts'];
    $empresa_nota  = $rows['empresa_rts'];
    

    $query_insert_nota = mysqli_query($conex_i, "INSERT INTO notas(dot_nota, razon_nota, `iniciales_nota`, `fecha_nota`, `texto_nota`, `tipo_nota`, `sucursal_nota`, `empresa_nota`) 
    VALUES ('$dot_rts', '$razon_nota', '$usuario', '$fecha', '$comentario', '$tipo_nota', '$sucursal_nota', '$empresa_nota') ");

    if ($query_insert_nota) {
        echo json_encode([
            'success' => true,
            'message' => 'Comentario y menciones insertadas correctamente.'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'Error al insertar el comentario: ' . mysqli_error($conex_i)
        ]);
    }
    exit;

}

foreach ($menciones as $index => $mencion) {

    /* echo "INSERT INTO notificaciones (dot_nota_tag, id_rts_tag, inicales_tag, usuario_tag,fecha_tag, estatus_tag)
    VALUES('$dot_rts', '$id_rts', '$mencion', '$usuario', '$fecha', 'Enviado') "; */
   
    $query_inseert = mysqli_query($conex_i, "INSERT INTO notificaciones (dot_nota_tag, id_rts_tag, inicales_tag, usuario_tag,fecha_tag, estatus_tag)
    VALUES('$dot_rts', '$id_rts', '$mencion', '$usuario', '$fecha', 'Enviado') ");

    if ($query_inseert) {
        $insert = true;

       /*  echo json_encode([
            'success' => true,
            'message' => 'Mención insertada correctamente.',
            'mencion' => $mencion
        ]); */

    } else {
        $insert = false;
        break; // Si una inserción falla, salimos del bucle

        /* echo json_encode([
            'success' => false,
            'error' => 'Error al insertar la mención: ' . mysqli_error($conex_i),
            'mencion' => $mencion,
            "query"   => "INSERT INTO notificaciones (dot_nota_tag, id_rts_tag, inicales_tag, usuario_tag,fecha_tag, estatus_tag)
                            VALUES('$dot_rts', '$id_rts', '$mencion', '$usuario', '$fecha', 'Enviado')"
        ]); */

    }

}

if ($insert) {
   /*  echo json_encode([
        'success' => true,
        'message' => 'Todas las menciones insertadas correctamente.'
    ]); */

    /* inseetar el comentario*/

    $razon_nota = "Renta";
    $tipo_nota  = "Operaciones";

    // Obtener la empresa y la sucursal del contrato

    $query_conrato = mysqli_query($conex_i, "SELECT sucursal_rts , empresa_rts FROM rentas WHERE dot_rts = '$dot_rts'");

    $rows = mysqli_fetch_array($query_conrato);

    $sucursal_nota = $rows['sucursal_rts'];
    $empresa_nota  = $rows['empresa_rts'];
    

    $query_insert_nota = mysqli_query($conex_i, "INSERT INTO notas(dot_nota, razon_nota, `iniciales_nota`, `fecha_nota`, `texto_nota`, `tipo_nota`, `sucursal_nota`, `empresa_nota`) 
    VALUES ('$dot_rts', '$razon_nota', '$usuario', '$fecha', '$comentario', '$tipo_nota', '$sucursal_nota', '$empresa_nota') ");

    if ($query_insert_nota) {
        echo json_encode([
            'success' => true,
            'message' => 'Comentario y menciones insertadas correctamente.'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'Error al insertar el comentario: ' . mysqli_error($conex_i)
        ]);
    }


} else {
    echo json_encode([
        'success' => false,
        'error' => 'Error al insertar una o más menciones.'
    ]);
}
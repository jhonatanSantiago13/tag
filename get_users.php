<?php

require("acces_ic.php");

// Parámetro opcional q (lo que escribe después del @)
$q = isset($_GET['q']) ? trim($_GET['q']) : '';

if ($q === '') {

    // Si q vacío => devolvemos (por defecto) todos los usuarios.
    // Si la tabla es muy grande deberías poner un LIMIT o paginar aquí.
    $sql = "SELECT `pi_per`, `nombre_per`, `nomcorto_per` FROM `personal` ORDER BY `nombre_per`";
    
}else{

    // echo "Valor de Q: ".$q."<br>";

    $sql = "SELECT `pi_per`, `nombre_per`, `nomcorto_per` FROM `personal` WHERE  `nombre_per` LIKE '%$q%' OR `nomcorto_per` LIKE  '%$q%' ORDER BY `nombre_per`";

    // echo "SQL: ".$sql."<br>";

}

$query = mysqli_query($conex_i, $sql);

while ($row = mysqli_fetch_assoc($query)) {
    $results[] = [
        'pi'      => $row['pi_per'],
        'name'    => $row['nombre_per']." (".$row['nomcorto_per'].")",
        'iniciales' => $row['nomcorto_per']
    ];
}

echo json_encode($results);
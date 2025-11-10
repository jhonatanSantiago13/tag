<?php
/* error_reporting(1);
ini_set('display_errors',1);
ini_set('display_startup_errors', 1); */

date_default_timezone_set("America/Mexico_City");
setlocale( LC_MONETARY, 'es_MX' );
//mysql_set_charset('utf8');

/* if ($_COOKIE['theuser'] == 'accept'){
	$conex_i = mysqli_connect("localhost","claritym_csmngr","Cs_MnGr#pHp_20#23","claritym_alterno");
	if (mysqli_connect_errno()) { echo "Error de conexión MySQLi"; }
		} */

//mysqli_query($conex_i,"SET character_set_results='utf8',character_set_client='utf8',character_set_connection='utf8',character_set_database='utf8',character_set_server='utf8'");

$host = "localhost";
$user = "root";
$password ="jhonatan";

$conex_i = mysqli_connect($host, $user, $password);

mysqli_select_db($conex_i, "clarity");

if(!$conex_i){
	echo "error al conectar";
}


?>
<?php 

    $host  = "localhost";
    $user  = "root";
    $pass  = "";
    $db    = "weekphp";


    try {
        $conn = new PDO("mysql:host=$host; dbname=".$db, $user, $pass);
        //echo "Conexão com banco de dados realizado com sucesso!<br/>";
    } catch (Exception $ex) {
        die("Erro: Por favor tente novamente. Caso o problema persista, entre em contato com o administrador: brunolimadevelopment@gmail.com!<br/>");
    }

?>
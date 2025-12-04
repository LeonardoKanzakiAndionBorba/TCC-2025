<?php
        $servername = "localhost";
        $username = "root";
        $password = "";
        $dbname = "info_cultura";

        $conn = new mysqli($servername, $username, $password, $dbname);

        $sql = "SELECT * FROM usuarios";
        $result = $conn->query($sql);

        while ($row = $result->fetch_assoc()) {
            echo "MEU NOME É: " . $row["nome"]. "<br>";
        }            

        $conn->close();
        ?>



<?php
header("Content-Type: application/json; charset=UTF-8");

$cp = trim($_GET["cp"] ?? "");

if ($cp === "") {
    echo json_encode([]);
    exit;
}

$catalogo = [

    /* =========================
       TEOTIHUACÁN
    ========================= */
    "55800" => ["colonias" => ["Teotihuacán Centro"], "municipio" => "Teotihuacán"],
    "55803" => ["colonias" => ["Acatitla", "Colatitla", "El Tejocote"], "municipio" => "Teotihuacán"],
    "55807" => ["colonias" => ["El Mirador"], "municipio" => "Teotihuacán"],
    "55810" => ["colonias" => ["San Juan Teotihuacán"], "municipio" => "Teotihuacán"],
    "55816" => ["colonias" => ["El Portal"], "municipio" => "Teotihuacán"],
    "55825" => ["colonias" => ["Ampliación Ejidal Tlajinga"], "municipio" => "Teotihuacán"],
    "55826" => ["colonias" => ["Ampliación San Francisco"], "municipio" => "Teotihuacán"],
    "55830" => ["colonias" => ["De los Deportes", "Del Valle"], "municipio" => "Teotihuacán"],
    "55833" => ["colonias" => ["El Potrero"], "municipio" => "Teotihuacán"],
    "55838" => ["colonias" => ["Atlatongo", "Ejido Purificación"], "municipio" => "Teotihuacán"],
    "55840" => ["colonias" => ["Ampliación Ejidal Maquixco"], "municipio" => "Teotihuacán"],
    "55843" => ["colonias" => ["Ampliación Cadena Maquixco", "El Cayahual"], "municipio" => "Teotihuacán"],
    "55844" => ["colonias" => ["Hacienda Cadena"], "municipio" => "Teotihuacán"],

    /* =========================
       SAN MARTÍN
    ========================= */
    "55850" => ["colonias" => ["San Martín de las Pirámides Centro", "Ejido San Martín"], "municipio" => "San Martín de las Pirámides"],
    "55852" => ["colonias" => ["Ixtlahuaca", "Santa María Tezompa", "Álvaro Obregón", "El Saltito"], "municipio" => "San Martín de las Pirámides"],
    "55853" => ["colonias" => ["Cozotlán Norte", "San Antonio de las Palmas", "Tlachinolpa"], "municipio" => "San Martín de las Pirámides"],
    "55854" => ["colonias" => ["La Noria", "San José Cerro Gordo", "San Marcos Cerro Gordo"], "municipio" => "San Martín de las Pirámides"],
    "55855" => ["colonias" => ["Chimalpa", "Club Campestre Teotihuacán", "Predio Palma y Raya", "San Pablo Ixquitlán"], "municipio" => "San Martín de las Pirámides"],
    "55856" => ["colonias" => ["Santa María Palapa"], "municipio" => "San Martín de las Pirámides"],
    "55859" => ["colonias" => ["Santiago Tepetitlán"], "municipio" => "San Martín de las Pirámides"],

    /* =========================
       AXAPUSCO
    ========================= */
    "55940" => ["colonias" => ["Axapusco", "Cuauhtémoc", "San Antonio", "San Bartolo Alto", "San Martín"], "municipio" => "Axapusco"],
    "55950" => ["colonias" => ["Guadalupe Relinas", "San Felipe Zacatepec", "San Antonio Coayuca"], "municipio" => "Axapusco"],
    "55954" => ["colonias" => ["San Pablo Xuchitl"], "municipio" => "Axapusco"],
    "55955" => ["colonias" => ["San Nicolás Tetepantla"], "municipio" => "Axapusco"],
    "55960" => ["colonias" => ["San Antonio Ometusco"], "municipio" => "Axapusco"],
    "55963" => ["colonias" => ["San Miguel Ometusco", "Santa Ana"], "municipio" => "Axapusco"],
    "55965" => ["colonias" => ["Jaltepec"], "municipio" => "Axapusco"],
    "55966" => ["colonias" => ["Atla (Tecuautitlán Atla)"], "municipio" => "Axapusco"],

    /* =========================
       NOPALTEPEC
    ========================= */
    "55970" => ["colonias" => ["Barrios Hidalgo", "Morelos", "Vicente Guerrero"], "municipio" => "Nopaltepec"],
    "55973" => ["colonias" => ["Exhacienda La Puerta"], "municipio" => "Nopaltepec"],
    "55975" => ["colonias" => ["San Felipe Teotitlán", "Huilotongo", "Tlaxixilo", "Colonia Roma"], "municipio" => "Nopaltepec"],
    "55976" => ["colonias" => ["San Miguel Atepoxco", "Tepetzingo"], "municipio" => "Nopaltepec"],
    "55978" => ["colonias" => ["Santa Inés Amiltepec", "Las Ambrises"], "municipio" => "Nopaltepec"],

    /* =========================
       TEMASCALAPA
    ========================= */
    "55980" => ["colonias" => ["San Bartolomé Actopan", "San Juan Teacalco", "San Miguel", "San Antonio", "De la Cruz", "De Dolores"], "municipio" => "Temascalapa"],
    "55983" => ["colonias" => ["El Abrojal", "El Chopo"], "municipio" => "Temascalapa"],
    "55984" => ["colonias" => ["Colonia Belén", "Ocotitlán"], "municipio" => "Temascalapa"],
    "55985" => ["colonias" => ["Atempan"], "municipio" => "Temascalapa"],
    "55988" => ["colonias" => ["Las Pintas"], "municipio" => "Temascalapa"],
    "55989" => ["colonias" => ["Presa del Rey"], "municipio" => "Temascalapa"],
    "55990" => ["colonias" => ["Ixtlahuaca de Cuauhtémoc"], "municipio" => "Temascalapa"],
    "55993" => ["colonias" => ["Ex Hacienda de Paula", "La Estrella"], "municipio" => "Temascalapa"],
    "55994" => ["colonias" => ["Axalpa"], "municipio" => "Temascalapa"],
    "55995" => ["colonias" => ["Mihuacán"], "municipio" => "Temascalapa"],
    "55996" => ["colonias" => ["Álvaro Obregón", "La Presa"], "municipio" => "Temascalapa"],
    "55998" => ["colonias" => ["El Tejocote"], "municipio" => "Temascalapa"],

    /* =========================
       TECÁMAC
    ========================= */
    "55730" => ["colonias" => ["Ozumbilla"], "municipio" => "Tecámac"],
    "55733" => ["colonias" => ["Santa Cruz Tecámac"], "municipio" => "Tecámac"],
    "55740" => ["colonias" => ["Tecámac Centro", "El Calvario", "Galaxias el Llano"], "municipio" => "Tecámac"],
    "55743" => ["colonias" => ["Hacienda del Bosque", "Real Granada", "La Palma", "Isidro Fabela"], "municipio" => "Tecámac"],
    "55745" => ["colonias" => ["Santa María Ajoloapan"], "municipio" => "Tecámac"],
    "55746" => ["colonias" => ["San Pedro Pozohuacán"], "municipio" => "Tecámac"],
    "55747" => ["colonias" => ["San Jerónimo Xonacahuacan"], "municipio" => "Tecámac"],
    "55748" => ["colonias" => ["Ejido de Tecámac", "1ro de Marzo"], "municipio" => "Tecámac"],
    "55749" => ["colonias" => ["5 de Mayo", "Ampliación 5 de Mayo"], "municipio" => "Tecámac"],
    "55750" => ["colonias" => ["San Juan Pueblo Nuevo"], "municipio" => "Tecámac"],
    "55755" => ["colonias" => ["Los Reyes Acozac"], "municipio" => "Tecámac"],
    "55760" => ["colonias" => ["San Martín Azcatepec", "San Pablo Tecalco"], "municipio" => "Tecámac"],
    "55763" => ["colonias" => ["Los Héroes Tecámac"], "municipio" => "Tecámac"],
    "55764" => ["colonias" => ["Los Héroes Tecámac Jardines"], "municipio" => "Tecámac"],
    "55765" => ["colonias" => ["Los Héroes Tecámac Bosques"], "municipio" => "Tecámac"],
    "55767" => ["colonias" => ["Los Héroes Tecámac Flores"], "municipio" => "Tecámac"],
    "55768" => ["colonias" => ["Los Héroes Tecámac Bosques II"], "municipio" => "Tecámac"],
    "55769" => ["colonias" => ["Los Héroes Tecámac Bosques III"], "municipio" => "Tecámac"],
    "55770" => ["colonias" => ["Hacienda Ojo de Agua"], "municipio" => "Tecámac"]
];

if (isset($catalogo[$cp])) {
    echo json_encode($catalogo[$cp], JSON_UNESCAPED_UNICODE);
} else {
    echo json_encode([], JSON_UNESCAPED_UNICODE);
}


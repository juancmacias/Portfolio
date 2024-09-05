<?php
header('Content-Type: application/json');

$inp = file_get_contents('datos_proyectos.json');

echo $inp;

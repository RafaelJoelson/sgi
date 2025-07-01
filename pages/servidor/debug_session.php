<?php
session_start();
header('Content-Type: text/plain');
print_r($_SESSION['usuario']);

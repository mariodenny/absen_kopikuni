<?php
$dbHost = "localhost";
$dbUsername = "denny_kopi";
$dbPassword = "Kucing123";
$dbName = "absensi_kopikuni";
$dbPort = 3306;

$connect = mysqli_connect($dbHost, $dbUsername, $dbPassword, $dbName);

if (mysqli_connect_errno()) {
    die("Something went wrong while connecting to db -> " . mysqli_connect_error());
}
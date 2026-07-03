<?php

require '../config/database.php';

$users = [
    [
    "nama"=>"Administrator",
    "username"=>"admin",
    "password"=>"Adm!nGBK2026",
    "role"=>"admin"
    ],

    [
    "nama"=>"Budi Santoso",
    "username"=>"budi",
    "password"=>"Budi@2026",
    "role"=>"petugas"
    ],

    [
    "nama"=>"Citra Lestari",
    "username"=>"citra",
    "password"=>"Citra#2026",
    "role"=>"petugas"
    ],

    [
    "nama"=>"Dimas Prakoso",
    "username"=>"dimas",
    "password"=>"Dimas@2026",
    "role"=>"petugas"
    ],

    [
    "nama"=>"Eka Putri",
    "username"=>"eka",
    "password"=>"Eka!2026",
    "role"=>"petugas"
    ],

    [
    "nama"=>"Fajar Nugraha",
    "username"=>"fajar",
    "password"=>"Fajar#2026",
    "role"=>"petugas"
    ],

    [
    "nama"=>"Andi Saputra",
    "username"=>"andi",
    "password"=>"Andi@2026",
    "role"=>"petugas"
    ],

    [
    "nama"=>"Galih Pratama",
    "username"=>"galih",
    "password"=>"Galih#2026",
    "role"=>"petugas"
    ],

    [
    "nama"=>"Hani Wulandari",
    "username"=>"hani",
    "password"=>"Hani!2026",
    "role"=>"petugas"
    ],

    [
    "nama"=>"Indra Kurniawan",
    "username"=>"indra",
    "password"=>"Indra@2026",
    "role"=>"petugas"
    ],

    [
    "nama"=>"Joko Firmansyah",
    "username"=>"joko",
    "password"=>"Joko#2026",
    "role"=>"petugas"
    ],

    [
    "nama"=>"Kevin Prasetyo",
    "username"=>"kevin",
    "password"=>"Kevin@2026",
    "role"=>"petugas"
    ],

    [
    "nama"=>"Laila Ramadhani",
    "username"=>"laila",
    "password"=>"Laila#2026",
    "role"=>"petugas"
    ],

    [
    "nama"=>"Muhammad Rizky",
    "username"=>"rizky",
    "password"=>"Rizky!2026",
    "role"=>"petugas"
    ],

    [
    "nama"=>"Nabila Sari",
    "username"=>"nabila",
    "password"=>"Nabila@2026",
    "role"=>"petugas"
    ],

    [
    "nama"=>"Oscar Wijaya",
    "username"=>"oscar",
    "password"=>"Oscar#2026",
    "role"=>"petugas"
    ]

];

$sql = "INSERT INTO users
(nama_lengkap,username,password,role)
VALUES
(:nama,:username,:password,:role)";

$stmt = $conn->prepare($sql);

    foreach($users as $user){
    $cek = $conn->prepare("
    SELECT id_user
    FROM users
    WHERE username=:username
    ");

    $cek->execute([
    ':username'=>$user['username']
    ]);

    if($cek->fetch()){
        continue;
    }
    
    $hash = password_hash(
        $user['password'],
        PASSWORD_DEFAULT
    );

    $stmt->execute([

        ':nama'=>$user['nama'],
        ':username'=>$user['username'],
        ':password'=>$hash,
        ':role'=>$user['role']
    ]);
}
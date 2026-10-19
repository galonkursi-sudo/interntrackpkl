<?php
// Pintu masuk aplikasi: arahkan ke login atau ke beranda sesuai peran.
require_once __DIR__ . '/config/helpers.php';
redirect(user() ? home_for(user()['role']) : 'auth/login.php');

<?php
require __DIR__ . '/app/bootstrap.php';
redirect(is_logged_in() ? 'dashboard.php' : 'login.php');

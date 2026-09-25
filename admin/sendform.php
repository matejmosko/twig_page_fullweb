<?php
header('Content-Type: text/html; charset=utf-8');
require_once(__DIR__."/functions.php");

if (solveCaptcha() == true) {
    sendMessage($_POST['email'], $_POST['subject'], $_POST['text']);
}

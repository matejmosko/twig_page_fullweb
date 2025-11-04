<?php

require_once(__DIR__."/functions.php");

if (solveCaptcha() == true) {
    sendMessage();
}

function sendMessage()
{
    $from = $GLOBALS['data']['opts']['contactEmail'];
    $subject = $_POST['subject'];
    $to = $_POST['email'];
    $txt = $_POST['text'];

    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";

    $headers .= 'From: '.$GLOBALS['data']['opts']['emailFrom']. "\r\n";
    $headers .= 'Cc: <'.$from.'>' . "\r\n";

    if (mail($to, $subject, $txt, $headers)) {} else return "<p class='error'>Pri posielaní správy došlo k chybe. Skúste nám správu poslať priamo na ". $from."</p>";

    file_put_contents("email.txt", "To: ".$to."\n\n Subject: ".$subject."\n\n From: ".$from."\n\n Txt: ".$txt."\n\n Headers: ".$headers);
}

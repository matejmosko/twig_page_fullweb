<?php
header('Content-Type: text/html; charset=utf-8');
require_once(__DIR__ . '/functions.php');

switch ($_GET['script']) {
  case 'eventGetGuests':
    $guests = eventGetGuests($_GET['projectId'], $_GET['eventId']);
    echo json_encode($guests);
    break;

  default:
    $arr = [];
    echo json_encode($arr);
    break;
}

<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once(__DIR__."/render.php");
require_once(__DIR__."/functions.php");

if (!empty($_POST['name']) && !empty($_POST['email'])) {
    //addGuest($_POST);
    if (solveCaptcha() == true) {
        addGuest($_POST);
    }
} else {
    echo "Zadajte údaje prihlasovaného tímu.";
}

/* reCaptcha */


function addGuest($newGuest)
{
    if (!isDuplicate($newGuest)) {
        dbNewGuest($newGuest);
    }
}

function dbNewGuest($newGuest)
{
    $conn = setupDB();
    $project = $newGuest['project'] ?? "";
    $event = $newGuest['event'] ?? "";
    $name = $newGuest['name'] ?? "";
    $email = $newGuest['email'] ?? "";
    $message = $newGuest['message'] ?? "";
    $faction = $newGuest['faction'] ?? "";
    $personalcheck = $newGuest['personalCheck'] ?? "";
    $emailcheck = $newGuest['emailCheck'] ?? "";

    $sql = "INSERT INTO events_guests (project, event, name, email, message, faction, personalcheck, emailcheck) VALUES ('$project', '$event', '$name', '$email', '$message', '$faction', '$personalcheck', '$emailcheck')";

    if (mysqli_query($conn, $sql)) {
        saveProject($project); // Renders new html for project = adds new team.
        echo "Úspešne ste zaregistrovali tím ".$name." na ".$event;
    } else {
        echo "Error: " . $sql . "<br>" . mysqli_error($conn);
    }

    mysqli_close($conn);
}

function isDuplicate($newGuest)
{
    $conn = setupDB();

    $project = $newGuest['project'] ?? "";
    $event = $newGuest['event'] ?? "";
    $name = $newGuest['name'] ?? "";
    $email = $newGuest['email'] ?? "";
    $message = $newGuest['message'] ?? "";
    $faction = $newGuest['faction'] ?? "";
    $personalcheck = $newGuest['personalCheck'] ?? "";
    $emailcheck = $newGuest['emailCheck'] ?? "";

    /*
    //TEST data
        $project = "turban";
        $event = "2018-02-13_historicky";
        $name = "Test5";
        $email = "matej.mosko@gmail.comx";
        $message = "Feri";
    */
    $sql = "SELECT * from events_guests WHERE project='$project' AND event='$event' AND (name='$name' OR email='$email')";

    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        echo "<br />Každý tím sa môže na jeden kvíz prihlásiť iba raz. Ak ste sa ešte neprihlasovali, skúste to s iným emailom a iným názvom tímu.";
        return true;
    } else {
        return false;
    }
    mysqli_close($conn);
}

/*
function saveGuest($newGuest)
{

      $guestList = [];
      $dir = 'data/projects/'.$_POST["project"].'/events/'.$_POST["event"];
      if (!file_exists($dir)) {
          mkdir($dir, 0777, true);
      }
      if (!file_exists($dir.'/registration.json')) {
          $data = "";
      } else {
          $data = file_get_contents($dir.'/registration.json');
      }
      if ($data != "") {
          $guestList = json_decode($data);
          $guestCount = count($guestList);
          $guestList[$guestCount] = $newGuest;
      } else {
          $guestList[0] = $newGuest;
      }

      file_put_contents($dir.'/registration.json', json_encode($guestList));
      print_r($guestList);

}
      */

<?php

 ini_set('display_errors', 1);
 ini_set('display_startup_errors', 1);
 error_reporting(E_ALL);
/** CALLS **/

require __DIR__ . '/vendor/autoload.php';
require_once(__DIR__."/../kamosko-config.php");

$data = getData();

  if (isset($_GET['script'])) {
      $script = $_GET['script'];
      switch ($script) {
      case 'getData':
        echo json_encode(getData(), JSON_UNESCAPED_UNICODE);
        json_encode(getData(), JSON_UNESCAPED_UNICODE);
      break;
      case 'getProjects':
        echo json_encode(getProjects(), JSON_UNESCAPED_UNICODE);
        break;
      case 'getEvents':
        if (isset($_GET['project'])) {
            echo json_encode(getProject($_GET['project']), JSON_UNESCAPED_UNICODE);
        } else {
            echo json_encode(getProjects(), JSON_UNESCAPED_UNICODE);
        }
        break;
      case 'getEvent':
        if (isset($_GET['event']) && isset($_GET['project'])) {
            echo json_encode(getEvent($_GET['project'], $_GET['event']), JSON_UNESCAPED_UNICODE);
        } else {
            echo "{}";
        }
          break;
          case 'getPartners':
            echo json_encode(getPartners(), JSON_UNESCAPED_UNICODE);
          break;
      default:
        # code...
        break;
    }
  }

// TODO After each reload save json data for initial loading (cache).

/** FUNCTIONS **/


function getData()
{
    $data = recursiveScandir($GLOBALS['options']['basepath'].'/data');
    $data = recursiveJsonSearch($data, $GLOBALS['options']['basepath'].'/data');
    return $data;
}

function getProjects()
{
    $data = recursiveScandir('data/projects/');
    $data = parseInfoFiles($data);
    return $data;
}
function getProject($project)
{
    $data = recursiveScandir('data/projects/'.$project);
    return $data;
}

function getEvent($project, $event)
{
    return recursiveScandir('data/projects/'.$project."/events/".$event);
}

function getPartners()
{
    $partners = recursiveScandir("data/partners");
    return $partners;
}

function recursiveScandir($path)
{
    $files = array();
    $entries = scandir($path);
    foreach ($entries as $entry) {
        if ($entry != "." && $entry != "..") { //Just removed . and ..
            if (is_dir($path . DIRECTORY_SEPARATOR . $entry)) {
                $files[$entry] = recursiveScandir($path . DIRECTORY_SEPARATOR . $entry);
            } else {
                $files[] = $entry;
            }
        }
    }
    return $files;
}

function parseJsonFile($value, $path)
{
    $myfile = $path."/".$value;
    /*echo $myfile;
    print_r(explode("/", $path));
    echo "<br />";*/
    if (file_exists($myfile)) {
        $infodata = file_get_contents($myfile);
    } else {
        $infodata = [];
    }
    return $infodata;
}

function parseMarkdownFile($value, $path){
  $myfile = $path."/".$value;
  if (file_exists($myfile)) {
      $Parsedown = new ParsedownExtra();
      $markfile = $Parsedown->text(file_get_contents($myfile));
  } else {
      $markfile = "";
  }
  return $markfile;
}

function recursiveJsonSearch(&$data, $path)
{
    foreach ($data as $key => &$value) {
        if (is_object($value) or is_array($value)) {
            recursiveJsonSearch($value, $path."/".$key);
        } elseif (pathinfo($value, PATHINFO_EXTENSION) == "json" && $value == "opts.json") {

            $json = parseJsonFile($value, $path);

            $data['opts'] = json_decode($json, true);
            /* Convert date to timestamp for later comparison */
            if (array_key_exists('date', $data['opts'])) {
                $data['opts']['timestamp'] = strtotime($data['opts']['date']);
            }
        } elseif (pathinfo($value, PATHINFO_EXTENSION) == "md") {

            $data[pathinfo($value, PATHINFO_FILENAME)] = parseMarkdownFile($value, $path);
        }
    }
    return $data;
}

function solveCaptcha(){
  if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Build POST request:
    $recaptcha_url = 'https://www.google.com/recaptcha/api/siteverify';
    $recaptcha_secret = '6LcsPIYUAAAAAOj6vgx6MY7C6neIRPzaBL7l8bzB';
    $recaptcha_response = $_POST['recaptcha_response'];

    // Make and decode POST request:
    $recaptcha = file_get_contents($recaptcha_url . '?secret=' . $recaptcha_secret . '&response=' . $recaptcha_response);
    $recaptcha = json_decode($recaptcha);

    // Take action based on the score returned:
    if ($recaptcha->score <= 0.5) {
      echo "<p class='error'>Máme podozrenie, že si robot. Ak nie ste robot, napíšte nám priamo na emailovú adresu uvedenú v sekcii Kontakt. Ak si robot, nechytaj sa našej stránky.</p>";
      return false;
    } else {
      echo "<p class='success'>Vaša správa úspešne opustila túto stránku a mala by doraziť tak na Váš email ako aj na našu adresu ".$GLOBALS['data']['opts']['contactEmail']."</p>";
      return true;
    }

}
}

/* REGISTRATION SYSTEM */

function createDB() // ERROR IN SQL SYNTAX, USE createTables() INSTEAD
{
    $conn = new mysqli($GLOBALS['db']['servername'], $GLOBALS['db']['username'], $GLOBALS['db']['password']);
    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Create database
    $dbname = $GLOBALS['db']['dbname'];
    $sql = "CREATE DATABASE '$dbname'";
    if ($conn->query($sql) === true) {
        echo "Database created successfully";
    } else {
        echo "Error creating database: " . $conn->error;
    }

    $conn->close();
    createTables();
}

//createTables();

function createTables()
{
    $conn = setupDB();
    $sql = "CREATE TABLE events_guests (
      id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
      event VARCHAR(30) NOT NULL,
      project VARCHAR(30) NOT NULL,
      email VARCHAR(50),
      name VARCHAR(50),
      message VARCHAR(255),
      faction VARCHAR(255),
      personalcheck TINYINT(1),
      emailcheck TINYINT(1),
      regtime TIMESTAMP
    )";

    if ($conn->query($sql) === true) {
        echo "Table event_guests created successfully";
    } else {
        echo "Error creating table: " . $conn->error;
    }

    $conn->close();
}

function setupDB()
{
    // Create connection
    $conn = new mysqli($GLOBALS['db']['servername'], $GLOBALS['db']['username'], $GLOBALS['db']['password'], $GLOBALS['db']['dbname']);

    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    return $conn;
}

function eventGuestCount($eventId)
{
    $conn = setupDB();
    $sql = "SELECT COUNT(*) FROM events_guests WHERE event='$eventId'";
    $result = $conn->query($sql);

    return $result->fetch_array()[0];
}

function eventGetGuests($projectId, $eventId)
{
    $conn = setupDB();
    $sql = "SELECT regtime, name FROM events_guests WHERE event='$eventId' AND project='$projectId'";
    $result = $conn->query($sql);
    $rows = [];
    while ($row = mysqli_fetch_array($result)) {
        $rows[] = $row;
    }
    return $rows;
}

function eventSendEmail()
{
}

function adminEventGetGuests($eventId)
{
    $conn = setupDB();
    $sql = "SELECT * FROM events_guests WHERE event='$eventId'";
    $result = $conn->query($sql);
    $output = "<table>";
    $output .= "<thead><tr><th>#</th><th>Time</th><th>Name</th><th>email</th><th>Message</th><th>Tools</th></tr></thead>";
    $output .= "<tbody>";

    if (mysqli_num_rows($result) > 0) {
        // output data of each row
        $i = 1;
        while ($row = mysqli_fetch_assoc($result)) {
            $output .= "<tr><td>".$i."</td><td>".date($row['regtime'])."</td><td><strong>" . $row["name"]. "</strong></td><td>" . $row["email"]. "</td><td>".$row["message"]."</td><td><a href='' class='delete'>X</a></td></tr>";
            $i++;
        }
    }
    $output .= "</tbody>";
    $output .= "</table>";
    return $output;
}

function adminEventRemoveGuest($guestId)
{
}

/*

function render_new_google_form($url, $formid, $formtitle, $height)
{
    $content .= '<iframe id="google-form" src="'.$url.'" width="760" height="'.$height.'" frameborder="0" marginheight="0" marginwidth="0" style="display:none">'.$strings['loading'].'</iframe>';
    if (!empty(filter_events('next'))) {
        echo $content;
    };
}


function render_picfolder($path, $clickable, $maxwidth)
{
    if (file_exists($path.'/desc.md')) {
        render_file($path.'/desc.md');
    }
    if (!$maxwidth){ $maxwidth = 'none';}
    $pics = array_diff(scandir($path), array('..', '.','orig','desc.md'));
    natsort($pics);
    $pics = array_reverse($pics, true);
    foreach ($pics as $picture) {
        $picname = preg_replace('/\\.[^.\\s]{3,4}$/', '', $picture);
        if ($clickable) {echo '<p class="archive" style="max-width:'.$maxwidth.'"><a data-fancybox="picfolder" title="'.$picname.'" href="'.$path.'/'.$picture.'"><img src="'.$path.'/'.$picture.'" /></a></p>';}
        else {echo '<p class="archive" style="max-width:'.$maxwidth.'"><img src="'.$path.'/'.$picture.'" /></p>';}
    }
}*/

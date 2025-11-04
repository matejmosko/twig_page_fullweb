<?php

if (isset($_GET['debug'])) {
    $debug = $_GET['debug'];
} else {
    $debug = false;
}

if ($debug) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}

Locale::setDefault('sk_SK');

require_once(__DIR__ . '/functions.php');

$loader = new Twig_Loader_Filesystem($GLOBALS['options']['basepath'].'templates/');
$twig = new Twig_Environment($loader);
$twig->addExtension(new Twig_Extensions_Extension_Intl());

createFiles();

if ($debug) {
    echo "Base FS path: ".PATH_BASE_FS."<br />";
    echo "Base WEB path: ".PATH_BASE_WEB."<br />";
    echo "<pre>";
    print_r($data);
    echo "</pre>";
}

function renderForm($projectId, $eventId)
{
    return $GLOBALS['twig']->render('registration-'.$projectId.'.twig', array(
    'projectId' => $projectId,
    'eventId' => $eventId,
    'project' => $GLOBALS['data']['projects'][$projectId],
    'event' => $GLOBALS['data']['projects'][$projectId]['events'][$eventId],
    'options' => $GLOBALS['options'],
    'checkboxes' => renderFormCheckboxes(),
    'guestCount' => eventGuestCount($eventId),
    'recaptcha' => "recaptcha" // TODO Recaptcha
  ));
}

function renderEvent($projectId, $eventId, $today, $format)
{
    $path = "../data/projects/".$projectId."/events/";
    $coverpath = $path.$eventId."/cover.jpg";

    if (realpath($coverpath)) {
        $eventPic = $coverpath;
    } else {
        $eventPic = $path."default.jpg";
    }
    $form = "";
    if (array_key_exists('regLink', $GLOBALS['data']['projects'][$projectId]['events'][$eventId]['opts'])) {
        if ($GLOBALS['data']['projects'][$projectId]['events'][$eventId]['opts']['regLink'] == "") {
            $form = renderForm($projectId, $eventId);
        }
    }

    return $GLOBALS['twig']->render('event.twig', array(
    'project' => $GLOBALS['data']['projects'][$projectId],
    'projectId' => $projectId,
    'eventId' => $eventId,
    'eventPic' => $eventPic,
    'event' => $GLOBALS['data']['projects'][$projectId]['events'][$eventId],
    'form' => $form,
    'options' => $GLOBALS['options'],
    'guestCount' => eventGuestCount($eventId),
    'guestList' => eventGetGuests($projectId, $eventId),
    'format' => $format
  ));
}

function renderEvents($projectId, $filter)
{
    $today = $GLOBALS['options']['today'];
    $html = '';

    if (array_key_exists('events', $GLOBALS['data']['projects'][$projectId])) {
        foreach ($GLOBALS['data']['projects'][$projectId]['events'] as $key => &$event) {
            if (is_string($event)) { // Checks if $event is array (folder) or simple file.
                break;
            }
            switch ($filter) {
  case 'all':
    $html .= renderEvent($projectId, $key, $today, 'archive');
    break;
  case 'next':
    if (array_key_exists('opts', $event) && $event['opts']['timestamp'] >= $today) {
        $html .= renderEvent($projectId, $key, $today, 'full');
    }
    break;
  case 'past':
    if (array_key_exists('opts', $event) && $event['opts']['timestamp'] <= $today) {
        $html .= renderEvent($projectId, $key, $today, 'gallery');
    }
    break;
  default:
    break;
          }
        }
    } else {
        $html .= "Momentálne nepripravujeme žiadne verejné udalosti.";
    }

    return $html;
}

function renderProjects()
{
    if (array_key_exists('projects', $GLOBALS['data'])) {
        return $GLOBALS['twig']->render('projectlist.twig', array(
    'projects' => $GLOBALS['data']['projects'],
    'options' => $GLOBALS['options']
  ));
    } else {
        return "";
    }
}

function mapProductCategory($element)
{
    return $element['opts']['category'];
}

function renderProducts()
{
    if (array_key_exists('products', $GLOBALS['data'])) {
        $products = $GLOBALS['data']['products'];

        array_multisort(array_map("mapProductCategory", $products), SORT_ASC, $products);

        $categories = array_values(array_unique(array_map("mapProductCategory", $products)));

        return $GLOBALS['twig']->render('productlist.twig', array(
          'data' => $GLOBALS['data'],
          'products' => $products,
          'categories' => $categories,
          'options' => $GLOBALS['options']
  ));
    } else {
        return "";
    }
}

function renderCover($masterClass = null, $projectId = null, $eventId = null)
{
    switch ($masterClass) {
    case 'project':
      $projectData = $GLOBALS['data']['projects'][$projectId];
      break;
      case 'product':
        $projectData = $GLOBALS['data']['products'][$projectId];
        break;

    default:
      $projectData = "";
      break;
  }
    /*
    $projectData = "";
    if ($masterClass == ) {
        $projectData = $GLOBALS['data']['projects'][$projectId];
    }*/
    return $GLOBALS['twig']->render('cover.twig', array(
    'data' => $GLOBALS['data'],
    'options' => $GLOBALS['options'],
    'masterClass' => $masterClass,
    'projectId' => $projectId,
    'projectData' => $projectData,
    'eventId' => $eventId
  ));
}
function renderFooter()
{
    return $GLOBALS['twig']->render('footer.twig', array(
    'data' => $GLOBALS['data'],
    'options' => $GLOBALS['options']
  ));
}


function renderHead($localopts)
{
    return $GLOBALS['twig']->render('head.twig', array(
    'data' => $GLOBALS['data'],
    'localopts' => $localopts,
    'options' => $GLOBALS['options']
  ));
}

function renderScripts()
{
    $today = $GLOBALS['options']['today'];
    return $GLOBALS['twig']->render('scripts.twig', array(
    'data' => $GLOBALS['data'],
    'options' => $GLOBALS['options'],
    'today' => $today
  ));
}

function renderProject($projectId, $filterEvents)
{
    return $GLOBALS['twig']->render('project.twig', array(
    'data' => $GLOBALS['data']['projects'][$projectId],
    'projectId' => $projectId,
    'events' => renderEvents($projectId, $filterEvents),
    'options' => $GLOBALS['options'],
    'filterEvents' => $filterEvents,
    'contactForm' => renderContactForm('Záujem o '.$projectId, 'Dobrý deň, máme záujem o váš produkt '.$GLOBALS['data']['projects'][$projectId]['opts']['name'].'. Pošlite nám prosím detailnejšie informácie.')
    //'pastEvents' => renderEvents($project, 'past')
  ));
}

function imagesToArray($array)
{
    $result = array();
    foreach ($array as $key => $value) {
        if (is_string($value)) {
            if (strpos($value, "jpg") !== false) {
                array_push($result, $value);
            }
        }
    }
    return $result;
}

function renderProduct($productId)
{
    return $GLOBALS['twig']->render('product.twig', array(
    'data' => $GLOBALS['data'],
    'product' => $GLOBALS['data']['products'][$productId],
    'images' => imagesToArray($GLOBALS['data']['products'][$productId]),
    'productId' => $productId,
    'options' => $GLOBALS['options'],
    'contactForm' => renderContactForm('Záujem o '.$productId, 'Dobrý deň, máme záujem o váš produkt '.$GLOBALS['data']['products'][$productId]['opts']['name'].'. Pošlite nám prosím detailnejšie informácie.')
  ));
}

function renderDocument($document)
{
    $image = pathinfo($document, PATHINFO_FILENAME).".jpg";
    return $GLOBALS['twig']->render('document.twig', array(
    'content' => $GLOBALS['data']['documents'][pathinfo($document, PATHINFO_FILENAME)],
    'data' => $GLOBALS['data']['documents'],
    'image' => $image,
    'options' => $GLOBALS['options'],
    'contactForm' => renderContactForm('List pre Pána Kráľa', '')
    //'pastEvents' => renderEvents($project, 'past')
  ));
}

function renderMenu()
{
    return $GLOBALS['twig']->render('menu.twig', array(
    'menu' => $GLOBALS['data']['opts']['menu'],
    'options' => $GLOBALS['options']
  ));
}

function renderGallery($projectId, $gallery)
{
    return $GLOBALS['twig']->render('gallery.twig', array(
    'data' => $GLOBALS['data']['projects'][$projectId]['gallery'][$gallery],
    'project' => $projectId,
    'gallery' => $gallery,
    'options' => $GLOBALS['options']
  ));
}

function renderContactForm($subject, $text)
{
    return $GLOBALS['twig']->render('contactform.twig', array(
  'data' => $GLOBALS['data'],
  'options' => $GLOBALS['options'],
  'subject' => $subject,
  'text' => $text
));
}

function renderFormCheckboxes()
{
    return $GLOBALS['twig']->render('form-checkboxes.twig', array(
    'data' => $GLOBALS['data'],
    'options' => $GLOBALS['options']
  ));
}

function renderHomePage()
{
    return $GLOBALS['twig']->render('page.twig', array(
  'menu' => renderMenu(),
  'head' => renderHead($GLOBALS['data']['opts']),
  'cover' => renderCover(),
  'content' => renderProjects().renderProducts(),
  'footer' => renderFooter(),
  'scripts' => renderScripts(),
  'masterClass' => 'home',
  'options' => $GLOBALS['options']
));
}

function renderProjectPage($projectId)
{
    return $GLOBALS['twig']->render('page.twig', array(
  'menu' => renderMenu(),
  'head' => renderHead($GLOBALS['data']['projects'][$projectId]['opts']),
  'cover' => renderCover('project', $projectId, ""),
  'content' => renderProject($projectId, 'next'),
  'footer' => renderFooter(),
  'scripts' => renderScripts(),
  'masterClass' => 'project',
  'options' => $GLOBALS['options']
));
}

function renderPage($pageType, $elementId)
{
    return $GLOBALS['twig']->render('page.twig', array(
  'menu' => renderMenu(),
  'head' => renderHead($GLOBALS['data'][$pageType.'s'][$elementId]['opts']),
  'cover' => renderCover($pageType, $elementId, ""),
  'content' => renderProduct($elementId),
  'footer' => renderFooter(),
  'scripts' => renderScripts(),
  'masterClass' => $pageType,
  'options' => $GLOBALS['options']
));
}

function renderProjectArchivePage($projectId)
{
    return $GLOBALS['twig']->render('page.twig', array(
  'menu' => renderMenu(),
  'head' => renderHead($GLOBALS['data']['projects'][$projectId]['opts']),
  'cover' => renderCover('project', $projectId, ""),
  'content' => renderProject($projectId, 'all'),
  'footer' => renderFooter(),
  'scripts' => renderScripts(),
  'masterClass' => 'project',
  'options' => $GLOBALS['options']
));
}

function renderGalleryPage($projectId, $gallery)
{
    return $GLOBALS['twig']->render('page.twig', array(
  'menu' => renderMenu(),
  'head' => renderHead($GLOBALS['data']['projects'][$projectId]['opts']),
  'cover' => renderCover(),
  'content' => renderGallery($projectId, $gallery),
  //'footer' => renderFooter(),
  'scripts' => renderScripts(),
  'masterClass' => 'project',
  'options' => $GLOBALS['options']
));
}

function renderDocumentPage($document)
{
    return $GLOBALS['twig']->render('page.twig', array(
      'menu' => renderMenu(),
      'head' => renderHead($GLOBALS['data']['opts']),
      'cover' => renderCover(),
      'content' => renderDocument($document),
      'footer' => renderFooter(),
      'scripts' => renderScripts(),
      'masterClass' => 'document',
      'options' => $GLOBALS['options']
  ));
}

function saveIndex()
{
    /* Save Index.twig */
    $content = renderHomePage();
    file_put_contents($GLOBALS['options']['basepath'].'index.html', $content);
}

function saveProject($key)
{
    /* Save Projects' html files */
    $dir = $GLOBALS['options']['basepath'].'./'.$key.'/';
    if (!file_exists($dir)) {
        mkdir($dir, 0777, true);
    }
    file_put_contents($dir.'/index.html', renderProjectPage($key));
    file_put_contents($dir.'/archive.html', renderProjectArchivePage($key));
    if (array_key_exists('gallery', $GLOBALS['data']['projects'][$key])) {
        foreach ($GLOBALS['data']['projects'][$key]['gallery'] as $key2 => $gallery) {
            $subdir = $GLOBALS['options']['basepath'].'./'.$key.'/gallery/';
            if (!file_exists($subdir)) {
                mkdir($subdir, 0777, true);
            }
            file_put_contents($subdir.'/'.$key2.'.html', renderGalleryPage($key, $key2));
        }
    }
}

function saveProduct($key)
{
    /* Save Projects' html files */
    $dir = $GLOBALS['options']['basepath'].'./products/';
    if (!file_exists($dir)) {
        mkdir($dir, 0777, true);
    }
    file_put_contents($dir.$key.'.html', renderPage('product', $key));
}

function saveDocument($doc)
{
    /* Save Documents' html files */
    file_put_contents($GLOBALS['options']['basepath'].pathinfo($doc, PATHINFO_FILENAME).'.html', renderDocumentPage($doc));
}

function createFiles()
{

  /* INITIATE SAVING OF INDEX.HTML */
    saveIndex();

    /* INITIATE SAVING OF PROJECTS FILES */
    if (array_key_exists('projects', $GLOBALS['data'])) {
        foreach ($GLOBALS['data']['projects'] as $key => $project) {
            saveProject($key);
        }
    }

    /* INITIATE SAVING OF PAGE'S DOCUMENTS */
    if (array_key_exists('documents', $GLOBALS['data'])) {
        foreach ($GLOBALS['data']['documents'] as $key => $doc) {
            if (pathinfo($doc, PATHINFO_EXTENSION) == "md") {
                saveDocument($doc);
            }
        }
    }

    /* INITIATE SAVING OF PRODUCTS products FILES */
    if (array_key_exists('products', $GLOBALS['data'])) {
        foreach ($GLOBALS['data']['products'] as $key => $project) {
            saveProduct($key);
        }
    }
    echo "<p>Done.</p>";
}




/*

You can create your own function or test and just pass the arguments to the PHP function.

$test = new Twig_SimpleTest('ondisk', function ($file) {
    return file_exists($file);
});

And then in your template:

{% if filename is ondisk %}
    blah
{% endif %}

Unfortunately is exists sounds weird in English. Perhaps a function would make more sense.


*/

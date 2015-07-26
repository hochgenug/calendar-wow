<?php
date_default_timezone_set('Europe/Paris');

require "vendor/autoload.php";
use Symfony\Component\Yaml\Yaml;

$paramFile = 'parameters.yml';
$params = Yaml::parse(file_get_contents($paramFile))['parameters'];

foreach ($params as $param => $value) {
    switch ($param) {
        case 'developer_key' && empty($value):
        case 'calendar_id' && empty($value):
        case 'ip' && empty($value):
        case 'mac_address' && empty($value):
            trigger_error("Merci de renseigner le parametre {$param} dans {$paramFile} (ou relancer un composer update).", E_USER_ERROR);
            break;
    }
}

$flag = false;

$client = new Google_Client();
$client->setDeveloperKey($params['developer_key']);

$service = new Google_Service_Calendar($client);

/** Coupures personnalisés */
$items = $service->events->listEvents($params['calendar_id'])->getItems();
/** @var Google_Service_Calendar_Event $item */
foreach ($items as $item) {
    if ($item->getStart()->date === date("Y-m-d")) {
        $flag = 'Coupure du WAN automatique';
    }
}

/** Jours fériés */
if (!empty($params['calendar_ferie'])) {
    $items = $service->events->listEvents($params['calendar_ferie'])->getItems();
    /** @var Google_Service_Calendar_Event $item */
    foreach ($items as $item) {
        if ($item->getStart()->date === date("Y-m-d")) {
            $flag = 'Jour férié, pas de WAN';
        }
    }
}

if ($flag === false) {
    header("Location: http://www.wakeonwan.fr/wakeup.php?ip={$params['ip']}&mac={$params['mac_address']}");
}

print $flag;

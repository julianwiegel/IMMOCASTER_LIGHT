ImmocasterLight v1.0.0
===================

ImmocasterLight ist eine ALL-IN-ONE PHP Klasse, um beispielsweise
Immobilienobjekte auf ihrer Homepage einzubinden. 

ImmocasterLight hilft Ihnen sich über OAuth an der Immobilienscout24 (IS24) API anzumelden und übernimmt
alle notwendige Kommunikation. Die Klasse ist bewusst minimalistisch gehalten und somit
leicht erweiter und auf Ihre Bewürfnisse anpassbar. Zur Speicherung ihrer Zugangsdaten
wird keine Datenbank benötigt. Bitte beachten Sie das keinerlei Fehlerbehandlung enthalten
ist.

Howto Use
========

1. Speichern Sie die PHP-Datei und binden Sie diese in Ihr Projekt ein
2. Erzeugen Sie ein neues ImmocasterLight-Objekt mit ihren API-Zugangdaten
   http://rest.immobilienscout24.de/restapi/security/registration
3. Erzeugen Sie ein authorisiertes Token zum Zugriff auf alle IS24-APIs
4. (Implementieren Sie eigene Funktionen in der Klasse ImmocasterLight)

Komplettes Beispiel
=================

require_once(dirname(__FILE__) ."/immocaster_light.php");

//3. Var = TRUE um ein authorisiertes Token zu beziehen. Dies muss nur einmal geschehen.

$is24 = new IMMOCASTER_LIGHT("IHR_API_KEY", "IHR_API_SECRET", TRUE);

$params = array("realestatetype" => "housebuy", "geocodes" => "1276", "channel" => "is24", "username" => "me");

$objects = $is24->search_region($params);

var_dump(json_decode($objects));

Lizenz
=====

ImmocasterLight wird unter der GNU Lesser General Public License veröffentlicht
http://opensource.org/licenses/LGPL-2.1

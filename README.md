Presenter Server
================

WebSocket Server für die `Presenter` App.

Voraussetzungen
---------------

* PHP
* Composer

Installation
------------

1. Webserver & PHP installieren.

  Es gibt für alle verbreiteten Betriebssysteme fertige Webserver-Pakete, wie z.B.
  * XAMPP ([Windows](http://www.apachefriends.org/de/xampp-windows.html)/[Linux](http://www.apachefriends.org/de/xampp-linux.html)/[OS X](http://www.apachefriends.org/de/xampp-macosx.html))
  * [EasyPHP](http://www.easyphp.org/)
  * [WampServer](http://www.wampserver.com)

  Die XAMPP-Familie besitzt eine kleine grafische Benutzeroberfläche und ist auch für Einsteiger gut geeignet.

2. [`Composer`](http://getcomposer.org) installieren.

  Mithilfe dieses kleinen Programms können Abhängigkeiten bzw. externe PHP-Bibliotheken einfach installiert und aktualisiert werden. Eine kurze Anleitung und weitere Informationen findet man auf der [Homepage](http://getcomposer.org/doc/00-intro.md) des Authors.

3. Abhängigkeiten installieren.

  Der Presenter Server benutzt die [Ratchet](http://socketo.me)-Bibliothek, um Websocket-Verbindungen nach dem [WAMP](http://wamp.ws/)-Standard (`WebSocket Application Protocol`) aufzubauen (nicht zu verwechseln mit der unter Punkt 1 erwähnten Webserver-Distribution für Windows `WAMPP`, die von der Organisation [Apache Friends](http://www.apachefriends.org/de/index.html) entwickelt wird).

  Nachdem `Composer` erfolgreich installiert wurde, kann dieser über die Console benutzt werden.
  Dazu wechselt man in der Console in das Verzeichnis des `Presenter Server`. Anschließend wird je nach Installationsart und Betriebssystem die Ratchet-Bibliothek per
  * `composer install` oder
  * `php composer.phar install`
  durchgeführt.

  Dadurch wird die [`composer.json`](https://github.com/larsbo/presenter-server/blob/master/composer.json)-Datei im Hauptverzeichnis des Composer Server eingelesen, in der die Abhängigkeit zur `Ratchet`-Bibliothek im JSON-Format hinterlegt ist.
  
  Nachdem der Download der externen Scripte abgeschlossen ist, sollte ein neues Verzeichnis `vendor` angelegt worden sein. In diesem befindet sich nun die erwähnte Ratchet-Bibliothek sowie weitere Abhängigkeiten von dieser. Es wurde außerdem automatisch eine neue Datei `autoload.php` in diesem Verzeichnis angelegt, die dafür sorgt, dass die Bibliothek korrekt vom Presenter Server Script eingelesen werden kann.

  Der Server ist nun einsatzbereit und kann über die Console per 
  * `php bin/run-wamp-server.php` oder alternativ auch in kürzerer Schreibweise über ein einfaches Script 
  * `server-start`
  gestartet werden.

  Wenn alles geklappt hat, startet der Server und wartet auf eingehende Verbindungen mit dem Hinweis
  `Server started. Waiting for clients...`
  
  Beendet wird der Server, indem man `CTRL` + `C` in der Console tippt oder diese schließt.


Konfiguration
-------------

Der Standard-Port für ist auf `8080` eingestellt (alternativer HTTP-Port). Dieser kann einfach geändert werden, indem man diesen in der Datei `bin/run-wamp-server.php` in Zeile 14 anpasst.

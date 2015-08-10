=== Plugin Name ===
Contributors: Adiro GmbH
Donate link: 
Tags: adiro,intext,advertisement, ads, advertising, money, werbung
Requires at least: 3.0.0
Tested up to: 4.3
Stable tag: 1.2.3

wpadiro ermöglicht es Ihnen, den Adiro InText Code auf einfachste Weise in Ihren WordPress-Blog zu integrieren.

== Description ==

Das Plugin wurde mit der Wordpress-Version 4.3 getestet.
Falls es dennoch zu Problemen kommt oder es Verbesserungswünsche gibt, benutzen Sie bitte das [Kontaktformular](http://www.adiro.de/kontakt/).

Features

* Einfache Integration des Adiro InText Codes
* Einstellungen
	* Farbe des Titels
	* Farbe des Textes
	* Farbe des Links
	* Unterstreichungsfarbe
	* Art der Unterstreichung
	* Anzahl der Hooks auf einer Seite
* Filter
	* Werbung anzeigen bei Suchmaschinen Besucher
	* Werbung anzeigen, wenn Benutzer angemeldet ist
	* Werbung anzeigen, wenn Benutzer Administrator ist
	* Ausschluss von Phrasen (keine Werbung, wenn 'Phrase' im Text vorhanden)


== Installation ==

1. Laden Sie das Verzeichnis wpadiro in das /wp-content/plugins/ Verzeichnis Ihrer WordPress-Installation.
2. Aktivieren Sie das Plugin über das Menü 'Plugins' in WordPress.
3. Konfigurieren Sie das Plugin über den neuen Menüpunkt 'wpadiro'.

== Frequently Asked Questions ==

= Wo bekomme ich die Placement ID her? =

Sie finden die jeweilige Placement ID im Bereich 'Webseiten' im [Adiro Publisherbereich](http://publisher.adiro.de)

= Wo bekomme ich einen Adiro Benutzerkonto her? =

Falls Sie noch kein Adiro Benutzerkonto besitzen, können Sie sich [hier](http://publisher.adiro.de/register/) kostenlos anmelden.

= Wo bekomme ich weiterführende Hilfe mit meinem Benutzerkonto? =

Eine ausführliche FAQ steht Ihnen [hier](http://www.adiro.de/faq/) zur Verfügung.

= Wie kann ich die Ausspielung der Adiro InText-Werbung auf bestimmten Seiten verhindern? =

Dazu fügen Sie einfach "&lt;!-- aeNoAds --&gt;" in den HTML Quelltext der Seite ein, auf der Sie keine InText-Werbung ausspielen wollen.

= Wie kann ich den Bereich eingrenzen, in dem Adiro InText-Werbung erscheinen soll? =

Es gibt zwei HTML Kommentare, die als Begrenzer fungieren ("&lt;!-- aeBeginAds --&gt;" Anfagsmarkierung, "&lt;!-- aeEndAds --&gt;" als Endmarkierung)

== Screenshots ==

1. Allgemeine Konfigurationen
2. InText Konfigurationen
3. Filter Konfigurationen

== Changelog ==
= 1.2.1 =
* Bugfixing

= 1.2 =
* Änderung des Pluginnamens in "wpadiro"
* Unterstützung für die neuen Adiro TAGs hinzugefügt
* Bugfixing

= 1.1.1 =
* Filter hinzugefügt
* InText Konfigurationen hinzugefügt
* Warnungen und Nachrichten hinzugefügt

= 1.0.2 =
* Keine Werbung auf "Feed" und "404" Seiten
* Nachrichten hinzugefügt

= 1.0.1 =
* Begrenzungskommentare hinzugefügt
* Administration hinzugefügt

= 1.0.0 =
* Initial Version
# CamSnapshot Module
Das Modul stellt Funktionen zum Laden und aktualisieren von Kamera Snapshots zur Verfügung

### Inhaltverzeichnis

1. [Funktionsumfang](#1-funktionsumfang)
2. [Voraussetzungen](#2-voraussetzungen)
3. [Software-Installation](#3-software-installation)
4. [Einrichten der Instanzen in IP-Symcon](#4-einrichten-der-instanzen-in-ip-symcon)
5. [Statusvariablen und Profile](#5-statusvariablen-und-profile)
6. [WebFront & IPSView](#6-webfront--ipsview)
7. [PHP-Befehlsreferenz](#7-php-befehlsreferenz)

### 1. Funktionsumfang

* Laden von Snapshot Images von einer Kamera
* Einstellbarer Timer zum regelmäßigen Aktualisieren der Bilder
* Support von verschiedenen Auflösungen

### 2. Voraussetzungen

- IP-Symcon ab Version 4.x

### 3. Software-Installation

Über das Modul-Control folgende URL hinzufügen.  
`git://github.com/brownson/BrownsonMisc.git`  

### 4. Einrichten der Instanzen in IP-Symcon

- Unter "Instanz hinzufügen" ist das 'CamSnapshot'-Modul unter dem Hersteller '(Brownson)' aufgeführt.  

__Konfigurationsseite__:

Name                                   | Beschreibung
-------------------------------------- | ---------------------------------
Snapshot URL                           | URL für den Download der Images
Use Media Cache                        | Aktivierung der "cached" Option für MediaImages 
Bild mit kleinerer Auflösung erstellen | Erstellt ein Bild mit geringer Auflösung (ermöglicht schnelleres Laden in der GUI)
Verhältnis Image                       | Verhältnis für die Skalierung 
Autom Aktualisierung                   | Automatische Aktualisierung der Images
Interval                               | Zeitinterval für die automatische Aktualisierung

__Testseite__:

Name                                   | Beschreibung
-------------------------------------- | ---------------------------------
Button "Aktualisieren"                 | Snapshot Images von Kamera herunterladen

### 5. Statusvariablen und Profile

Die Statusvariablen/Kategorien werden automatisch angelegt. Das Löschen einzelner kann zu Fehlfunktionen führen.

##### Statusvariablen

Keine speziellen Statusvariablen vorhanden

##### Profile:

Es werden keine zusätzlichen Profile hinzugefügt

### 6. WebFront & IPSView

ImageLarge - MediaImage des heruntergeladenen Kamerabildes
ImageSmall - MediaImage des verkleinerten Kamerabildes
Refresh    - Skript zum Refresh des Kamerabildes

### 7. PHP-Befehlsreferenz

`boolean CamSnapshot_Refresh(integer $InstanzID);`  
Aktualisieren der Snapshot Images.  
Die Funktion liefert keinerlei Rückgabewert.  
Beispiel:  
`CamSnapshot_Refresh(12345);`


<?php

   /**
    @cond
    ############################################################################
    # GPL License                                                              #
    #                                                                          #
    # This file is part of the StudIP-Punkteverwaltung.                        #
    # Copyright (c) 2013, Philipp Kraus, <philipp.kraus@tu-clausthal.de>       #
    # This program is free software: you can redistribute it and/or modify     #
    # it under the terms of the GNU General Public License as                  #
    # published by the Free Software Foundation, either version 3 of the       #
    # License, or (at your option) any later version.                          #
    #                                                                          #
    # This program is distributed in the hope that it will be useful,          #
    # but WITHOUT ANY WARRANTY; without even the implied warranty of           #
    # MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the            #
    # GNU General Public License for more details.                             #
    #                                                                          #
    # You should have received a copy of the GNU General Public License        #
    # along with this program. If not, see <http://www.gnu.org/licenses/>.     #
    ############################################################################
    @endcond
    **/



    require_once("uebung.class.php");
    require_once("interface.class.php");
    require_once("bonuspunkt.class.php");
    require_once(dirname(__DIR__) . "/student.class.php");
    require_once(dirname(__DIR__) . "/studipincludes.php");

    

    /** Klasse f�r die Veranstaltungsdaten **/
    class Veranstaltung implements VeranstaltungsInterface
    {
        
        /** ID der Veranstaltung **/
        private $mcID = null;

        /** Cache ob die Veranstaltung geschlossen ist **/
        private $mlClose = false;

        /** Datum wann die Veranstaltung geschlossen wurde **/
        private $mcCloseDateTime = null;

        /** Cache f�r die Bestanden-Prozent-Zahl **/
        private $mnBestandenProzent = 0;

        /** Cache f�r �bungsanzahl, die als nicht-bestanden erlaubt ist **/
        private $mnAllowNichtBestanden = 0;



        /* statische Methode f�r die �berpr�fung, ob �bungsdaten zu einer Veranstaltung existieren
         * @param $px VeranstaltungsID (SeminarID) oder Veranstaltungsobjekt [leer f�r aktuelle ID, sofern vorhanden]
         * @return liefert null (false) bei Nicht-Existenz, andernfalls das Veranstaltungsobject
         **/
        static function get( $px = null )
        {
            return new Veranstaltung($px);
        }


        /** erzeugt einen neuen Eintrag f�r die Veranstaltung
         * @param $pcID VeranstaltungsID (SeminarID) [leer f�r aktuelle ID, sofern vorhanden]
         **/
        static function create( $pcID = null )
        {
            if ( (empty($pcID)) && (isset($GLOBALS["SessionSeminar"])) )
                $pcID = $GLOBALS["SessionSeminar"];

            $loPrepare = DBManager::get()->prepare( "insert into ppv_seminar (id, bestandenprozent, allow_nichtbestanden) values (:semid, :prozent, :nichtbestanden)" );
            $loPrepare->execute( array("semid" => $pcID, "prozent" => 100, "nichtbestanden" => 0) );
        }


        /** l�scht die Veranstaltung mit allen abh�ngigen Daten
         * @param $px Veranstaltungsobjekt / -ID
         * @param $pDummy Dummy Element, um die Interface Methode korrekt zu implementieren
         **/
        static function delete( $px, $pDummy = null )
        {
            $lo = Veranstaltung::get($px);
            if ($lo->isClosed())
                throw new Exception(_("Die Veranstaltung wurde geschlossen und kann somit nicht mehr gel�scht werden"));

            Uebung::delete( $lo );
            Bonuspunkt::delete( $lo );
            
            $laSQL = array(
                "delete from ppv_seminar where id = :semid",
                "delete from ppv_seminarmanuellezulassung where seminar = :semid",
                "delete from ppv_studiengang where seminar = :semid",
                "delete from ppv_ignore where seminar = :semid"
            );

            foreach( $laSQL  as $lcSQL )
            {
                $loPrepare = DBManager::get()->prepare( $lcSQL );
                $loPrepare->execute( array("semid" => $lo->id()) );
            }
        }
    
    
        /** privater Ctor, um das Objekt nur durch den statischen Factory (get) erzeugen zu k�nnen
         * @param $px VeranstaltungsID (SeminarID) oder Veranstaltungsobjekt
         **/
        private function __construct($px)
        {
            if ( (empty($px)) && (isset($GLOBALS["SessionSeminar"])) )
                $px = $GLOBALS["SessionSeminar"];

            if ($px instanceof $this)
            {
                $this->mcID                  = $px->id();
                $this->mlClose               = $px->mlClose;
                $this->mcCloseDateTime       = $px->mcCloseDateTime;
                $this->mnBestandenProzent    = $px->mnBestandenProzent;
                $this->mnAllowNichtBestanden = $px->mnAllowNichtBestanden;
            }
            elseif (is_string($px))
            {
                $loPrepare = DBManager::get()->prepare("select id, close, bestandenprozent, allow_nichtbestanden from ppv_seminar where id = :semid", array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY) );
                $loPrepare->execute( array("semid" => $px) );
                if ($loPrepare->rowCount() != 1)
                    throw new Exception(_("Veranstaltung nicht gefunden"));

                $result                       = $loPrepare->fetch(PDO::FETCH_ASSOC);
                $this->mcID                   = $result["id"];
                $this->mlClose                = !empty($result["close"]);
                $this->mnBestandenProzent     = floatval($result["bestandenprozent"]);
                $this->mnAllowNichtBestanden  = intval($result["allow_nichtbestanden"]);

                if ($this->mlClose)
                    $this->mcCloseDateTime = DateTime::createFromFormat("Y-m-d H:i:s", $result["close"])->format("d.m.Y H:i");
            }
            else
                throw new Exception(_("Veranstaltungparameter inkrorrekt"));
        }


        /** liefert die ID der Veranstaltung
         * @return ID
         **/
        function id()
        {
            return $this->mcID;
        }


        /** liefert den Namen der Veranstaltung
         * @return Semester
         **/
        function name()
        {
            $loSeminar = new Seminar($this->mcID);
            return $loSeminar->getName();
        }


        /** liefert den Semesternamen der Veranstaltung
         * @return Semester
         **/
        function semester()
        {
            $loSeminar  = new Seminar($this->mcID);
            return $loSeminar->getStartSemesterName();
        }


        /** liefert die Prozentzahl (�ber alle �bungen) ab wann eine Veranstaltung als bestanden gilt
         * @param $pn Wert zum setzen der Prozentzahl
         * @return Prozentwert
         **/
        function bestandenProzent( $pn = null )
        {
            $ln = 0;

            if (is_numeric($pn))
            {
                if ($this->isClosed())
                    throw new Exception(_("Die Veranstaltung wurde geschlossen, es k�nnen keine �nderungen mehr durchgef�hrt werden"));

                if (($pn < 0) || ($pn > 100))
                    throw new Exception(_("Parameter Prozentzahl f�r das Bestehen liegt nicht im Interval [0,100]"));

                $this->mnBestandenProzent = floatval($pn);
                DBManager::get()->prepare( "update ppv_seminar set bestandenprozent = :prozent where id = :semid" )->execute( array("semid" => $this->mcID, "prozent" => $this->mnBestandenProzent) );
            }
                
            return $this->mnBestandenProzent;
        }


        /** liefert die Anzahl an �bungen, die als nicht-bestanden
         * gewertet werden d�rfen, um die Veranstaltung trotzdem zu bestehen
         * @param $pn Anzahl der �bungen, die als nicht-bestanden akzeptiert werden
         * @return Anzahl
         **/
        function allowNichtBestanden( $pn = null )
        {
            $ln = 0;

            if (is_numeric($pn))
            {
                if ($this->isClosed())
                    throw new Exception(_("Die Veranstaltung wurde geschlossen, es k�nnen keine �nderungen mehr durchgef�hrt werden"));

                if ($pn < 0)
                    throw new Exception(_("Der Parameter f�r die Anzahl der als nicht bestand gewertenden �bungen, die trotzdem akzeptiert werden, muss gr��er gleich null sein"));

                $this->mnAllowNichtBestanden = intval($pn);
                DBManager::get()->prepare( "update ppv_seminar set allow_nichtbestanden = :anzahl where id = :semid" )->execute( array("semid" => $this->mcID, "anzahl" => $this->mnAllowNichtBestanden) );
            }
            
            return $this->mnAllowNichtBestanden;
        }


        /** liefert / setzt die Bemerkung 
         * @param $pc Bemerkung 
         * @return Bemerkungstext
         **/
        function bemerkung( $pc = false )
        {
            $lc = null;

            if ( (!is_bool($pc)) && ((empty($pc)) || (is_string($pc))) )
            {
                if ($this->isClosed())
                    throw new Exception(_("Die Veranstaltung wurde geschlossen, es k�nnen keine �nderungen mehr durchgef�hrt werden"));


                DBManager::get()->prepare( "update ppv_seminar set bemerkung = :bem where id = :semid" )->execute( array("semid" => $this->mcID, "bem" => (empty($pc) ? null : $pc)) );

                $lc = $pc;
            } else {
                $loPrepare = DBManager::get()->prepare("select bemerkung from ppv_seminar where id = :semid", array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY) );
                $loPrepare->execute( array("semid" => $this->mcID) );

                if ($loPrepare->rowCount() == 1)
                {
                    $result = $loPrepare->fetch(PDO::FETCH_ASSOC);
                    $lc     = $result["bemerkung"];
                }

            }

            return $lc;
        }


        /** schlie�t die Veranstaltung f�r �nderungen **/
        function close()
        {
            if ($this->mlClose)
                return;

            $laErrorList = array();

            // Studieng�nge der Teilnehmer setzen, sofern sie es nicht selbstst�ndig gemacht haben
            $loPrepare = DBManager::get()->prepare("select student from ppv_uebungstudent as ues join ppv_uebung as ueb on ues.uebung = ueb.id where ueb.seminar = :semid group by student", array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY) );
            $loPrepare->execute( array("semid" => $this->mcID) );


            try {
                foreach( $loPrepare->fetchAll(PDO::FETCH_ASSOC) as $row )
                {
                    $loStudent = new Student( $row["student"] );
                    if (!$loStudent->studiengang($this))
                    {
                        $laStudiengaenge = $loStudent->studiengang();

                        // der Datenstand ist nicht immer konsistent, d.h. es existieren Studenten, bei denen der Studiengang
                        // und/oder Abschluss fehlt, wir holen somit den ersten Studiengang in der Liste, fehlt dort etwas
                        // nehmen wir den letzten und wir hoffen, dass es dann klappt...
                        $laStudiengang   = reset( $laStudiengaenge );
                        if ( (!$laStudiengang["abschluss_id"]) || (!$laStudiengang["fach_id"]) )
                            $laStudiengang = end($laStudiengaenge);
                        $loStudent->studiengang( $this, $laStudiengang["abschluss_id"], $laStudiengang["fach_id"]);
                    }
                }
            } catch (Exception $e) {
                array_push($laErrorList, $e->getMessage());
            }


            if (empty($laErrorList))
            {
                // Veranstaltung schlie�en
                $this->mlClose         = true;
                $this->mcCloseDateTime = date("Y-m-d H:i:s");
                DBManager::get()->prepare( "update ppv_seminar set close = :close where id = :semid" )->execute( array("semid" => $this->mcID, "close" => $this->mcCloseDateTime) );

            } else {

                $this->mlClose         = false;
                $this->mcCloseDateTime = null;
                
                throw new Exception(_("Veranstaltung konnte nicht geschlossen werden, da Fehler aufgetreten sind: ".implode(", ", $laErrorList)));
            }

        }

        /** �ffnet die Veranstaltung wieder, falls sie geschlossen wurde **/
        function reopen()
        {
            DBManager::get()->prepare( "update ppv_seminar set close = :close where id = :semid" )->execute( array("semid" => $this->mcID, "close" => null) );
            $this->mlClose         = false;
            $this->mcCloseDateTime = null;
        }


        /** liefert ob die Veranstaltung geschlossen ist
         * @return boolean
         **/
        function isClosed()
        {
            return $this->mlClose;
        }

        /** liefert Datum & Uhrzeit zur�ck, wann die Veranstaltung geschlossen wurde
         * @return String mit Datum & Uhrzeit oder null
         **/
        function closedDateTime()
        {
            return $this->mcCloseDateTime;
        }

        
        /** liefert die Inforamtion, ob die Veranstaltung �bungen hat
         * @return Boolean ob �bungen existieren
         **/
        function hasUebungen()
        {
            $loPrepare = DBManager::get()->prepare("select id from ppv_uebung where seminar = :semid", array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY) );
            $loPrepare->execute( array("semid" => $this->mcID) );
            
            return $loPrepare->rowCount() > 0;
        }


        /** liefert ein Array mit allen �bungsobjekten
         * @return Array mit �bungsobjekten
         **/
        function uebungen()
        {
            $la = array();

            $loPrepare = DBManager::get()->prepare("select id from ppv_uebung where seminar = :semid", array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY) );
            $loPrepare->execute( array("semid" => $this->mcID) );

            foreach( $loPrepare->fetchAll(PDO::FETCH_ASSOC) as $row )
                array_push($la, new Uebung($this, $row["id"]) );

            // Sortierung f�r die Ausgabe
            usort($la, function($a, $b) {
                $lxSort1 = $a->abgabeDatum( false, Uebung::DATEASOBJECT );
                $lxSort2 = $b->abgabeDatum( false, Uebung::DATEASOBJECT );

                if ( (!empty($lxSort1)) && (!empty($lxSort2)) )
                {
                  $lxSort1 = $lxSort1->getTimestamp();
                  $lxSort2 = $lxSort2->getTimestamp();
                  return ($lxSort1 < $lxSort2) ? -1 :  ( ($lxSort1 > $lxSort2) ? 1 : 0 );
                }
                                                                                         
                $lxSort1 = $a->name();
                $lxSort2 = $b->name();
                return strcasecmp($lcSort1, $lcSort2);
            });

            return $la;
        }


        /** liefert ein Bonuspunkteobjekt f�r die Veranstaltung
         * @return Bonuspunkteobjekt
         **/
        function bonuspunkte()
        {
            return new Bonuspunkt( $this );
        }


        /** liefert alle Teilnehmer der Veranstaltung
         * @preturn Array mit Studentenobjekten
         **/
        function teilnehmer()
        {
            $loPrepare = DBManager::get()->prepare("select user_id as student from seminar_user where status = :status and Seminar_id = :semid" );
            $loPrepare->execute( array("semid" => $this->mcID, "status" => "autor") );
            
            $la = array();
            $laIgnore = $this->getIgnore();
            foreach( $loPrepare->fetchAll(PDO::FETCH_ASSOC) as $row )
                if (!array_key_exists($row["student"], $laIgnore))
                    array_push($la, new Student($row["student"]) );

            return $la;
        }
        
        
        /** updated alle Teilnehmer in den �bungen **/
        function updateTeilnehmer()
        {
            foreach($this->uebungen() as $ueb)
                $ueb->updateTeilnehmer();
        }
    
    
        /** erm�glicht das "harte" L�schen aller User-Daten, die in der Veranstaltung f�r den User
         * hinterlegt sind, ohne explizite Existenzpr�fungen durchzuf�hren (z.B. bei Studenten ohne Matrikelnummer)
         * @param $pxUser User-Hash / User- / Studentenobjekt
         **/
        function clearUserData( $pxUser )
        {
            if ($pxUser instanceof Student)
                $pxUser = $pxUser->id();
            elseif ($pxUser instanceof User)
                $pxUser = $pxUser->getUserid();
            elseif (is_string($pxUser)) {}
            else
                throw new Exception(_("Fehlerhaftes Datenobjekt �bergeben"));

            // es wird gepr�ft, ob f�r den Studenten Daten hinterlegt sind, sofern die Veranstaltung
            // geschlossen ist, in diesem Fall wird dann eine Exception geworfen, da keine
            // �nderungen durchgef�hrt werden d�rfen, andernfalls werden die Daten des Users entfernt
            if ($this->isClosed())
            {
                $laSelectExecutes = array(
                    "select * from ppv_uebungstudent as us join ppv_uebung as u on us.uebung = u.id where u.seminar= :semid and us.student= :student",
                    "select * from ppv_seminarmanuellezulassung where seminar= :semid and student= :student",
                    "select * from ppv_studiengang where seminar= :semid and student= :student",
                    "select * from from ppv_uebungstudentlog as usl join ppv_uebung as u on usl.uebung = u.id where u.seminar= :semid and usl.student= :student"
                );
                
                foreach( $laSelectExecutes as $lcSQL )
                {
                    $loPrepare = DBManager::get()->prepare( $lcSQL );
                    $loPrepare->execute( array("semid" => $this->mcID, "student" => $pxUser) );
                    if ($loPrepare->rowCount() > 0)
                        throw new Exception(_("Die Veranstaltung wurde geschlossen und somit k�nnen keine �nderungen durchgef�hrt werden"));
                }
            }
            
            
            // l�sche zuerst alle Punktedaten, manuelle Zulassungen, Studiengang & Logdaten
            $laDeleteExecutes = array(
                            "delete us from ppv_uebungstudent as us join ppv_uebung as u on us.uebung = u.id where u.seminar= :semid and us.student= :student",
                            "delete from ppv_seminarmanuellezulassung where seminar= :semid and student= :student",
                            "delete from ppv_studiengang where seminar= :semid and student= :student",
                            "delete usl from ppv_uebungstudentlog as usl join ppv_uebung as u on usl.uebung = u.id where u.seminar= :semid and usl.student= :student"
            );
        
            foreach( $laDeleteExecutes as $lcSQL )
            {
                $loPrepare = DBManager::get()->prepare( $lcSQL );
                $loPrepare->execute( array("semid" => $this->mcID, "student" => $pxUser) );
            }
        }
        
        
        /** f�gt einen Benutzer auf die Ignoreliste
         * @param $pxUser Userobjekt / -authentifizierung
         * @param $pcBemerkung Bemerkungstext
         **/
        function setIgnore( $pxUser, $pcBemerkung = null )
        {
            if ($pxUser instanceof Student)
                $pxUser = $pxUser->id();
            elseif ($pxUser instanceof User)
                $pxUser = $pxUser->getUserid();
            elseif (is_string($pxUser)) {}
            else
                throw new Exception(_("Fehlerhaftes Datenobjekt �bergeben"));

            
            // pr�ft ob die User-ID als Teilnehmer der Veranstaltung gefunden werden kann
            $loPrepare = DBManager::get()->prepare("select * from seminar_user where Seminar_id = :semid and user_id = :uid" );
            $loPrepare->execute( array("semid" => $this->mcID, "uid" => $pxUser) );
            if ($loPrepare->rowCount() != 1)
                throw new Exception(_("User konnte nicht als Teilnehmer ermittelt werden"));
            
            
            // entfernt die Daten und f�gt den User auf die Ignore-Liste ein
            $this->clearUserData( $pxUser );
            
            $loPrepare = DBManager::get()->prepare( "insert ignore into ppv_ignore (seminar, student, bemerkung) values (:semid, :student, :bemerkung)" );
            $loPrepare->execute( array("semid" => $this->mcID, "student" => $pxUser, "bemerkung" => (empty($pcBemerkung) ? null : $pcBemerkung)) );
        }
        
        
        /** entfernt einen Eintrag von der Ignoreliste
         * @param $pxUser Userobjekt / -authentifizierung
         **/
        function removeIgnore( $pxUser )
        {
            if ($this->isClosed())
                throw new Exception(_("Die Veranstaltung wurde geschlossen und somit k�nnen keine �nderungen durchgef�hrt werden"));
            
            if ($pxUser instanceof Student)
                $pxUser = $pxUser->id();
            elseif ($pxUser instanceof User)
                $pxUser = $pxUser->getUserid();
            elseif (is_string($pxUser)) {}
            else
                throw new Exception(_("Fehlerhaftes Datenobjekt �bergeben"));
            
            
            $loPrepare = DBManager::get()->prepare( "delete from ppv_ignore where seminar = :semid and student = :student" );
            $loPrepare->execute( array("semid" => $this->mcID, "student" => $pxUser) );
        }
        
        
        /** liefert eine Liste mit allen Daten der Ignorelist
         * @return Array mit Authentifizierungen und Bemerkungen
         **/
        function getIgnore()
        {
            $loPrepare = DBManager::get()->prepare( "select student, bemerkung from ppv_ignore where seminar = :semid" );
            $loPrepare->execute( array("semid" => $this->mcID) );
         
            $la = array();
            foreach( $loPrepare->fetchAll(PDO::FETCH_ASSOC) as $row )
                $la[ $row["student"] ] = $row["bemerkung"];
            
            return $la;
        }

    }
    
    
?>

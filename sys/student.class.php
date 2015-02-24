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



    require_once("tools.class.php");
    require_once("baseuser.class.php");
    require_once("matrikelnummer/factory.class.php");
    require_once("veranstaltung/veranstaltung.class.php");
    

    
    /** Klasse um einen Studenten vollst�ndig abzubilden **/
    class Student extends BaseUser
    {

        /** speichert die Matrikelnummer des Users **/
        protected $mnMatrikelnummer = null;

        
        /** Ctor um einen Studenten zu erzeugen
         * @param $px Studentenobjekt oder AuthentifizierungsID
         **/
        function __construct( $px )
        {
            if (is_numeric($px))
            {
                $la = MatrikelNummerFactory::get()->get( $px );
                if (is_array($la))
                {
                    $this->mnMatrikelnummer = $px;
                    parent::__construct($la["uid"]);
                }
            
            } else {
                
                parent::__construct($px);
                $la = MatrikelNummerFactory::get()->get( $this->mcID );
                if (is_array($la))
                    $this->mnMatrikelnummer = $la["num"];

            }
                
            if (empty($this->mnMatrikelnummer))
                throw new UserDataIncomplete( sprintf(_("Matrikelnummer zum Login: [%s] / EMail: [%s] konnten nicht ermittelt werden. %s"), $loUser->username, $loUser->email, sprintf("<a href=\"%s\">%s</a>", Tools::url_for("admin/addignore", array("Auth" => $this->mcID)), _("Benutzer ignorieren")) ) );
        }


        /** liefert den Studiengang des Users inkl. dem Abschluss
         * @param $poVeranstaltung Veranstaltungsobjekt
         * @param $pcAbschluss AbschlussID
         * @param $pcStudiengang StudiengangsID
         * @return Studiengang als Array oder den Eintrag des Studiengangs f�r die Veranstaltung
         **/
        function studiengang( $poVeranstaltung = null, $pcAbschluss = null, $pcStudiengang = null )
        {
            $laStudiengaenge = UserModel::getUserStudycourse($this->mcID);
            if (!($poVeranstaltung instanceof Veranstaltung))
                return $laStudiengaenge;

            $la = array();
            if ( (($pcStudiengang) && (!$pcAbschluss)) || ((!$pcStudiengang) && ($pcAbschluss)) )
            {
                throw new Exception( sprintf(_("F�r den Studierenden %s (%s) stimmen Studiengang- und/oder Abschlusszuordnung nicht"), $this->mcName, $this->mcEmail) );
            } elseif (($pcStudiengang) && ($pcAbschluss)) {
                if ($poVeranstaltung->isClosed())
                    throw new Exception(_("Veranstaltung ist geschlossen, eine �nderung des Studiengangs ist nicht m�glich."));

                $llFound = false;
                foreach ( $laStudiengaenge as $item )
                    if ( ($item["abschluss_id"] == $pcAbschluss) && ($item["fach_id"] == $pcStudiengang) )
                    {
                        $llFound = true;
                        break;
                    }
                if (!$llFound)
                    throw new Exception(_("Der Studiengang / Abschluss wurde nicht in der Liste der eingetragenen Studieng�nge gefunden"));

                $loPrepare = DBManager::get()->prepare( "insert into ppv_studiengang values (:semid, :student, :abschluss, :studiengang) on duplicate key update abschluss = :abschluss, studiengang = :studiengang" );
                $loPrepare->execute( array("semid" => $poVeranstaltung->id(), "student" => $this->mcID, "abschluss" => $pcAbschluss, "studiengang" => $pcStudiengang) );
            }

            $loPrepare = DBManager::get()->prepare( "select s.abschluss, s.studiengang, a.name as abschlussname, g.name as studiengangname from ppv_studiengang as s left join abschluss as a on a.abschluss_id = s.abschluss left join studiengaenge as g on g.studiengang_id = s.studiengang where student = :student and seminar = :semid" );
            $loPrepare->execute( array("semid" => $poVeranstaltung->id(), "student" => $this->mcID) );

            if ($loPrepare->rowCount() == 1)
            {
                $result = $loPrepare->fetch(PDO::FETCH_ASSOC);
                array_push($la, array("fach" => $result["studiengangname"], "abschluss" => $result["abschlussname"], "abschluss_id" => $result["abschluss"], "fach_id" => $result["studiengang"], "semester" => null) );
            }

            return $la;
        }


        /** pr�ft, ob f�r den Studenten der Studiengang korrekt hinterlegt ist
         * @retrun boolean ob ein Fehler vorhanden ist
         **/
        function checkStudiengangAbschlussFehler()
        {
            foreach ( UserModel::getUserStudycourse($this->mcID) as $item )
                if ( (empty($item["abschluss_id"])) || (empty($item["fach_id"])) )
                    return true;

            return false;
        }


        /** liefert die Information, ob f�r den Studenten eine manuelle Zulassung hinterlegt wurde
         * @param $poVeranstaltung Veranstaltungsobjekt
         * @param $pcBemerkung Bemerkungsstring, der gesetzt werden soll
         * @return String mit einer Bemerkung oder null
         **/
        function manuelleZulassung( $poVeranstaltung, $pcBemerkung = false )
        {
            if (!($poVeranstaltung instanceof Veranstaltung))
                throw new Exception(_("kein Veranstaltungsobjekt �bergeben"));

            $lc = null;
            if ( (!is_bool($pcBemerkung)) || (is_string($pc)) )
            {

                if ($poVeranstaltung->isClosed())
                    throw new Exception(_("Die Veranstaltung wurde geschlossen, es k�nnen keine �nderungen mehr durchgef�hrt werden"));

                if (empty($pcBemerkung))
                {
                    $loPrepare = DBManager::get()->prepare( "delete from ppv_seminarmanuellezulassung where seminar=:semid and student=:student limit 1" );
                    $loPrepare->execute( array("semid" => $poVeranstaltung->id(), "student" => $this->mcID) );
                } else {
                    $loPrepare = DBManager::get()->prepare( "insert into ppv_seminarmanuellezulassung (seminar, student, bemerkung) values (:semid, :student, :bemerkung) on duplicate key update bemerkung = :bemerkung" );
                    $loPrepare->execute( array("semid" => $poVeranstaltung->id(), "student" => $this->mcID, "bemerkung" => $pcBemerkung) );
                }

                $lc = $pcBemerkung;

            } else {

                $loPrepare = DBManager::get()->prepare( "select bemerkung from ppv_seminarmanuellezulassung where seminar=:semid and student=:student" );
                $loPrepare->execute( array("semid" => $poVeranstaltung->id(), "student" => $this->mcID) );

                if ($loPrepare->rowCount() == 1)
                {
                    $result = $loPrepare->fetch(PDO::FETCH_ASSOC);
                    $lc     = $result["bemerkung"];
                }
                
            }

            return $lc;
        }
        
        
        /** liefert alle Gruppen einer Veranstaltung in denen der Student angemeldet ist
         * @param $poVeranstaltung Veranstaltungsobjekt
         * @return Array mit GruppenID und Namen
         **/
        function gruppen( $poVeranstaltung )
        {
            if (!($poVeranstaltung instanceof Veranstaltung))
                throw new Exception(_("kein Veranstaltungsobjekt �bergeben"));
            
            $la = array();
            
            $loPrepare = DBManager::get()->prepare( "SELECT statusgruppe_id as id, name FROM statusgruppen JOIN statusgruppe_user USING (statusgruppe_id) WHERE range_id = :semid AND user_id = :student" );
            $loPrepare->execute( array("semid" => $poVeranstaltung->id(), "student" => $this->mcID) );
            
            foreach( $loPrepare->fetchAll(PDO::FETCH_ASSOC) as $row )
                array_push($la, $row );
            
            return $la;
        }


        /** liefert die Matrikelnummer des Users, sofern vorhanden
         * @return Matrikelnummer oder null
         **/
        function matrikelnummer()
        {
            return $this->mnMatrikelnummer;
        }

    }




?>

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
    require_once(dirname(__DIR__) . "/student.class.php");



    /** Klasse f�r die �bung-Student-Beziehung
     * @note Die Klasse legt bei �nderungen automatisiert ein Log an
     **/
    class StudentUebung implements VeranstaltungsInterface
    {

        /** �bungsobjekt **/
        private $moUebung      = null;

        /** Studentenobjekt **/
        private $moStudent     = null;

        /** Prepare Statement f�r das Log zu erezugen **/
        private $moLogPrepare  = null;

        

        /** l�scht den Eintrag von einem Studenten zur �bung mit einem ggf vorhandenen Log
         * @param $pxUebung �bung
         * @param $pxAuth Authentifizierungsschl�ssel des Users oder Studentenobjekt
         **/
        static function delete( $pxUebung, $pxAuth )
        {
            $loUebung  = new Uebung( $pxUebung );
            $loStudent = new Student( $pxAuth );


            $loPrepare = DBManager::get()->prepare( "delete from ppv_uebungstudentlog where uebung = :uebungid and student = :auth" );
            $loPrepare->execute( array("uebungid" => $loUebung->id(), "auth" => $loStudent->id()) );

            $loPrepare = DBManager::get()->prepare( "delete from ppv_uebungstudent where uebung = :uebungid and student = :auth" );
            $loPrepare->execute( array("uebungid" => $loUebung->id(), "auth" => $loStudent->id()) );

        }



        /** Ctor f�r den Zugriff auf auf die Studenten-�bungsbeziehung 
         * @param $pxUebung �bung
         * @param $pcAuth Authentifizierung
         **/
        function __construct( $pxUebung, $pxAuth )
        {
            if (is_string($pxAuth))
                $this->moStudent = new Student($pxAuth);
            elseif ($pxAuth instanceof Student)
                $this->moStudent = $pxAuth;
            else
                throw new Exception(_("Keine korrekten Authentifizierungsdaten �bergeben"));

            $this->moUebung     = new Uebung( $pxUebung );
            $this->moLogPrepare = DBManager::get()->prepare( "insert into ppv_uebungstudentlog select null, * from ppv_uebungstudentlog where uebung = :uebungid and student = :auth" );
        }


        /** liefert die Authentifizierung
         * @return AuthString
         **/
        function student()
        {
            return $this->moStudent;
        }


        /** liefert die �bung
         * @return liefert das �bungsobjekt
         **/
        function uebung()
        {
            return $this->moUebung;
        }

        
        /** liefert die IDs des Datensatzes
         * @return ID als Array
         **/
        function id()
        {
            return array( "uebung" => $this->moUebung->id(), "uid" => $this->moStudent->id() );
        }


        /** liefert / setzt die erreichten Punkte f�r den Stundent / �bung
         * und schreibt ggf einen vorhanden Datensatz ins Log
         * @param $pn Punke
         * @return Punkte
         **/
        function erreichtePunkte( $pn = false )
        {
            $ln = 0;
            if (is_numeric($pn))
            {
                $this->moLogPrepare->execute( array("uebungid" => $loUebung->id(), "auth" => $pcAuth) );
                
                $loPrepare = DBManager::get()->prepare( "insert into ppv_uebungstudent (uebung, student, erreichtepunkte) values (:uebungid, :auth, :punkte) on duplicate key update erreichtepunkte = :punkte" );
                $loPrepare->execute( array("uebungid" => $this->moUebung->id(), "auth" => $this->moStudent->id(), "punkte" => floatval($pn)) );

                $ln = $pn;

            } else {
                $loPrepare = DBManager::get()->prepare( "select erreichtepunkte from ppv_uebungstudent where uebung = :uebungid and student = :auth" );
                $loPrepare->execute( array("uebungid" => $this->moUebung->id(), "auth" => $this->moStudent->id()) );

                if ($loPrepare->rowCount() == 1)
                {
                    $result = $loPrepare->fetch(PDO::FETCH_ASSOC);
                    $ln     = $result["erreichtepunkte"];
                }
            }

            return floatval($ln);
        }


        /** liefert / setzt die Zusatzpunkt zu einer �bung f�r einen Studenten
         * und schreibt die Daten ins Log sofern vorhanden
         * @param $pn Punkte
         * @return Punkte
         **/
        function zusatzPunkte( $pn = false )
        {
            $ln = 0;
            if (is_numeric($pn))
            {
                $this->moLogPrepare->execute( array("uebungid" => $loUebung->id(), "auth" => $pcAuth) );
                
                $loPrepare = DBManager::get()->prepare( "insert into ppv_uebungstudent (uebung, student, zusatzpunkte) values (:uebungid, :auth, :punkte) on duplicate key update zusatzpunkte = :punkte" );
                $loPrepare->execute( array("uebungid" => $this->moUebung->id(), "auth" => $this->moStudent->id(), "punkte" => floatval($pn)) );

                $ln = $pn;

            } else {
                $loPrepare = DBManager::get()->prepare( "select zusatzpunkte from ppv_uebungstudent where uebung = :uebungid and student = :auth" );
                $loPrepare->execute( array("uebungid" => $this->moUebung->id(), "auth" => $this->moStudent->id()) );

                if ($loPrepare->rowCount() == 1)
                {
                    $result = $loPrepare->fetch(PDO::FETCH_ASSOC);
                    $ln     = $result["zusatzpunkte"];
                }
            }
            
            return floatval($ln);
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
                $this->moLogPrepare->execute( array("uebungid" => $loUebung->id(), "auth" => $pcAuth) );

                DBManager::get()->prepare( "update ppv_uebungstudent set bemerkung = :bem where seminar = :uebungid and student = :auth" )->execute( array("uebungid" => $this->moUebung->id(), "auth" => $this->moStudent->id(), "bem" => (empty($pc) ? null : $pc)) );

                $lc = $pc;
            } else {
                $loPrepare = DBManager::get()->prepare("select bemerkung from ppv_uebungstudent where seminar = :uebungid and student = :auth", array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY) );
                $loPrepare->execute( array("uebungid" => $this->moUebung->id(), "auth" => $this->moStudent->id()) );

                if ($loPrepare->rowCount() == 1)
                {
                    $result = $loPrepare->fetch(PDO::FETCH_ASSOC);
                    $lc     = $result["bemerkung"];
                }

            }
            
            return $lc;
        }
        

        /** liefert alle Logeintr�ge f�r diese Student-�bungsbeziehung
         * als assoziatives Array
         * @return assoziatives Array
         **/
        function log()
        {
            $la = array();

            $loPrepare = DBManager::get()->prepare("select erreichtepunkte, zusatzpunkte, bemerkung from ppv_uebungstudentlog where uebung = :uebungid and student = :auth", array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY) );
            $loPrepare->execute( array("uebungid" => $this->moUebung->id(), "auth" => $this->moStudent->id()) );

            foreach( $loPrepare->fetchAll(PDO::FETCH_ASSOC) as $row )
                array_push($la, $row );

            return $la;
        }

    }

?>

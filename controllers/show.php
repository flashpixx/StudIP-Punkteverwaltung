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



    require_once(dirname(__DIR__) . "/sys/veranstaltung/veranstaltung.class.php");
    require_once(dirname(__DIR__) . "/sys/auswertung.class.php");
    require_once(dirname(__DIR__) . "/sys/student.class.php");
    require_once(dirname(__DIR__) . "/sys/tools.class.php");
    

    /** Controller f�r die Sicht eines Studenten **/
    class ShowController extends StudipController
    {

        /** Ctor, um aus dem Dispatcher die Referenz auf das Pluginobjekt
         * zu bekommen
         * @param $poDispatch
         **/
        function __construct( $poDispatch )
        {
            parent::__construct($poDispatch);
            $this->plugin   = $poDispatch->plugin;
        }
    
    
        /** Before-Aufruf zum setzen von Defaultvariablen
         * @warn da der StudIPController keine Session initialisiert, muss die
         * Eigenschaft "flash" h�ndisch initialisiert werden, damit persistent die Werte
         * �bergeben werden k�nnen
         **/
        function before_filter( &$action, &$args )
        {
            PageLayout::setTitle( sprintf(_("%s - Punkteverwaltung - Anzeige"), $_SESSION["SessSemName"]["header_line"]) );
            $this->set_layout($GLOBALS["template_factory"]->open("layouts/base_without_infobox"));

            // Initialisierung der Session & setzen der Veranstaltung, damit jeder View
            // die aktuellen Daten bekommt
            $this->flash                                 = Trails_Flash::instance();
            $this->flash["veranstaltung"]                = Veranstaltung::get();
            
            $this->flash["studiengang"]                  = array();
            $this->flash["veranstaltungstudiengang"]     = array();
            
            
            $this->student                               = null;
            $this->auswertung                            = null;
            $this->initerror                             = null;
            
            try {
            
                $this->student                           = new Student($GLOBALS["user"]->id);
                
                $loAuswertung                            = new Auswertung( $this->flash["veranstaltung"] );
                $this->auswertung                        = $loAuswertung->studentdaten( $this->student );
                
                $this->flash["studiengang"]              = $this->student->studiengang();
                $this->flash["veranstaltungstudiengang"] = $this->student->studiengang($this->flash["veranstaltung"]);
                
            } catch (Exception $e) {
                $this->initerror = $e->getMessage();
            }
        }


        /** Default Action **/
        function index_action()
        {
            Tools::addHTMLHeaderElements( $this->plugin );
            
            $this->listaction       = $this->url_for( "show/jsonlist");
        }

        
        /** liest den oder die Studieng�nge des Users **/
        function studiengang_action()
        {
            try {
                
                if (!empty($this->initerror))
                    throw new Exception($this->initerror);

            } catch (Exception $e) { $this->flash["message"] = Tools::createMessage( "error", $e->getMessage() ); }
        }
        
        
        /** setzt den Studiengang des Users **/
        function studiengangset_action()
        {
            try {
                
                if (!empty($this->initerror))
                    throw new Exception($this->initerror);
                
                
                $laData    = explode("#", Request::quoted("studiengang"));
                if (count($laData) == 2)
                {
                    $this->student->studiengang($this->flash["veranstaltung"], trim($laData[0]), trim($laData[1]));
                    $this->flash["message"] = Tools::createMessage( "success", _("Anerkennung f�r den Studiengang f�r diese Veranstaltung ge�ndert") );
                }
                

            } catch (Exception $e) { $this->flash["message"] = Tools::createMessage( "error", $e->getMessage() ); }

            $this->redirect("show/studiengang");
        }
        
        
        
        /** erzeugt einen Eintrag f�r die Punktetabelle
         * @param $pcName Name der 1. Spalte
         * @param $pnPunkte Punkte der 2. Spalten
         * @param $pnProzent Prozent der Punkte der 3. Spalte
         * @param $pnScore Scorewert der 4. Spalte
         * @return Array
         **/
        private function createPunkteTableRow( $pcName = null, $pnPunkte = null, $pnProzent = null, $pnScore = null )
        {
            return array( "Uebung" => studip_utf8encode($pcName), "Punkte" => $pnPunkte, "PunkteProzent" => $pnProzent, "Score" => $pnScore );
        }
        
        
        /** Action, um die Json-Daten f�r die jTable zu erzeugen **/
        function jsonlist_action()
        {
            // Daten f�r das Json Objekt holen und ein Default Objekt setzen
            $laResult = array( "Result"  => "ERROR", "Records" => array() );
            
            try {
                
                if (!empty($this->initerror))
                    throw new Exception($this->initerror);
                
                $la = array();
                foreach( $this->auswertung["uebungen"] as $laUebung )
                    array_push( $la, $this->createPunkteTableRow( $laUebung["name"], $laUebung["studenten"][$this->student->id()]["punktesumme"], $laUebung["studenten"][$this->student->id()]["erreichteprozent"], $laUebung["studenten"][$this->student->id()]["score"] ) );

                array_push( $la, $this->createPunkteTableRow() );
                array_push( $la, $this->createPunkteTableRow( _("Bonuspunkte"), $this->auswertung["studenten"][$this->student->id()]["bonuspunkte"]) );
                array_push( $la, $this->createPunkteTableRow( _("Veranstaltung bestanden"),   $this->auswertung["studenten"][$this->student->id()]["veranstaltungenbestanden"] ? _("ja") : _("nein") ) );
                

                // alles fehlerfrei durchlaufen, setze Result
                $laResult["Records"] = $la;
                $laResult["Result"]  = "OK";
                
            } catch (Exception $e) { $laResult["Message"] = studip_utf8encode( $e->getMessage() ); }
            
            Tools::sendJson( $this, $laResult );
        }


        /** URL Aufruf **/
        function url_for($to)
        {
            $args = func_get_args();

            # find params
            $params = array();
            if (is_array(end($args)))
                $params = array_pop($args);

            # urlencode all but the first argument
            $args    = array_map("urlencode", $args);
            $args[0] = $to;
            
            return PluginEngine::getURL($this->dispatcher->plugin, $params, join("/", $args));
        }
        
    }

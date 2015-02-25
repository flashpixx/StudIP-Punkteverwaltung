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



    require_once(dirname(__DIR__) . "/sys/tools.class.php");
    require_once(dirname(__DIR__) . "/sys/student.class.php");
    require_once(dirname(__DIR__) . "/sys/authentification.class.php");
    require_once(dirname(__DIR__) . "/sys/veranstaltung/veranstaltung.class.php");
    require_once(dirname(__DIR__) . "/sys/veranstaltung/uebung.class.php");


    /** Controller f�r die Administration **/
    class AdminController extends StudipController
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
            PageLayout::setTitle( sprintf("%s - Punkteverwaltung - Administration", $_SESSION["SessSemName"]["header_line"]) );
            $this->set_layout($GLOBALS["template_factory"]->open("layouts/base_without_infobox"));

            // Initialisierung der Session & setzen der Veranstaltung, damit jeder View
            // die aktuellen Daten bekommt
            $this->flash                  = Trails_Flash::instance();
            $this->flash["veranstaltung"] = Veranstaltung::get();
        }


        /** Default Action **/
        function index_action()
        {
            Tools::addHTMLHeaderElements( $this->plugin );
        }


        /** erzeugt f�r eine Veranstaltung einen neuen Eintrag mit Defaultwerten **/
        function create_action()
        {
            if (!Authentification::hasDozentRecht())
                $this->flash["message"] = Tools::createMessage( "error", _("Sie haben nicht die erforderlichen Rechte um die Punkteverwaltung zu aktivieren") );

            else
                try {
                    Veranstaltung::create();
                    $this->flash["message"] = Tools::createMessage( "success", _("�bungsverwaltung wurde aktiviert") );
                } catch (Exception $e) {
                    $this->flash["message"] = Tools::createMessage( "error", $e->getMessage() );
                }
                
            $this->redirect("admin");
        }


        /** Update Aufruf, um die Einstellungen zu setzen **/
        function updatesettings_action()
        {
            if (!Authentification::hasDozentRecht())
                $this->flash["message"] = Tools::createMessage( "error", _("Sie haben nicht die erforderlichen Rechte um die Einstellung der Punkteverwaltung zu �ndern") );
            
            elseif (Request::submitted("submitted"))
            {
                try {
                    $this->flash["veranstaltung"]->bemerkung( Request::quoted("bemerkung") );
                    $this->flash["veranstaltung"]->bestandenProzent( Request::float("bestandenprozent"), 100 );
                    $this->flash["veranstaltung"]->allowNichtBestanden( Request::int("allow_nichtbestanden"), 0 );
                    
                    $this->flash["message"] = Tools::createMessage( "success", _("Einstellung gespeichert") );
                } catch (Exception $e) {
                    $this->flash["message"] = Tools::createMessage( "error", $e->getMessage() );
                }
            }

            $this->redirect("admin");
        }


        /** schlie�t eine Veranstaltung **/
        function close_action()
        {
            if (!Authentification::hasDozentRecht())
                $this->flash["message"] = Tools::createMessage( "error", _("Sie haben nicht die erforderlichen Rechte um die Daten zu l�schen") );

            elseif (Request::int("dialogyes"))
            {
                try {
                    $this->flash["veranstaltung"]->close();
                    $this->flash["message"] = Tools::createMessage( "success", _("Veranstaltung geschlossen") );
                } catch (Exception $e) {
                    $this->flash["message"] = Tools::createMessage( "error", $e->getMessage() );
                }
            }
            elseif (Request::int("dialogno")) { }
            else
                $this->flash["message"] = Tools::createMessage( "question", _("Sollen die Veranstaltung geschlossen werden, danach sind keine �nderungen mehr m�glich?"), array(), $this->url_for("admin/close") );

            $this->redirect("admin");
        }


        /** l�scht alle Daten zu der Veranstaltung **/
        function delete_action()
        {
            if (!Authentification::hasDozentRecht())
                $this->flash["message"] = Tools::createMessage( "error", _("Sie haben nicht die erforderlichen Rechte um die Daten zu l�schen") );
            elseif (Request::int("dialogyes"))
            {
                try {
                    Veranstaltung::delete( $this->flash["veranstaltung"] );
                    $this->flash["message"] = Tools::createMessage( "success", _("Daten gel�scht") );
                } catch (Exception $e) {
                    $this->flash["message"] = Tools::createMessage( "error", $e->getMessage() );
                }
            }
            elseif (Request::int("dialogno")) { }
            else
                $this->flash["message"] = Tools::createMessage( "question", _("Sollen alle �bungen inkl aller Punkte gel�scht werden?"), array(), $this->url_for("admin/delete") );

            $this->redirect("admin");
        }

        
        /** Funktion, um die Teilnehmer zu verwalten **/
        function teilnehmer_action()
        {
            Tools::addHTMLHeaderElements( $this->plugin );
            
            $this->ignorelistaction   = $this->url_for( "admin/jsonlistignore");
            $this->ignoreremoveaction = $this->url_for( "admin/jsonignoreremove");
            $this->ignoreupdateaction = $this->url_for( "admin/jsonignoreupdate");
        }

        
        /** updatet die Teilnehmerliste in allen �bungen **/
        function updateteilnehmer_action()
        {
            if (!Authentification::hasDozentRecht())
                $this->flash["message"] = Tools::createMessage( "error", _("Sie haben nicht die erforderlichen Rechte um die Teilnehmer zu aktualisieren") );

            else
            {
                try {
                    $this->flash["veranstaltung"]->updateTeilnehmer();
                    $this->flash["message"] = Tools::createMessage( "success", _("Teilnehmer in den �bungen aktualisiert") );
                } catch (Exception $e) {
                    $this->flash["message"] = Tools::createMessage( "error", $e->getMessage() );
                }
            }

            $this->redirect("admin/teilnehmer");
        }
        
        
        /** liefert die Liste der ignorierten Teilnehmer **/
        function jsonlistignore_action()
        {
            // Daten f�r das Json Objekt holen und ein Default Objekt setzen
            $laResult = array( "Result"  => "ERROR", "Records" => array() );
            
    
            try {
                
                // hole die �bung und pr�fe die Berechtigung (in Abh�ngigkeit des gesetzen Parameter die �bung initialisieren)
                if (!Authentification::hasDozentRecht( $this->flash["veranstaltung"] ))
                    throw new Exception(_("Sie haben nicht die notwendige Berechtigung"));
                
                $la = array();
                foreach( $this->flash["veranstaltung"]->getIgnore() as $lcKey => $lcBemerkung)
                {
                    $lo = new BaseUser( $lcKey );
                
                    // manuelles lesen der Matrikelnummer, da nicht sicher ist, ob eine Nummer existiert
                    $lnMatrikelnummer = null;
                    $laNum = MatrikelNummerFactory::get()->get( $lo->id() );
                    if (is_array($laNum))
                        $lnMatrikelnummer = $laNum["num"];
                
                    array_push( $la, array("Auth" => studip_utf8encode($lo->id()), "Name" => studip_utf8encode($lo->name()), "EMailAdresse" => studip_utf8encode($lo->email()), "Matrikelnummer" => $lnMatrikelnummer, "Bemerkung" => studip_utf8encode($lcBemerkung)) );
                }
                    
                // alles fehlerfrei durchlaufen, setze Result
                $laResult["TotalRecordCount"] = count($la);
                $laResult["Records"]          = $la;
                $laResult["Result"]           = "OK";
                
            // fange Exception und liefer Exceptiontext passend codiert in das Json-Result
            } catch (Exception $e) { $laResult["Message"] = studip_utf8encode( $e->getMessage() ); }
            
            Tools::sendJson( $this, $laResult );
        }
        
        
        /** liefert die Liste, wenn ein Datensatz der Ignoreliste entfernt wurde **/
        function jsonignoreremove_action()
        {
            // Daten f�r das Json Objekt holen und ein Default Objekt setzen
            $laResult = array( "Result"  => "ERROR", "Records" => array() );
            
            
            try {
                
                // hole die �bung und pr�fe die Berechtigung (in Abh�ngigkeit des gesetzen Parameter die �bung initialisieren)
                if (!Authentification::hasDozentRecht( $this->flash["veranstaltung"] ))
                    throw new Exception(_("Sie haben nicht die notwendige Berechtigung"));
                
                $this->flash["veranstaltung"]->removeIgnore( Request::quoted("Auth") );
                
                // alles fehlerfrei durchlaufen, setze Result
                $laResult["Result"] = "OK";
                
            // fange Exception und liefer Exceptiontext passend codiert in das Json-Result
            } catch (Exception $e) { $laResult["Message"] = studip_utf8encode( $e->getMessage() ); }
            
            Tools::sendJson( $this, $laResult );
        }
        
        
        /** liefert die Liste, wenn ein Eintrag der Ignoreliste ge�ndert wurde **/
        function jsonignoreupdate_action()
        {
            // Daten f�r das Json Objekt holen und ein Default Objekt setzen
            $laResult = array( "Result"  => "ERROR", "Records" => array() );
            
            
            try {
                
                // hole die �bung und pr�fe die Berechtigung (in Abh�ngigkeit des gesetzen Parameter die �bung initialisieren)
                if (!Authentification::hasDozentRecht( $this->flash["veranstaltung"] ))
                    throw new Exception(_("Sie haben nicht die notwendige Berechtigung"));
                
            // fange Exception und liefer Exceptiontext passend codiert in das Json-Result
            } catch (Exception $e) { $laResult["Message"] = studip_utf8encode( $e->getMessage() ); }
            
            Tools::sendJson( $this, $laResult );
        }

        
        /** Funktion, um neue �bungen zu erzeugen **/
        function createuebung_action()
        {
        
        }

        
        /** Aufruf um eine neue �bung zu erzeugen **/
        function adduebung_action()
        {
            if (!Authentification::hasDozentRecht())
                $this->flash["message"] = Tools::createMessage( "error", _("Sie haben nicht die erforderlichen Rechte um eine �bung anzulegen") );

            elseif (Request::submitted("submitted"))
            {
                try {
                    Uebung::create( $this->flash["veranstaltung"], Request::quoted("uebungname") );
                    $this->flash["message"] = Tools::createMessage( "success", _("neue �bung erstellt") );
                } catch (Exception $e) {
                    $this->flash["message"] = Tools::createMessage( "error", $e->getMessage() );
                    $this->redirect("admin/createuebung");
                    return;
                }
            }
            
            $this->redirect("uebung");
        }

        
        /** �ffnet die Veranstaltung, wenn sie geschlossen wurde **/
        function reopen_action()
        {
            if (!Authentification::hasAdminRecht())
                $this->flash["message"] = Tools::createMessage( "error", _("Sie haben nicht die erforderlichen Rechte um die Veranstaltung wieder zu �ffnen") );
            else
            {
                try {
                    $this->flash["veranstaltung"]->reopen();
                    $this->flash["message"] = Tools::createMessage( "success", _("Veranstaltung erfolgreich ge�ffnet") );
                } catch (Exception $e) {
                    $this->flash["message"] = Tools::createMessage( "error", $e->getMessage() );
                }
            }

            $this->redirect("admin");
        }
        
        
        /** f�gt einen User auf die Ignorelist ein **/
        function addignore_action()
        {
            if (!Authentification::hasDozentRecht())
                $this->flash["message"] = Tools::createMessage( "error", _("Sie haben nicht die erforderlichen Rechte um eine �bung anzulegen") );
            
            try {
                
                $this->flash["veranstaltung"]->setIgnore( Request::quoted("Auth") );
                
            } catch (Exception $e) {
                $this->flash["message"] = Tools::createMessage( "error", $e->getMessage() );
            }
            
            $this->redirect("admin/teilnehmer");
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

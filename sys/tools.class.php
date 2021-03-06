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



    /** Klasse f�r zentrale Funktionen **/
    class Tools
    {
        
        /** global static Array f�r zentrale Speicherung von Daten **/
        private static $maGlobalStorage = array();
        
        
        
        /** pr�ft, ob in dem globalen Storage ein Eintrag existiert
         * @param $pcName Name des Eintrages
         * @return Bool f�r die Existenz
         **/
        static function existStorage( $pcName )
        {
            if (!is_string($pcName))
                throw new Exception( _("F�r das Storage muss ein String �bergeben werden") );
            
            return array_key_exists( $pcName, self::$maGlobalStorage );
        }
        
        
        /** setzt einen Wert in das Storage
         * @param $pcName Name f�r den Eintrag
         * @param $pxValue Wert
         **/
        static function setStorage( $pcName, $pxValue = null )
        {
            if (!is_string($pcName))
                throw new Exception( _("F�r das Storage muss ein String �bergeben werden") );
            
            self::$maGlobalStorage[$pcName] = $pxValue;
        }
        
        
        /** liefert ein Element aus dem Storage
         * @param $pcName Name des Element
         * @return Daten
         **/
        static function getStorage( $pcName )
        {
            if (!self::existStorage($pcName))
                throw new Exception( _("Element im Storage nicht gefunden") );
            
            return self::$maGlobalStorage[$pcName];
        }
        
        
        /** entfernt ein Element aus dem Storage
         * @param $pcName Name des Elements
         **/
        static function deleteStorage( $pcName )
        {
            if (!self::existStorage($pcName))
                throw new Exception( _("Element im Storage nicht gefunden") );
            
            unset(self::$maGlobalStorage[$pcName]);
        }
        
        
        /** erzeugt eine URL f�r das Plugin - analog in den Controllern
         * @note das url_for in den Controllern darf nicht entfernt werden, da
         * dies von der Klassenstruktur her vorhanden sein muss
         * @param $to Ziel-URL
         * @return volle URL
         **/
        static function url_for($to)
        {
            $args = func_get_args();
            
            # find params
            $params = array();
            if (is_array(end($args)))
                $params = array_pop($args);
            
            # urlencode all but the first argument
            $args    = array_map("urlencode", $args);
            $args[0] = $to;
            
            return PluginEngine::getURL(self::getStorage("plugin"), $params, join("/", $args));
        }
        

        /** Methode, die eine Messagebox generiert, sofern Daten vorhanden sind
         * @see http://docs.studip.de/develop/Entwickler/ModalerDialog
         * @see http://docs.studip.de/develop/Entwickler/Messagebox
         * @param $paMessage Message-Array
         * @return Booleanwert, ob die Nachricht eine Information / Success war
         **/
        static function showMessage( $paMessage, $pcURL = "?" )
        {
            if ( (empty($paMessage)) || (!is_array($paMessage)) || (!isset($paMessage["type"])) || (!isset($paMessage["msg"])) )
                return true;

            $la = array();
            if ( (isset($paMessage["info"])) && (is_array($paMessage["info"])) )
                $la = $paMessage["info"];

            if (($paMessage) && (strcasecmp($paMessage["type"], "error") == 0))
            {
                echo MessageBox::error($paMessage["msg"], $la);
                return false;
            } elseif ( ($paMessage) && (strcasecmp($paMessage["type"], "success") == 0))
                echo MessageBox::success($paMessage["msg"], $la);
            elseif ( ($paMessage) && (strcasecmp($paMessage["type"], "info") == 0))
                echo MessageBox::info($paMessage["msg"], $la);
            elseif ( ($paMessage) && (strcasecmp($paMessage["type"], "question") == 0) )
                echo createQuestion($paMessage["msg"], array("dialogyes" => true), array("dialogno" => true), $paMessage["url"] );

            return true;
        }


        /** Methode um einen Messagetext zu generieren
         * @param $pcTyp ist der Messagetyp, Werte sind: error, success, info, question
         * @param $pcText Text der Nachricht
         * @param $paInfo weitere Texte oder f�r den Question-Dialog das return Array
         * @param $pcURL URL auf die geleitet werden soll
         * @return Array mit Messagedaten
         **/

        static function createMessage( $pcType, $pcText, $paInfo = array(), $pcURL = "?" )
        {
            return array("type" => $pcType, "msg" => $pcText, "info" => $paInfo, "url" => $pcURL );
        }
    
    
        /** setzt alle notwendigen Elemente in den HTML Header
         * @warning nach HTML5 ist das Attribut "charset" veraltet
         * @see http://docs.studip.de/develop/Entwickler/PageLayout
         * @param $poPlugin PluginObjekt
         **/
        static function addHTMLHeaderElements( $poPlugin )
        {
            // da StudIP < 3 keine Charset-Option bei addScript bzw addStylesheet erlaubt
            // wird �ber addHeaderElement der Eintrag manuell gesetzt und UTF-8 als
            // Encoding verwendet, da StudIP Windows-1252 als Encoding ist (was einfach
            // absolut veraltet ist und dadurch massiv zu Encoding-Problemen f�hrt)
            //
            // der 3. Parameter muss auf "" gesetzt werden, da sonst das Tag nicht geschlossen wird
        
            PageLayout::addHeadElement( "link", array( "charset" => "UTF-8", "rel" => "stylesheet", "href" => $poPlugin->getPluginUrl() . "/assets/style.css"), "" );
            PageLayout::addHeadElement( "link", array( "charset" => "UTF-8", "rel" => "stylesheet", "href" => $poPlugin->getPluginUrl() . "/sys/extensions/jtable/themes/lightcolor/blue/jtable.min.css"), "" );
        
        
            PageLayout::addHeadElement( "script", array( "charset" => "UTF-8", "src" => $poPlugin->getPluginUrl() . "/assets/application.js"), "" );
        
            PageLayout::addHeadElement( "script", array( "charset" => "UTF-8", "src" => $poPlugin->getPluginUrl() . "/sys/extensions/jtable/jquery.jtable.min.js"), "" );
            PageLayout::addHeadElement( "script", array( "charset" => "UTF-8", "src" => $poPlugin->getPluginUrl() . "/sys/extensions/jtable/jquery.jtable.min.js"), "" );
            PageLayout::addHeadElement( "script", array( "charset" => "UTF-8", "src" => $poPlugin->getPluginUrl() . "/sys/extensions/jtable/localization/jquery.jtable.de.js"), "" );
        
            PageLayout::addHeadElement( "script", array( "charset" => "UTF-8", "src" => $poPlugin->getPluginUrl() . "/sys/extensions/d3.v3/d3.v3.min.js"), "" );
            PageLayout::addHeadElement( "script", array( "charset" => "UTF-8", "src" => $poPlugin->getPluginUrl() . "/sys/extensions/d3.v3/box.js"), "" );
            
            
            PageLayout::addHeadElement( "script", array( "charset" => "UTF-8", "src" => $poPlugin->getPluginUrl() . "/sys/extensions/raty/jquery.raty.js"), "" );
        }
    
    
        /** sendet das Json Dokument mit passendem Header
         * @param $poController Controller Objekt
         * @param $pxJson Datenstruktur f�r Json
         **/
        static function sendJson( $poController, $pxJson )
        {
            // wenn der Header direkt gesendet wird, das Rendering des Views deaktivieren
            $poController->set_layout(null);
            $poController->render_nothing();
        
            header("Content-Type: application/json");
            echo json_encode( $pxJson );
        }
        
        
        /** �berpr�ft, ob in einem String ein Substring oder ein Element eines Array
         * von Substrings enthalten ist
         * @param $pcString String, in dem gesucht wird
         * @param $pxSearch String oder Array von Strings, die gesucht werden sollen
         * @return true beim einem Fund
         **/
        static function foundCISubStr( $pcString, $pxSearch )
        {
            if (is_string($pxSearch))
                return stripos($pcString, $pxSearch) !== false;
            elseif (is_array($pxSearch))
                foreach( $pxSearch as $lc )
                    if (self::foundCISubStr($pcString, $lc))
                        return true;
            
            return false;
        }
    
    }
    
    ?>

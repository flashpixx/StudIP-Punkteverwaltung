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



    require_once(dirname(__DIR__) . "/sys/authentification.class.php");


    /** Controller f�r die Root-Einstellungen **/
    class RootController extends StudipController
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
            // Menupunkt aktivieren
            Navigation::activateItem("/admin/punkteverwaltung");
        
            PageLayout::setTitle( _("Punkteverwaltung - Root Administration") );
            $this->set_layout($GLOBALS["template_factory"]->open("layouts/base_without_infobox"));

            // Initialisierung der Session
            $this->flash                  = Trails_Flash::instance();
        }


        /** Default Action **/
        function index_action()
        {
            Tools::addHTMLHeaderElements( $this->plugin );
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

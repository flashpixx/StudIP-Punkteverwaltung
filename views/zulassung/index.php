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



    require_once(dirname(dirname(__DIR__)) . "/sys/tools.class.php");
    require_once(dirname(dirname(__DIR__)) . "/sys/permission.class.php");
    require_once(dirname(dirname(__DIR__)) . "/sys/veranstaltung/veranstaltung.class.php");


    Tools::showMessage($flash["message"]);

    try {

        $loVeranstaltung = isset($flash["veranstaltung"]) ? $flash["veranstaltung"] : null;
        if (!Permission::hasDozentRecht($loVeranstaltung))
            throw new Exception(_("Sie haben nicht die erforderlichen Rechte"));


        // jTable f�r die Studenten / Bemerkungen erzeugen
        echo "<script type=\"text/javascript\">";
        echo "jQuery(document).ready(function() {";
        echo "jQuery(\"#zulassungstabelle\").jtable({";

        echo "title          : \"manuelle Zulassung\",";
        echo "paging         : true,";
        echo "pageSize       : 500,";
        echo "sorting        : true,";
        echo "defaultSorting : \"Matrikelnummer ASC\",";
        echo "actions: {";
        echo "listAction   : \"".$listaction."\",";
        if (!$flash["veranstaltung"]->isClosed())
            echo "updateAction : \"".$updateaction."\",";
        echo "},";

        echo "fields: {";

        echo "Auth : { key : true, create : false, edit : false, list : false },";
        echo "Name : { edit : false, title : \"Name\", width : \"10%\" },";
        echo "EmailAdresse : { edit : false, title : \"EMail Adresse\", width : \"10%\" },";
        echo "Matrikelnummer : { edit : false, title : \"Matrikelnummer\", width : \"5%\" },";
        echo "Bemerkung : { title : \"Bemerkung\", type  : \"textarea\", width : \"75%\" }";
        
        echo "}";
        echo "});";
        
        echo "jQuery(\"#zulassungstabelle\").jtable(\"load\");";
        echo "});";
        echo "</script>";


        echo "<p>"._("�ber die nachfolgende Tabelle kann f�r einzelne Studenten manuell die Zulassung (bestanden Kriterium) f�r die Veranstaltung eingetragen werden. Um einen Studenten manuell zuzulassen, muss eine Bemerkung hinterlegt werden, beim der Entfernung der Bemerkung wird die manuelle Zulassung wieder entfernt und die aufgrund der hinterlegten Punkten erzeugte Zulassung aktiviert. Diese Funktion ist gedacht, um Studenten, die z.B. wegen Krankheit / sozialer H�rte die Zulassung nicht erreicht haben, dennoch zuzulassen.")."</p>";
        echo "<div id=\"zulassungstabelle\"></div>";

        
    } catch (Exception $e) {
        Tools::showMessage( Tools::createMessage("error", $e->getMessage()) );
    }
    
?>

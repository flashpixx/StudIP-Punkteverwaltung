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
    
    
    
    Tools::showMessage($flash["message"]);
    
    try {
        
        $loVeranstaltung = isset($flash["veranstaltung"]) ? $flash["veranstaltung"] : null;
        if (!$loVeranstaltung)
            throw new Exception(_("keine Veranstaltung gefunden"));
        
        $laStudiengaenge = isset($flash["studiengang"]) ? $flash["studiengang"] : null;
        if (empty($laStudiengaenge))
            throw new Exception(_("keine Studieng�nge f�r den User eingetragen"));
        
        $laCurrentStudiengang = empty($flash["veranstaltungstudiengang"]) ? $flash["veranstaltungstudiengang"] : array_shift($flash["veranstaltungstudiengang"]);
        
        
        
        
        if ($loVeranstaltung->isClosed())
            printf(_("Diese Veranstaltung wurde f�r den Studiengang %s anerkannt."), "<strong>" . $laCurrentStudiengang["abschluss"] ." ". $laCurrentStudiengang["fach"] . "</strong>");
        elseif (count($laStudiengaenge) == 1)
            printf(_("Diese Veranstaltung wird f�r den Studiengang %s anerkannt."), "<strong>" . $laStudiengaenge[0]["abschluss"] ." ". $laStudiengaenge[0]["fach"] . "</strong>");
        elseif (count($laStudiengaenge) > 1)
        {
            
            echo "<form method=\"post\" action=\"".$controller->url_for("show/studiengangset")."\">\n";
            CSRFProtection::tokenTag();
         
            echo "<label for=\"studiengang\">"._("Studiengang ausw�hlen, f�r den die Veranstaltung anerkannt werden soll").":</label> ";
            echo "<select id=\"studiengang\" name=\"studiengang\" size=\"1\">";
            foreach ($laStudiengaenge as $item)
            {
                $lcSelect = (!empty($laCurrentStudiengang)) && ($item["abschluss_id"] == $laCurrentStudiengang["abschluss_id"]) && ($item["fach_id"] == $laCurrentStudiengang["fach_id"]) ? "selected=\"selected\"" : null;
                printf("<option value=\"%s\" %s >%s</option>", $item["abschluss_id"]."#".$item["fach_id"], $lcSelect, trim($item["abschluss"]." ".$item["fach"]));
            }
            echo "</select> ";
         
            echo "<input type=\"submit\" name=\"submitted\" value=\""._("�bernehmen")."\"/>";
            echo "</form>";
         }
        else
            throw new Exception(_("Studieng�nge k�nnen nicht ermittelt werden"));
        
        
    } catch (Exception $e) {
        Tools::showMessage( Tools::createMessage("error", $e->getMessage()) );
    }
    
?>

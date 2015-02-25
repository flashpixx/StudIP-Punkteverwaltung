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
    require_once(dirname(dirname(__DIR__)) . "/sys/authentification.class.php");
    require_once(dirname(dirname(__DIR__)) . "/sys/veranstaltung/veranstaltung.class.php");


    Tools::showMessage($flash["message"]);

    try {

        $loVeranstaltung = isset($flash["veranstaltung"]) ? $flash["veranstaltung"] : null;

        if (!$loVeranstaltung)
            echo "<p><a href=\"".$controller->url_for("admin/create")."\">"._("F�r diese Veranstaltung die Punkteverwaltung aktivieren")."</a></p>";
        else {
            
            echo "<form method=\"post\" action=\"".$controller->url_for("admin/updatesettings")."\">\n";
            CSRFProtection::tokenTag();

            echo "<div class=\"steel1\">\n";
            
            echo "<p>"._("Mit diesen Einstellungen k�nnen globale Optionen gesetzt werden, um den �bungsbetrieb zu verwalten. Hierzu z�hlt einmal die Angabe in Prozent (�ber die Summe aller Punkte aller �bungen), die f�r das Bestehen der Veranstaltung notwendig sind. Mit der zweiten Option kann eine Anzahl an �bungen festgelegt werden, die unterhalb der individuell f�r jede �bung definierten Bestehensgrenze liegen d�rfen, um noch die Veranstaltung bestehen zu k�nnen (z.B. wenn eine �bung ausgelassen / nicht abgegeben werden darf, w�re der Wert 1 einzutragen). Die Bemerkung ist optional und kann nur von Benutzern mit Dozenten oder Tutorrechten gesehen werden.")."</p><p>"._("Die Veranstaltung muss abgeschlossen werden, um vollst�ndige Listen generieren zu k�nnen. Durch das Abschlie�en werden die, von den Studenten ausgew�hlten Studieng�nge, �bernommen, damit erscheint dieser Studiengang in der Auswertung f�r diese Veranstaltung. Sollte kein expliziter Studiengang ausgew�hlt worden sein, wird der ein verf�gbarer Studiengang verwendet.")."</p><hr width=\"100%\"/>";

            echo "<p><table width=\"100%\">\n";

            echo "<tr><td width=\"50%\"><label for=\"bestandenprozent\">"._("Prozentzahl �ber die Summe aller Punkte, damit die Veranstaltung als bestanden gilt")."</label></td>";
            echo "<td><input type=\"text\" id=\"bestandenprozent\" name=\"bestandenprozent\" value=\"".$loVeranstaltung->bestandenProzent()."\" size=\"35\"/></td></tr>\n";

            echo "<tr><td><label for=\"allow_nichtbestanden\">"._("Anzahl an nicht bestandenen �bungen, um die Veranstaltung trotzdem bei erreichen der Punkte als bestanden zu werten")."</label></td>";
            echo "<td><input type=\"text\" id=\"allow_nichtbestanden\" name=\"allow_nichtbestanden\" value=\"".$loVeranstaltung->allowNichtBestanden()."\" size=\"35\"/></td></tr>\n";

            echo "<tr><td><label for=\"bemerkung\">"._("Bemerkung")."</label></td>";
            echo "<td><textarea id=\"bemerkung\" name=\"bemerkung\" cols=\"37\" rows=\"5\">".$loVeranstaltung->bemerkung()."</textarea></td></tr>\n";

            if (!$loVeranstaltung->isClosed())
            {
                echo "<tr><td colspan=\"2\">&nbsp;</td></tr>\n";
                echo "<tr><td colspan=\"2\"><a href=\"".$controller->url_for("admin/close")."\">Veranstaltung schliessen</a></td></tr>\n";

                echo "<tr><td colspan=\"2\">&nbsp;</td></tr>\n";
                echo "<tr><td colspan=\"2\"><a href=\"".$controller->url_for("admin/delete")."\">alle Einstellungen und Daten zur Punkteverwaltung dieser Veranstaltung entfernen</a></td></tr>\n";
            }


            echo "</table>\n";
            echo "</div>\n";
            if ($loVeranstaltung->isClosed())
            {
                echo "<p><strong>" . sprintf(_("Die Veranstaltung wurde am %s geschlossen"), $loVeranstaltung->closedDateTime()) . "</strong>";
                if (Authentification::hasAdminRecht())
                    echo " (<a href=\"".$controller->url_for("admin/reopen")."\">Veranstaltung wieder �ffnen</a>)";
                echo "</p>";
            } else
                echo "<p><input type=\"submit\" name=\"submitted\" value=\""._("Angaben �bernehmen")."\"/></p>";

            echo "</form></p><br/><br/>";

            echo "<p class=\"ppv ci\"><a target=\"_blank\" href=\"https://github.com/flashpixx/StudIP-Punkteverwaltung\"><img src=\"".$plugin->getPluginURL()."/assets/img/github.png"."\" /> https://github.com/flashpixx/StudIP-Punkteverwaltung</a></p>";
        }
        
    } catch (Exception $e) {
        Tools::showMessage( Tools::createMessage("error", $e->getMessage()) );
    }

?>

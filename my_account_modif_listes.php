<?php
/**
 * my_account_modif_listes.php
 * Page "Ajax" utilis�e pour g�n�rer les listes de domaines et de ressources, en liaison avec my_account.php
 * Derni�re modification : $Date: 2009-04-14 12:59:17 $
 * @author    Laurent Delineau <laurent.delineau@ac-poitiers.fr>
 * @copyright Copyright 2003-2008 Laurent Delineau
 * @link      http://www.gnu.org/licenses/licenses.html
 * @package   root
 * @version   $Id: my_account_modif_listes.php,v 1.4 2009-04-14 12:59:17 grr Exp $
 * @filesource
 *
 * This file is part of GRR.
 *
 * GRR is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * GRR is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with GRR; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */
/**
 * $Log: my_account_modif_listes.php,v $
 * Revision 1.4  2009-04-14 12:59:17  grr
 * *** empty log message ***
 *
 * Revision 1.3  2008-11-16 22:00:59  grr
 * *** empty log message ***
 *
 * Revision 1.2  2008-11-11 22:01:14  grr
 * *** empty log message ***
 *
 * Revision 1.1  2008-11-07 21:39:41  grr
 * *** empty log message ***
 *
 *
 */

/* Arguments pass�s par la m�thode GET :
$use_site : 'y' (fonctionnalit� multisite activ�e) ou 'n' (fonctionnalit� multisite d�sactiv�e)
$id_site : l'identifiant du site
$default_area : domaine par d�faut
$default_room : ressource par d�faut
$session_login : identifiant
$type : 'ressource'-> on actualise la liste des ressources
        'domaine'-> on actualise la liste des domaines
$action : 1-> on actualise la liste des ressources
          2-> on vide la liste des ressouces
*/

include "include/admin.inc.php";

if ((authGetUserLevel(getUserName(),-1) < 1))
{
    showAccessDenied("","","","","");
    exit();
}
/*
 * Actualiser la liste des domaines
 */

if ($_GET['type']=="domaine") {
 // Initialisation
 if (isset($_GET["id_site"])) {
  $id_site = $_GET["id_site"];
  settype($id_site,"integer");
 } else die();
 if (isset($_GET["default_area"])) {
  $default_area = $_GET["default_area"];
  settype($default_area,"integer");
 } else die();
 if (isset($_GET["session_login"])) {
  $session_login = $_GET["session_login"];
 } else die();
 if (isset($_GET["use_site"])) {
  $use_site = $_GET["use_site"];
 } else die();

 if ($use_site=='y') { // on a activ� les sites
   if ($id_site!=-1)
     $sql = "SELECT a.id, a.area_name,a.access
           FROM ".TABLE_PREFIX."_area a, ".TABLE_PREFIX."_j_site_area j
           WHERE a.id=j.id_area and j.id_site=$id_site
           ORDER BY a.order_display, a.area_name";
   else
     $sql = "";
 } else {
     $sql = "SELECT id, area_name,access
           FROM ".TABLE_PREFIX."_area
           ORDER BY order_display, area_name";
 }
 if (($id_site!=-1) or ($use_site=='n'))
    $resultat = grr_sql_query($sql);
 $display_liste = '
        <table border="0"><tr>
          <td>'.get_vocab('default_area').'</td>
          <td>
            <select id="id_area" name="id_area"  onchange="modifier_liste_ressources(1)">
              <option value="-1">'.get_vocab('choose_an_area').'</option>'."\n";

  if (($id_site!=-1) or ($use_site=='n')) {
 for ($enr = 0; ($row = grr_sql_row($resultat, $enr)); $enr++)
 {
  if (authUserAccesArea($session_login, $row[0])!=0)
  {
    $display_liste .=  '              <option value="'.$row[0].'"';
    if ($default_area == $row[0])
      $display_liste .= ' selected="selected" ';
    $display_liste .= '>'.my_htmlspecialcharacters($row[1]);
    if ($row[2]=='r')
      $display_liste .= ' ('.get_vocab('restricted').')';
    $display_liste .= '</option>'."\n";
  }
 }
 }
 $display_liste .= '            </select>';
 $id_area=5;
 $display_liste .=  '</td>
        </tr></table>'."\n";
}

/*
 * Actualiser la liste des ressources
 */

if ($_GET['type']=="ressource") {
  if (isset($_GET["default_room"])) {
    $default_room = $_GET["default_room"];
    settype($default_room,"integer");
  } else die();


  if ($_GET['action']==2) { //on vide la liste des ressources
    $display_liste = '
        <table border="0"><tr>
          <td>'.get_vocab('default_room').'</td>
          <td>
            <select name="id_room">
              <option value="-1">'.get_vocab('default_room_all').'</option>
            </select>
          </td>
        </tr></table>'."\n";
  } else {
    if (isset($_GET["id_area"])) {
      $id_area = $_GET["id_area"];
      settype($id_area,"integer");
    } else die();

    $sql = "SELECT id, room_name
           FROM ".TABLE_PREFIX."_room
           WHERE area_id='".$id_area."'";
    // on ne cherche pas parmi les ressources invisibles pour l'utilisateur
    $tab_rooms_noaccess = verif_acces_ressource(getUserName(), 'all');
    foreach($tab_rooms_noaccess as $key){
      $sql .= " and id != $key ";
    }
    $sql .= " ORDER BY order_display,room_name";
    $resultat = grr_sql_query($sql);
    $display_liste = '
        <table border="0"><tr>
          <td>'.get_vocab('default_room').'</td>
          <td>
            <select name="id_room">
              <option value="-1"';
     if ($default_room == -1)
         $display_liste .= ' selected="selected" ';
         $display_liste .= ' >'.get_vocab('default_room_all').'</option>'."\n".
              '<option value="-2"';
     if ($default_room == -2)
         $display_liste .= ' selected="selected" ';
         $display_liste .= ' >'.get_vocab('default_room_week_all').'</option>'."\n".
              '<option value="-3"';
     if ($default_room == -3)
         $display_liste .= ' selected="selected" ';
         $display_liste .= ' >'.get_vocab('default_room_month_all').'</option>'."\n".
              '<option value="-4"';
     if ($default_room == -4)
         $display_liste .= ' selected="selected" ';
         $display_liste .= ' >'.get_vocab('default_room_month_all_bis').'</option>'."\n";


    for ($enr = 0; ($row = grr_sql_row($resultat, $enr)); $enr++)
    {
       $display_liste .=  '              <option value="'.$row[0].'"';
       if ($default_room == $row[0])
         $display_liste .= ' selected="selected" ';
       $display_liste .= '>'.my_htmlspecialcharacters($row[1]).' '.get_vocab('display_week');
       $display_liste .= '</option>'."\n";
    }

    $display_liste .= '            </select>
          </td>
        </tr></table>'."\n";
  }
}

if ($unicode_encoding)
 header("Content-Type: text/html;charset=utf-8");
else
 header("Content-Type: text/html;charset=".$charset_html);

header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');

echo $display_liste;
?>
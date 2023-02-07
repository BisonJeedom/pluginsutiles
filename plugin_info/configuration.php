<?php
/* This file is part of Jeedom.
*
* Jeedom is free software: you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation, either version 3 of the License, or
* (at your option) any later version.
*
* Jeedom is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
* GNU General Public License for more details.
*
* You should have received a copy of the GNU General Public License
* along with Jeedom. If not, see <http://www.gnu.org/licenses/>.
*/

require_once dirname(__FILE__) . '/../../../core/php/core.inc.php';
include_file('core', 'authentification', 'php');
if (!isConnect()) {
	include_file('desktop', '404', 'php');
	die();
}
?>
<form class="form-horizontal">
	<fieldset>

		<div class="form-group">
			<label class="col-sm-3 control-label">{{Mots-clef}}
				<sup><i class="fas fa-question-circle tooltips" title="{{Séparer les mots-clefs avec des points-virgules. Exemple : photovoltaïque;energie;soleil}}"></i></sup>
			</label>
			<div class="col-sm-5">
				<textarea class="configKey form-control" data-l1key="cfg_keywords"></textarea>
			</div>
		</div>

		<div class="form-group">
			<label class="col-sm-3 control-label">{{Alertes dans le centre de message}}</label>
			<div class="col-sm-2">
				<input type="checkbox" class="configKey eqLogicAttr" data-l1key="cfg_messagecenter">
			</div>
		</div>

		<!--
    <div class="col-lg-8">
    	<legend><i class="fas fa-sign-in-alt"></i> {{Action(s) sur notification}}</legend>
  		<label>
  			<a class="btn btn-success btn-sm addAction" data-type="action_notif" style="margin:5px;"><i class="fas fa-plus-circle"></i> {{Ajouter une action}}</a>
  		</label>
    	<div id="div_action_notif"></div>
    </div>
    -->

	</fieldset>

	<br>
	<div class="alert alert-success" style="text-align:center;">
		{{Historique des 50 derniers évènements}}
	</div>
	<div class="form-group">
		<div class="col-lg-5">
			<?php
			$array_historique = array_reverse(config::byKey('array_historique', 'pluginsutiles'));
			foreach ($array_historique as $historique) { // [0] date / [1] id / [2] nom plugin / [3] auteur
				if ($nb == 50) {
					exit;
				}
				$nb++;
				echo '<div class="col-sm-12">';
				if ($historique[3] == '') {
					echo $historique[0] . ' : ' . $historique[2];
				} else {
					echo '<div class="market cursor install" data-market_id="' . $historique[1] . '" data-market_type="plugin">' . $historique[0] . ' : ' . $historique[2] . ' par ' . $historique[3] . '</div>';
				}
				echo '</div>';
				echo '<br>';
			}
			?>
		</div>
	</div>

</form>

<?php include_file('desktop', 'pluginsutiles', 'js', 'pluginsutiles'); ?>
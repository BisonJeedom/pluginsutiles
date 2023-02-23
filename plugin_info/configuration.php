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
</form>

<?php include_file('desktop', 'pluginsutiles', 'js', 'pluginsutiles'); ?>
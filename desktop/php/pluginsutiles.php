<?php
if (!isConnect('admin')) {
	throw new Exception('{{401 - Accès non autorisé}}');
}
// Déclaration des variables obligatoires
$plugin = plugin::byId('pluginsutiles');
sendVarToJS('eqType', $plugin->getId());
$eqLogics = eqLogic::byType($plugin->getId());
?>

<div class="row row-overflow pluginsutiles">
	<!-- Page d'accueil du plugin -->
	<div class="col-xs-12 eqLogicThumbnailDisplay">
		<legend><i class="fas fa-cog"></i> {{Gestion}}</legend>
		<!-- Boutons de gestion du plugin -->
		<div class="eqLogicThumbnailContainer">
			<div class="cursor eqLogicAction logoPrimary" data-action="add">
				<i class="fas fa-plus-circle"></i>
				<br>
				<span>{{Ajouter}}</span>
			</div>
			<div class="cursor eqLogicAction logoSecondary" data-action="gotoPluginConf">
				<i class="fas fa-wrench"></i>
				<br>
				<span>{{Configuration}}</span>
			</div>
		</div>

		<legend><i class="fas fa-table"></i> {{Mes équipements}}</legend>
		<?php
		if (count($eqLogics) == 0) {
			echo '<br><div class="text-center" style="font-size:1.2em;font-weight:bold;">{{Aucun équipement trouvé, cliquer sur "Ajouter" pour commencer}}</div>';
		} else {
			// Champ de recherche
			echo '<div class="input-group" style="margin:5px;">';
			echo '<input class="form-control roundedLeft" placeholder="{{Rechercher}}" id="in_searchEqlogic">';
			echo '<div class="input-group-btn">';
			echo '<a id="bt_resetSearch" class="btn" style="width:30px"><i class="fas fa-times"></i></a>';
			echo '<a class="btn roundedRight hidden" id="bt_pluginDisplayAsTable" data-coreSupport="1" data-state="0"><i class="fas fa-grip-lines"></i></a>';
			echo '</div>';
			echo '</div>';
			// Liste des équipements du plugin
			echo '<div class="eqLogicThumbnailContainer">';
			foreach ($eqLogics as $eqLogic) {
				$opacity = ($eqLogic->getIsEnable()) ? '' : 'disableCard';
				echo '<div class="eqLogicDisplayCard cursor ' . $opacity . '" data-eqLogic_id="' . $eqLogic->getId() . '">';
				echo '<img src="' . $plugin->getPathImgIcon() . '">';
				echo '<br>';
				echo '<span class="name">' . $eqLogic->getHumanName(true, true) . '</span>';
				echo '<span class="hiddenAsCard displayTableRight hidden">';
				echo ($eqLogic->getIsVisible() == 1) ? '<i class="fas fa-eye" title="{{Equipement visible}}"></i>' : '<i class="fas fa-eye-slash" title="{{Equipement non visible}}"></i>';
				echo '</span>';
				echo '</div>';
			}
			echo '</div>';
		}
		?>

	</div> <!-- /.eqLogicThumbnailDisplay -->

	<!-- Page de présentation de l'équipement -->
	<div class="col-xs-12 eqLogic" style="display: none;">
		<!-- barre de gestion de l'équipement -->
		<div class="input-group pull-right" style="display:inline-flex;">
			<span class="input-group-btn">
				<!-- Les balises <a></a> sont volontairement fermées à la ligne suivante pour éviter les espaces entre les boutons. Ne pas modifier -->
				<a class="btn btn-sm btn-default eqLogicAction roundedLeft" data-action="configure"><i class="fas fa-cogs"></i><span class="hidden-xs"> {{Configuration avancée}}</span>
				</a><a class="btn btn-sm btn-default eqLogicAction" data-action="copy"><i class="fas fa-copy"></i><span class="hidden-xs"> {{Dupliquer}}</span>
				</a><a class="btn btn-sm btn-success eqLogicAction" data-action="save"><i class="fas fa-check-circle"></i> {{Sauvegarder}}
				</a><a class="btn btn-sm btn-danger eqLogicAction roundedRight" data-action="remove"><i class="fas fa-minus-circle"></i> {{Supprimer}}
				</a>
			</span>
		</div>
		<!-- Onglets -->
		<ul class="nav nav-tabs" role="tablist">
			<li role="presentation"><a href="#" class="eqLogicAction" aria-controls="home" role="tab" data-toggle="tab" data-action="returnToThumbnailDisplay"><i class="fas fa-arrow-circle-left"></i></a></li>
			<li role="presentation" class="active"><a href="#eqlogictab" aria-controls="home" role="tab" data-toggle="tab"><i class="fas fa-tachometer-alt"></i> {{Equipement}}</a></li>
			<li role="presentation"><a href="#commandtab" aria-controls="home" role="tab" data-toggle="tab"><i class="fas fa-list"></i> {{Commandes}}</a></li>
		</ul>
		<div class="tab-content">
			<!-- Onglet de configuration de l'équipement -->
			<div role="tabpanel" class="tab-pane active" id="eqlogictab">
				<!-- Partie gauche de l'onglet "Equipements" -->
				<!-- Paramètres généraux et spécifiques de l'équipement -->
				<form class="form-horizontal">
					<fieldset>
						<div class="col-lg-5">
							<legend><i class="fas fa-wrench"></i> {{Paramètres généraux}}</legend>
							<div class="form-group">
								<label class="col-sm-4 control-label">{{Nom de l'équipement}}</label>
								<div class="col-sm-6">
									<input type="text" class="eqLogicAttr form-control" data-l1key="id" style="display:none;">
									<input type="text" class="eqLogicAttr form-control" data-l1key="name" placeholder="{{Nom de l'équipement}}">
								</div>
							</div>
							<div class="form-group">
								<label class="col-sm-4 control-label">{{Objet parent}}</label>
								<div class="col-sm-6">
									<select id="sel_object" class="eqLogicAttr form-control" data-l1key="object_id">
										<option value="">{{Aucun}}</option>
										<?php
										$options = '';
										foreach ((jeeObject::buildTree(null, false)) as $object) {
											$options .= '<option value="' . $object->getId() . '">' . str_repeat('&nbsp;&nbsp;', $object->getConfiguration('parentNumber')) . $object->getName() . '</option>';
										}
										echo $options;
										?>
									</select>
								</div>
							</div>
							<div class="form-group">
								<label class="col-sm-4 control-label">{{Catégorie}}</label>
								<div class="col-sm-6">
									<?php
									foreach (jeedom::getConfiguration('eqLogic:category') as $key => $value) {
										echo '<label class="checkbox-inline">';
										echo '<input type="checkbox" class="eqLogicAttr" data-l1key="category" data-l2key="' . $key . '" >' . $value['name'];
										echo '</label>';
									}
									?>
								</div>
							</div>
							<div class="form-group">
								<label class="col-sm-5 control-label">{{Options}}</label>
								<div class="col-sm-6">
									<label class="checkbox-inline"><input type="checkbox" class="eqLogicAttr" data-l1key="isEnable" checked>{{Activer}}</label>
									<label class="checkbox-inline"><input type="checkbox" class="eqLogicAttr" data-l1key="isVisible" checked>{{Visible}}</label>
								</div>
							</div>

							<legend><i class="fas fa-search"></i> {{Paramètres de recherche}}</legend>

							<div class="form-group">
								<label class="col-sm-5 control-label">{{Mots-clef}}
									<sup><i class="fas fa-question-circle tooltips" title="{{Séparer les mots-clefs avec des points-virgules. Exemple : photovoltaïque;energie;soleil}}"></i></sup>
								</label>
								<div class="col-sm-6">
									<textarea class="form-control eqLogicAttr autogrow" data-l1key="configuration" data-l2key="cfg_keywords"></textarea>
								</div>
							</div>

							<div class="form-group">
								<label class="col-sm-5 control-label">{{Nom du plugin}}</label>
								<div class="col-sm-6">
									<input type="checkbox" class="eqLogicAttr" data-l1key="configuration" data-l2key="checkName" checked>
								</div>
							</div>

							<div class="form-group">
								<label class="col-sm-5 control-label">{{Description du plugin}}</label>
								<div class="col-sm-6">
									<input type="checkbox" class="eqLogicAttr" data-l1key="configuration" data-l2key="checkDescription" checked>
								</div>
							</div>
							<div class="form-group">
								<label class="col-sm-5 control-label">{{Utilisation du plugin}}</label>
								<div class="col-sm-6">
									<input type="checkbox" class="eqLogicAttr" data-l1key="configuration" data-l2key="checkUtilisation">
								</div>
							</div>
							<div class="form-group">
								<label class="col-sm-5 control-label">{{Auteur du plugin}}</label>
								<div class="col-sm-6">
									<input type="checkbox" class="eqLogicAttr" data-l1key="configuration" data-l2key="checkAutor">
								</div>
							</div>

							<div class="form-group">
								<label class="col-sm-5 control-label">{{Version stable uniquement}}</label>
								<div class="col-sm-6">
									<input type="checkbox" class="eqLogicAttr" data-l1key="configuration" data-l2key="cfg_checkStableOnly">
								</div>
							</div>

							<div class="form-group">
								<label class="col-sm-5 control-label">{{Version beta uniquement}}</label>
								<div class="col-sm-6">
									<input type="checkbox" class="eqLogicAttr" data-l1key="configuration" data-l2key="cfg_checkBetaOnly">
								</div>
							</div>

							<div class="form-group">
								<label class="col-sm-5 control-label">{{Exclure les plugins privés}}</label>
								<div class="col-sm-6">
									<input type="checkbox" class="eqLogicAttr" data-l1key="configuration" data-l2key="cfg_checkExcludePrivate">
								</div>
							</div>

							<legend><i class="fas fa-dice"></i> {{Autres critères indépendants des mots-clefs}}</legend>

							<div class="form-group">
								<label class="col-sm-5 control-label">{{Afficher les plugins en promo}}</label>
								<div class="col-sm-6">
									<input type="checkbox" class="eqLogicAttr" data-l1key="configuration" data-l2key="checkDiscount">
								</div>
							</div>

							<div class="form-group">
								<label class="col-sm-5 control-label">{{Toutes les versions stable}}</label>
								<div class="col-sm-6">
									<input type="checkbox" class="eqLogicAttr" data-l1key="configuration" data-l2key="cfg_checkAllStable">
								</div>
							</div>

							<div class="form-group">
								<label class="col-sm-5 control-label">{{Toutes les versions beta}}</label>
								<div class="col-sm-6">
									<input type="checkbox" class="eqLogicAttr" data-l1key="configuration" data-l2key="cfg_checkAllBeta">
								</div>
							</div>

							<div class="form-group">
								<label class="col-sm-5 control-label">{{Changements sur version/prix/privé}}</label>
								<div class="col-sm-6">
									<input type="checkbox" class="eqLogicAttr" data-l1key="configuration" data-l2key="cfg_checkChanges">
								</div>
							</div>

							<legend><i class="fas fa-bullhorn"></i> {{Paramètres de notification}}</legend>

							<div class="form-group">
								<label class="col-sm-5 control-label">{{Alertes dans le centre de message}}</label>
								<div class="col-sm-6">
									<input type="checkbox" class="eqLogicAttr" data-l1key="configuration" data-l2key="cfg_messagecenter">
								</div>
							</div>

							<div class="form-group notifDiv">
								<label class="col-sm-5 control-label">{{Alertes via une notification}}</label>
								<div class="col-sm-6">
									<input type="checkbox" class="eqLogicAttr" data-l1key="configuration" data-l2key="cfg_notif">
								</div>
								<div class="notifDiv-option" style="display:none;">

									<div class="col-sm-12 form-group ">
										<label class=" col-sm-5 control-label">{{Commande de notification}}</label>
										<input class="expressionAttr form-control col-sm-6 input-sm cmdAction" data-l1key="cmd" data-type="notifDiv" />
										<span class="input-group-btn">
											<!-- <a class="btn btn-sm listAction" data-type="notifDiv" title="{{Sélectionner un mot-clé}}"><i class="fas fa-tasks"></i></a> -->
											<a class="btn btn-sm listCmdAction" data-type="notifDiv" title="{{Sélectionner une commande action}}"><i class="fas fa-list-alt"></i></a>
										</span>
									</div>
									<br />
									<div class="col-sm-12 form-group">
										<label class="col-sm-4 control-label"></label>
										<div class="col-sm-8 form-group ">

											<div class="actionOptions" id="opt_PU_1234">
											</div>
										</div>
									</div>


									<div class="col-lg-12 noDate">
										<legend><i class="fas fa-paint-brush"></i> {{Personnalisation}}</legend>
										<div class="col-sm-12">
											<span class="">
												Vous pouvez utiliser les tags suivants qui seront automatiquement remplacés lors de la notification :
												<ul>
													<li>#eqId# -> numéro de l'équipement Plugins Utiles</li>
													<li>#eqName# -> nom de l'équipement Plugins Utiles</li>
													<li>#name# -> nom du plugin</li>
													<li>#author# -> auteur du plugin</li>
													<li>#cost# -> coût du plugin</li>
													<li>#certification# -> certification du plugin</li>
													<li>#msg# -> message standard envoyé</li>
												</ul>
											</span>
										</div>
									</div>
								</div>
							</div>

						</div>

						<!-- Partie droite de l'onglet "Équipement" -->
						<!-- Affiche un champ de commentaire par défaut mais vous pouvez y mettre ce que vous voulez -->
						<div class="col-lg-7">
							<legend><i class="fas fa-info"></i> {{Historique des évènements}}</legend>

							<table id="table_plugins_info" class="table table-bordered table-condensed">
								<thead>
									<tr>
										<th style="width: 70px;">{{Date}}</th>
										<th style="width: 25px;">{{Id}}</th>
										<th style="width: 230px;">{{Nom}}</th>
										<th style="width: 90px;">{{Auteur}}</th>
										<th style="width: 15px;"></th>
										<th style="width: 15px;"></th>
										<th style="width: 15px;"></th>
										<th style="width: 15px;"></th>
									</tr>
								</thead>
								<tbody class="cmd_pluginsInfo">
								</tbody>
							</table>
						</div>



					</fieldset>
				</form>
			</div><!-- /.tabpanel #eqlogictab-->

			<!-- Onglet des commandes de l'équipement -->
			<div role="tabpanel" class="tab-pane" id="commandtab">
				<a class="btn btn-default btn-sm pull-right cmdAction" data-action="add" style="margin-top:5px;"><i class="fas fa-plus-circle"></i> {{Ajouter une commande}}</a>
				<br><br>
				<div class="table-responsive">
					<table id="table_cmd" class="table table-bordered table-condensed">
						<thead>
							<tr>
								<th class="hidden-xs" style="min-width:50px;width:70px;">ID</th>
								<th style="min-width:200px;width:350px;">{{Nom}}</th>
								<th>{{Type}}</th>
								<th style="min-width:260px;">{{Options}}</th>
								<th style="min-width:80px;width:200px;">{{Actions}}</th>
							</tr>
						</thead>
						<tbody>
						</tbody>
					</table>
				</div>
			</div><!-- /.tabpanel #commandtab-->

		</div><!-- /.tab-content -->
	</div><!-- /.eqLogic -->
</div><!-- /.row row-overflow -->

<!-- Inclusion du fichier javascript du plugin (dossier, nom_du_fichier, extension_du_fichier, id_du_plugin) -->
<?php include_file('desktop', 'pluginsutiles', 'js', 'pluginsutiles'); ?>
<!-- Inclusion du fichier javascript du core - NE PAS MODIFIER NI SUPPRIMER -->
<?php include_file('core', 'plugin.template', 'js'); ?>
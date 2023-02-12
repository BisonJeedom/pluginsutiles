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
$("#table_cmd").sortable({ axis: "y", cursor: "move", items: ".cmd", placeholder: "ui-state-highlight", tolerance: "intersect", forcePlaceholderSize: true });

$("#div_action_notif").sortable({ axis: "y", cursor: "move", items: ".action_notif", placeholder: "ui-state-highlight", tolerance: "intersect", forcePlaceholderSize: true });


$('.addAction').off('click').on('click', function () {
  addAction({}, $(this).attr('data-type'));
});

$("body").off('click', '.bt_removeAction').on('click', '.bt_removeAction', function () {
  var type = $(this).attr('data-type');
  $(this).closest('.' + type).remove();
});

// permet d'afficher la liste des cmd Jeedom pour choisir sa commande de type "action"
$("body").off('click', '.listCmdAction').on('click', '.listCmdAction', function () {
  var type = $(this).attr('data-type');
  var el = $(this).closest('.' + type).find('.expressionAttr[data-l1key=cmd]');
  jeedom.cmd.getSelectModal({ cmd: { type: 'action', subtype: 'message' } }, function (result) {
    el.value(result.human);
    jeedom.cmd.displayActionOption(el.value(), '', function (html) {
      el.closest('.' + type).find('.actionOptions').html(html);
      taAutosize();
    });
  });
});

// copier/coller du core (cmd.configure.php), permet de choisir la liste des actions (scenario, attendre, ...)
$("body").undelegate(".listAction", 'click').delegate(".listAction", 'click', function () {
  var type = $(this).attr('data-type');
  var el = $(this).closest('.' + type).find('.expressionAttr[data-l1key=cmd]');
  jeedom.getSelectActionModal({}, function (result) {
    el.value(result.human);
    jeedom.cmd.displayActionOption(el.value(), '', function (html) {
      el.closest('.' + type).find('.actionOptions').html(html);
      taAutosize();
    });
  });
});


//sert à charger les champs quand on clique dehors -> A garder !!!
$('body').off('focusout', '.cmdAction.expressionAttr[data-l1key=cmd]').on('focusout', '.cmdAction.expressionAttr[data-l1key=cmd]', function (event) {
  var type = $(this).attr('data-type');
  var expression = $(this).closest('.' + type).getValues('.expressionAttr');
  var el = $(this);
  jeedom.cmd.displayActionOption($(this).value(), init(expression[0].options), function (html) {
    el.closest('.' + type).find('.actionOptions').html(html);
  });

});


/* Permet la réorganisation des commandes dans l'équipement */
$("#table_cmd").sortable({
  axis: "y",
  cursor: "move",
  items: ".cmd",
  placeholder: "ui-state-highlight",
  tolerance: "intersect",
  forcePlaceholderSize: true
})


function addAction(_action, _type) {
  alert("action");
  var div = '<div class="' + _type + '">';
  div += '<div class="form-group ">';

  div += '<label class="col-sm-3 control-label">Action</label>';
  div += '<div class="col-sm-7">';
  div += '<div class="input-group">';
  div += '<span class="input-group-btn">';
  div += '<a class="btn btn-default bt_removeAction roundedLeft" data-type="' + _type + '"><i class="fas fa-minus-circle"></i></a>';
  div += '</span>';
  div += '<input class="expressionAttr form-control cmdAction" data-l1key="cmd" data-type="' + _type + '" />';
  div += '<span class="input-group-btn">';
  div += '<a class="btn btn-default listAction" data-type="' + _type + '" title="{{Sélectionner un mot-clé}}"><i class="fa fa-tasks"></i></a>';
  div += '<a class="btn btn-default listCmdAction roundedRight" data-type="' + _type + '" title="{{Sélectionner une commande}}"><i class="fas fa-list-alt"></i></a>';
  div += '</span>';
  div += '</div>';
  div += '<div class="actionOptions">';
  div += jeedom.cmd.displayActionOption(init(_action.cmd, ''), _action.options);
  div += '</div>';
  div += '</div>';
  div += '</div>';

  div += '</div>';

  $('#div_' + _type).append(div);
  $('#div_' + _type + ' .' + _type + '').last().setValues(_action, '.expressionAttr');
}

function addHistory(_history) {
  // console.log("history =>", _history)

  if (_history.id != '') {
    var tr = '<tr class="market cursor install" data-market_id="' + _history.id + '" data-market_type="plugin">'; // Plugins
  } else {
    var tr = '<tr style="font-weight: bold;font-style: italic;">'; // Mise à jour des mots-clefs
  }
  tr += '<td><span class="pu_history" data-l1key="date"></span></td>';
  tr += '<td><span class="pu_history" data-l1key="id"></span></td>';
  tr += '<td><span class="pu_history" data-l1key="name"></span>';
  tr += '<td><span class="pu_history" data-l1key="author"></span></td>';

  // version
  tr += '<td>';
  if (_history.id != '') {
    color = (_history.stable) ? 'success' : 'warning';
    title = (_history.stable) ? 'stable' : 'beta';
    tr += '<span><sub style="font-size:40px" class="' + color + '" title="plugin en version ' + title + '">&#8226</sub></span>';
  }
  tr += '</td>';

  //private
  tr += '<td>';
  tr += (_history.private) ? '<i class="fas fa-lock" title="plugin privé"></i>' : '';
  tr += '</td>';

  // cost
  tr += '<td>';
  tr += (_history.cost && _history.cost != 0) ? '<i class="icon jeedom2-tirelire1" title="plugin payant : ' + _history.cost + ' €" style="color:var(--al-danger-color)"></i>' : '';
  tr += '</td>';

  // discount
  tr += '<td>';
  tr += (_history.discount) ? '<i class="fas fa-tags" title="plugin en promo ! ancien prix : ' + _history.realcost + ' €" style="color:var(--al-info-color)"></i>' : '';
  tr += '</td>';

  tr += '</tr>';

  $('#table_plugins_info tbody').append(tr);
  $('#table_plugins_info tbody tr').last().setValues(_history, '.pu_history');

}


// Fct core permettant de sauvegarder
function saveEqLogic(_eqLogic) {
  if (!isset(_eqLogic.configuration)) {
    _eqLogic.configuration = {};
  }

  if (_eqLogic.configuration.cfg_notif == 1) {

    // recup des infos notifs
    var myData = $('.notifDiv').getValues('.expressionAttr');

    if (!isset(myData[0].options)) myData[0].options = {};
    myData[0].options.expression = init(myData[0].cmd, '');
    myData[0].options.id = 'opt_PU_1234';
    _eqLogic.configuration.action_notif = myData;

  }
  else {
    _eqLogic.configuration.action_notif = [];
  }

  return _eqLogic;
}

// fct core permettant de restituer les cmd declarées
function printEqLogic(_eqLogic) {
  $('#table_plugins_info tbody').empty();

  // items notif
  $('#opt_PU_1234').empty();
  var eltNotif = $('.notifDiv .expressionAttr[data-l1key=cmd]');
  eltNotif.val('');

  if (isset(_eqLogic.configuration)) {
    if (isset(_eqLogic.configuration.array_historique)) {
      var myHistory = _eqLogic.configuration.array_historique;
      if (myHistory.length > 0) {
        myHistory.sort(function (a, b) {
          if (new Date(a["date"]) == new Date(b["date"])) { //si meme date
            return a["name"].localeCompare(b["name"]); //on trie par nom ///// a[1] - b[1]; // ==> on trie par ID
          }
          return new Date(b["date"]) - new Date(a["date"]); //sinon on trie par date
        });

        for (var i in myHistory) {
          addHistory(myHistory[i]);
        }
      }
    }

    if (_eqLogic.configuration.cfg_notif) {
      actionOptions = []
      var notifs = _eqLogic.configuration.action_notif || [];

      // ajout de la cmd notif
      if (notifs.length > 0) eltNotif.value(notifs[0].cmd);

      // recup des options
      notifs.forEach(_action => {
        // console.log('check _action', _action);
        actionOptions.push({
          expression: _action.cmd,
          options: _action.options,
          id: _action.options.id
        });
      })

      //affichage des options
      jeedom.cmd.displayActionsOption({
        params: actionOptions,
        async: false,
        error: function (error) {
          $('#div_alert').showAlert({ message: error.message, level: 'danger' });
        },
        success: function (data) {
          for (var i in data) {
            $('#' + data[i].id).append(data[i].html.html);
          }
          taAutosize();
        }
      });
    }
  }
}

$('body').off('click', '.pluginsutiles .market').on('click', '.pluginsutiles .market', function () {
  if ($(".pluginsutiles #marketModal").length == 0) {
    $('.pluginsutiles').append('<div id="marketModal"></div>');
  }
  $('#marketModal').dialog({
    title: "{{Market}}",
    closeText: '',
    autoOpen: false,
    modal: true,
    width: 1250,
    height: 0.8 * $(window).height()
  });
  $('#marketModal').load('index.php?v=d&modal=update.display&type=' + $(this).attr('data-market_type') + '&id=' + $(this).attr('data-market_id') + '&repo=market').dialog('open');
});

$('.eqLogicAttr[data-l1key=configuration][data-l2key=cfg_notif]').on('change', function () {
  var elt = $('.notifDiv .notifDiv-option');
  if ($(this).is(':checked')) {
    elt.show();
  }
  else {
    elt.hide();
  }
});


$('.eqLogicAttr[data-l1key=configuration][data-l2key=cfg_checkStableOnly]').on('change', function () {
  var elt = $('.eqLogicAttr[data-l1key=configuration][data-l2key=cfg_checkBetaOnly]');
  if ($(this).is(':checked')) {   
    //elt.prop('checked', false);
    elt.prop('disabled', 'disabled');
  } else {
    elt.prop('disabled', false);
  }
});

$('.eqLogicAttr[data-l1key=configuration][data-l2key=cfg_checkBetaOnly]').on('change', function () {
  var elt = $('.eqLogicAttr[data-l1key=configuration][data-l2key=cfg_checkStableOnly]');
  if ($(this).is(':checked')) {   
    //elt.prop('checked', false);
    elt.prop('disabled', 'disabled');
  } else {
    elt.prop('disabled', false);
  }
});

/* Fonction permettant l'affichage des commandes dans l'équipement */
function addCmdToTable(_cmd) {
  if (!isset(_cmd)) {
    var _cmd = { configuration: {} }
  }
  if (!isset(_cmd.configuration)) {
    _cmd.configuration = {}
  }
  var tr = '<tr class="cmd" data-cmd_id="' + init(_cmd.id) + '">'
  tr += '<td class="hidden-xs">'
  tr += '<span class="cmdAttr" data-l1key="id"></span>'
  tr += '</td>'
  tr += '<td>'
  tr += '<div class="input-group">'
  tr += '<input class="cmdAttr form-control input-sm roundedLeft" data-l1key="name" placeholder="{{Nom de la commande}}">'
  tr += '<span class="input-group-btn"><a class="cmdAction btn btn-sm btn-default" data-l1key="chooseIcon" title="{{Choisir une icône}}"><i class="fas fa-icons"></i></a></span>'
  tr += '<span class="cmdAttr input-group-addon roundedRight" data-l1key="display" data-l2key="icon" style="font-size:19px;padding:0 5px 0 0!important;"></span>'
  tr += '</div>'
  tr += '<select class="cmdAttr form-control input-sm" data-l1key="value" style="display:none;margin-top:5px;" title="{{Commande info liée}}">'
  tr += '<option value="">{{Aucune}}</option>'
  tr += '</select>'
  tr += '</td>'
  tr += '<td>'
  tr += '<span class="type" type="' + init(_cmd.type) + '">' + jeedom.cmd.availableType() + '</span>'
  tr += '<span class="subType" subType="' + init(_cmd.subType) + '"></span>'
  tr += '</td>'
  tr += '<td>'
  tr += '<label class="checkbox-inline"><input type="checkbox" class="cmdAttr" data-l1key="isVisible" checked/>{{Afficher}}</label> '
  tr += '<label class="checkbox-inline"><input type="checkbox" class="cmdAttr" data-l1key="isHistorized" checked/>{{Historiser}}</label> '
  tr += '<label class="checkbox-inline"><input type="checkbox" class="cmdAttr" data-l1key="display" data-l2key="invertBinary"/>{{Inverser}}</label> '
  tr += '<div style="margin-top:7px;">'
  tr += '<input class="tooltips cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="minValue" placeholder="{{Min}}" title="{{Min}}" style="width:30%;max-width:80px;display:inline-block;margin-right:2px;">'
  tr += '<input class="tooltips cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="maxValue" placeholder="{{Max}}" title="{{Max}}" style="width:30%;max-width:80px;display:inline-block;margin-right:2px;">'
  tr += '<input class="tooltips cmdAttr form-control input-sm" data-l1key="unite" placeholder="Unité" title="{{Unité}}" style="width:30%;max-width:80px;display:inline-block;margin-right:2px;">'
  tr += '</div>'
  tr += '</td>'
  tr += '<td>'
  if (is_numeric(_cmd.id)) {
    tr += '<a class="btn btn-default btn-xs cmdAction" data-action="configure"><i class="fas fa-cogs"></i></a> '
    tr += '<a class="btn btn-default btn-xs cmdAction" data-action="test"><i class="fas fa-rss"></i> Tester</a>'
  }
  tr += '<i class="fas fa-minus-circle pull-right cmdAction cursor" data-action="remove" title="{{Supprimer la commande}}"></i></td>'
  tr += '</tr>'
  $('#table_cmd tbody').append(tr)
  var tr = $('#table_cmd tbody tr').last()
  jeedom.eqLogic.buildSelectCmd({
    id: $('.eqLogicAttr[data-l1key=id]').value(),
    filter: { type: 'info' },
    error: function (error) {
      $('#div_alert').showAlert({ message: error.message, level: 'danger' })
    },
    success: function (result) {
      tr.find('.cmdAttr[data-l1key=value]').append(result)
      tr.setValues(_cmd, '.cmdAttr')
      jeedom.cmd.changeType(tr, init(_cmd.subType))
    }
  })
}
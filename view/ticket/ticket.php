<?php
?>
<script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
<script>
    tailwind.config = {
        darkMode: "class",
        theme: {
            extend: {
                colors: {
                    primary: "#0066CC",
                    "background-light": "#F1F5F9",
                    "background-dark": "#0F172A",
                },
                fontFamily: {
                    display: ["Inter", "sans-serif"],
                },
                borderRadius: {
                    DEFAULT: "0.25rem",
                },
            },
        },
    };
</script>

<?php

if (file_exists('../digiriskdolibarr.main.inc.php')) {
    require_once __DIR__ . '/../digiriskdolibarr.main.inc.php';
} elseif (file_exists('../../digiriskdolibarr.main.inc.php')) {
    require_once __DIR__ . '/../../digiriskdolibarr.main.inc.php';
} else {
    die('Include of digiriskdolibarr main fails');
}

require_once DOL_DOCUMENT_ROOT.'/ticket/class/actions_ticket.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formticket.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/ticket.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';

// Global variables definitions
global $conf, $db, $hookmanager, $moduleName, $moduleNameLowerCase, $langs, $user;

// Load translation files required by the page
$langs->loadLangs(array("companies", "other", "ticket"));
saturne_load_langs();

// Get parameters
$id        = GETPOSTINT('id');
$ref       = GETPOST('ref', 'alpha');
$track_id  = GETPOST('track_id', 'alpha', 3);
$socid     = GETPOSTINT('socid');
$contactid = GETPOSTINT('contactid');
$projectid = GETPOSTINT('projectid');
$notifyTiers = GETPOST("notify_tiers_at_create", 'alpha');

$action    = GETPOST('action', 'aZ09');
$cancel    = GETPOST('cancel', 'alpha');
$backtopage = GETPOST('backtopage', 'alpha');
$backtopageforcancel = GETPOST('backtopageforcancel', 'alpha');
$contextpage = GETPOST('contextpage', 'aZ');

$limit = GETPOSTINT('limit') ? GETPOSTINT('limit') : $conf->liste_limit;
$sortfield = GETPOST('sortfield', 'aZ09comma') ? GETPOST('sortfield', 'aZ09comma') : "a.datep";
$sortorder = GETPOST('sortorder', 'aZ09comma') ? GETPOST('sortorder', 'aZ09comma') : "desc";
$search_rowid = GETPOST('search_rowid');
$search_agenda_label = GETPOST('search_agenda_label');

$hookmanager->initHooks(array('ticketcard', 'globalcard'));

$object = new Ticket($db);
$extrafields = new ExtraFields($db);
$form = new Form($db);

$extrafields->fetch_name_optionals_label($object->table_element);

/*
 * Actions
 */

$object->fetch($id, $ref, $track_id);
$object->fetch_optionals();

$parameters = ['arrayfields' => &$arrayfields];
$resHook    = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($resHook < 0) {
    setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if (empty($resHook)) {

    if ($action == 'confirm_set_status' && !GETPOST('cancel')) {
		// Reopen ticket
		if ($object->fetch(GETPOSTINT('id'), GETPOST('track_id', 'alpha')) >= 0) {
			$new_status = GETPOSTINT('new_status');
			$res = $object->setStatut($new_status, null, '', $triggermodname);
			if ($res) {
                setEventMessage('Updated');
			} else {
				$error++;
				setEventMessages($object->error, $object->errors, 'errors');
			}
		}
	}
}

/*
 * View
 */

if ($mode == 'pwa') {
    $conf->dol_hide_topmenu  = 1;
    $conf->dol_hide_leftmenu = 1;
}

$title = $langs->trans('Signatories');
saturne_header(0,'', $title, $helpUrl ?? '', '', 0, 0, [], [], '', 'mod-' . $object->module . '-' . $object->element . ' page-list bodyforlist');

?>

<div class="flex h-screen overflow-hidden bg-slate-50">
    <main class="flex-1 flex flex-col min-w-0">
        <header class="bg-white border-b border-slate-200 flex items-center px-4 h-9 flex-shrink-0">
            <div class="flex space-x-4 h-full text-[12px] font-medium">
                <button class="flex items-center text-primary border-b-2 border-primary px-1">
                    <span class="fas fa-ticket-alt infobox-contrat mr-1" style="" title="No photo"></span>
                    Ticket
                </button>
                <button class="flex items-center text-slate-500 hover:text-slate-700 px-1 transition-colors">
                    Contacts
                    <span class="ml-1 bg-slate-100 px-1 py-0 rounded-full text-[9px]">1</span>
                </button>
                <button class="flex items-center text-slate-500 hover:text-slate-700 px-1 transition-colors">Fichiers</button>
                <button class="flex items-center text-slate-500 hover:text-slate-700 px-1 transition-colors">Agenda</button>
                <button class="flex items-center text-slate-500 hover:text-slate-700 px-1 transition-colors">Contrôles</button>
                <button class="flex items-center text-slate-500 hover:text-slate-700 px-1 transition-colors">Questionnaires</button>
                <button class="flex items-center text-slate-500 hover:text-slate-700 px-1 transition-colors">Diffusion</button>
            </div>
        </header>

        <div class="flex-1 overflow-y-auto custom-scrollbar p-3 space-y-3">
            <div class="bg-white rounded border border-slate-200 p-2.5 flex justify-between items-center shadow-sm">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 bg-slate-50 rounded border border-slate-200 flex items-center justify-center">
                        <span class="fas fa-ticket-alt infobox-contrat" style="" title="No photo"></span>
                    </div>
                    <div>
                        <div class="flex items-center space-x-2">
                            <h1 class="text-lg font-bold text-slate-900 leading-none"><?= $object->ref ?></h1>
                            <span class="text-[10px] text-slate-400"><?= $object->track_id ?></span>
                        </div>
                        <div class="flex items-center flex-row text-[12px] space-x-2 mt-0.5">
                            <?php
                                $userTmp = new User($db);
                                $userTmp->fetch($object->fk_user_create);
                                print $userTmp->getNomUrl(-1); // @TODO problème css la photo est au dessus
                            ?>
                        </div>
                    </div>
                </div>
                <div class="flex items-center space-x-2">
                    <span class="text-[10px] font-bold text-slate-400 uppercase cursor-pointer"><?= $langs->trans('BackToList') ?></span>
                    <div class="flex border border-slate-200 rounded overflow-hidden">
                        <button class="px-1.5 py-0.5 bg-white hover:bg-slate-50 text-slate-600 border-r border-slate-200 cursor-pointer">
                            <i class="fa fa-chevron-left"></i>
                        </button>
                        <button class="px-1.5 py-0.5 bg-white hover:bg-slate-50 text-slate-600 cursor-pointer">
                            <i class="fa fa-chevron-right"></i>
                        </button>
                    </div>
                    <?= $object->getLibStatut(2) ?>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-5 gap-3">
                <div class="lg:col-span-3 space-y-3">
                    <div class="bg-white rounded border border-slate-200 overflow-hidden shadow-sm">
                        <div class="px-3 py-1.5 bg-slate-50 border-b border-slate-200 flex justify-between items-center">
                            <span class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Titre du message</span>
                            <span class="fas fa-pencil-alt text-[14px] text-slate-400 cursor-pointer" title="Modifier"></span>
                        </div>
                        <div class="p-2.5">
                            <p class="text-[13px] font-medium text-slate-900"><?= $object->subject ?></p>
                        </div>
                    </div>

                    <div class="bg-white rounded border border-slate-200 overflow-hidden shadow-sm">
                        <div class="px-3 py-1.5 bg-slate-50 border-b border-slate-200 flex justify-between items-center">
                            <span class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Message initial</span>
                            <span class="fas fa-pencil-alt text-[14px] text-slate-400 cursor-pointer" title="Modifier"></span>
                        </div>
                        <div class="p-2.5">
                            <p class="text-[12px] text-slate-600 leading-relaxed">
                                <?= $object->message ?>
                            </p>
                        </div>
                    </div>

                    <div class="bg-white rounded border border-slate-200 shadow-sm">
                        <div class="px-3 py-1.5 bg-slate-50 border-b border-slate-200 flex items-center">
                            <span class="text-[10px] font-bold text-primary uppercase tracking-wider">Informations registres</span>
                        </div>
                        <div class="p-3 grid grid-cols-2 gap-x-8 gap-y-3">
                            <?php
                                foreach ($object->array_options as $optional => $value) {
                                    $optional = substr($optional, strlen('options_'));
                                    print '<div>';

                                    # ExtraField Name Display
                                    $resHook    = $hookmanager->executeHooks('cardExtrafieldNameDisplay', ['name' => $optional, 'value' => $langs->transnoentities($extrafields->attributes['ticket']['label'][$optional])], $object, $action);
                                    print '<div class="flex items-center gap-1 text-[10px] font-bold text-slate-400 mb-0.5 uppercase">' . (empty($resHook) ? $langs->transnoentities($extrafields->attributes['ticket']['label'][$optional]) : $hookmanager->resPrint) . '<span class="fas fa-pencil-alt text-[9px] text-slate-300 cursor-pointer hover:text-slate-500 transition-colors" title="Modifier"></span></div>';

                                    # Extrafield Value Display
                                    $resHook    = $hookmanager->executeHooks('cardExtrafieldValueDisplay', ['name' => $optional, 'value' => $value, 'type' => $extrafields->attributes['ticket']['type'][$optional]], $object, $action);
                                    if (!empty($resHook)) {
                                        print '<div class="text-[12px] font-medium text-slate-900 border-b border-dashed border-slate-200 pb-0.5">' . $hookmanager->resPrint . '</div>';
                                    } else {
                                        switch ($extrafields->attributes['ticket']['type'][$optional]) {
                                            case 'datetime':
                                                print '<div class="text-[12px] font-medium text-slate-900 border-b border-dashed border-slate-200 pb-0.5">' . dol_print_date($value, 'dayhour', 'tzuserrel') . '</div>';
                                                break;
                                            default:
                                                print '<div class="text-[12px] font-medium text-slate-900 border-b border-dashed border-slate-200 pb-0.5">' . ($value ?? '-') . '</div>';
                                                break;
                                        }
                                    }
                                    print '</div>';
                                }
                            ?>
                        </div>
                    </div>
                </div>

                <div class="lg:col-span-2 space-y-3">
                    <div class="bg-white rounded border border-slate-200 overflow-hidden shadow-sm">
                        <div class="px-3 py-1.5 bg-slate-50 border-b border-slate-200 flex justify-between items-center">
                            <span class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Responsable et avancement</span>
                        </div>
                        <div class="p-2.5">
                            <div class="flex justify-between items-center mb-2">
                                <div class="flex space-x-3 text-[10px]">
                                    <div class="flex items-center text-slate-400 font-medium uppercase"><?= $langs->trans('AssignedTo') ?> <span class="fas fa-pencil-alt text-[10px] text-slate-400 ml-2 cursor-pointer" title="Modifier"></span></div>
                                    <div class="flex items-center text-slate-400 font-medium uppercase">Progression <span class="fas fa-pencil-alt text-[10px] text-slate-400 ml-2 cursor-pointer" title="Modifier"></span></div>
                                </div>
                                <span class="text-[11px] font-bold text-primary bg-primary/10 px-1.5 rounded"><?= $object->progress ?>%</span>
                            </div>
                            <!-- <div class="overflow-x-auto">
                                <table class="w-full text-[10px] text-left">
                                    <thead class="bg-slate-50 text-slate-400 uppercase font-bold">
                                        <tr>
                                            <th class="py-1 px-1 border-b border-slate-200">Réf.</th>
                                            <th class="py-1 px-1 border-b border-slate-200">Tâche</th>
                                            <th class="py-1 px-1 border-b border-slate-200">Prog.</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100">
                                        <tr class="hover:bg-slate-50">
                                            <td class="py-1 px-1 text-primary font-bold">TK-0941</td>
                                            <td class="py-1 px-1">Souris ergo...</td>
                                            <td class="py-1 px-1"><span class="bg-emerald-500 text-white px-1 rounded text-[9px]">100%</span></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div> -->
                        </div>
                    </div>

                    <div class="bg-white rounded border border-slate-200 overflow-hidden shadow-sm">
                        <div class="px-3 py-1.5 bg-slate-50 border-b border-slate-200 flex justify-between items-center">
                            <span class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Classification</span>
                            <span class="fas fa-pencil-alt text-[14px] text-slate-400 cursor-pointer hover:text-slate-600 transition-colors" title="Modifier"></span>
                        </div>
                        <div class="p-2.5 space-y-2.5">
                            <div class="flex flex-wrap gap-1">
                                <?= $form->showCategories($object->id, Categorie::TYPE_TICKET, 1)  ?>
                            </div>
                            <div class="pt-2 border-t border-slate-100 space-y-0.5">
                                <div class="flex justify-between items-center text-[10px]">
                                    <span class="text-slate-400"><?= $langs->trans('DateCreationShort') ?></span>
                                    <span class="font-bold"><?= dol_print_date($object->datec, 'dayhour', 'tzuser') ?> <span class="text-slate-300 font-normal italic ml-1"><?= $langs->trans("TimeElapsedSince") ?>: <?= convertSecondToTime(roundUpToNextMultiple($now - $object->datec, 60)) ?></span></span>
                                </div>
                                <div class="flex justify-between items-center text-[10px]">
                                    <span class="text-slate-400"><?= $langs->trans('DateReading') ?></span>
                                    <?php if ($object->dateread != null) { ?>
                                        <span class="font-bold"><?= dol_print_date($object->dateread, 'dayhour', 'tzuser') ?> <span class="text-slate-300 font-normal italic ml-1"><?= $langs->trans("TimeElapsedSince") ?>: <?= convertSecondToTime(roundUpToNextMultiple($now - $object->dateread, 60)) ?></span></span>
                                    <?php } else { ?>
                                        <span class="font-bold">-</span>
                                    <?php } ?>
                                </div>
                                <div class="flex justify-between items-center text-[10px]">
                                    <span class="text-slate-400"><?= $langs->trans('DateClosing') ?></span>
                                    <?php if ($object->date_close != null) { ?>
                                        <span class="font-bold"><?= dol_print_date($object->date_close, 'dayhour', 'tzuser') ?> <span class="text-slate-300 font-normal italic ml-1"><?= $langs->trans("TimeElapsedSince") ?>: <?= convertSecondToTime(roundUpToNextMultiple($now - $object->date_close, 60)) ?></span></span>
                                    <?php } else { ?>
                                        <span class="font-bold">-</span>
                                    <?php } ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="flex flex-wrap items-center justify-end gap-3 text-[10px] font-bold">
                        <?php
                            $actionTicket = new ActionsTicket($db);
                            print($actionTicket->viewStatusActions($object))
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<?php
// End of page
llxFooter();
$db->close();

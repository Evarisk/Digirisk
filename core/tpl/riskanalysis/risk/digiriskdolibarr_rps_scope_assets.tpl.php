<?php
/* Copyright (C) 2026 EVARISK <technique@evarisk.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 */

/**
 * \file       core/tpl/riskanalysis/risk/digiriskdolibarr_rps_scope_assets.tpl.php
 * \ingroup    digiriskdolibarr
 * \brief      Styles, dictionnaire de criteres et scripts du perimetre RPS.
 *             Inclus une seule fois, avec le bloc de qualification.
 *
 *             Le bloc est pose au-dessus du tableau de la liste et bati en CSS grid : un
 *             tableau imbrique dans une cellule de la liste fausse la largeur de ses colonnes.
 *
 * Variables attendues : $rpsScale, $rpsSubCategories (definis par le perimetre)
 */

global $langs;

if (empty($rpsScale)) {
    return;
}

$rpsGridCriteria = Risk::getPsychosocialRiskGrid();
$rpsGridFamily   = '';

?>
<style>
/* ---------------------------------------------------------------------------
 * Perimetre RPS insere dans la liste des risques
 * ------------------------------------------------------------------------ */
.rps-scope-top { margin:0 0 14px; }

.rps-scope { position:relative; z-index:1; background:#fff; border:1px solid #e6e8ec; border-radius:10px;
             box-shadow:0 1px 2px rgba(16,24,40,.05), 0 6px 18px -12px rgba(16,24,40,.18);
             font-size:13px; color:#1f2430; overflow:hidden; }
.rps-scope * { box-sizing:border-box; }
.rps-num { font-variant-numeric:tabular-nums; font-feature-settings:"tnum"; }

/* En-tete */
.rps-head { display:flex; align-items:center; gap:16px; padding:11px 16px;
            background:linear-gradient(180deg,#fdf5f5 0%,#fbf0f0 100%); border-bottom:1px solid #f0e0e0; flex-wrap:wrap; }
.rps-head-title { display:inline-flex; align-items:baseline; gap:8px; font-weight:650; font-size:14px; letter-spacing:-.1px; }
.rps-head-title .fa-brain { color:#d35968; font-size:15px; align-self:center; }
.rps-head-sub { font-weight:400; font-size:12px; color:#7b828f; }
.rps-head-metrics { display:inline-flex; align-items:center; gap:18px; margin-left:auto; flex-wrap:wrap; }
.rps-metric { display:inline-flex; align-items:baseline; gap:6px; }
.rps-metric .rps-num { font-size:20px; font-weight:650; letter-spacing:-.4px; }
.rps-metric-label { font-size:11px; color:#7b828f; }
.rps-metric-coverage { flex-direction:column; align-items:flex-start; gap:4px; min-width:112px; }
.rps-metric-coverage b { color:#1f2430; }
.rps-bar { display:block; width:112px; height:4px; border-radius:2px; background:#e8eaee; overflow:hidden; }
.rps-bar > i { display:block; height:100%; border-radius:2px; background:#1b6b3a; transition:width .18s ease; }

.rps-pill { display:inline-block; padding:2px 9px; border-radius:999px; font-size:11px; font-weight:650;
            letter-spacing:.1px; white-space:nowrap; border:1px solid rgba(16,24,40,.06); }
.rps-pill-band { font-size:12px; padding:3px 11px; }
.rps-pill-draft { background:#fdecc8; color:#7a5300; text-transform:uppercase; font-size:10px; letter-spacing:.4px; }
.rps-pill-sim { background:#efe9fd; color:#5b2ea8; text-transform:uppercase; font-size:10px; letter-spacing:.4px; }

.rps-icon-button { border:1px solid #e0e3e8; background:#fff; color:#7b828f; width:26px; height:26px;
                   border-radius:7px; cursor:pointer; display:inline-flex; align-items:center; justify-content:center; }
.rps-icon-button:hover { background:#f4f6f8; color:#1f2430; }

/* Groupes */
.rps-body { background:#fff; }
.rps-group + .rps-group { border-top:1px solid #eef0f3; }
.rps-group[hidden] { display:none; }
.rps-group-head { display:flex; align-items:center; gap:9px; padding:9px 16px 7px; }
.rps-group-title { font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.6px; color:#4b5563; }
.rps-count { min-width:20px; height:19px; padding:0 6px; border-radius:999px; background:#eef0f3; color:#49505c;
             font-size:11px; font-weight:700; display:inline-flex; align-items:center; justify-content:center; }
.rps-group[data-group="yes"] .rps-count { background:#e2f2e8; color:#1b6b3a; }
.rps-group[data-group="tbd"] .rps-count { background:#fdeecd; color:#8a5a00; }
.rps-group-hint { font-size:11.5px; color:#9aa1ab; }
.rps-group-legend { margin-left:auto; display:inline-flex; gap:12px; }
.rps-group-legend abbr { width:26px; text-align:center; font-size:10px; font-weight:700; color:#9aa1ab;
                         text-decoration:none; border-bottom:1px dotted #c9ced6; cursor:help; }

/* Lignes : la grille change selon l'etat, une unite non concernee n'a ni facteur ni note */
.rps-rows { padding:0 8px 8px; }
.rps-row { display:grid; align-items:center; gap:14px; padding:8px 8px; border-radius:8px;
           grid-template-columns:minmax(200px,1fr) 182px 108px 236px; }
.rps-row + .rps-row { box-shadow:inset 0 1px 0 #f2f3f5; }
.rps-row:hover { background:#fafbfc; }
.rps-row[data-state="0"], .rps-row[data-state="-1"] { grid-template-columns:minmax(200px,1fr) 236px; }
.rps-row[data-state="0"] .rps-factors, .rps-row[data-state="0"] .rps-score,
.rps-row[data-state="-1"] .rps-factors, .rps-row[data-state="-1"] .rps-score { display:none; }
.rps-row[data-state="0"] .rps-evaluate { display:none; }
.rps-row[data-state="0"] .rps-unit-ref, .rps-row[data-state="0"] .rps-unit-label { color:#7b828f; }

.rps-unit { min-width:0; }
.rps-unit-ref { font-weight:700; font-size:12px; color:#49505c; margin-right:6px; }
.rps-unit-label { font-weight:600; }
.rps-unit-meta { display:block; font-size:11.5px; color:#9aa1ab; margin-top:2px; white-space:nowrap;
                 overflow:hidden; text-overflow:ellipsis; }
.rps-attention { color:#b07000; font-weight:600; }
.rps-reason-input { border:1px solid #e0e3e8; border-radius:6px; padding:2px 7px; font-size:11.5px;
                    width:min(330px,100%); color:#49505c; }

.rps-factors { display:flex; gap:4px; }
.rps-cell { width:26px; height:26px; border-radius:7px; display:inline-flex; align-items:center; justify-content:center;
            font-size:11.5px; font-weight:700; border:1px solid rgba(16,24,40,.07); font-variant-numeric:tabular-nums; }
.rps-cell-empty { background:#fff; border:1px dashed #d7dbe1; color:#a4abb5; }

.rps-score { display:flex; align-items:center; gap:7px; justify-content:flex-end; }
.rps-score .rps-num { font-size:15px; font-weight:650; }

.rps-actions { display:flex; align-items:center; gap:8px; justify-content:flex-end; }
.rps-switch { display:inline-flex; background:#f1f2f5; border-radius:999px; padding:2px; }
.rps-switch button { border:0; background:transparent; padding:3px 11px; border-radius:999px; font-size:11.5px;
                     font-weight:600; color:#7b828f; cursor:pointer; line-height:1.5; }
.rps-switch button:hover { color:#1f2430; }
.rps-switch button.is-yes { background:#1b6b3a; color:#fff; box-shadow:0 1px 2px rgba(16,24,40,.18); }
.rps-switch button.is-no { background:#6b7280; color:#fff; box-shadow:0 1px 2px rgba(16,24,40,.18); }
.rps-switch button.is-tbd { background:#b07000; color:#fff; box-shadow:0 1px 2px rgba(16,24,40,.18); }

.rps-evaluate, .rps-button { display:inline-flex; align-items:center; gap:6px; padding:4px 11px; border-radius:7px;
                             border:1px solid #e0e3e8; background:#fff; font-size:12px; font-weight:500;
                             color:#1f2430; cursor:pointer; white-space:nowrap; }
.rps-evaluate:hover, .rps-button:hover { background:#f4f6f8; border-color:#d2d6dd; }
.rps-evaluate i, .rps-button i { color:#7b828f; font-size:11px; }

/* Pied */
.rps-foot { display:flex; align-items:center; gap:14px; padding:9px 16px; background:#fafbfc;
            border-top:1px solid #eef0f3; font-size:11.5px; color:#7b828f; flex-wrap:wrap; }
.rps-foot-keys { margin-left:auto; display:inline-flex; align-items:center; gap:12px; }
.rps-foot-keys span { display:inline-flex; align-items:center; gap:5px; }
.rps-foot-keys i { width:11px; height:11px; border-radius:3px; display:inline-block; border:1px solid rgba(16,24,40,.08); }
.rps-note { padding:9px 16px; background:#fffaf0; border-top:1px solid #f4e3bf; font-size:11.5px;
            line-height:1.55; color:#6b5520; }
.rps-note .fa-triangle-exclamation { color:#b07000; }
.rps-note-action { display:flex; align-items:center; gap:14px; flex-wrap:wrap; }
.rps-note-action > span { flex:1; min-width:280px; }
.rps-note-action .rps-button { background:#fff; border-color:#e0cfa4; }
.rps-scope-hidden { display:none; }

/* Ligne psychosociale de l'unite de travail consultee */
.rps-pill-yes { background:#e2f2e8; color:#1b6b3a; }
.rps-unit-line { display:flex; align-items:center; gap:24px; padding:14px 16px; flex-wrap:wrap; }
.rps-unit-factors { display:flex; gap:10px; }
.rps-factor { display:inline-flex; flex-direction:column; align-items:center; gap:4px; }
.rps-factor .rps-cell { width:34px; height:30px; border-radius:8px; font-size:13px; }
.rps-factor abbr { font-size:10px; font-weight:700; color:#9aa1ab; text-decoration:none;
                   border-bottom:1px dotted #c9ced6; cursor:help; }
.rps-unit-state { font-size:12.5px; color:#49505c; line-height:1.5; max-width:340px; }
.rps-unit-actions { display:flex; align-items:center; gap:8px; margin-left:auto; }
.rps-evaluate-main { background:#1b4f93; border-color:#1b4f93; color:#fff; font-weight:600;
                     padding:7px 15px; font-size:12.5px; }
.rps-evaluate-main:hover { background:#174380; border-color:#174380; }
.rps-evaluate-main i { color:#cfe0f5; }
.rps-foot-note a { color:#1b4f93; }

/* Modale de saisie de la grille RPS-DU */
.rps-modal-overlay { position:fixed; inset:0; background:rgba(16,24,40,.5); z-index:1400; display:none;
                     align-items:flex-start; justify-content:center; padding:32px 16px; overflow:auto; }
.rps-modal-overlay.open { display:flex; }
.rps-modal { background:#fff; border-radius:12px; width:100%; max-width:880px; overflow:hidden;
             box-shadow:0 24px 60px rgba(16,24,40,.32); }
.rps-modal-wide { max-width:980px; }
.rps-modal-head { padding:14px 20px; border-bottom:1px solid #eef0f3; display:flex; gap:12px; align-items:flex-start; }
.rps-modal-head h2 { margin:0; font-size:16px; font-weight:650; }
.rps-modal-head h2 i { color:#1b4f93; }
.rps-modal-head .sub { font-size:12px; color:#7b828f; margin-top:4px; line-height:1.55; }
.rps-modal-close { margin-left:auto; background:none; border:0; font-size:22px; line-height:1; cursor:pointer; color:#9aa1ab; }
.rps-modal-toolbar { display:flex; align-items:flex-end; gap:14px; padding:12px 20px; background:#fafbfc;
                     border-bottom:1px solid #eef0f3; flex-wrap:wrap; }
.rps-modal-toolbar label { display:flex; flex-direction:column; gap:4px; font-size:10px; font-weight:700;
                           text-transform:uppercase; letter-spacing:.5px; color:#7b828f; }
.rps-grow { flex:1; min-width:220px; }
.rps-input { border:1px solid #e0e3e8; border-radius:7px; padding:6px 10px; font-size:12.5px;
             color:#1f2430; font-weight:400; text-transform:none; letter-spacing:0; }
.rps-grid-progress { font-size:12px; color:#7b828f; margin-left:auto; }
.rps-grid-progress b { color:#1f2430; font-size:14px; }
.rps-modal-body { max-height:52vh; overflow:auto; padding:0 20px 8px; }
.rps-grid-family { font-size:10px; font-weight:700; text-transform:uppercase; letter-spacing:.6px;
                   color:#4b5563; padding:14px 0 6px; position:sticky; top:0; background:#fff; }
.rps-grid-row { display:flex; align-items:center; gap:18px; padding:8px 0; border-top:1px solid #f4f5f7; }
.rps-grid-criterion { flex:1; min-width:0; }
.rps-grid-name { display:block; font-size:12.5px; font-weight:600; }
.rps-grid-question { display:block; font-size:11.5px; color:#9aa1ab; margin-top:2px; }
.rps-grid-answers { display:flex; gap:5px; flex:0 0 auto; }
.rps-grid-answer { border:1px solid #e0e3e8; background:#fff; border-radius:7px; padding:5px 10px;
                   font-size:11.5px; cursor:pointer; min-width:92px; color:#49505c; }
.rps-grid-answer:hover { border-color:#c9ced6; background:#fafbfc; }
.rps-modal-foot { padding:12px 20px; border-top:2px solid #eef0f3; display:flex; align-items:center;
                  gap:18px; flex-wrap:wrap; }
.rps-grid-summary { display:flex; align-items:center; gap:22px; }
.rps-grid-factors { display:flex; gap:9px; }
.rps-grid-error { flex-basis:100%; color:#8f2222; font-size:12px; }
.rps-modal-actions { display:flex; gap:8px; margin-left:auto; }
.rps-button-primary { background:#1b4f93; border-color:#1b4f93; color:#fff; font-weight:600; padding:7px 15px; font-size:12.5px; }
.rps-button-primary:hover { background:#174380; border-color:#174380; }
.rps-button-primary i { color:#cfe0f5; }
.rps-button[disabled] { opacity:.5; cursor:not-allowed; }

/* Les quatre paliers se lisent sur les reponses elles-memes : teinte au repos, couleur pleine
 * une fois la reponse retenue. La rampe vert / ambre / rouge / rouge sombre se suit de gauche
 * a droite, et chaque palier garde sa propre encre pour rester lisible dans les deux etats. */
.rps-grid-answer.scale-1 { background:#e7f5ec; border-color:#a6d6bb; color:#1f6b42; }
.rps-grid-answer.scale-2 { background:#fdf3e3; border-color:#eac98d; color:#8a5a00; }
.rps-grid-answer.scale-3 { background:#fde6e4; border-color:#eda9a5; color:#9c3230; }
.rps-grid-answer.scale-4 { background:#f7dbdb; border-color:#cf9494; color:#7a1d1d; }
.rps-grid-answer.scale-1:hover { background:#d8eee2; }
.rps-grid-answer.scale-2:hover { background:#fbe7c4; }
.rps-grid-answer.scale-3:hover { background:#fbd6d3; }
.rps-grid-answer.scale-4:hover { background:#f2caca; }

/* La reponse retenue ne se distingue pas par la seule couleur : anneau et graisse */
.rps-grid-answer.is-picked { font-weight:700; box-shadow:0 0 0 2px rgba(16,24,40,.55); }
.rps-grid-answer.scale-1.is-picked { background:#3a9d5d; border-color:#2d7d49; color:#ffffff; }
.rps-grid-answer.scale-2.is-picked { background:#e9ad4f; border-color:#c88f33; color:#3a2a00; }
.rps-grid-answer.scale-3.is-picked { background:#e05353; border-color:#bf3f3f; color:#ffffff; }
.rps-grid-answer.scale-4.is-picked { background:#8f2222; border-color:#6d1a1a; color:#ffffff; }

/* Actions de prevention : seuls les facteurs qui depassent le seuil ouvrent un champ */
.rps-actions-block { border-top:1px solid #eef0f3; margin-top:14px; padding:14px 0 4px; }
.rps-actions-head { display:flex; align-items:baseline; gap:10px; flex-wrap:wrap; margin-bottom:10px; }
.rps-actions-title { font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.6px; color:#4b5563; }
.rps-actions-hint { font-size:11.5px; color:#9aa1ab; flex:1; min-width:260px; line-height:1.5; }
.rps-action-row { display:flex; align-items:center; gap:10px; padding:5px 0; }
.rps-action-row[hidden] { display:none; }
.rps-action-factor { font-size:12.5px; font-weight:600; min-width:200px; }
.rps-action-input { flex:1; }

/* Bandeau des actions sur la ligne de l unite */
.rps-actions-strip { padding:9px 16px; border-top:1px solid #eef0f3; background:#fafbfc; font-size:12.5px; }
.rps-actions-strip.is-missing { background:#fffaf0; border-top-color:#f4e3bf; }
.rps-actions-strip-head { display:flex; align-items:center; gap:8px; flex-wrap:wrap; }
.rps-button-sm { padding:3px 9px; font-size:11.5px; margin-left:auto; }
.rps-actions-list { margin:8px 0 0; padding:0 0 0 18px; font-size:12px; color:#49505c; line-height:1.7; }
.rps-actions-list a { color:#1b4f93; margin-right:6px; font-weight:600; }

/* Les facteurs a traiter ressortent, sans fermer les autres a la saisie */
.rps-action-row.is-to-treat { background:#fffaf0; border-radius:7px; padding-left:8px; padding-right:8px; }
.rps-action-row.is-to-treat .rps-action-factor::after { content:' · à traiter'; color:#b07000; font-weight:600; }

/* Les facteurs sous le seuil se deplient a la demande */
.rps-action-row[hidden] { display:none; }
.rps-action-owner { max-width:160px; }
.rps-action-due { max-width:150px; }
.rps-actions-more { border:0; background:none; color:#1b4f93; font-size:12px; font-weight:600;
                    cursor:pointer; padding:8px 0 2px; display:inline-flex; align-items:center; gap:7px; }
.rps-actions-more:hover { text-decoration:underline; }
.rps-actions-more[hidden] { display:none; }
.rps-actions-more i { font-size:10px; }

/* Etat des actions sur la ligne de l unite */
.rps-actions-list li.is-done { color:#9aa1ab; }
.rps-actions-list li.is-done .rps-action-label { text-decoration:line-through; }
.rps-action-state { font-size:10.5px; font-weight:700; text-transform:uppercase; letter-spacing:.4px;
                    padding:1px 7px; border-radius:999px; margin-right:6px; }
.rps-action-state.is-done { background:#e2f2e8; color:#1b6b3a; }
.rps-action-state.is-todo { background:#fdeecd; color:#8a5a00; }
.rps-action-owner-name { color:#49505c; }
.rps-action-due-date { color:#9aa1ab; }

/* Plan d'action de l'unite, tenu depuis le bloc */
.rps-actions-list { margin-top:9px; display:flex; flex-direction:column; gap:3px; }
.rps-action-item { display:flex; align-items:center; gap:9px; padding:5px 8px; border-radius:7px;
                   background:#fff; border:1px solid #eef0f3; font-size:12.5px; }
.rps-action-item.is-done { background:#fafbfc; }
.rps-action-item.is-done .rps-action-label { text-decoration:line-through; color:#9aa1ab; }
.rps-action-item.is-saving { opacity:.55; }
.rps-action-check { display:inline-flex; align-items:center; }
.rps-action-check input { width:15px; height:15px; cursor:pointer; accent-color:#1b6b3a; }
.rps-action-ref { color:#1b4f93; font-weight:600; white-space:nowrap; }
.rps-action-label { flex:1; min-width:0; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
.rps-task-owner { max-width:170px; }
.rps-task-due { max-width:148px; }
.rps-task-delete:hover { color:#9c3230; border-color:#eda9a5; background:#fde6e4; }
.rps-actions-count b { color:#1f2430; }

/* Choix de la methode dans la fenetre d evaluation */
.rps-method-bar { display:flex; align-items:center; gap:12px; padding:10px 20px; background:#fff;
                  border-bottom:1px solid #eef0f3; flex-wrap:wrap; }
.rps-method-label { font-size:10px; font-weight:700; text-transform:uppercase; letter-spacing:.5px; color:#7b828f; }
.rps-method-switch { display:inline-flex; background:#f1f2f5; border-radius:9px; padding:3px; gap:3px; }
.rps-method { border:0; background:transparent; border-radius:7px; padding:5px 13px; cursor:pointer;
              font-size:12.5px; font-weight:600; color:#7b828f; text-align:left; line-height:1.3; }
.rps-method small { display:block; font-size:10.5px; font-weight:400; opacity:.8; }
.rps-method:hover { color:#1f2430; }
.rps-method.is-on { background:#fff; color:#1f2430; box-shadow:0 1px 3px rgba(16,24,40,.14); }
.rps-method-hint { font-size:11.5px; color:#9aa1ab; margin-left:auto; }
.rps-pane { display:none; }
#rps-eval-overlay[data-method="point"] .rps-pane[data-method="point"],
#rps-eval-overlay[data-method="grid"] .rps-pane[data-method="grid"] { display:block; }
.rps-pane-source { font-size:11.5px; color:#9aa1ab; padding:12px 0 2px; }
.rps-grid-row.is-context { opacity:.82; }

/* Dictionnaire des criteres */
.rps-dict-overlay { position:fixed; inset:0; background:rgba(16,24,40,.5); z-index:1400; display:none;
                    align-items:flex-start; justify-content:center; padding:40px 16px; overflow:auto; }
.rps-dict-overlay.open { display:flex; }
.rps-dict { background:#fff; border-radius:12px; width:100%; max-width:900px; overflow:hidden;
            box-shadow:0 24px 60px rgba(16,24,40,.32); }
.rps-dict-head { padding:14px 20px; border-bottom:1px solid #eef0f3; display:flex; gap:12px; align-items:flex-start; }
.rps-dict-head h2 { margin:0; font-size:16px; font-weight:650; }
.rps-dict-head .sub { font-size:12px; color:#7b828f; margin-top:4px; line-height:1.55; }
.rps-dict-close { margin-left:auto; background:none; border:0; font-size:22px; line-height:1; cursor:pointer; color:#9aa1ab; }
.rps-dict-body { max-height:60vh; overflow:auto; }
table.rps-dict-t { width:100%; border-collapse:collapse; font-size:12.5px; }
table.rps-dict-t th { text-align:left; font-size:10px; text-transform:uppercase; letter-spacing:.5px; color:#9aa1ab;
                      padding:8px 20px; border-bottom:1px solid #eef0f3; position:sticky; top:0; background:#fff; }
table.rps-dict-t td { padding:7px 20px; border-bottom:1px solid #f4f5f7; vertical-align:top; }
table.rps-dict-t tr.fam td { background:#fafbfc; font-weight:650; font-size:11.5px; }
table.rps-dict-t tr:hover td { background:#fcfcfd; }
.rps-dict-q { color:#9aa1ab; font-size:11.5px; display:block; margin-top:2px; }
.rps-dict-foot { padding:12px 20px; border-top:1px solid #eef0f3; display:flex; gap:12px; align-items:center; flex-wrap:wrap; }
</style>

<!-- psychosocialRiskGrid.json devient un dictionnaire modifiable et versionne -->
<div class="rps-dict-overlay" id="rps-dict-overlay">
    <div class="rps-dict">
        <div class="rps-dict-head">
            <div>
                <h2>Critères d'évaluation des risques psychosociaux</h2>
                <div class="sub">
                    <?php echo dol_escape_htmltag($langs->trans('PsychosocialGridSource')); ?> &middot;
                    <?php echo count($rpsGridCriteria); ?> critères, <?php echo count($rpsSubCategories); ?> facteurs.
                    Aujourd'hui figés dans <code>js/json/psychosocialRiskGrid.json</code> : la proposition est d'en faire un
                    dictionnaire modifiable et <b>versionné</b>, chaque évaluation conservant la version qui l'a produite.
                </div>
            </div>
            <button type="button" class="rps-dict-close" aria-label="Fermer">&times;</button>
        </div>
        <div class="rps-dict-body">
            <table class="rps-dict-t">
                <thead>
                <tr>
                    <th style="width:44px">N°</th>
                    <th>Critère</th>
                    <th style="width:155px">Facteur</th>
                    <th style="width:92px">Sens</th>
                    <th style="width:62px">Active</th>
                    <th style="width:48px"></th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($rpsGridCriteria as $criterion) : ?>
                    <?php if ($criterion['family'] != $rpsGridFamily) :
                        $rpsGridFamily = $criterion['family']; ?>
                        <tr class="fam"><td colspan="6"><?php echo dol_escape_htmltag($rpsGridFamily); ?></td></tr>
                    <?php endif; ?>
                    <tr>
                        <td style="color:#9aa1ab"><?php echo (int) $criterion['position']; ?></td>
                        <td>
                            <?php echo dol_escape_htmltag($criterion['name']); ?>
                            <span class="rps-dict-q"><?php echo dol_escape_htmltag($criterion['question']); ?></span>
                        </td>
                        <td style="color:#7b828f">
                            <?php
                            $subPosition = (int) $criterion['subCategory'];
                            echo isset($rpsSubCategories[$subPosition]) ? dol_escape_htmltag($langs->trans($rpsSubCategories[$subPosition]['label'])) : '&mdash;';
                            ?>
                        </td>
                        <td style="color:#7b828f" title="<?php echo !empty($criterion['reversed']) ? 'Ressource : « Jamais » est la réponse rouge' : 'Contrainte : « Jamais » est la réponse verte'; ?>">
                            <?php echo !empty($criterion['reversed']) ? 'ressource' : 'contrainte'; ?>
                        </td>
                        <td><i class="fas fa-toggle-on" style="color:#1b6b3a;font-size:16px"></i></td>
                        <td style="text-align:right"><button type="button" class="rps-button"><i class="fas fa-pencil-alt"></i></button></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="rps-dict-foot">
            <span style="font-size:11.5px;color:#7b828f;max-width:460px;line-height:1.55">
                Désactiver un critère le retire des futures évaluations sans effacer les réponses déjà collectées :
                les évaluations signées restent lisibles avec le libellé en vigueur à leur date.
            </span>
            <div style="margin-left:auto;display:flex;gap:8px">
                <button type="button" class="rps-button"><i class="fas fa-rotate-left"></i> Restaurer la grille INRS</button>
                <button type="button" class="rps-button"><i class="fas fa-plus"></i> Nouveau critère</button>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    var BANDS = [
        { min: 3.5, color: '#8f2222', ink: '#fff',    label: '80-100 · Extrême' },
        { min: 2.5, color: '#e05353', ink: '#fff',    label: '51-79 · Elevé' },
        { min: 1.5, color: '#e9ad4f', ink: '#3a2a00', label: '48-50 · Modéré' },
        { min: 0,   color: '#3a9d5d', ink: '#fff'  , label: '0-47 · Faible' }
    ];

    function bandOf(value) {
        for (var i = 0; i < BANDS.length; i++) {
            if (value >= BANDS[i].min) { return BANDS[i]; }
        }
        return BANDS[BANDS.length - 1];
    }

    // Une unite non concernee n'a ni facteur ni note : le passage d'un etat a l'autre
    // deplace la ligne dans son groupe et recalcule la cotation du risque.
    function refresh(scope) {
        var totals = { 1: 0, 0: 0, '-1': 0 };
        var sum = 0, assessed = 0;

        scope.querySelectorAll('.rps-row').forEach(function (row) {
            var state = row.getAttribute('data-state');
            totals[state] = (totals[state] || 0) + 1;
            if (state === '1' && row.getAttribute('data-average')) {
                sum += parseFloat(row.getAttribute('data-average'));
                assessed++;
            }
        });

        scope.querySelectorAll('.rps-group').forEach(function (group) {
            var rows = group.querySelectorAll('.rps-row').length;
            group.querySelector('.rps-count').textContent = rows;
            if (rows === 0) { group.setAttribute('hidden', ''); } else { group.removeAttribute('hidden'); }
        });

        var concerned = totals['1'] || 0;
        var average   = assessed > 0 ? sum / assessed : 0;
        var band      = bandOf(average);

        scope.querySelector('#rps-average').textContent = assessed > 0 ? average.toFixed(2).replace('.', ',') : '—';
        scope.querySelector('#rps-done').textContent      = assessed;
        scope.querySelector('#rps-concerned').textContent = concerned;
        scope.querySelector('#rps-bar').style.width = (concerned > 0 ? Math.round(100 * assessed / concerned) : 0) + '%';

        var pill = scope.querySelector('#rps-band');
        pill.textContent       = assessed > 0 ? band.label : 'non cotée';
        pill.style.background  = assessed > 0 ? band.color : '#f1f2f5';
        pill.style.color       = assessed > 0 ? band.ink : '#9aa1ab';
    }

    function moveRow(row, state) {
        var scope = row.closest('.rps-scope');
        var key   = state === '1' ? 'yes' : (state === '0' ? 'no' : 'tbd');
        var list  = scope.querySelector('.rps-group[data-group="' + key + '"] .rps-rows');
        if (!list) { return; }

        // Insertion a sa place, les unites restant classees par reference
        var reference = row.querySelector('.rps-unit-ref').textContent.trim();
        var before    = null;
        list.querySelectorAll('.rps-row').forEach(function (other) {
            if (before || other === row) { return; }
            if (other.querySelector('.rps-unit-ref').textContent.trim().localeCompare(reference, 'fr', { numeric: true }) > 0) {
                before = other;
            }
        });
        list.insertBefore(row, before);
    }

    // Une unite declaree non concernee doit porter un motif : il est demande sur place.
    function askReason(row) {
        var meta = row.querySelector('.rps-unit-meta');
        if (meta.querySelector('.rps-reason-input')) { return; }
        if (!row.hasAttribute('data-meta')) { row.setAttribute('data-meta', meta.innerHTML); }
        var current = meta.querySelector('.rps-reason');
        var value   = current ? current.textContent.trim() : '';
        meta.innerHTML = '';
        var input = document.createElement('input');
        input.type = 'text';
        input.className = 'rps-reason-input';
        input.placeholder = 'Motif de non-exposition (obligatoire pour le document unique)';
        input.value = value;
        meta.appendChild(input);
        input.focus();
    }

    function restoreMeta(row) {
        var meta = row.querySelector('.rps-unit-meta');
        if (!meta.querySelector('.rps-reason-input')) { return; }
        meta.innerHTML = row.hasAttribute('data-meta')
            ? row.getAttribute('data-meta')
            : '<span class="rps-attention">aucun facteur coté</span>';
    }


    /* ----------------------------------------------------------------------
     * Grille RPS-DU : une seule evaluation enregistree pour l'unite
     * ------------------------------------------------------------------- */

    var RPS_SCALE = {
        1: {color: '#3a9d5d', ink: '#ffffff', label: '0-47 \u00b7 Faible'},
        2: {color: '#e9ad4f', ink: '#3a2a00', label: '48-50 \u00b7 Mod\u00e9r\u00e9'},
        3: {color: '#e05353', ink: '#ffffff', label: '51-79 \u00b7 Elev\u00e9'},
        4: {color: '#8f2222', ink: '#ffffff', label: '80-100 \u00b7 Extr\u00eame'}
    };

    // Une seule fenetre d'evaluation : la methode se choisit dedans, chaque volet portant
    // ses propres lignes de saisie. Seul le volet actif est lu, affiche et enregistre.
    function evalOverlay() { return document.getElementById('rps-eval-overlay'); }

    function activePane(overlay) {
        return overlay ? overlay.querySelector('.rps-pane[data-method="' + overlay.getAttribute('data-method') + '"]') : null;
    }

    // La cotation de l'unite est celle de son facteur le plus severe : un facteur rouge
    // ne doit pas etre noye par cinq facteurs verts.
    function refreshEval() {
        var overlay = evalOverlay();
        var pane    = activePane(overlay);
        if (!pane) { return; }

        var factors = {}, answered = 0, rows = pane.querySelectorAll('.rps-grid-row');

        rows.forEach(function (row) {
            var picked = row.querySelector('.rps-grid-answer.is-picked');
            if (!picked) { return; }
            answered++;
            var factor = parseInt(row.getAttribute('data-sub-category'), 10);
            var scale  = parseInt(picked.getAttribute('data-scale'), 10);
            // Les positions 6 et 7 decrivent le contexte et l'impact : hors cotation
            if (factor <= 5) { factors[factor] = Math.max(factors[factor] || 0, scale); }
        });

        overlay.querySelector('#rps-eval-answered').textContent = answered;
        overlay.querySelector('#rps-eval-total').textContent = rows.length;

        overlay.querySelectorAll('.rps-grid-factors .rps-cell').forEach(function (cell) {
            var scale = factors[cell.getAttribute('data-factor')];
            if (!scale) {
                cell.className = 'rps-cell rps-cell-empty';
                cell.textContent = '';
                cell.removeAttribute('style');
                return;
            }
            cell.className = 'rps-cell';
            cell.textContent = scale;
            cell.style.background = RPS_SCALE[scale].color;
            cell.style.color = RPS_SCALE[scale].ink;
        });

        // La cotation suit la moyenne des facteurs cotes, comme le chiffre affiche sur l'unite
        var keys = Object.keys(factors), sum = 0;
        keys.forEach(function (key) { sum += factors[key]; });
        var level = 0;
        if (keys.length > 0) {
            var average = sum / keys.length;
            level = average >= 3.5 ? 4 : (average >= 2.5 ? 3 : (average >= 1.5 ? 2 : 1));
        }

        var band = overlay.querySelector('#rps-eval-band');
        if (level > 0) {
            band.textContent = RPS_SCALE[level].label;
            band.style.background = RPS_SCALE[level].color;
            band.style.color = RPS_SCALE[level].ink;
        } else {
            band.textContent = 'non cot\u00e9e';
            band.style.background = '#f1f2f5';
            band.style.color = '#9aa1ab';
        }

        var save = overlay.querySelector('#rps-eval-save');
        if (answered > 0) { save.removeAttribute('disabled'); } else { save.setAttribute('disabled', 'disabled'); }

        refreshActions(overlay, factors);
    }

    // Une action peut etre engagee sur n'importe quel facteur : les six champs restent
    // ouverts, seuls ceux cotes Eleve ou Extreme sont signales comme a traiter.
    // Seuls les facteurs a traiter sont ouverts d'emblee : afficher les six champs en
    // permanence noyait les trois qui comptent. Les autres restent accessibles d'un clic,
    // et un facteur deja renseigne ne se replie jamais.
    function refreshActions(overlay, factors) {
        var expanded = overlay.classList.contains('rps-actions-expanded');
        var hidden   = 0;

        overlay.querySelectorAll('.rps-action-row').forEach(function (row) {
            var scale = factors[row.getAttribute('data-factor')] || 0;
            var cell  = row.querySelector('[data-action-cell]');
            var input = row.querySelector('.rps-action-input');
            var filled = input && input.value.trim() !== '';

            row.classList.toggle('is-to-treat', scale >= 3);

            if (scale >= 3 || expanded || filled) {
                row.removeAttribute('hidden');
            } else {
                row.setAttribute('hidden', '');
                hidden++;
            }

            if (!scale) {
                cell.className = 'rps-cell rps-cell-empty';
                cell.textContent = '';
                cell.removeAttribute('style');
                return;
            }

            cell.className = 'rps-cell';
            cell.textContent = scale;
            cell.style.background = RPS_SCALE[scale].color;
            cell.style.color = RPS_SCALE[scale].ink;
        });

        var more = overlay.querySelector('#rps-actions-more');
        if (!more) { return; }

        if (hidden === 0) {
            more.setAttribute('hidden', '');
            return;
        }

        more.removeAttribute('hidden');
        more.querySelector('span').textContent = expanded
            ? 'Masquer les facteurs en dessous du seuil'
            : 'Agir aussi sur un facteur en dessous du seuil (' + hidden + ')';
        more.querySelector('i').className = expanded ? 'fas fa-chevron-up' : 'fas fa-chevron-down';
    }

    function postRps(payload, onDone) {
        var root = (document.getElementById('dol_url_root') || {}).value || window.location.origin;
        var token = (window.saturne && window.saturne.toolbox) ? window.saturne.toolbox.getToken() : '';

        return fetch(root + '/custom/digiriskdolibarr/core/ajax/save_rps_assessment.php?token=' + token, {
            method: 'POST',
            headers: {'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest'},
            body: JSON.stringify(payload),
            credentials: 'same-origin'
        }).then(function (response) {
            return response.json().then(function (body) { return {ok: response.ok, body: body}; });
        }).then(onDone);
    }

    function showEvalError(message) {
        var overlay = evalOverlay();
        var error   = overlay ? overlay.querySelector('#rps-eval-error') : null;
        if (!error) {
            window.alert(message);
            return;
        }
        error.textContent = message;
        error.removeAttribute('hidden');
    }

    function saveEval(button) {
        var overlay = evalOverlay();
        var pane    = activePane(overlay);
        if (!pane) {
            showEvalError('Aucune méthode active : rouvrez la fenêtre.');
            return;
        }

        var answers = [];
        pane.querySelectorAll('.rps-grid-row').forEach(function (row) {
            var picked = row.querySelector('.rps-grid-answer.is-picked');
            if (!picked) { return; }
            answers.push({
                criterion: parseInt(row.getAttribute('data-criterion'), 10),
                label: row.getAttribute('data-label'),
                sub_category: parseInt(row.getAttribute('data-sub-category'), 10),
                scale: parseInt(picked.getAttribute('data-scale'), 10)
            });
        });

        if (answers.length === 0) {
            showEvalError('Cotez au moins un critère avant d\'enregistrer.');
            return;
        }

        // Les actions saisies sur les facteurs affiches deviennent des taches du projet,
        // prefixees du facteur pour rester lisibles dans le plan d'action.
        var tasks = [];
        overlay.querySelectorAll('.rps-action-row').forEach(function (row) {
            var input = row.querySelector('.rps-action-input');
            var label = input ? input.value.trim() : '';
            if (label === '') { return; }

            var owner = row.querySelector('.rps-action-owner');
            var due   = row.querySelector('.rps-action-due');

            tasks.push({
                label: input.getAttribute('data-factor-label') + ' : ' + label,
                owner: owner && owner.value ? parseInt(owner.value, 10) : 0,
                due: due ? due.value : ''
            });
        });

        var error = overlay.querySelector('#rps-eval-error');
        error.setAttribute('hidden', '');
        button.setAttribute('disabled', 'disabled');

        postRps({
            action: 'assessment',
            fk_element: parseInt(overlay.getAttribute('data-element'), 10),
            date: overlay.querySelector('#rps-eval-date').value,
            comment: overlay.querySelector('#rps-eval-comment').value,
            answers: answers,
            tasks: tasks
        }, function (result) {
            if (result.ok && result.body && result.body.success) {
                window.location.reload();
                return;
            }
            error.textContent = (result.body && result.body.error) ? result.body.error : 'Enregistrement impossible.';
            error.removeAttribute('hidden');
            button.removeAttribute('disabled');
        }).catch(function () {
            error.textContent = 'Enregistrement impossible.';
            error.removeAttribute('hidden');
            button.removeAttribute('disabled');
        });
    }


    // Le plan d'action se tient depuis le bloc : chaque changement part aussitot, sans
    // recharger la page ni ouvrir la tache. L'unite est reprise du bandeau qui porte la ligne.
    function unitOf(element) {
        var card = element.closest('.rps-unit-card');
        var open = card ? card.querySelector('.rps-open-actions, .rps-consolidate') : null;
        var overlay = document.getElementById('rps-eval-overlay');
        if (overlay) { return parseInt(overlay.getAttribute('data-element'), 10); }
        return open ? parseInt(open.getAttribute('data-element'), 10) : 0;
    }

    function refreshActionCount(card) {
        var counter = card.querySelector('.rps-actions-count');
        if (!counter) { return; }

        var items = card.querySelectorAll('.rps-action-item');
        var done  = card.querySelectorAll('.rps-action-item.is-done').length;
        var plural = items.length > 1 ? 's' : '';

        counter.innerHTML = '<b>' + done + '</b>/' + items.length + ' action' + plural + ' faite' + plural;
    }

    function saveTask(item, payload, onSaved) {
        item.classList.add('is-saving');
        payload.action     = payload.action || 'task_update';
        payload.task_id    = parseInt(item.getAttribute('data-task'), 10);
        payload.fk_element = unitOf(item);

        postRps(payload, function (result) {
            item.classList.remove('is-saving');
            if (result.ok && result.body && result.body.success) {
                if (onSaved) { onSaved(); }
                return;
            }
            window.alert((result.body && result.body.error) ? result.body.error : 'Modification impossible.');
        }).catch(function () {
            item.classList.remove('is-saving');
            window.alert('Modification impossible.');
        });
    }

    document.addEventListener('change', function (event) {
        var field = event.target;
        var item  = field.closest ? field.closest('.rps-action-item') : null;
        if (!item) { return; }

        if (field.classList.contains('rps-task-done')) {
            var done = field.checked;
            saveTask(item, {done: done ? 1 : 0}, function () {
                item.classList.toggle('is-done', done);
                refreshActionCount(item.closest('.rps-unit-card'));
            });
            return;
        }

        if (field.classList.contains('rps-task-owner')) {
            saveTask(item, {owner: field.value ? parseInt(field.value, 10) : 0});
            return;
        }

        if (field.classList.contains('rps-task-due')) {
            saveTask(item, {due: field.value});
        }
    });

    document.addEventListener('click', function (event) {
        var target = event.target.closest ? event.target.closest('button, .rps-open-dict') : null;
        if (!target) { return; }

        if (target.classList.contains('rps-scope-toggle')) {
            var body = target.closest('.rps-scope').querySelector('.rps-body');
            body.classList.toggle('rps-scope-hidden');
            target.querySelector('i').className = body.classList.contains('rps-scope-hidden') ? 'fas fa-chevron-down' : 'fas fa-chevron-up';
            return;
        }

        if (target.classList.contains('rps-open-dict')) {
            document.getElementById('rps-dict-overlay').classList.add('open');
            return;
        }
        if (target.classList.contains('rps-dict-close')) {
            target.closest('.rps-dict-overlay').classList.remove('open');
            return;
        }

        // Grille RPS-DU
        if (target.classList.contains('rps-open-eval') || target.classList.contains('rps-open-actions')) {
            var overlay = evalOverlay();
            if (!overlay) { return; }
            overlay.classList.add('open');
            refreshEval();
            // Le bouton Actions ouvre la meme fenetre, mais directement sur sa section
            if (target.classList.contains('rps-open-actions')) {
                var section = overlay.querySelector('#rps-eval-actions');
                if (section) { section.scrollIntoView({block: 'start'}); }
            }
            return;
        }
        if (target.classList.contains('rps-modal-close')) {
            target.closest('.rps-modal-overlay').classList.remove('open');
            return;
        }
        if (target.classList.contains('rps-method')) {
            switchMethod(target);
            return;
        }
        if (target.classList.contains('rps-grid-answer')) {
            var answers   = target.parentNode;
            var wasPicked = target.classList.contains('is-picked');

            answers.querySelectorAll('.rps-grid-answer').forEach(function (other) {
                other.classList.remove('is-picked');
            });

            // Une reponse cliquee par erreur doit pouvoir etre retiree : sans ce retour
            // en arriere le critere part quand meme dans l'evaluation.
            if (!wasPicked) {
                target.classList.add('is-picked');
            }

            refreshEval();
            return;
        }
        if (target.classList.contains('rps-task-delete')) {
            var item = target.closest('.rps-action-item');
            var label = item.querySelector('.rps-action-label').textContent.trim();
            if (!window.confirm('Supprimer l\'action "' + label + '" ?')) { return; }
            saveTask(item, {action: 'task_delete'}, function () {
                var card = item.closest('.rps-unit-card');
                item.parentNode.removeChild(item);
                refreshActionCount(card);
            });
            return;
        }
        if (target.id === 'rps-actions-more') {
            var overlay = evalOverlay();
            overlay.classList.toggle('rps-actions-expanded');
            refreshEval();
            return;
        }
        if (target.id === 'rps-eval-save') {
            try {
                saveEval(target);
            } catch (exception) {
                showEvalError('Erreur inattendue : ' + exception.message);
                target.removeAttribute('disabled');
            }
            return;
        }
        // Consolidation des risques eclates d'une unite en une seule ligne
        if (target.classList.contains('rps-consolidate')) {
            target.setAttribute('disabled', 'disabled');
            target.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Consolidation...';
            postRps({
                action: 'consolidate',
                fk_element: parseInt(target.getAttribute('data-element'), 10)
            }, function (result) {
                if (result.ok && result.body && result.body.success) {
                    window.location.reload();
                    return;
                }
                target.removeAttribute('disabled');
                target.innerHTML = '<i class="fas fa-compress"></i> Consolider en une ligne';
                window.alert((result.body && result.body.error) ? result.body.error : 'Consolidation impossible.');
            });
            return;
        }

        if (target.parentNode && target.parentNode.classList.contains('rps-switch')) {
            var group = target.parentNode;
            var row   = target.closest('.rps-row');
            var state = target.getAttribute('data-value');

            Array.prototype.forEach.call(group.children, function (button) { button.className = ''; });
            target.className = state === '1' ? 'is-yes' : (state === '0' ? 'is-no' : 'is-tbd');

            row.setAttribute('data-state', state);
            if (state === '0') { askReason(row); } else { restoreMeta(row); }
            moveRow(row, state);
            refresh(row.closest('.rps-scope'));

            // La qualification est enregistree tout de suite : c'est elle qui commande
            // l'affichage du risque psychosocial sur la fiche de l'unite.
            var reasonInput = row.querySelector('.rps-reason-input');
            var reasonText  = reasonInput ? reasonInput.value : (row.querySelector('.rps-reason') || {}).textContent;
            postRps({
                action: 'scope',
                fk_element: parseInt(row.getAttribute('data-element'), 10),
                concerned: parseInt(state, 10),
                reason: (reasonText || '').trim()
            }, function () {});
        }
    });

    // Le motif d'une unite non concernee est enregistre a la sortie du champ
    document.addEventListener('focusout', function (event) {
        var input = event.target;
        if (!input.classList || !input.classList.contains('rps-reason-input')) { return; }
        var row = input.closest('.rps-row');
        if (!row) { return; }
        postRps({
            action: 'scope',
            fk_element: parseInt(row.getAttribute('data-element'), 10),
            concerned: parseInt(row.getAttribute('data-state'), 10),
            reason: input.value.trim()
        }, function () {});
    });

    document.querySelectorAll('.rps-dict-overlay').forEach(function (overlay) {
        overlay.addEventListener('mousedown', function (event) {
            if (event.target === overlay) { overlay.classList.remove('open'); }
        });
    });
})();
</script>

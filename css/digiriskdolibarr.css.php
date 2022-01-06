<?php

/* Copyright (C) 2021 SuperAdmin
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    digiriskdolibarr/css/digiriskdolibarr.css.php
 * \ingroup digiriskdolibarr
 * \brief   CSS file for module DigiriskDolibarr.
 */

//if (! defined('NOREQUIREUSER')) define('NOREQUIREUSER','1');	// Not disabled because need to load personalized language
//if (! defined('NOREQUIREDB'))   define('NOREQUIREDB','1');	// Not disabled. Language code is found on url.
if ( ! defined('NOREQUIRESOC'))    define('NOREQUIRESOC', '1');
//if (! defined('NOREQUIRETRAN')) define('NOREQUIRETRAN','1');	// Not disabled because need to do translations
if ( ! defined('NOCSRFCHECK'))     define('NOCSRFCHECK', 1);
if ( ! defined('NOTOKENRENEWAL'))  define('NOTOKENRENEWAL', 1);
if ( ! defined('NOLOGIN'))         define('NOLOGIN', 1); // File must be accessed by logon page so without login
//if (! defined('NOREQUIREMENU'))   define('NOREQUIREMENU',1);  // We need top menu content
if ( ! defined('NOREQUIREHTML'))   define('NOREQUIREHTML', 1);
if ( ! defined('NOREQUIREAJAX'))   define('NOREQUIREAJAX', '1');

session_cache_limiter('public');
// false or '' = keep cache instruction added by server
// 'public'  = remove cache instruction added by server
// and if no cache-control added later, a default cache delay (10800) will be added by PHP.

// Load Dolibarr environment
$res = 0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if ( ! $res && ! empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) $res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"] . "/main.inc.php";
// Try main.inc.php into web root detected using web root calculated from SCRIPT_FILENAME
$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME']; $tmp2 = realpath(__FILE__); $i = strlen($tmp) - 1; $j = strlen($tmp2) - 1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] == $tmp2[$j]) { $i--; $j--; }
if ( ! $res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1)) . "/main.inc.php")) $res    = @include substr($tmp, 0, ($i + 1)) . "/main.inc.php";
if ( ! $res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1)) . "/../main.inc.php")) $res = @include substr($tmp, 0, ($i + 1)) . "/../main.inc.php";
// Try main.inc.php using relative path
if ( ! $res && file_exists("../../main.inc.php")) $res    = @include "../../main.inc.php";
if ( ! $res && file_exists("../../../main.inc.php")) $res = @include "../../../main.inc.php";
if ( ! $res) die("Include of main fails");

require_once DOL_DOCUMENT_ROOT . '/core/lib/functions2.lib.php';

// Load user to have $user->conf loaded (not done by default here because of NOLOGIN constant defined) and load permission if we need to use them in CSS
/*if (empty($user->id) && ! empty($_SESSION['dol_login']))
{
	$user->fetch('',$_SESSION['dol_login']);
	$user->getrights();
}*/


// Define css type
top_httphead('text/css');
//// Important: Following code is to cache this file to avoid page request by browser at each Dolibarr page access.
//// You can use CTRL+F5 to refresh your browser cache.
if (empty($dolibarr_nocache)) header('Cache-Control: max-age=10800, public, must-revalidate');
else header('Cache-Control: no-cache');

require DOL_DOCUMENT_ROOT . '/theme/' . $conf->theme . '/theme_vars.inc.php';
if (defined('THEME_ONLY_CONSTANT')) return;

?>

div.mainmenu.digiriskdolibarr::before {
	content: "\f249";
}
div.mainmenu.digiriskdolibarr {
	background-image: none;
}

.header{
	margin: 0 auto;
	width: 100%;
	height: 50px;
	background: rgb(60,70,100);
}

.topnav-left {
	float: left;
}
.topnav-right {

}

.topnav div.login_block_other, .topnav div.login_block_user {
	max-width: unset;
	width: unset;
}
.topnav{
	background: rgb(<?php echo $colorbackhmenu1 ?>);
	background-image: linear-gradient(-45deg, <?php echo colorAdjustBrightness(colorArrayToHex(colorStringToArray($colorbackhmenu1)), '5'); ?>, rgb(<?php echo $colorbackhmenu1 ?>));
	overflow: hidden;
	height: 100%;
}
.topnav .tmenu {
	display: block;
}

.topnav a{
	float: left;
	color: #f2f2f2;
	text-decoration: none;
}
.topnav .login_block_other a {
	padding: 5px 10px;
	margin-left: 4px;
	font-size: 1.3em;
}
.topnav-right > a {
	font-size: 17px;
}

.topnav-left a {
	padding: 7px 4px 7px 4px;
	margin: 8px;
	margin-left: 4px;
}
.topnav-left a:hover, .topnav .login_block_other a:hover {
	background-color: #ddd;
	color: black;
}

.topnav-right{
	float: right;
}

.topnav input[type="text"] {
	background-color: #fff;
	color: #000;
	float: left;
	border-bottom: none !important;
	margin-left: 6px;
	font-size: 1.3em;
	max-width: 250px;
	border-radius: 5px;
}

@charset "UTF-8";
/*!
	DIGIRISK
	Created by Eoxia

	version: 7.0.0
*/
/*--------------------------------------------------------------
>>> TABLE OF CONTENTS:
----------------------------------------------------------------
# fonticons
# Normalize
# Modules
--------------------------------------------------------------*/
/* Bleu ciel, couleur principale */
/* Bleu fonce, couleur de l'interface */
/* Autres couleurs */
/*--------------------------------------------------------------
# Normalize
--------------------------------------------------------------*/
.wpeo-wrap {
	/*padding-right: 20px;*/
}

.wpeo-wrap * {
	box-sizing: border-box;
}

.wpeo-wrap::after {
	display: block;
	content: '';
	clear: both;
}

.wpeo-wrap ul, .wpeo-wrap li {
	margin: 0;
}

.wpeo-wrap.causerie-wrap .main-content {
	margin-top: 1em;
}

.wpeo-wrap.causerie-wrap .main-content .causerie-title {
	font-size: 18px;
	font-weight: 400;
	text-transform: uppercase;
	margin: 0;
}

.wpeo-wrap.causerie-wrap .main-content .causerie-description {
	margin-top: 0;
	margin-bottom: 20px;
	color: rgba(0, 0, 0, 0.6);
}

.wpeo-wrap.causerie-wrap .main-content .causerie-stats li {
	list-style-type: disc;
	margin-left: 1.4em;
}

.wpeo-wrap.causerie-wrap .main-content .wpeo-autocomplete .autocomplete-label {
	padding: 0.6em;
}

.wpeo-wrap.causerie-wrap .table.causerie-step-3 .avatar {
	float: left;
	margin-right: 10px;
}

.wpeo-wrap.causerie-wrap .table.causerie-step-3 .participant {
	display: block;
	line-height: 50px;
}

.wpeo-wrap.causerie-wrap .step-2 .owl-nav {
	height: 100%;
	width: 100%;
	justify-content: center;
	align-items: center;
	display: flex;
	position: absolute;
	top: 0;
}

.wpeo-wrap.causerie-wrap .step-2 .owl-nav div {
	width: auto;
	height: auto;
}

.wpeo-wrap.causerie-wrap .step-2 .owl-nav div.owl-next {
	margin-left: auto;
}

.wpeo-wrap.causerie-wrap .step-2 .owl-dots {
	margin: 50px;
}

.wpeo-wrap.causerie-wrap .step-2 .owl-dots .active button {
	color: blue !important;
}

.wpeo-wrap.causerie-wrap .step-2 .owl-dots .owl-dot button {
	background: none;
	border: none;
	padding-right: 10px;
	color: #555555;
	font-size: 50px;
	font-weight: bold;
	cursor: pointer;
}

.wpeo-wrap.causerie-wrap h2 {
	font-size: 2.2em;
	line-height: 1em;
	margin: .200em 0 0 0;
}

.wpeo-wrap.causerie-wrap .step .step-list {
	width: 100%;
	display: flex;
}

.wpeo-wrap.causerie-wrap .step .step-list .step {
	text-decoration: none;
}

.wpeo-wrap.causerie-wrap .step .step-list .step .title {
	padding-bottom: 0px;
	min-height: 30px;
}

.wpeo-wrap.causerie-wrap .step .step-list .step:after {
	position: relative;
	z-index: 90;
	display: block;
	margin: auto;
	content: '';
	width: 30px;
	height: 30px;
	top: 8px;
	background: #d6d6d6;
	border-radius: 50%;
	transition: all 0.2s ease-out;
}

.wpeo-wrap.prevention-wrap .digi-prevention-parent.step-2 .end-date-element .form-element-disable .form-field-container {
	display: none;
}

.wpeo-wrap.prevention-wrap .digi-prevention-parent.step-2 .dropdown-toggle .button-icon {
	color: #000;
}

.wpeo-wrap {
	/*--------------------------------------------------------------
	  # Fonticons
	  --------------------------------------------------------------*/
	/*!
   * Font Awesome Pro 5.0.10 by @fontawesome - https://fontawesome.com
   * License - https://fontawesome.com/license (Commercial License)
   */
	/*--------------------------------------------------------------
	  # Modules
	  --------------------------------------------------------------*/
	/**
   * Structure de la page
   */
	/* CSS spécifique pour le champs domaine de l'email dans les utilisateurs */
	/**
   * Search bar
   */
	/**
   * .cotation -> classe de base
   * .default-cotation -> par défaut
   * .level1 -> cotaiton 0
   * .level2 -> cotation 48
   * .level4 -> cotation 51
   * .level5 -> cotation 80
   * .method -> cotation personnalisée
   */
	/** table */
	/**
   * Style spécifique à la navigation des workunits
   * .unit-header -> Affichage des media et de l'UT ou GP
   * .workunit-navigation -> la navigation toggle des workunit -> le toggle devrait disparaitre dans les prochaines versions
   * .workunit-list -> Liste des UT
   * .workunit-add -> Ajout d'une UT
   */
	/**
   * Header de la navigation UT/GP
   * @since 7.0.0
   */
	/**
   * Toolbar de la navigation UT/GP
   * @since 7.0.0
   */
	/**
   * Liste de la navigation UT/GP
   * @since 7.0.0
   */
	/**
   * Version mobile de la navigation UT/GP
   * @since 7.0.0
   */
	/** Responsive */
	/**
   * Table design
   * .table -> classe principale
   * .media
   * .cotation-container
   * .categorie-container
   * .comment-container
   * .action -> Boite contenant les actions sur une ligne
   * 		.task
   * 		.edit
   * 		.delete
   * .w50 -> cellule de 50px
   * .w100 -> cellule de 100px
   * .wm130 -> cellule de 130px minimum
   * .w150 -> cellule de 150px
   * .full -> cellule de 100%
   * .padding -> Ajoute un padding à la cellule de 8px sur les côtés
   */
	/**
   * CSS spécifique
   * Responsive Table Risk
   */
	/**
   * CSS spécifique
   * Responsive Table Evaluateurs
   */
	/**
   * Table design
   * .table -> classe principale
   * .media
   * .action -> Boite contenant les actions sur une ligne
   * 		.task
   * 		.edit
   * 		.delete
   * .w50 -> cellule de 50px
   * .w100 -> cellule de 100px
   * .wm130 -> cellule de 130px minimum
   * .w150 -> cellule de 150px
   * .full -> cellule de 100%
   * .padding -> Ajoute un padding à la cellule de 8px sur les côtés
   */
	/** Autocompelte */
	/** Responsive Table Causeries */
	/**
   * Installer de digirisk avec slider
   * .bloc-create-society -> premier bloc
   * .w^dogo-components -> Installation des composant, avec slider
   *
   */
	/** Classe parente .wpeo-wrap */
	/**
   * Step for installer
   */
	/** Progress button */
	/**
  Ne pas activer le responsive pour les grilles dans les tableaux.
  le responsive sera activé manuellement.
  */
	/** Couleur */
	/* Couleurs */
	/** Responsive */
	/** Colors */
	/* Vertical */
	/** Ligne entete */
	/* Header, footer */
	/* Couleurs */
	/** La classe parente est .wpeo--wrap */
}

.wpeo-wrap .fa,
.wpeo-wrap .fas,
.wpeo-wrap .navigation-container .unit > .unit-container .toggle-unit .icon,
.wpeo-wrap.wpdigi-installer .wpdigi-components .owl-nav .owl-prev,
.wpeo-wrap.wpdigi-installer .wpdigi-components .owl-nav .owl-next,
.wpeo-wrap .far,
.wpeo-wrap .fal,
.wpeo-wrap .unit.toggled > .unit-container .toggle-unit .icon,
.wpeo-wrap .fab {
	-moz-osx-font-smoothing: grayscale;
	-webkit-font-smoothing: antialiased;
	display: inline-block;
	font-style: normal;
	font-variant: normal;
	text-rendering: auto;
	line-height: 1;
}

.wpeo-wrap canvas {
	width: 100%;
}

.wpeo-wrap img.signature {
	width: 100%;
	max-width: 150px;
	height: 100%;
}

/*.wpeo-wrap .navigation-container, .wpeo-wrap .main-container {*/
/*	display: block;*/
/*	float: left;*/
/*	margin-top: 1em;*/
/*}*/
/**/
/*.wpeo-wrap .navigation-container {*/
/*	width: 100%;*/
/*	padding-right: 10px;*/
/*}*/

.wpeo-wrap .main-container {
	width: 100%;
	padding-left: 10px;
}

.wpeo-wrap .main-content {
	padding: 16px;
	background: #fff;
}

.wpeo-wrap .main-content:after {
	display: block;
	content: '';
	clear: both;
}

@media (max-width: 480px) {
	.wpeo-wrap .main-content {
		padding: 16px 0;
	}
}

.wpeo-wrap.wpdigi-installer .main-content {
	margin-top: 20px;
}

.wpeo-wrap .email-domain {
	float: right;
	width: 100%;
	max-width: 460px;
}

.wpeo-wrap .email-domain .form-element {
	width: 80% !important;
	float: left;
}

@media screen and (max-width: 1200px) {
	/*.wpeo-wrap .navigation-container, .wpeo-wrap .main-container {*/
	/*	float: none;*/
	/*}*/
	/*.wpeo-wrap .navigation-container {*/
	/*	width: 100%;*/
	/*	padding-right: 0;*/
	/*}*/
	.wpeo-wrap .main-container {
		width: 100%;
		margin-top: 20px;
		padding-left: 0;
	}
}

@media screen and (max-width: 680px) {
	/*.wpeo-wrap .navigation-container {*/
	/*	width: 100%;*/
	/*	padding-right: 0;*/
	/*}*/
	.wpeo-wrap .main-container .main-header .unit-header {
		width: 100%;
	}
	.wpeo-wrap .main-container .main-header .save {
		margin: 0 0 0 auto;
	}
	.wpeo-wrap .main-container .main-header .dut {
		display: none;
	}
}

@media (max-width: 480px) {
	.wpeo-wrap {
		padding: 10px 10px 10px 0;
	}
	.wpeo-wrap .main-container {
		margin-top: 0;
	}
}

.wpeo-wrap .main-header {
	background: #272a35;
	overflow: hidden;
}

.wpeo-wrap .main-header .unit-header {
	position: relative;
	display: flex;
	flex: 0 1 auto;
	height: 50px;
	transition: all 0.2s ease-out;
}

.wpeo-wrap .main-header .title {
	width: 100%;
	line-height: 50px;
	padding: 0 10px;
	color: rgba(255, 255, 255, 0.7);
	white-space: nowrap;
	overflow: hidden;
}

.wpeo-wrap .main-header .title input {
	background: none;
	border: 0;
	color: #fff;
	width: 100%;
}

.wpeo-wrap .main-header .title input:focus {
	background: #fff;
	color: rgba(0, 0, 0, 0.7);
}

.wpeo-wrap .main-header .media {
	background: #1e2129;
}

.wpeo-wrap .main-header .edit {
	margin-left: auto;
	transition: none;
}

.wpeo-wrap .main-header .edit .button-icon {
	transition: all 0.2s ease-out;
	color: rgba(255, 255, 255, 0.4);
}

.wpeo-wrap .main-header .edit:hover .button-icon {
	color: #47e58e;
}

.wpeo-wrap .main-header .save {
	opacity: 0;
	position: absolute;
	right: -5px;
	pointer-events: none;
	transition: all 0.2s ease-out;
}

.wpeo-wrap .main-header .save.active {
	opacity: 1;
	right: 0;
	pointer-events: auto;
}

.wpeo-wrap .main-header .mobile-navigation {
	display: none;
	width: 38px;
	min-width: 38px;
	line-height: 50px;
	text-align: center;
}

.wpeo-wrap .main-header .mobile-navigation .icon {
	color: rgba(255, 255, 255, 0.4);
	transition: all 0.2s ease-out;
	line-height: 1;
	font-size: 18px;
}

.wpeo-wrap .main-header .mobile-navigation:hover {
	cursor: pointer;
}

.wpeo-wrap .main-header .mobile-navigation:hover .icon {
	color: rgba(255, 255, 255, 0.8);
}

@media (max-width: 480px) {
	.wpeo-wrap .main-header .mobile-navigation {
		display: block;
	}
}

.wpeo-wrap .statistic {
	text-align: center;
}

.wpeo-wrap .statistic .number {
	width: 60px;
	height: 60px;
	color: #fff;
	font-size: 22px;
	font-weight: 700;
	text-align: center;
	padding: 0;
	margin: 0;
	line-height: 60px;
	background: #e9ad4f;
	display: block;
	border-radius: 50%;
	margin: 0 auto 10px auto;
}

.wpeo-wrap .statistic .label {
	text-transform: uppercase;
	color: rgba(0, 0, 0, 0.7);
}

.wpeo-wrap .statistic.active .number {
	background: #3495f0;
}

.wpeo-wrap .statistic.active .label {
	color: #3495f0;
}

.wpeo-wrap .digi-tools-main-container .block {
	padding: 10px;
	text-align: center;
}

.wpeo-wrap .digi-tools-main-container .block .container {
	background: #fff;
	padding: 20px;
	min-height: 220px;
}

.wpeo-wrap .digi-tools-main-container .block h3 {
	margin: 0 0 6px 0;
	text-transform: uppercase;
	width: 100%;
	text-align: center;
	font-size: 20px;
	font-weight: bold;
	color: rgba(0, 0, 0, 0.8);
}

.wpeo-wrap .digi-tools-main-container .block .wp-digi-bton-first {
	background: #3495f0;
	display: inline-block;
}

.wpeo-wrap .digi-tools-main-container .block .content {
	padding: 10px 0;
}

.wpeo-wrap .digi-tools-main-container .block input[type="file"] {
	display: none;
}

.wpeo-wrap .digi-tools-main-container .block .content {
	color: rgba(0, 0, 0, 0.4);
}

.wpeo-wrap .digi-tools-main-container .block progress {
	display: block;
	width: 80%;
	margin: 20px auto;
}

.wpeo-wrap .digi-tools-main-container .block [class^="wp-digi-bton"] {
	display: block;
	max-width: 60%;
	margin: auto;
	margin-bottom: 10px;
}

.wpeo-wrap .digi-tools-main-container .block .upload.wp-digi-bton-first {
	padding: 0 16px;
	line-height: 1.4;
}

/*.cotation {*/
/*	display: block;*/
/*	width: 50px;*/
/*	min-width: 50px;*/
/*	height: 50px;*/
/*	line-height: 50px;*/
/*	text-align: center;*/
/*	transition: all 0.2s ease-out;*/
/*	border-radius: 5px;*/
/*	margin: 2px;*/
/*}*/
/**/
/*.cotation.default-cotation:hover {*/
/*	background: rgba(0, 0, 0, 0.1);*/
/*}*/
/**/
/*.cotation[data-scale="2"], .wpeo-wrap .cotation[data-scale="3"], .wpeo-wrap .cotation[data-scale="4"] {*/
/*	color: #fff !important;*/
/*}*/
/**/
/*.cotation[data-scale="1"], .wpeo-wrap .cotation.level1 {*/
/*	background: #e2e2e2;*/
/*	color: rgba(0, 0, 0, 0.6);*/
/*}*/
/**/
/*.cotation[data-scale="1"]:hover, .wpeo-wrap .cotation.level1:hover {*/
/*	background: #cecece;*/
/*}*/
/**/
/*.cotation[data-scale="2"], .wpeo-wrap .cotation.level2 {*/
/*	background: #e9ad4f;*/
/*	color: #fff;*/
/*}*/
/**/
/*.cotation[data-scale="2"]:hover, .wpeo-wrap .cotation.level2:hover {*/
/*	background: #e49c2b;*/
/*}*/
/**/
/*.cotation[data-scale="3"], .wpeo-wrap .cotation.level3 {*/
/*	background: #e05353;*/
/*	color: #fff;*/
/*}*/
/**/
/*.cotation[data-scale="3"]:hover, .wpeo-wrap .cotation.level3:hover {*/
/*	background: #da3030;*/
/*}*/
/**/
/*.cotation[data-scale="4"], .wpeo-wrap .cotation.level4 {*/
/*	background: #2b2b2b;*/
/*	color: #fff;*/
/*}*/
/**/
/*.cotation[data-scale="4"]:hover, .wpeo-wrap .cotation.level4:hover {*/
/*	background: #171717;*/
/*}*/
/**/
/*.cotation.method {*/
/*	background: #3495f0;*/
/*	color: #fff;*/
/*}*/
/**/
/*.wpeo-wrap .cotation.method:hover {*/
/*	background: #1360c8;*/
/*}*/

/*.wpeo-wrap .wpeo-modal .cotation {*/
/*	float: left;*/
/*}*/

/*.wpeo-table.evaluation-method {*/
/*	background: none !important;*/
/*}*/
/**/
/*.wpeo-table.evaluation-method .table-row.table-header {*/
/*	background: #fff;*/
/*}*/
/**/
/*@media (max-width: 480px) {*/
/*	.wpeo-table.evaluation-method .table-row.table-header {*/
/*		display: none;*/
/*	}*/
/*}*/
/**/
/*.wpeo-table.evaluation-method {*/
/*	margin: 0;*/
/*	text-align: center;*/
/*	border: 1px solid rgba(0, 0, 0, 0.1);*/
/*	padding: 0.8em 0.4em;*/
/*	position: relative;*/
/*}*/
/**/
/*@media (max-width: 480px) {*/
/*	.wpeo-table.evaluation-method .table-cell {*/
/*		width: 100% !important;*/
/*	}*/
/*}*/
/**/
/*.wpeo-table.evaluation-method .table-cell.can-select::after {*/
/*	display: block;*/
/*	content: '';*/
/*	position: absolute;*/
/*	top: 0.4em;*/
/*	right: 0.4em;*/
/*	bottom: 0.4em;*/
/*	left: 0.4em;*/
/*	background: rgba(0, 0, 0, 0.1);*/
/*	transform: scale(0);*/
/*	opacity: 0;*/
/*	transition: all 0.2s ease-out;*/
/*}*/
/**/
/*.wpeo-table.evaluation-method .table-cell.can-select:hover {*/
/*	cursor: pointer;*/
/*	transform: scale(1);*/
/*	opacity: 1;*/
/*}*/
/**/
/*.wpeo-table.evaluation-method .table-cell.can-select:hover::after {*/
/**/
/*}*/
/**/
/*.wpeo-table.evaluation-method .table-cell.can-select.active {*/
/*	color: #fff;*/
/*	transform: scale(1) !important;*/*/
/*	opacity: 1 !important;*/
/*	background: #3495f0;*/
/*	z-index: -1;*/
/*}*/
/**/
/*.wpeo-table.evaluation-method .table-cell.can-select.active::after {*/
/**/
/*}*/
/**/
/*.wpeo-table.evaluation-method .table-row > .table-cell:first-child {*/
/*	background: #33333e;*/
/*	color: rgba(255, 255, 255, 0.8);*/
/*	margin: 0;*/
/*}*/
/**/
/*@media (max-width: 480px) {*/
/*	.wpeo-table.evaluation-method .table-row > .table-cell:first-child {*/
/*		background: #272a35;*/
/*	}*/
/*}*/
/**/
/*.wpeo-table.evaluation-method .table-row:not(.header) .table-cell:nth-of-type(2).active::after {*/
/*	background: #3495f0;*/
/*}*/
/**/
/*.wpeo-table.evaluation-method .table-row:not(.header) .table-cell:nth-of-type(3).active::after {*/
/*	background: #0f6fc9;*/
/*}*/
/**/
/*.wpeo-table.evaluation-method .table-row:not(.header) .table-cell:nth-of-type(4).active::after {*/
/*	background: #0a4781;*/
/*}*/
/**/
/*.wpeo-table.evaluation-method .table-row:not(.header) .table-cell:nth-of-type(5).active::after {*/
/*	background: #04203a;*/
/*}*/
/**/
/*.wpeo-table.evaluation-method .table-row:not(.header) .table-cell:nth-of-type(6).active::after {*/
/*	background: black;*/
/*}*/

/*.wpeo-wrap .navigation-container .society-header {*/
/*	background: #3d4152;*/
/*	display: flex;*/
/*	height: 50px;*/
/*	padding: 0 1em;*/
/*	position: relative;*/
/*}*/
/**/
/*@media (max-width: 480px) {*/
/*	.wpeo-wrap .navigation-container .society-header {*/
/*		padding: 0 0 0 1em;*/
/*	}*/
/*}*/
/**/
/*.wpeo-wrap .navigation-container .society-header:hover {*/
/*	cursor: pointer;*/
/*}*/
/**/
/*.wpeo-wrap .navigation-container .society-header .icon {*/
/*	display: inline;*/
/*	font-size: 12px;*/
/*	color: #fff;*/
/*	margin: auto 0.4em auto 0;*/
/*	line-height: 1.8;*/
/*}*/
/**/
/*.wpeo-wrap .navigation-container .society-header .title {*/
/*	color: #fff;*/
/*	font-size: 12px;*/
/*	text-transform: uppercase;*/
/*	width: 100%;*/
/*	margin-top: auto;*/
/*	margin-bottom: auto;*/
/*}*/
/**/
/*.wpeo-wrap .navigation-container .society-header > .add-container {*/
/*	top: 50% !important;*/
/*	transform: translateY(-50%);*/
/*	right: 0;*/
/*}*/
/**/
/*.wpeo-wrap .navigation-container .society-header:hover > .add-container {*/
/*	opacity: 1;*/
/*	pointer-events: all;*/
/*}*/
/**/
/*.wpeo-wrap .navigation-container .toolbar {*/
/*	background: #272a35;*/
/*	padding: 0.2em 1em;*/
/*}*/
/**/
/*@media (max-width: 480px) {*/
/*	.wpeo-wrap .navigation-container .toolbar {*/
/*		padding-left: 2.2em;*/
/*	}*/
/*}*/
/**/
/*.wpeo-wrap .navigation-container .toolbar [class*="toggle"] {*/
/*	display: inline-block;*/
/*	color: rgba(255, 255, 255, 0.4);*/
/*	margin-right: 0.4em;*/
/*	transition: all 0.2s ease-out;*/
/*}*/
/**/
/*.wpeo-wrap .navigation-container .toolbar [class*="toggle"]:hover {*/
/*	color: rgba(255, 255, 255, 0.8);*/
/*	cursor: pointer;*/
/*}*/
/**/
/*@media (max-width: 480px) {*/
/*	.wpeo-wrap .navigation-container .toolbar [class*="toggle"] .icon {*/
/*		font-size: 24px;*/
/*		padding: 4px;*/
/*	}*/
/*}*/
/**/
/*.wpeo-wrap .navigation-container .workunit-list {*/
/*	background: #272a35;*/
/*	padding: 0 0 1em 0;*/
/*	margin: 0;*/
/*}*/
/**/
/*.wpeo-wrap .navigation-container .workunit-list .unit {*/
/*	margin-bottom: 0;*/
/*}*/
/**/
/*.wpeo-wrap .navigation-container .workunit-list .unit > .unit-container {*/
/*	display: flex;*/
/*	position: relative;*/
/*}*/

/*.wpeo-wrap .unit.active > .unit-container:hover .name {*/
/*	color: #fff !important;*/
/*}*/
/**/
/*.wpeo-wrap .unit.active > .unit-container .ref {*/
/*	color: rgba(255, 255, 255, 0.6) !important;*/
/*}*/

/*.wpeo-wrap .unit.active > .unit-container .title {*/
/*	background: #3495f0;*/
/*}*/

/*.wpeo-wrap .unit.active > .unit-container .add-container .wpeo-button {*/
/*	background: #1360c8;*/
/*	border-color: #1360c8;*/
/*}*/

/*.wpeo-wrap .unit.toggled > .sub-list {*/
/*	display: block !important;*/
/*}*/
/**/
/*.wpeo-wrap .unit.toggled > .unit-container .toggle-unit .icon::before {*/
/*	font-family: "Font Awesome 5 Free", "Font Awesome 5 Pro";*/
/*	content: "\f107" !important;*/
/*}*/

/*.wpeo-wrap .navigation-container .unit > .unit-container {*/
/*	padding: 4px 0 0 0;*/
/*	height: 54px;*/
/*}*/
/**/
/*.wpeo-wrap .navigation-container .unit > .unit-container:hover .title .name {*/
/*	color: #3495f0;*/
/*}*/
/**/
/*.wpeo-wrap .navigation-container .unit > .unit-container .toggle-unit {*/
/*	color: rgba(255, 255, 255, 0.4);*/
/*	line-height: 50px;*/
/*	font-size: 22px;*/
/*	min-width: 30px;*/
/*	width: 30px;*/
/*	text-align: center;*/
/*	transition: all 0.2s ease-out;*/
/*}*/
/**/
/*.wpeo-wrap .navigation-container .unit > .unit-container .toggle-unit:hover {*/
/*	cursor: pointer;*/
/*	color: rgba(255, 255, 255, 0.8);*/
/*}*/
/**/
/*.wpeo-wrap .navigation-container .unit > .unit-container .toggle-unit .icon:before {*/
/*	font-family: "Font Awesome 5 Free", "Font Awesome 5 Pro";*/
/*	content: "\f105";*/
/*}*/
/**/
/*.wpeo-wrap .navigation-container .unit > .unit-container .spacer {*/
/*	min-width: 30px;*/
/*	width: 30px;*/
/*}*/
/**/
/*.wpeo-wrap .navigation-container .unit > .unit-container .media.no-file {*/
/*	background: #1a1c23;*/
/*}*/
/**/
/*.wpeo-wrap .navigation-container .unit > .unit-container .media.no-file .button-add {*/
/*	color: rgba(255, 255, 255, 0.2);*/
/*}*/
/**/
/*.wpeo-wrap .navigation-container .unit > .unit-container .media .default-icon-container .svg-inline--fa {*/
/*	color: rgba(255, 255, 255, 0.4);*/
/*}*/
/**/
/*.wpeo-wrap .navigation-container .unit > .unit-container .title {*/
/*	width: 100%;*/
/*	padding-left: 1em;*/
/*	display: flex;*/
/*}*/
/**/
/*.wpeo-wrap .navigation-container .unit > .unit-container .title .title-container {*/
/*	margin: auto 0;*/
/*}*/
/**/
/*.wpeo-wrap .navigation-container .unit > .unit-container .title:hover {*/
/*	cursor: pointer;*/
/*}*/
/**/
/*.wpeo-wrap .navigation-container .unit > .unit-container .title .ref, .wpeo-wrap .navigation-container .unit > .unit-container .title .name {*/
/*	display: block;*/
/*	line-height: 1;*/
/*	transition: all 0.2s ease-out;*/
/*}*/
/**/
/*.wpeo-wrap .navigation-container .unit > .unit-container .title .ref {*/
/*	font-size: 10px;*/
/*	color: #3495f0;*/
/*	text-transform: uppercase;*/
/*	font-weight: 800;*/
/*}*/
/**/
/*.wpeo-wrap .navigation-container .unit > .unit-container .title .name {*/
/*	font-size: 16px;*/
/*	color: #fff;*/
/*}*/
/**/
/*.wpeo-wrap .navigation-container .unit > .unit-container:hover > .add-container {*/
/*	opacity: 1;*/
/*	pointer-events: all;*/
/*}*/
/**/
/*.wpeo-wrap .navigation-container .add-container {*/
/*	position: absolute;*/
/*	top: 4px;*/
/*	right: 0;*/
/*	opacity: 0;*/
/*	pointer-events: none;*/
/*	transition: all 0.2s ease-out;*/
/*}*/
/**/
/*.wpeo-wrap .navigation-container .add-container .wpeo-button {*/
/*	float: left;*/
/*	margin-left: 2px;*/
/*	display: flex;*/
/*}*/
/**/
/*.wpeo-wrap .navigation-container .add-container .wpeo-button .button-icon {*/
/*	margin: auto;*/
/*	font-size: 20px;*/
/*	line-height: 1;*/
/*}*/
/**/
/*.wpeo-wrap .navigation-container .add-container .wpeo-button .button-add {*/
/*	position: absolute;*/
/*	color: rgba(255, 255, 255, 0.6);*/
/*	transition: all 0.2s ease-out;*/
/*	top: 6px;*/
/*	right: 6px;*/
/*	font-size: 12px;*/
/*}*/
/**/
/*.wpeo-wrap .navigation-container .add-container .wpeo-button:hover .button-add {*/
/*	-webkit-animation-name: noFileAdd;*/
/*	animation-name: noFileAdd;*/
/*}*/
/**/
/*@media (max-width: 960px) {*/
/*	.wpeo-wrap .navigation-container .add-container {*/
/*		display: none;*/
/*	}*/
/*}*/
/**/
/*.wpeo-wrap .navigation-container .unit.new {*/
/*	background: #fff;*/
/*	height: 50px;*/
/*	display: none;*/
/*}*/
/**/
/*.wpeo-wrap .navigation-container .unit.new.active {*/
/*	display: flex;*/
/*}*/
/**/
/*.wpeo-wrap .navigation-container .unit.new .placeholder-icon {*/
/*	margin: auto;*/
/*	min-width: 40px;*/
/*	color: rgba(0, 0, 0, 0.4);*/
/*}*/
/**/
/*.wpeo-wrap .navigation-container .unit.new .unit-label {*/
/*	height: 50px;*/
/*	width: 100%;*/
/*	margin: 0;*/
/*	padding: 1em 0.2em;*/
/*	border: 0;*/
/*}*/
/**/
/*.wpeo-wrap .navigation-container .unit.new .unit-label:focus {*/
/*	outline: none;*/
/*}*/
/**/
/*.wpeo-wrap .navigation-container .unit.new .wpeo-button {*/
/*	min-width: 50px;*/
/*}*/
/**/
/*.wpeo-wrap .navigation-container .unit > .sub-list {*/
/*	padding-left: 0.6em;*/
/*	display: none;*/
/*}*/
/**/
/*.wpeo-wrap .navigation-container .unit > .sub-list .unit {*/
/*	position: relative;*/
/*	margin-left: 0.6em;*/
/*}*/
/**/
/*.wpeo-wrap .navigation-container .unit > .sub-list .unit:before {*/
/*	display: block;*/
/*	position: absolute;*/
/*	content: '';*/
/*	width: 1px;*/
/*	height: 100%;*/
/*	left: 0;*/
/*	top: 0;*/
/*	background: #50556a;*/
/*}*/
/**/
/*.wpeo-wrap .navigation-container .unit > .sub-list > .unit:last-child:before {*/
/*	height: 30px;*/
/*}*/
/**/
/*.wpeo-wrap .navigation-container .unit > .sub-list .spacer, .wpeo-wrap .navigation-container .unit > .sub-list .toggle-unit {*/
/*	position: relative;*/
/*}*/
/**/
/*.wpeo-wrap .navigation-container .unit > .sub-list .spacer:before, .wpeo-wrap .navigation-container .unit > .sub-list .toggle-unit:before {*/
/*	display: block;*/
/*	position: absolute;*/
/*	content: '';*/
/*	height: 1px;*/
/*	left: 0;*/
/*	right: 10px;*/
/*	top: 25px;*/
/*	background: #50556a;*/
/*}*/
/**/
/*.wpeo-wrap .navigation-container .unit > .sub-list .toggle-unit .icon {*/
/*	margin-left: 8px;*/
/*}*/
/**/
/*.wpeo-wrap .navigation-container .unit > .sub-list .toggle-unit::before {*/
/*	right: 20px;*/
/*}*/
/**/
/*.wpeo-wrap .navigation-container .mobile-add-container, .wpeo-wrap .navigation-container .close-popup {*/
/*	display: none;*/
/*	color: rgba(255, 255, 255, 0.4);*/
/*	line-height: 50px;*/
/*	min-width: 32px;*/
/*	width: 32px;*/
/*	text-align: center;*/
/*	transition: all 0.2s ease-out;*/
/*}*/
/**/
/*@media (max-width: 960px) {*/
/*	.wpeo-wrap .navigation-container .mobile-add-container, .wpeo-wrap .navigation-container .close-popup {*/
/*		display: block;*/
/*	}*/
/*	.wpeo-wrap .navigation-container .mobile-add-container .content, .wpeo-wrap .navigation-container .close-popup .content {*/
/*		left: inherit !important;*/
/*	}*/
/*}*/
/**/
/*.wpeo-wrap .navigation-container .mobile-add-container:hover {*/
/*	cursor: pointer;*/
/*}*/
/**/
/*.wpeo-wrap .navigation-container .mobile-add-container:hover .action {*/
/*	color: #fff;*/
/*}*/
/**/
/*.wpeo-wrap .navigation-container .mobile-add-container .dropdown-toggle {*/
/*	width: 100%;*/
/*}*/
/**/
/*.wpeo-wrap .navigation-container .mobile-add-container .dropdown-content {*/
/*	text-align: left;*/
/*	line-height: 1.6;*/
/*}*/
/**/
/*.wpeo-wrap .navigation-container .mobile-add-container .dropdown-content .icon {*/
/*	color: rgba(0, 0, 0, 0.5);*/
/*	margin-right: 0.2em;*/
/*	font-size: 16px;*/
/*}*/
/**/
/*.wpeo-wrap .navigation-container .mobile-add-container .action {*/
/*	font-size: 18px;*/
/*	transition: all 0.2s ease-out;*/
/*}*/
/**/
/*.wpeo-wrap .navigation-container .close-popup {*/
/*	display: none;*/
/*}*/
/**/
/*.wpeo-wrap .navigation-container .close-popup .icon {*/
/*	transition: all 0.2s ease-out;*/
/*	font-size: 16px;*/
/*}*/
/**/
/*.wpeo-wrap .navigation-container .close-popup:hover .icon {*/
/*	color: #fff;*/
/*}*/
/**/
/*@media (max-width: 480px) {*/
/*	.wpeo-wrap .navigation-container .close-popup {*/
/*		display: block;*/
/*	}*/
/*}*/
/**/
/*@media (max-width: 480px) {*/
/*	.wpeo-wrap .navigation-container {*/
/*		opacity: 0;*/
/*		pointer-events: none;*/
/*		transform: scale(0.95);*/
/*		position: fixed;*/
/*		top: 0;*/
/*		left: 0;*/
/*		width: 100%;*/
/*		height: 100%;*/
/*		z-index: 99999;*/
/*		background: #272a35;*/
/*		transition: 0.2s ease-out;*/
/*		overflow-y: auto;*/
/*	}*/
/*	.wpeo-wrap .navigation-container.active {*/
/*		opacity: 1;*/
/*		pointer-events: all;*/
/*		transform: scale(1);*/
/*	}*/
/*}*/
/**/
/*.wpeo-wrap .wp-core-ui .navigation-container .button-color__secondary:focus,*/
/*.wpeo-wrap .wp-core-ui .navigation-container .button-color__secondary:hover,*/
/*.wpeo-wrap .wp-core-ui .navigation-container .button.focus,*/
/*.wpeo-wrap .wp-core-ui .navigation-container .button.hover,*/
/*.wpeo-wrap .wp-core-ui .navigation-container .button:focus,*/
/*.wpeo-wrap .wp-core-ui .navigation-container .button:hover {*/
/*	color: #fff;*/
/*}*/

.wpeo-wrap .wpeo-table.table-flex {
	border: 1px solid rgba(0, 0, 0, 0.1);
	background: #fff;
}

.wpeo-wrap .wpeo-table.table-flex .table-row.table-header {
	background: #33333e;
	border-bottom: 2px solid rgba(0, 0, 0, 0.3);
}

.wpeo-wrap .wpeo-table.table-flex .table-row.table-header .table-cell {
	color: rgba(255, 255, 255, 0.8);
	text-align: center;
}

.wpeo-wrap .wpeo-table.table-flex .table-row:not(.table-header):nth-of-type(odd) {
	background: rgba(0, 0, 0, 0.03);
}

.wpeo-wrap .wpeo-table.table-flex .table-cell {
	box-sizing: content-box;
}

.wpeo-wrap .wpeo-table.table-flex .table-button-edit {
	color: #3495f0;
}

.wpeo-wrap .wpeo-table.table-flex input[type=date],
.wpeo-wrap .wpeo-table.table-flex input[type=datetime-local],
.wpeo-wrap .wpeo-table.table-flex input[type=datetime],
.wpeo-wrap .wpeo-table.table-flex input[type=email],
.wpeo-wrap .wpeo-table.table-flex input[type=month],
.wpeo-wrap .wpeo-table.table-flex input[type=number],
.wpeo-wrap .wpeo-table.table-flex input[type=password],
.wpeo-wrap .wpeo-table.table-flex input[type=search],
.wpeo-wrap .wpeo-table.table-flex input[type=tel],
.wpeo-wrap .wpeo-table.table-flex input[type=text],
.wpeo-wrap .wpeo-table.table-flex input[type=time],
.wpeo-wrap .wpeo-table.table-flex input[type=url],
.wpeo-wrap .wpeo-table.table-flex input[type=week],
.wpeo-wrap .wpeo-table.table-flex select,
.wpeo-wrap .wpeo-table.table-flex textarea {
	width: 100%;
}

.wpeo-wrap .wpeo-table.table-flex input[type="checkbox"],
.wpeo-wrap .wpeo-table.table-flex input[type="radio"] {
	width: auto;
}

.wpeo-wrap .wpeo-table.table-flex .wpeo-autocomplete .autocomplete-label {
	padding: 0 1em;
}

.wpeo-wrap .wpeo-table .table-row-advanced {
	background: #fff;
}

.wpeo-wrap .wpeo-table .table-row-advanced:nth-of-type(odd) {
	background: rgba(0, 0, 0, 0.03);
}

.wpeo-wrap .wpeo-table .table-row-advanced .table-row {
	background: none !important;
}

.wpeo-wrap .wpeo-table .table-row-advanced .advanced {
	padding: 1em;
}

.wpeo-wrap .wpeo-table .table-row-advanced .advanced .form-element {
	margin: 0.5em 0;
}

.wpeo-wrap .wpeo-table .table-row-advanced .advanced .form-element .form-element {
	margin: 0;
}

.wpeo-wrap .wpeo-table .table-row-advanced .advanced .wpeo-autocomplete .autocomplete-search-input {
	padding: 1em;
}

.wpeo-wrap .wpeo-table .table-row-advanced .advanced .comment-container {
	width: 100%;
}

.wpeo-wrap .wpeo-table .table-row-advanced .advanced .comment-container textarea {
	background: #ececec;
	border-radius: 0;
}

.wpeo-wrap .wpeo-table .table-row-advanced .advanced .list-stopping-day .comment {
	margin: 0.5em 0;
}

.wpeo-wrap .wpeo-table .table-row-advanced .advanced .list-stopping-day input[type="text"] {
	padding: 0.6em;
	line-height: 1;
	background: #ececec;
	box-shadow: none;
	transition: all 0.2s ease-out;
}

.wpeo-wrap .wpeo-table .table-row-advanced .advanced .list-stopping-day .wpeo-media, .wpeo-wrap .wpeo-table .table-row-advanced .advanced .list-stopping-day .media {
	margin: 0 1em;
}

.wpeo-wrap .wpeo-table .table-row-advanced .advanced .list-stopping-day .wpeo-button {
	margin: auto 0;
}

.wpeo-wrap .wpeo-table .table-row-advanced .advanced .table-action {
	text-align: right;
}

.wpeo-wrap .wpeo-table .avatar {
	display: inline-block;
	width: 50px;
	height: 50px;
	min-width: 50px;
	text-align: center;
}

.wpeo-wrap .wpeo-table .avatar span {
	line-height: 50px;
	color: #fff !important;
	font-size: 16px;
	font-weight: 300;
	text-transform: uppercase;
}

.wpeo-wrap .wpeo-table.table-flex .comment-container .comment {
	display: flex;
	margin-top: 0.2em;
}

.wpeo-wrap .wpeo-table.table-flex .comment-container .comment:first-child {
	margin-top: 0;
}

.wpeo-wrap .wpeo-table.table-flex .avatar {
	border-radius: 50%;
	width: 30px;
	height: 30px;
	min-width: 30px;
	text-align: center;
	color: #fff;
}

.wpeo-wrap .wpeo-table.table-flex .avatar span {
	line-height: 30px;
}

.wpeo-wrap .wpeo-table.table-flex .comment-container .comment .date {
	font-weight: 700;
	width: 84px;
	margin: auto 4px;
	font-size: 13px;
}

.wpeo-wrap .wpeo-table.table-flex .comment-container .comment .content {
	white-space: normal;
	margin: auto 0;
}

.wpeo-wrap .wpeo-table.table-flex .comment-container .comment .delete, .wpeo-wrap .wpeo-table.table-flex .comment-container .comment .add {
	width: 30px;
	height: 30px;
	line-height: 24px;
	min-width: 30px;
	margin-left: 4px;
}

.wpeo-wrap .wpeo-table.table-flex .comment-container .comment .delete:hover {
	color: #e05353;
}

.wpeo-wrap .wpeo-table.table-flex .comment-container .comment .add {
	color: #fff;
}

.wpeo-wrap .wpeo-table.table-flex .comment-container .comment .mysql-date {
	display: none;
}

.wpeo-wrap .wpeo-table.table-flex .comment-container .comment .group-date {
	margin: auto 4px;
}

.wpeo-wrap .wpeo-table.table-flex .comment-container .comment .group-date input.date {
	width: 84px;
}

.wpeo-wrap .wpeo-table.table-flex .comment-container .comment input.date, .wpeo-wrap .wpeo-table.table-flex .comment-container .comment textarea {
	width: 100%;
	margin: 0;
	padding: 0.2em;
}

.wpeo-wrap .wpeo-table.table-flex .comment-container .comment.new {
	margin-bottom: 0.5em;
	padding-bottom: 0.5em;
	border-bottom: 1px solid rgba(0, 0, 0, 0.1);
}

.wpeo-wrap .dropdown-add-button {
	display: block;
	width: 50px;
	min-width: 50px;
	height: 50px;
	line-height: 50px;
	position: relative;
	text-align: center;
	transition: all 0.2s ease-out;
	padding: 0 !important;
}

.wpeo-wrap .dropdown-add-button .icon-add {
	position: absolute;
	top: 12px;
	right: 8px;
	font-size: 12px;
	color: rgba(0, 0, 0, 0.3);
	transition: color 0.2s ease-out;
}

.wpeo-wrap .dropdown-add-button .icon {
	font-size: 16px;
}

.wpeo-wrap .dropdown-add-button:hover {
	cursor: pointer;
	background: rgba(0, 0, 0, 0.1);
}

.wpeo-wrap .dropdown-add-button:hover .icon-add {
	color: #3495f0;
}

@media (max-width: 770px) {
	.wpeo-wrap .wpeo-table.table-risk {
		border: 0;
	}
	.wpeo-wrap .wpeo-table.table-risk .table-row.table-header {
		display: none;
	}
	.wpeo-wrap .wpeo-table.table-risk .table-row:not(.table-header) {
		flex-wrap: wrap;
		flex-direction: row !important;
		background: #0000c1 !important;
		margin-bottom: 2em;
		border: 1px solid rgba(0, 0, 0, 0.1);
	}
	.wpeo-wrap .wpeo-table.table-risk .table-row:not(.table-header) .table-cell {
		max-width: none !important;
		min-width: 0 !important;
		box-sizing: border-box;
	}
	.wpeo-wrap .wpeo-table.table-risk .table-row:not(.table-header) .cell-reference {
		order: 1;
	}
	.wpeo-wrap .wpeo-table.table-risk .table-row:not(.table-header) .cell-risk {
		order: 3;
	}
	.wpeo-wrap .wpeo-table.table-risk .table-row:not(.table-header) .cell-cotation {
		order: 4;
	}
	.wpeo-wrap .wpeo-table.table-risk .table-row:not(.table-header) .cell-photo {
		order: 5;
	}
	.wpeo-wrap .wpeo-table.table-risk .table-row:not(.table-header) .cell-comment {
		order: 6;
	}
	.wpeo-wrap .wpeo-table.table-risk .table-row:not(.table-header) .cell-action {
		order: 2;
	}
	.wpeo-wrap .wpeo-table.table-risk .table-row:not(.table-header) .cell-reference {
		width: 30%;
	}
	.wpeo-wrap .wpeo-table.table-risk .table-row:not(.table-header) .cell-action {
		width: 70%;
	}
	.wpeo-wrap .wpeo-table.table-risk .table-row:not(.table-header) .cell-action .wpeo-gridlayout {
		display: flex !important;
	}
	.wpeo-wrap .wpeo-table.table-risk .table-row:not(.table-header) .cell-action .wpeo-gridlayout .wpeo-button:first-child {
		margin-left: auto;
	}
	.wpeo-wrap .wpeo-table.table-risk .table-row:not(.table-header) .cell-risk, .wpeo-wrap .wpeo-table.table-risk .table-row:not(.table-header) .cell-cotation, .wpeo-wrap .wpeo-table.table-risk .table-row:not(.table-header) .cell-photo {
		width: 33.33333%;
		text-align: center;
	}
	.wpeo-wrap .wpeo-table.table-risk .table-row:not(.table-header) .cell-risk .cotation, .wpeo-wrap .wpeo-table.table-risk .table-row:not(.table-header) .cell-risk .media, .wpeo-wrap .wpeo-table.table-risk .table-row:not(.table-header) .cell-cotation .cotation, .wpeo-wrap .wpeo-table.table-risk .table-row:not(.table-header) .cell-cotation .media, .wpeo-wrap .wpeo-table.table-risk .table-row:not(.table-header) .cell-photo .cotation, .wpeo-wrap .wpeo-table.table-risk .table-row:not(.table-header) .cell-photo .media {
		margin: auto;
	}
	.wpeo-wrap .wpeo-table.table-risk .table-row:not(.table-header) .cell-risk::before, .wpeo-wrap .wpeo-table.table-risk .table-row:not(.table-header) .cell-cotation::before, .wpeo-wrap .wpeo-table.table-risk .table-row:not(.table-header) .cell-photo::before {
		display: block;
		content: attr(data-title);
		color: rgba(0, 0, 0, 0.6);
		margin-bottom: 0.6em;
	}
	.wpeo-wrap .wpeo-table.table-risk .table-row.edit .cell-reference {
		width: 100%;
	}
	.wpeo-wrap .wpeo-table.table-risk .table-row.edit .cell-action {
		order: 10;
	}
}

.wpeo-wrap .table {
	width: 100%;
	border: 1px solid rgba(0, 0, 0, 0.1);
}

.wpeo-wrap .table td, .wpeo-wrap .table th, .wpeo-wrap .table tr {
	height: 50px;
	text-align: left;
	vertical-align: middle;
	white-space: nowrap;
}

.wpeo-wrap .table > thead {
	background: #272a35;
	color: #fff;
}

.wpeo-wrap .table > thead th {
	font-weight: 600;
}

.wpeo-wrap .table > thead tr {
	background: #272a35 !important;
}

.wpeo-wrap .table > thead .icon {
	padding-right: 4px;
}

.wpeo-wrap .table > thead input {
	color: rgba(0, 0, 0, 0.8);
}

.wpeo-wrap .table > tbody tr:nth-child(odd) {
	background: #fff;
}

.wpeo-wrap .table > tbody tr:nth-child(even) {
	background: rgba(0, 0, 0, 0.1);
}

.wpeo-wrap .table .row-title, .wpeo-wrap .table .row-subtitle {
	display: block;
}

.wpeo-wrap .table .row-title {
	font-size: 18px !important;
	font-weight: 400;
}

.wpeo-wrap .table .media {
	background: rgba(0, 0, 0, 0.1);
}

.wpeo-wrap .table .media.no-file .add {
	color: rgba(0, 0, 0, 0.6);
}

.wpeo-wrap .table .media .default-image {
	color: rgba(0, 0, 0, 0.3);
}

.wpeo-wrap .table strong {
	font-weight: 700;
	color: #3495f0;
}

.wpeo-wrap .table .cotation-container.tooltip:hover {
	cursor: pointer;
}

.wpeo-wrap .table .cotation-container .dropdown-content {
	background: #2b2b2b;
	border: 4px solid #2b2b2b;
	width: auto !important;
}

.wpeo-wrap .table .wpeo-dropdown .dropdown-toggle {
	font-size: 14px;
	line-height: 44px;
	padding: 0 2px;
	color: rgba(0, 0, 0, 0.8);
}

.wpeo-wrap .table .wpeo-dropdown .dropdown-toggle [data-icon] {
	margin-left: 2px;
}

.wpeo-wrap .table .wpeo-dropdown .dropdown-toggle img {
	float: left;
	max-width: 44px;
}

.wpeo-wrap .table .wpeo-dropdown .wpeo-button:hover {
	box-shadow: inset 0 -3em rgba(0, 0, 0, 0.1);
}

.wpeo-wrap .table .user {
	background: none;
	color: rgba(0, 0, 0, 0.7);
	font-weight: 700;
}

.wpeo-wrap .table .comment-container {
	padding-top: 6px;
	padding-bottom: 6px;
}

.wpeo-wrap .table .comment-container .avatar {
	border-radius: 50%;
	width: 30px;
	height: 30px;
	min-width: 30px;
}

.wpeo-wrap .table .comment-container .avatar span {
	line-height: 30px;
}

.wpeo-wrap .table .comment-container .comment {
	display: flex;
	flex: 0 1 auto;
	line-height: 1.8;
	margin-top: 4px;
}

.wpeo-wrap .table .comment-container .comment input, .wpeo-wrap .table .comment-container .comment textarea {
	height: 28px;
}

.wpeo-wrap .table .comment-container .comment > * {
	margin: auto 0;
}

.wpeo-wrap .table .comment-container .comment .date {
	font-weight: 700;
	width: 84px;
	margin: auto 4px;
	font-size: 13px;
}

.wpeo-wrap .table .comment-container .comment .content {
	white-space: normal;
	line-height: 1.3;
}

.wpeo-wrap .table .comment-container .comment input {
	color: rgba(0, 0, 0, 0.7);
}

.wpeo-wrap .table .comment-container .comment .delete, .wpeo-wrap .table .comment-container .comment .add {
	width: 30px;
	height: 30px;
	line-height: 24px;
	min-width: 30px;
}

.wpeo-wrap .table .comment-container .comment .delete i, .wpeo-wrap .table .comment-container .comment .add i {
	transition: all 0.2s ease-out;
}

.wpeo-wrap .table .comment-container .comment .delete {
	margin-left: 4px;
}

.wpeo-wrap .table .comment-container .comment .delete:hover {
	color: #e05353;
}

.wpeo-wrap .table .comment-container .comment .add {
	color: #fff;
}

.wpeo-wrap .table .comment-container .comment ~ .new.comment {
	padding-top: 4px;
	border-top: 1px solid rgba(0, 0, 0, 0.1);
}

.wpeo-wrap .table .comment-container .new.comment {
	line-height: 2;
}

.wpeo-wrap .table .comment-container .new.comment .date {
	font-weight: 400;
}

.wpeo-wrap .table .comment-container .new.comment .add {
	margin-left: 4px;
	background: #3495f0;
}

.wpeo-wrap .table .comment-container .new.comment .add .svg-inline--fa {
	color: #fff !important;
}

.wpeo-wrap .table .comment-container .new.comment .add:hover {
	background: #1360c8;
}

.wpeo-wrap .table .action {
	flex-wrap: nowrap;
}

.wpeo-wrap .table .action .wpeo-button .button-icon {
	color: rgba(0, 0, 0, 0.6);
}

.wpeo-wrap .table .action .task:hover .button-icon {
	color: rgba(0, 0, 0, 0.6);
}

.wpeo-wrap .table .action .edit:hover .button-icon {
	color: #3495f0;
}

.wpeo-wrap .table .action .delete {
	background: rgba(0, 0, 0, 0.05);
}

.wpeo-wrap .table .action .delete:hover .button-icon {
	color: #e05353;
}

.wpeo-wrap .table .action .add, .wpeo-wrap .table .action .save {
	margin-left: auto;
}

.wpeo-wrap .table .action .add .button-icon, .wpeo-wrap .table .action .save .button-icon {
	color: #fff !important;
}

.wpeo-wrap .table .avatar {
	width: 50px;
	height: 50px;
	min-width: 50px;
	text-align: center;
}

.wpeo-wrap .table .avatar span {
	line-height: 50px;
	color: #fff !important;
	font-size: 16px;
	font-weight: 300;
	text-transform: uppercase;
}

.wpeo-wrap .table input.affect {
	width: 50px;
}

.wpeo-wrap .table tfoot {
	border-top: 1px solid rgba(0, 0, 0, 0.05);
}

.wpeo-wrap .table input[type="text"], .wpeo-wrap .table textarea {
	width: 100%;
}

.wpeo-wrap .table td.w50, .wpeo-wrap .table th.w50 {
	width: 50px;
}

.wpeo-wrap .table td.w70, .wpeo-wrap .table th.w70 {
	width: 70px;
}

.wpeo-wrap .table td.w100, .wpeo-wrap .table th.w100 {
	width: 100px;
}

.wpeo-wrap .table td.wm130, .wpeo-wrap .table th.wm130 {
	min-width: 130px;
}

.wpeo-wrap .table td.wm40, .wpeo-wrap .table th.wm40 {
	min-width: 40px;
}

.wpeo-wrap .table td.wmax70 {
	max-width: 70px;
}

.wpeo-wrap .table td.w150, .wpeo-wrap .table th.w150 {
	width: 150px;
}

.wpeo-wrap .table td.full, .wpeo-wrap .table th.full {
	width: 100%;
}

.wpeo-wrap .table td.full input[type="text"], .wpeo-wrap .table td.full textarea, .wpeo-wrap .table th.full input[type="text"], .wpeo-wrap .table th.full textarea {
	width: 100%;
}

.wpeo-wrap .table td.padding, .wpeo-wrap .table th.padding {
	padding-left: 8px;
	padding-right: 8px;
}

.wpeo-wrap .table tr.edit .categorie-container:hover .action {
	background: rgba(0, 0, 0, 0.1);
}

.wpeo-wrap .table tr.edit .categorie-container:hover .action:hover {
	cursor: pointer;
}

.wpeo-wrap .table tr.edit .categorie-container:hover .action span, .wpeo-wrap .table tr.edit .categorie-container:hover .action .icon {
	color: #000;
}

.wpeo-wrap .table tr.edit .categorie-container:hover .action .icon {
	-webkit-animation-duration: 0.6s;
	animation-duration: 0.6s;
	-webkit-animation-name: downAndUp;
	animation-name: downAndUp;
}

.wpeo-wrap .table tr.edit .action {
	text-align: right;
}

.wpeo-wrap .table tr:not(.edit) .cotation {
	pointer-events: none;
}

@media screen and (max-width: 1280px) {
	.wpeo-wrap th, .wpeo-wrap tr, .wpeo-wrap td {
		padding: 0 !important;
	}
}

@media (max-width: 680px) {
	.wpeo-wrap .table.risk {
		display: block;
		width: 100% !important;
		height: auto !important;
	}
	.wpeo-wrap .table.risk tr, .wpeo-wrap .table.risk thead, .wpeo-wrap .table.risk tbody, .wpeo-wrap .table.risk tfoot, .wpeo-wrap .table.risk td, .wpeo-wrap .table.risk th {
		display: block;
		width: 100% !important;
		height: auto !important;
	}
	.wpeo-wrap .table.risk > thead {
		display: none;
	}
	.wpeo-wrap .table.risk .risk-row {
		display: flex;
		flex-wrap: wrap;
		padding: 1em !important;
	}
	.wpeo-wrap .table.risk .risk-row > td {
		width: 100%;
	}
	.wpeo-wrap .table.risk .risk-row > td:nth-of-type(1) {
		width: 40% !important;
		display: flex;
		padding: 1em 0 !important;
	}
	.wpeo-wrap .table.risk .risk-row > td:nth-of-type(1) > span {
		width: 30%;
		font-size: 10px;
	}
	.wpeo-wrap .table.risk .risk-row > td:nth-of-type(2), .wpeo-wrap .table.risk .risk-row > td:nth-of-type(3), .wpeo-wrap .table.risk .risk-row > td:nth-of-type(4) {
		width: 33.333333% !important;
		max-width: 33.333333%;
		padding-top: 0.4em !important;
		text-align: center;
		min-width: 0;
	}
	.wpeo-wrap .table.risk .risk-row > td:nth-of-type(2) .cotation, .wpeo-wrap .table.risk .risk-row > td:nth-of-type(2) .media, .wpeo-wrap .table.risk .risk-row > td:nth-of-type(3) .cotation, .wpeo-wrap .table.risk .risk-row > td:nth-of-type(3) .media, .wpeo-wrap .table.risk .risk-row > td:nth-of-type(4) .cotation, .wpeo-wrap .table.risk .risk-row > td:nth-of-type(4) .media {
		margin: auto;
	}
	.wpeo-wrap .table.risk .risk-row > td:nth-of-type(2) .categorie-container .action, .wpeo-wrap .table.risk .risk-row > td:nth-of-type(3) .categorie-container .action, .wpeo-wrap .table.risk .risk-row > td:nth-of-type(4) .categorie-container .action {
		text-align: center;
	}
	.wpeo-wrap .table.risk .risk-row > td:nth-of-type(2) .categorie-container .tooltip, .wpeo-wrap .table.risk .risk-row > td:nth-of-type(2) .categorie-container .tooltip img, .wpeo-wrap .table.risk .risk-row > td:nth-of-type(3) .categorie-container .tooltip, .wpeo-wrap .table.risk .risk-row > td:nth-of-type(3) .categorie-container .tooltip img, .wpeo-wrap .table.risk .risk-row > td:nth-of-type(4) .categorie-container .tooltip, .wpeo-wrap .table.risk .risk-row > td:nth-of-type(4) .categorie-container .tooltip img {
		display: inline-block;
	}
	.wpeo-wrap .table.risk .risk-row > td:nth-of-type(2) .categorie-container .content, .wpeo-wrap .table.risk .risk-row > td:nth-of-type(3) .categorie-container .content, .wpeo-wrap .table.risk .risk-row > td:nth-of-type(4) .categorie-container .content {
		width: 230px;
	}
	.wpeo-wrap .table.risk .risk-row > td:nth-of-type(2):before, .wpeo-wrap .table.risk .risk-row > td:nth-of-type(3):before, .wpeo-wrap .table.risk .risk-row > td:nth-of-type(4):before {
		display: block;
		content: attr(data-title);
		color: rgba(0, 0, 0, 0.4);
		padding-bottom: 0.4em;
	}
	.wpeo-wrap .table.risk .risk-row > td:nth-of-type(6) {
		width: 60% !important;
		margin: auto 0;
	}
	.wpeo-wrap .table.risk .risk-row > td:nth-of-type(6) .action {
		display: block !important;
		text-align: right;
	}
	.wpeo-wrap .table.risk .risk-row > td:nth-of-type(6) .wpeo-button {
		width: 30px !important;
		border-radius: 50%;
		min-width: 30px;
		background: #ececec;
		padding: 0 !important;
		height: 30px !important;
		display: inline-block;
		text-align: center;
		line-height: 24px;
		font-size: 14px;
	}
	.wpeo-wrap .table.risk .risk-row > td:nth-of-type(6) .wpeo-button .button-icon {
		line-height: 26px !important;
	}
	.wpeo-wrap .table.risk .risk-row > td:nth-of-type(6) .wpeo-button.button-green {
		background: #47e58e;
	}
	.wpeo-wrap .table.risk .risk-row > td:nth-of-type(6) .wpeo-button.button-main, .wpeo-wrap .table.risk .risk-row > td:nth-of-type(6) .wpeo-button.add {
		background: #3495f0;
	}
	.wpeo-wrap .table.risk .risk-row .comment-container .comment {
		flex-wrap: wrap;
		position: relative;
		margin-top: 10px;
	}
	.wpeo-wrap .table.risk .risk-row .comment-container .content {
		width: 100%;
		font-size: 16px;
	}
	.wpeo-wrap .table.risk .risk-row .comment-container .wpeo-button {
		position: absolute;
		right: 0;
		top: 0;
	}
	.wpeo-wrap .table.risk .risk-row .comment-container .comment ~ .new.comment {
		padding-top: 10px;
	}
	.wpeo-wrap .table.risk .risk-row .wpeo-dropdown.dropdown-large .dropdown-content {
		width: 200px;
	}
	.wpeo-wrap .table.risk .risk-row .wpeo-dropdown.dropdown-large .dropdown-content .dropdown-item {
		width: 33.333333%;
	}
	.wpeo-wrap .table.risk .risk-row > td:nth-of-type(1) {
		order: 1;
	}
	.wpeo-wrap .table.risk .risk-row > td:nth-of-type(2) {
		order: 3;
	}
	.wpeo-wrap .table.risk .risk-row > td:nth-of-type(3) {
		order: 4;
	}
	.wpeo-wrap .table.risk .risk-row > td:nth-of-type(4) {
		order: 5;
	}
	.wpeo-wrap .table.risk .risk-row > td:nth-of-type(5) {
		order: 5;
	}
	.wpeo-wrap .table.risk .risk-row > td:nth-of-type(6) {
		order: 2;
	}
	.wpeo-wrap .table.risk > tfoot {
		background: #f1f1f1;
		padding-top: 4em;
	}
	.wpeo-wrap .table.risk > tfoot > tr {
		background: #fff;
	}
	.wpeo-wrap .table.risk .risk-row:not(.edit) .comment-container .comment {
		display: none;
	}
	.wpeo-wrap .table.risk .risk-row:not(.edit) .comment-container .comment:nth-of-type(1) {
		display: flex;
	}
}

@media (max-width: 770px) {
	.wpeo-wrap .table .comment-container .comment textarea {
		min-width: 100px;
		height: 150px;
	}
}

@media (max-width: 480px) {
	.wpeo-wrap .table.evaluators tr, .wpeo-wrap .table.evaluators thead, .wpeo-wrap .table.evaluators tbody, .wpeo-wrap .table.evaluators tfoot, .wpeo-wrap .table.evaluators td:not(.hidden), .wpeo-wrap .table.evaluators th, .wpeo-wrap .table.affected-evaluator tr, .wpeo-wrap .table.affected-evaluator thead, .wpeo-wrap .table.affected-evaluator tbody, .wpeo-wrap .table.affected-evaluator tfoot, .wpeo-wrap .table.affected-evaluator td:not(.hidden), .wpeo-wrap .table.affected-evaluator th {
		display: block;
		width: 100%;
		height: auto;
	}
	.wpeo-wrap .table.evaluators thead > tr {
		padding: 1em 6em !important;
		background: #fff !important;
		color: rgba(0, 0, 0, 0.8);
	}
	.wpeo-wrap .table.evaluators thead th {
		display: none;
	}
	.wpeo-wrap .table.evaluators thead th:nth-of-type(6) {
		display: block;
	}
	.wpeo-wrap .table.evaluators thead th:nth-of-type(6):before {
		font-family: "Font Awesome 5 Free", "Font Awesome 5 Pro";
		font-weight: 400;
		display: inline-block;
		content: "\f017";
		font-size: 20px;
		padding-right: 0.4em;
	}
	.wpeo-wrap .table.evaluators thead th:nth-of-type(6) input {
		display: inline-block;
		max-width: 60px;
	}
	.wpeo-wrap .table.affected-evaluator thead th {
		display: none;
	}
	.wpeo-wrap .table.evaluators tbody > tr, .wpeo-wrap .table.affected-evaluator tbody > tr {
		display: flex;
		flex-wrap: wrap;
		padding: 1em;
		position: relative;
		padding: 1em 6em !important;
	}
	.wpeo-wrap .table.evaluators tbody > tr:nth-child(even), .wpeo-wrap .table.affected-evaluator tbody > tr:nth-child(even) {
		background: #fff;
	}
	.wpeo-wrap .table.evaluators tbody td:nth-of-type(1), .wpeo-wrap .table.affected-evaluator tbody td:nth-of-type(1) {
		width: 50px;
		min-width: 50px;
		margin-right: 0.6em;
		position: absolute;
		left: 10px;
		top: 50%;
		transform: translateY(-50%);
	}
	.wpeo-wrap .table.evaluators tbody td:nth-of-type(2), .wpeo-wrap .table.evaluators tbody td:nth-of-type(3), .wpeo-wrap .table.evaluators tbody td:nth-of-type(4), .wpeo-wrap .table.affected-evaluator tbody td:nth-of-type(2), .wpeo-wrap .table.affected-evaluator tbody td:nth-of-type(3), .wpeo-wrap .table.affected-evaluator tbody td:nth-of-type(4) {
		width: auto;
		padding: 0 0.2em !important;
		font-size: 14px;
	}
	.wpeo-wrap .table.evaluators tbody td:nth-of-type(5):before, .wpeo-wrap .table.affected-evaluator tbody td:nth-of-type(5):before {
		font-family: "Font Awesome 5 Free", "Font Awesome 5 Pro";
		font-weight: 400;
		content: "\f017";
		display: inline-block;
		font-size: 20px;
		padding-right: 0.4em;
	}
	.wpeo-wrap .table.evaluators tbody td:nth-of-type(5) input, .wpeo-wrap .table.affected-evaluator tbody td:nth-of-type(5) input {
		display: inline-block;
		max-width: 60px;
	}
	.wpeo-wrap .table.evaluators tbody td:nth-of-type(6) {
		width: auto;
		position: absolute;
		right: 10px;
		top: 50%;
		transform: translateY(-50%);
	}
	.wpeo-wrap .table.affected-evaluator tbody tr {
		background: rgba(0, 0, 0, 0.1) !important;
	}
	.wpeo-wrap .table.affected-evaluator tbody td:nth-of-type(5) {
		width: 100%;
	}
	.wpeo-wrap .table.affected-evaluator tbody td:nth-of-type(5):before {
		display: inline-block;
		font-family: "Font Awesome 5 Free", "Font Awesome 5 Pro";
		font-weight: 400;
		content: "\f073";
		font-size: 20px;
		padding-right: 0.4em;
	}
	.wpeo-wrap .table.affected-evaluator tbody td:nth-of-type(6):before {
		font-family: "Font Awesome 5 Free", "Font Awesome 5 Pro";
		font-weight: 400;
		content: "\f017";
		display: inline-block;
		font-size: 20px;
		padding-right: 0.4em;
	}
	.wpeo-wrap .table.affected-evaluator tbody td:nth-of-type(6) input {
		display: inline-block;
		max-width: 60px;
	}
	.wpeo-wrap .table.affected-evaluator tbody td:nth-of-type(7) {
		width: auto;
		position: absolute;
		right: 10px;
		top: 50%;
		transform: translateY(-50%);
	}
}

.wpeo-wrap .flex-table {
	width: 100%;
	border: 1px solid rgba(0, 0, 0, 0.1);
	background: #fff;
	/* table general */
	/* table header */
	/* table body */
	/* table body */
	/* media */
	/* mysql date */
	/* action */
}

.wpeo-wrap .flex-table .col, .wpeo-wrap .flex-table .header-cell, .wpeo-wrap .flex-table .cell {
	height: auto;
	text-align: left;
}

.wpeo-wrap .flex-table .col {
	display: flex;
	min-height: 50px;
	flex-wrap: nowrap;
}

.wpeo-wrap .flex-table .col.advanced {
	display: block;
	height: auto;
}

.wpeo-wrap .flex-table .col.advanced .col {
	width: 100%;
	background: transparent !important;
}

.wpeo-wrap .flex-table .col.advanced .advanced {
	padding: 8px;
}

.wpeo-wrap .flex-table .cell, .wpeo-wrap .flex-table .header-cell {
	width: 100%;
	margin: auto 0;
}

.wpeo-wrap .flex-table strong {
	font-weight: 700;
	color: #3495f0;
}

.wpeo-wrap .flex-table > .table-header {
	background: #272a35;
	color: #fff;
}

.wpeo-wrap .flex-table > .table-header .header-cell {
	font-weight: 600;
}

.wpeo-wrap .flex-table > .table-header .col {
	background: #272a35 !important;
}

.wpeo-wrap .flex-table > .table-header .icon {
	padding-right: 4px;
}

.wpeo-wrap .flex-table > .table-body .col:nth-child(odd) {
	background: #fff;
}

.wpeo-wrap .flex-table > .table-body .col:nth-child(even) {
	background: rgba(0, 0, 0, 0.1);
}

.wpeo-wrap .flex-table > .table-body .advanced {
	width: 100%;
	min-width: 100%;
}

.wpeo-wrap .flex-table > .table-body .advanced::after {
	display: block;
	content: '';
	clear: both;
}

.wpeo-wrap .flex-table > .table-body .advanced .comment-container .comment {
	margin: 0.2em 0;
}

.wpeo-wrap .flex-table > .table-body .advanced .comment-container .comment.new {
	border-top: 0 !important;
	padding-top: 1em !important;
}

.wpeo-wrap .flex-table > .table-body .advanced .comment-container .comment textarea, .wpeo-wrap .flex-table > .table-body .advanced .comment-container .comment input {
	padding: 0.6em;
	line-height: 1;
	background: #ececec;
	box-shadow: none;
	transition: all 0.2s ease-out;
}

.wpeo-wrap .flex-table > .table-body .advanced .comment-container .comment textarea:hover, .wpeo-wrap .flex-table > .table-body .advanced .comment-container .comment input:hover {
	background: #dfdfdf;
}

.wpeo-wrap .flex-table > .table-body .comment-container {
	width: 100%;
}

.wpeo-wrap .flex-table > .table-body .comment-container .comment {
	display: flex;
	flex: 0 1 auto;
}

.wpeo-wrap .flex-table > .table-body .comment-container .comment > * {
	margin: auto 0;
}

.wpeo-wrap .flex-table > .table-body .comment-container .comment .content {
	white-space: normal;
	line-height: 1.3;
}

.wpeo-wrap .flex-table > .table-body .comment-container .comment span, .wpeo-wrap .flex-table > .table-body .comment-container .comment input, .wpeo-wrap .flex-table > .table-body .comment-container .comment textarea {
	color: rgba(0, 0, 0, 0.7);
}

.wpeo-wrap .flex-table > .table-body .comment-container .comment .delete, .wpeo-wrap .flex-table > .table-body .comment-container .comment .add {
	width: 20px;
	min-width: 20px;
	height: 20px;
	line-height: 20px;
	padding: 0 !important;
	text-align: center;
	border-radius: 50%;
	font-size: 10px;
}

.wpeo-wrap .flex-table > .table-body .comment-container .comment .delete i, .wpeo-wrap .flex-table > .table-body .comment-container .comment .add i {
	transition: all 0.2s ease-out;
}

.wpeo-wrap .flex-table > .table-body .comment-container .comment .delete {
	margin-left: 4px;
	background: rgba(0, 0, 0, 0.1);
}

.wpeo-wrap .flex-table > .table-body .comment-container .comment .delete i {
	color: rgba(0, 0, 0, 0.2);
}

.wpeo-wrap .flex-table > .table-body .comment-container .comment .delete:hover i {
	color: #e05353;
}

.wpeo-wrap .flex-table > .table-body .comment-container .comment ~ .new.comment {
	padding-top: 4px;
	border-top: 1px solid rgba(0, 0, 0, 0.1);
}

.wpeo-wrap .flex-table > .table-body .comment-container .new.comment {
	line-height: 2;
}

.wpeo-wrap .flex-table > .table-body .comment-container .new.comment .date {
	font-weight: 400;
}

.wpeo-wrap .flex-table > .table-body .comment-container .new.comment .add {
	margin-left: 4px;
	background: #3495f0;
}

.wpeo-wrap .flex-table > .table-body .comment-container .new.comment .add .svg-inline--fa {
	color: #fff !important;
}

.wpeo-wrap .flex-table > .table-body .comment-container .new.comment .add:hover {
	background: #1360c8;
}

.wpeo-wrap .flex-table > .table-body canvas {
	background: #fff;
	border: 1px solid rgba(0, 0, 0, 0.2);
	width: 100%;
}

.wpeo-wrap .flex-table > .table-body .canvas-eraser {
	pointer-events: auto;
	float: right;
}

.wpeo-wrap .flex-table > .table-body .canvas-eraser .fa-circle {
	color: rgba(0, 0, 0, 0.4);
	transition: all 0.2s ease-out;
}

.wpeo-wrap .flex-table > .table-body .canvas-eraser:hover .fa-circle {
	color: #e05353;
}

.wpeo-wrap .flex-table > .table-footer {
	border-top: 1px solid rgba(0, 0, 0, 0.05);
}

.wpeo-wrap .flex-table .media {
	margin: auto 0.6em !important;
	background: rgba(0, 0, 0, 0.1);
	width: 36px;
	height: 36px;
	min-width: 36px;
}

.wpeo-wrap .flex-table .media.no-file .button-add {
	color: rgba(0, 0, 0, 0.6);
	top: 4px;
	right: 4px;
}

.wpeo-wrap .flex-table .media .default-icon-container {
	line-height: 36px;
}

.wpeo-wrap .flex-table .media .default-image {
	color: rgba(0, 0, 0, 0.3);
}

.wpeo-wrap .flex-table .mysql-date {
	position: absolute;
}

.wpeo-wrap .flex-table .action {
	flex-wrap: nowrap;
	text-align: right;
}

.wpeo-wrap .flex-table .action .task:hover .icon {
	color: rgba(0, 0, 0, 0.6);
}

.wpeo-wrap .flex-table .action .edit:hover .icon {
	color: #3495f0;
}

.wpeo-wrap .flex-table .action .delete {
	background: rgba(0, 0, 0, 0.05);
}

.wpeo-wrap .flex-table .action .delete:hover .icon {
	color: #e05353;
}

.wpeo-wrap .flex-table .action .add, .wpeo-wrap .flex-table .action .save {
	margin-left: auto;
}

.wpeo-wrap .flex-table .action .add .icon, .wpeo-wrap .flex-table .action .save .icon {
	color: #fff !important;
}

.wpeo-wrap .flex-table {
	/* cell manual width */
	/* cell manual min width */
	/* cell manual max width */
}

.wpeo-wrap .flex-table input.affect {
	width: 50px;
}

.wpeo-wrap .flex-table input[type="text"], .wpeo-wrap .flex-table textarea {
	width: 100%;
}

.wpeo-wrap .flex-table .cell.w50, .wpeo-wrap .flex-table .header-cell.w50 {
	width: 50px;
	min-width: 50px;
}

.wpeo-wrap .flex-table .cell.w70, .wpeo-wrap .flex-table .header-cell.w70 {
	width: 70px;
	min-width: 70px;
}

.wpeo-wrap .flex-table .cell.w100, .wpeo-wrap .flex-table .header-cell.w100 {
	width: 100px;
	min-width: 100px;
}

.wpeo-wrap .flex-table .cell.w150, .wpeo-wrap .flex-table .header-cell.w150 {
	width: 150px;
	min-width: 150px;
}

.wpeo-wrap .flex-table .cell.w200, .wpeo-wrap .flex-table .header-cell.w200 {
	width: 200px;
	min-width: 200px;
}

.wpeo-wrap .flex-table .cell.wm130, .wpeo-wrap .flex-table .header-cell.wm130 {
	min-width: 130px;
}

.wpeo-wrap .flex-table .cell.wm40, .wpeo-wrap .flex-table .header-cell.wm40 {
	min-width: 40px;
}

.wpeo-wrap .flex-table .cell.wmax70 {
	max-width: 70px;
}

.wpeo-wrap .flex-table .cell.full, .wpeo-wrap .flex-table .header-cell.full {
	width: 100%;
}

.wpeo-wrap .flex-table .cell.full input[type="text"], .wpeo-wrap .flex-table .cell.full textarea, .wpeo-wrap .flex-table .header-cell.full input[type="text"], .wpeo-wrap .flex-table .header-cell.full textarea {
	width: 100%;
}

.wpeo-wrap .flex-table .cell.padding, .wpeo-wrap .flex-table .header-cell.padding {
	padding-left: 8px;
	padding-right: 8px;
}

.wpeo-wrap .flex-table .table-footer .wpeo-autocomplete .autocomplete-label {
	padding: 0.6em;
	background: #fff;
	box-shadow: none;
	border: 1px solid rgba(0, 0, 0, 0.1);
}

.wpeo-wrap .flex-table .table-footer .wpeo-autocomplete .autocomplete-label .autocomplete-search-list {
	background: #fff;
}

@media (max-width: 1200px) {
	.wpeo-wrap .flex-table .cell[class*="w"], .wpeo-wrap .flex-table .header-cell[class*="w"] {
		min-width: 50px;
	}
}

@media (max-width: 480px) {
	.wpeo-wrap .flex-table .col {
		height: auto;
		min-height: 50px;
		flex-wrap: wrap;
	}
	.wpeo-wrap .flex-table .cell, .wpeo-wrap .flex-table .header-cell {
		line-height: 1;
	}
}

.wpeo-wrap .wpeo-table .row-title, .wpeo-wrap .wpeo-table .row-subtitle {
	display: block;
}

.wpeo-wrap .wpeo-table .row-title {
	font-size: 18px !important;
	font-weight: 400;
}

.wpeo-wrap .table .causerie-description {
	height: 100%;
	padding: 8px;
	white-space: normal;
}

.wpeo-wrap .table .causerie-description input, .wpeo-wrap .table .causerie-description textarea {
	display: block;
	margin: 0.4em 0;
}

@media (max-width: 800px) {
	.wpeo-wrap .table.closed-causerie {
		display: block;
		width: 100% !important;
		height: auto !important;
		font-size: 18px;
	}
	.wpeo-wrap .table.closed-causerie tr, .wpeo-wrap .table.closed-causerie thead, .wpeo-wrap .table.closed-causerie tbody, .wpeo-wrap .table.closed-causerie tfoot, .wpeo-wrap .table.closed-causerie td, .wpeo-wrap .table.closed-causerie th {
		display: block;
		width: 100% !important;
		height: auto !important;
	}
	.wpeo-wrap .table.closed-causerie > thead {
		display: none;
	}
	.wpeo-wrap .table.closed-causerie tr.item {
		display: flex;
		flex-wrap: wrap;
		padding: 0.4em !important;
		/** Ref */
		/** Actions */
		/** Titre */
		/** Photo et catégorie */
	}
	.wpeo-wrap .table.closed-causerie tr.item > td {
		width: 100%;
	}
	.wpeo-wrap .table.closed-causerie tr.item > td[data-title]:before {
		display: block;
		content: attr(data-title);
		color: rgba(0, 0, 0, 0.4);
		padding-bottom: 0.4em;
	}
	.wpeo-wrap .table.closed-causerie tr.item > td:nth-of-type(1) {
		order: 1;
	}
	.wpeo-wrap .table.closed-causerie tr.item > td:nth-of-type(2) {
		order: 4;
	}
	.wpeo-wrap .table.closed-causerie tr.item > td:nth-of-type(3) {
		order: 5;
	}
	.wpeo-wrap .table.closed-causerie tr.item > td:nth-of-type(4) {
		order: 3;
	}
	.wpeo-wrap .table.closed-causerie tr.item > td:nth-of-type(5) {
		order: 6;
	}
	.wpeo-wrap .table.closed-causerie tr.item > td:nth-of-type(6) {
		order: 7;
	}
	.wpeo-wrap .table.closed-causerie tr.item > td:nth-of-type(7) {
		order: 8;
	}
	.wpeo-wrap .table.closed-causerie tr.item > td:nth-of-type(8) {
		order: 9;
	}
	.wpeo-wrap .table.closed-causerie tr.item > td:nth-of-type(9) {
		order: 2;
	}
	.wpeo-wrap .table.closed-causerie tr.item > td:nth-of-type(1) {
		width: 30% !important;
		margin: auto 0;
	}
	.wpeo-wrap .table.closed-causerie tr.item > td:nth-of-type(9) {
		width: 70% !important;
		margin: auto 0;
	}
	.wpeo-wrap .table.closed-causerie tr.item > td:nth-of-type(9) .action {
		display: block !important;
		text-align: right;
	}
	.wpeo-wrap .table.closed-causerie tr.item > td:nth-of-type(9) .wpeo-button {
		width: 50px !important;
		border-radius: 50%;
		min-width: 50px;
		background: #ececec;
		border-color: #ececec;
		padding: 0 !important;
		height: 50px !important;
		display: inline-block;
		text-align: center;
		line-height: 44px;
		font-size: 16px;
		color: rgba(0, 0, 0, 0.6);
	}
	.wpeo-wrap .table.closed-causerie tr.item > td:nth-of-type(9) .wpeo-button [data-icon] {
		line-height: 50px !important;
	}
	.wpeo-wrap .table.closed-causerie tr.item > td:nth-of-type(4) {
		padding: 0.4em 0 !important;
	}
	.wpeo-wrap .table.closed-causerie tr.item > td:nth-of-type(4) .row-title {
		font-size: 26px !important;
		font-weight: 600;
	}
	.wpeo-wrap .table.closed-causerie tr.item > td:nth-of-type(2), .wpeo-wrap .table.closed-causerie tr.item > td:nth-of-type(3), .wpeo-wrap .table.closed-causerie tr.item > td:nth-of-type(5), .wpeo-wrap .table.closed-causerie tr.item > td:nth-of-type(6), .wpeo-wrap .table.closed-causerie tr.item > td:nth-of-type(7), .wpeo-wrap .table.closed-causerie tr.item > td:nth-of-type(8) {
		width: 50% !important;
		text-align: center;
		padding: 0.6em !important;
	}
	.wpeo-wrap .table.closed-causerie tr.item > td:nth-of-type(2) .media, .wpeo-wrap .table.closed-causerie tr.item > td:nth-of-type(2) .avatar, .wpeo-wrap .table.closed-causerie tr.item > td:nth-of-type(2) .wpeo-button-pulse, .wpeo-wrap .table.closed-causerie tr.item > td:nth-of-type(3) .media, .wpeo-wrap .table.closed-causerie tr.item > td:nth-of-type(3) .avatar, .wpeo-wrap .table.closed-causerie tr.item > td:nth-of-type(3) .wpeo-button-pulse, .wpeo-wrap .table.closed-causerie tr.item > td:nth-of-type(5) .media, .wpeo-wrap .table.closed-causerie tr.item > td:nth-of-type(5) .avatar, .wpeo-wrap .table.closed-causerie tr.item > td:nth-of-type(5) .wpeo-button-pulse, .wpeo-wrap .table.closed-causerie tr.item > td:nth-of-type(6) .media, .wpeo-wrap .table.closed-causerie tr.item > td:nth-of-type(6) .avatar, .wpeo-wrap .table.closed-causerie tr.item > td:nth-of-type(6) .wpeo-button-pulse, .wpeo-wrap .table.closed-causerie tr.item > td:nth-of-type(7) .media, .wpeo-wrap .table.closed-causerie tr.item > td:nth-of-type(7) .avatar, .wpeo-wrap .table.closed-causerie tr.item > td:nth-of-type(7) .wpeo-button-pulse, .wpeo-wrap .table.closed-causerie tr.item > td:nth-of-type(8) .media, .wpeo-wrap .table.closed-causerie tr.item > td:nth-of-type(8) .avatar, .wpeo-wrap .table.closed-causerie tr.item > td:nth-of-type(8) .wpeo-button-pulse {
		margin: auto;
	}
	.wpeo-wrap .table.closed-causerie tr.item .wpeo-button-pulse {
		width: 50px;
		min-width: 50px;
		height: 50px;
		line-height: 50px;
	}
}

.wpeo-wrap.wpdigi-installer .logo {
	width: 100%;
	text-align: center;
	margin: 20px 0 30px 0;
}

.wpeo-wrap.wpdigi-installer .logo img {
	max-width: 260px;
	width: 100%;
}

.wpeo-wrap.wpdigi-installer .main-content {
	box-shadow: 0px 4px 14px 1px rgba(0, 0, 0, 0.05);
	max-width: 1200px;
	margin: auto;
}

.wpeo-wrap.wpdigi-installer .bloc-create-society {
	padding: 100px 0;
	text-align: center;
	max-width: 700px;
	margin: auto;
}

.wpeo-wrap.wpdigi-installer .bloc-create-society .bloc-default-data {
	text-align: left;
	width: 460px;
	margin: auto;
	margin-top: 20px;
}

.wpeo-wrap.wpdigi-installer .bloc-create-society .title {
	font-size: 28px;
	color: rgba(0, 0, 0, 0.4);
	font-weight: 300;
	line-height: 1.2;
	margin-bottom: 36px;
}

.wpeo-wrap.wpdigi-installer .wpdigi-components h2 {
	text-align: center;
	display: block;
	text-transform: uppercase;
	font-size: 18px;
	font-weight: 400;
	margin: 30px 0 30px 0;
}

.wpeo-wrap.wpdigi-installer .wpdigi-components .content {
	padding: 20px;
	font-size: 16px;
	margin: auto;
}

.wpeo-wrap.wpdigi-installer .wpdigi-components .content h3 {
	margin-bottom: 20px;
	font-weight: 600;
	font-size: 18px;
}

.wpeo-wrap.wpdigi-installer .wpdigi-components .content h3:after {
	display: block;
	content: '';
	width: 100%;
	max-width: 50px;
	height: 3px;
	background: #3495f0;
	margin-top: 14px;
}

.wpeo-wrap.wpdigi-installer .wpdigi-components .content h3.center:after {
	margin-left: auto;
	margin-right: auto;
}

.wpeo-wrap.wpdigi-installer .wpdigi-components .content ul {
	margin-bottom: 20px;
}

.wpeo-wrap.wpdigi-installer .wpdigi-components .content ul li {
	padding-left: 26px;
}

.wpeo-wrap.wpdigi-installer .wpdigi-components .content ul li:before {
	display: block;
	content: '';
	width: 5px;
	height: 5px;
	border-radius: 50%;
	background: #3495f0;
	float: left;
	margin-right: 10px;
	margin-top: 7px;
}

.wpeo-wrap.wpdigi-installer .wpdigi-components .content p {
	margin-bottom: 20px;
}

.wpeo-wrap.wpdigi-installer .wpdigi-components .content .strong {
	font-weight: 600;
}

.wpeo-wrap.wpdigi-installer .wpdigi-components .content .center {
	text-align: center;
}

.wpeo-wrap.wpdigi-installer .wpdigi-components .content .oversize {
	font-size: 20px;
}

.wpeo-wrap.wpdigi-installer .wpdigi-components .content .light {
	font-weight: 300;
}

.wpeo-wrap.wpdigi-installer .wpdigi-components .content .padding {
	padding: 10px;
}

.wpeo-wrap.wpdigi-installer .wpdigi-components .grid-layout.w2 {
	background: rgba(0, 0, 0, 0.05);
}

.wpeo-wrap.wpdigi-installer .wpdigi-components .owl-carousel {
	position: relative;
	margin-bottom: 30px;
	overflow: hidden;
}

.wpeo-wrap.wpdigi-installer .wpdigi-components .owl-nav .owl-prev, .wpeo-wrap.wpdigi-installer .wpdigi-components .owl-nav .owl-next {
	position: absolute;
	top: 50%;
	font-size: 18px;
	background: rgba(255, 255, 255, 0.6);
	padding: 30px;
	border-radius: 50%;
	transition: all 0.2s ease-out;
	box-shadow: 0px 0px 14px 1px rgba(0, 0, 0, 0.1);
}

.wpeo-wrap.wpdigi-installer .wpdigi-components .owl-nav .owl-prev:before, .wpeo-wrap.wpdigi-installer .wpdigi-components .owl-nav .owl-next:before {
	position: relative;
	display: block;
	transition: all 0.2s ease-out;
}

.wpeo-wrap.wpdigi-installer .wpdigi-components .owl-nav .owl-prev:hover, .wpeo-wrap.wpdigi-installer .wpdigi-components .owl-nav .owl-next:hover {
	background: white;
}

.wpeo-wrap.wpdigi-installer .wpdigi-components .owl-nav .owl-prev {
	left: -40px;
}

.wpeo-wrap.wpdigi-installer .wpdigi-components .owl-nav .owl-prev:hover:before {
	left: 12px;
}

.wpeo-wrap.wpdigi-installer .wpdigi-components .owl-nav .owl-next {
	right: -40px;
}

.wpeo-wrap.wpdigi-installer .wpdigi-components .owl-nav .owl-next:hover:before {
	right: 12px;
}

.wpeo-wrap.wpdigi-installer .wpdigi-components .owl-nav .owl-prev:before {
	left: 16px;
	font-family: "Font Awesome 5 Free", "Font Awesome 5 Pro";
	font-weight: 900;
	content: "\f104";
}

.wpeo-wrap.wpdigi-installer .wpdigi-components .owl-nav .owl-next:before {
	right: 16px;
	font-family: "Font Awesome 5 Free", "Font Awesome 5 Pro";
	font-weight: 900;
	content: "\f105";
}

.wpeo-wrap.wpdigi-installer .wpdigi-components .owl-dots {
	text-align: right;
	position: relative;
	top: -30px;
	right: 20px;
}

.wpeo-wrap.wpdigi-installer .wpdigi-components .owl-dots .owl-dot {
	display: inline-block;
	margin: 0 6px;
}

.wpeo-wrap.wpdigi-installer .wpdigi-components .owl-dots .owl-dot span {
	display: inline-block;
	width: 10px;
	height: 10px;
	background: #fff;
	border-radius: 50%;
	box-shadow: 0px 1px 6px 0px rgba(0, 0, 0, 0.2);
	transition: all 0.2s ease-out;
}

.wpeo-wrap.wpdigi-installer .wpdigi-components .owl-dots .owl-dot.active span {
	border: 2px solid #3495f0;
	box-shadow: 0px 0px 0px 2px #3495f0;
}

.wpeo-wrap.wpdigi-installer .button.disable {
	pointer-events: none;
}

.wpeo-wrap.wpdigi-installer .society-form {
	position: relative;
	width: 100%;
	max-width: 460px;
	margin: auto;
}

.wpeo-wrap.wpdigi-installer .society-form .society-name {
	width: 100%;
	border: 0;
	background: rgba(0, 0, 0, 0.05);
	box-shadow: none;
	padding: 16px 20px;
}

.wpeo-wrap.wpdigi-installer .society-form .society-label {
	position: absolute;
	top: 50%;
	transform: translateY(-50%);
	left: 20px;
	color: rgba(0, 0, 0, 0.3);
	pointer-events: none;
}

.wpeo-wrap .step {
	width: 100%;
}

.wpeo-wrap .step .bar {
	position: relative;
	top: -9px;
}

.wpeo-wrap .step .bar .background, .wpeo-wrap .step .bar .loader {
	position: absolute;
	top: 0;
	height: 4px;
}

.wpeo-wrap .step .bar .background {
	width: 100%;
	background: #d6d6d6;
}

.wpeo-wrap .step .bar .loader {
	width: 0%;
	background: #47e58e;
	transition: all 0.1s linear;
}

.wpeo-wrap .step .step-list {
	width: 100%;
	display: flex;
}

.wpeo-wrap .step .step-list .step:after {
	position: relative;
	z-index: 90;
	display: block;
	margin: auto;
	content: '';
	width: 14px;
	height: 14px;
	background: #d6d6d6;
	border-radius: 50%;
	transition: all 0.2s ease-out;
}

.wpeo-wrap .step .step-list .step .title {
	display: block;
	text-align: center;
	margin: auto;
	color: rgba(0, 0, 0, 0.3);
	font-size: 14px;
	max-width: 160px;
	font-weight: 600;
	padding-bottom: 10px;
	transition: all 0.2s ease-out;
	min-height: 45px;
}

.wpeo-wrap .step .step-list .step:first-child:after, .wpeo-wrap .step .step-list .step:first-child .title {
	margin: 0 auto 0 0;
	text-align: left;
}

.wpeo-wrap .step .step-list .step:last-child:after, .wpeo-wrap .step .step-list .step:last-child .title {
	margin: 0 0 0 auto;
	text-align: right;
}

.wpeo-wrap .step .step-list .step.active:after {
	background: #47e58e !important;
}

.wpeo-wrap .step .step-list .step.active .title {
	color: #47e58e;
}

.wpeo-wrap .step .step-list:after {
	display: block;
	content: '';
	clear: both;
}

.wpeo-wrap .step.install {
	margin: 20px auto;
	max-width: 1200px;
}

.wpeo-wrap .step.install .step-list .step:first-child {
	width: 12.5%;
}

.wpeo-wrap .step.install .step-list .step:nth-of-type(2) {
	width: 25%;
}

.wpeo-wrap .step.install .step-list .step:nth-of-type(3) {
	width: 25%;
}

.wpeo-wrap .step.install .step-list .step:nth-of-type(4) {
	width: 25%;
}

.wpeo-wrap .step.install .step-list .step:last-child {
	width: 12.5%;
}

@media screen and (max-width: 1080px) {
	.wpeo-wrap .step .step-list .step .title {
		font-size: 10px;
	}
}

.wpeo-wrap .wpeo-autocomplete .autocomplete-label {
	padding: 1em;
}

.wpeo-wrap .wpeo-autocomplete .autocomplete-label:hover {
	border: 1px solid #3495f0;
}

.wpeo-wrap .wpeo-autocomplete .autocomplete-label .autocomplete-loading {
	background: #3495f0;
}

.wpeo-wrap .wpeo-autocomplete .autocomplete-label .autocomplete-loading-background {
	background: rgba(52, 149, 240, 0.4);
}

/*.wpeo-wrap .wpeo-button {*/
/*	/* par défaut */*/
/*	background: #3495f0;*/
/*	border-color: #3495f0;*/
/*}*/
/**/
/*.wpeo-wrap .wpeo-button.button-main {*/
/*	background: #3495f0;*/
/*	border-color: #3495f0;*/
/*}*/
/**/
/*.wpeo-wrap .wpeo-button.button-dark {*/
/*	background: #272a35;*/
/*	border-color: #272a35;*/
/*}*/
/**/
/*.wpeo-wrap .wpeo-button.button-grey {*/
/*	background: #ececec;*/
/*	border-color: #ececec;*/
/*}*/
/**/
/*.wpeo-wrap .wpeo-button.button-red {*/
/*	background: #e05353;*/
/*	border-color: #e05353;*/
/*}*/
/**/
/*.wpeo-wrap .wpeo-button.button-yellow {*/
/*	background: #e9ad4f;*/
/*	border-color: #e9ad4f;*/
/*}*/
/**/
/*.wpeo-wrap .wpeo-button.button-green {*/
/*	background: #47e58e;*/
/*	border-color: #47e58e;*/
/*}*/
/**/
/*.wpeo-wrap .wpeo-button.button-pink {*/
/*	background: #e454a2;*/
/*	border-color: #e454a2;*/
/*}*/
/**/
/*.wpeo-wrap .wpeo-button.button-purple {*/
/*	background: #898de5;*/
/*	border-color: #898de5;*/
/*}*/
/**/
/*.wpeo-wrap .wpeo-button.button-grey {*/
/*	background: #ececec;*/
/*	border-color: #ececec;*/
/*}*/
/**/
/*.wpeo-wrap .wpeo-button.button-transparent {*/
/*	background: transparent;*/
/*	border-color: transparent;*/
/*}*/
/**/
/*.wpeo-wrap .wpeo-button.button-bordered {*/
/*	border-color: #3495f0;*/
/*	color: #3495f0;*/
/*}*/
/**/
/*.wpeo-wrap .wpeo-button.button-bordered.button-main {*/
/*	border-color: #3495f0;*/
/*	color: #3495f0;*/
/*}*/
/**/
/*.wpeo-wrap .wpeo-button.button-bordered.button-main:hover {*/
/*	box-shadow: inset 0 -2.6em #3495f0;*/
/*}*/
/**/
/*.wpeo-wrap .wpeo-button.button-bordered.button-dark {*/
/*	border-color: #272a35;*/
/*	color: #272a35;*/
/*}*/
/**/
/*.wpeo-wrap .wpeo-button.button-bordered.button-dark:hover {*/
/*	box-shadow: inset 0 -2.6em #272a35;*/
/*}*/
/**/
/*.wpeo-wrap .wpeo-button.button-bordered.button-grey {*/
/*	border-color: #ececec;*/
/*	color: #a0a0a0;*/
/*}*/
/**/
/*.wpeo-wrap .wpeo-button.button-bordered.button-grey:hover {*/
/*	box-shadow: inset 0 -2.6em #ececec;*/
/*}*/
/**/
/*.wpeo-wrap .wpeo-button.button-bordered.button-red {*/
/*	border-color: #e05353;*/
/*	color: #e05353;*/
/*}*/
/**/
/*.wpeo-wrap .wpeo-button.button-bordered.button-red:hover {*/
/*	box-shadow: inset 0 -2.6em #e05353;*/
/*}*/
/**/
/*.wpeo-wrap .wpeo-button.button-bordered.button-yellow {*/
/*	border-color: #e9ad4f;*/
/*	color: #e9ad4f;*/
/*}*/
/**/
/*.wpeo-wrap .wpeo-button.button-bordered.button-yellow:hover {*/
/*	box-shadow: inset 0 -2.6em #e9ad4f;*/
/*}*/
/**/
/*.wpeo-wrap .wpeo-button.button-bordered.button-green {*/
/*	border-color: #47e58e;*/
/*	color: #47e58e;*/
/*}*/
/**/
/*.wpeo-wrap .wpeo-button.button-bordered.button-green:hover {*/
/*	box-shadow: inset 0 -2.6em #47e58e;*/
/*}*/
/**/
/*.wpeo-wrap .wpeo-button.button-bordered.button-pink {*/
/*	border-color: #e454a2;*/
/*	color: #e454a2;*/
/*}*/
/**/
/*.wpeo-wrap .wpeo-button.button-bordered.button-pink:hover {*/
/*	box-shadow: inset 0 -2.6em #e454a2;*/
/*}*/
/**/
/*.wpeo-wrap .wpeo-button.button-bordered.button-purple {*/
/*	border-color: #898de5;*/
/*	color: #898de5;*/
/*}*/
/**/
/*.wpeo-wrap .wpeo-button.button-bordered.button-purple:hover {*/
/*	box-shadow: inset 0 -2.6em #898de5;*/
/*}*/
/**/
/*.wpeo-wrap .wpeo-button.button-bordered.button-grey {*/
/*	border-color: #ececec;*/
/*	color: #ececec;*/
/*}*/
/**/
/*.wpeo-wrap .wpeo-button.button-bordered.button-grey:hover {*/
/*	box-shadow: inset 0 -2.6em #ececec;*/
/*}*/
/**/
/*.wpeo-wrap .wpeo-button.button-bordered:hover {*/
/*	box-shadow: inset 0 -2.6em #3495f0;*/
/*}*/
/**/
/*.wpeo-wrap .wpeo-button.button-progress.button-success {*/
/*	background: #47e58e;*/
/*	border-color: #47e58e;*/
/*}*/
/**/
/*.wpeo-wrap .wpeo-button.button-progress.button-error {*/
/*	background: #e05353;*/
/*	border-color: #e05353;*/
/*}*/
/**/
/*.wpeo-wrap .wpeo-button.button-progress.button-load {*/
/*	background: #ececec;*/
/*}*/
/**/
/*.wpeo-wrap .wpeo-button.button-progress.button-load:before {*/
/*	border-top: 3px solid #3495f0;*/
/*}*/

.wpeo-wrap .wpeo-dropdown.tab-element {
	padding: 0 !important;
	margin-left: auto;
}

.wpeo-wrap .wpeo-dropdown.tab-element .dropdown-toggle {
	padding: 1em 1.4em !important;
}

.wpeo-wrap .wpeo-form .form-element input[type="checkbox"].form-field:not(:checked) + label:hover::before {
	box-shadow: 0 0 0 2px #3495f0;
}

.wpeo-wrap .wpeo-form .form-element input[type="checkbox"].form-field:checked + label::before {
	box-shadow: 0 0 0 2px #3495f0;
	background: #3495f0;
}

.wpeo-wrap .wpeo-form .form-element input[type="radio"].form-field:hover {
	border: 1px solid #3495f0;
	box-shadow: 0 0 0 1px #3495f0 inset;
}

.wpeo-wrap .wpeo-form .form-element input[type="radio"].form-field:checked {
	border: 1px solid #3495f0;
	box-shadow: 0 0 0 4px #3495f0 inset;
}

.wpeo-wrap .wpeo-gridlayout {
	display: grid;
}

.wpeo-wrap .wpeo-gridlayout {
	/** Du parent */
	/** Chaque enfant peut modifier sa propre taille */
	/** Chaque enfant peut modifier sa propre taille */
	/** Chaque enfant peut modifier sa propre taille */
	/** Chaque enfant peut modifier sa propre taille */
	/** Chaque enfant peut modifier sa propre taille */
	/** Chaque enfant peut modifier sa propre taille */
}

.wpeo-wrap .wpeo-gridlayout.grid-1 {
	grid-template-columns: repeat(1, 1fr) !important;
}

.wpeo-wrap .wpeo-gridlayout.grid-1 > .gridw-1 {
	grid-column: auto/span 1 !important;
}

.wpeo-wrap .wpeo-gridlayout.grid-2 {
	grid-template-columns: repeat(2, 1fr) !important;
}

.wpeo-wrap .wpeo-gridlayout.grid-2 > .gridw-1 {
	grid-column: auto/span 1 !important;
}

.wpeo-wrap .wpeo-gridlayout.grid-2 > .gridw-2 {
	grid-column: auto/span 2 !important;
}

.wpeo-wrap .wpeo-gridlayout.grid-3 {
	grid-template-columns: repeat(3, 1fr) !important;
}

.wpeo-wrap .wpeo-gridlayout.grid-3 > .gridw-1 {
	grid-column: auto/span 1 !important;
}

.wpeo-wrap .wpeo-gridlayout.grid-3 > .gridw-2 {
	grid-column: auto/span 2 !important;
}

.wpeo-wrap .wpeo-gridlayout.grid-3 > .gridw-3 {
	grid-column: auto/span 3 !important;
}

.wpeo-wrap .wpeo-gridlayout.grid-4 {
	grid-template-columns: repeat(4, 1fr) !important;
}

.wpeo-wrap .wpeo-gridlayout.grid-4 > .gridw-1 {
	grid-column: auto/span 1 !important;
}

.wpeo-wrap .wpeo-gridlayout.grid-4 > .gridw-2 {
	grid-column: auto/span 2 !important;
}

.wpeo-wrap .wpeo-gridlayout.grid-4 > .gridw-3 {
	grid-column: auto/span 3 !important;
}

.wpeo-wrap .wpeo-gridlayout.grid-4 > .gridw-4 {
	grid-column: auto/span 4 !important;
}

.wpeo-wrap .wpeo-gridlayout.grid-5 {
	grid-template-columns: repeat(5, 1fr) !important;
}

.wpeo-wrap .wpeo-gridlayout.grid-5 > .gridw-1 {
	grid-column: auto/span 1 !important;
}

.wpeo-wrap .wpeo-gridlayout.grid-5 > .gridw-2 {
	grid-column: auto/span 2 !important;
}

.wpeo-wrap .wpeo-gridlayout.grid-5 > .gridw-3 {
	grid-column: auto/span 3 !important;
}

.wpeo-wrap .wpeo-gridlayout.grid-5 > .gridw-4 {
	grid-column: auto/span 4 !important;
}

.wpeo-wrap .wpeo-gridlayout.grid-5 > .gridw-5 {
	grid-column: auto/span 5 !important;
}

.wpeo-wrap .wpeo-gridlayout.grid-6 {
	grid-template-columns: repeat(6, 1fr) !important;
}

.wpeo-wrap .wpeo-gridlayout.grid-6 > .gridw-1 {
	grid-column: auto/span 1 !important;
}

.wpeo-wrap .wpeo-gridlayout.grid-6 > .gridw-2 {
	grid-column: auto/span 2 !important;
}

.wpeo-wrap .wpeo-gridlayout.grid-6 > .gridw-3 {
	grid-column: auto/span 3 !important;
}

.wpeo-wrap .wpeo-gridlayout.grid-6 > .gridw-4 {
	grid-column: auto/span 4 !important;
}

.wpeo-wrap .wpeo-gridlayout.grid-6 > .gridw-5 {
	grid-column: auto/span 5 !important;
}

.wpeo-wrap .wpeo-gridlayout.grid-6 > .gridw-6 {
	grid-column: auto/span 6 !important;
}

.wpeo-wrap .wpeo-loader .loader-spin {
	border-top: 3px solid #3495f0;
}

/*.wpeo-wrap .wpeo-modal .modal-container .modal-header .modal-close:hover {*/
/*	color: #3495f0;*/
/*}*/

.wpeo-wrap .wpeo-notification .notification-title a {
	color: #3495f0;
}

.wpeo-wrap .wpeo-notification .notification-close:hover {
	color: #3495f0;
}

.wpeo-wrap .wpeo-notification.notification-green {
	border-left: 4px solid #47e58e;
}

.wpeo-wrap .wpeo-notification.notification-green .notification-icon {
	color: #47e58e;
}

.wpeo-wrap .wpeo-notification.notification-orange {
	border-left: 4px solid #e9ad4f;
}

.wpeo-wrap .wpeo-notification.notification-orange .notification-icon {
	color: #e9ad4f;
}

.wpeo-wrap .wpeo-notification.notification-red {
	border-left: 4px solid #e05353;
}

.wpeo-wrap .wpeo-notification.notification-red .notification-icon {
	color: #e05353;
}

.wpeo-wrap .wpeo-pagination .pagination-element.pagination-current a {
	background: #3495f0;
}

.wpeo-wrap .wpeo-pagination .pagination-element.pagination-current a:hover {
	background: #3495f0;
}

.wpeo-wrap .wpeo-popover.popover-primary {
	background: #3495f0;
}

.wpeo-wrap .wpeo-popover.popover-primary.popover-top::before {
	border-color: #3495f0 transparent transparent transparent;
}

.wpeo-wrap .wpeo-popover.popover-primary.popover-right::before {
	border-color: transparent #3495f0 transparent transparent;
}

.wpeo-wrap .wpeo-popover.popover-primary.popover-bottom::before {
	border-color: transparent transparent #3495f0 transparent;
}

.wpeo-wrap .wpeo-popover.popover-primary.popover-left::before {
	border-color: transparent transparent transparent #3495f0;
}

.wpeo-wrap .wpeo-popover.popover-light {
	background: #ececec;
	color: rgba(0, 0, 0, 0.6);
}

.wpeo-wrap .wpeo-popover.popover-light.popover-top::before {
	border-color: #ececec transparent transparent transparent;
}

.wpeo-wrap .wpeo-popover.popover-light.popover-right::before {
	border-color: transparent #ececec transparent transparent;
}

.wpeo-wrap .wpeo-popover.popover-light.popover-bottom::before {
	border-color: transparent transparent #ececec transparent;
}

.wpeo-wrap .wpeo-popover.popover-light.popover-left::before {
	border-color: transparent transparent transparent #ececec;
}

.wpeo-wrap .wpeo-popover.popover-red {
	background: #e05353;
}

.wpeo-wrap .wpeo-popover.popover-red.popover-top::before {
	border-color: #e05353 transparent transparent transparent;
}

.wpeo-wrap .wpeo-popover.popover-red.popover-right::before {
	border-color: transparent #e05353 transparent transparent;
}

.wpeo-wrap .wpeo-popover.popover-red.popover-bottom::before {
	border-color: transparent transparent #e05353 transparent;
}

.wpeo-wrap .wpeo-popover.popover-red.popover-left::before {
	border-color: transparent transparent transparent #e05353;
}

.wpeo-wrap .wpeo-tab .tab-content::after {
	display: block;
	content: '';
	clear: both;
}

.wpeo-wrap .wpeo-tab .tab-list {
	background: rgba(0, 0, 0, 0.1);
}

.wpeo-wrap .wpeo-tab .tab-list .tab-element {
	margin-bottom: 0;
	text-transform: none;
	font-size: 14px !important;
	padding: 1em 1.4em;
	background: transparent;
}

.wpeo-wrap .wpeo-tab .tab-list .tab-element:hover {
	background: rgba(0, 0, 0, 0.1);
}

.wpeo-wrap .wpeo-tab .tab-list .tab-element.tab-active {
	background: #fff;
}

@media (max-width: 480px) {
	.wpeo-wrap .wpeo-tab .tab-container .tab-content {
		padding: 0;
	}
}

@media (max-width: 480px) {
	.wpeo-wrap .wpeo-tab .tab-element > span:not(.tab-icon) {
		display: none;
	}
}

.wpeo-wrap .wpeo-tab .tab-list .tab-element {
	/* Active */
}

.wpeo-wrap .wpeo-tab .tab-list .tab-element::before {
	background: #3495f0;
}

.wpeo-wrap .wpeo-tab .tab-list .tab-element.tab-active {
	color: #3495f0;
}

.wpeo-wrap .wpeo-tab .tab-list .tab-element.tab-active > a {
	color: #3495f0;
}

.wpeo-wrap .wpeo-tab.tab-vertical .tab-list .tab-element {
	/* Active */
}

.wpeo-wrap .wpeo-tab.tab-vertical .tab-list .tab-element.tab-active {
	color: #3495f0;
}

.wpeo-wrap .wpeo-tab.tab-vertical .tab-list .tab-element.tab-active a {
	color: #3495f0;
}

.wpeo-wrap .wpeo-table > thead, .wpeo-wrap .wpeo-table > tfoot {
	background: #272a35;
}

.wpeo-wrap .wpeo-table input, .wpeo-wrap .wpeo-table textarea {
	width: 100%;
}

@media (max-width: 480px) {
	.wpeo-wrap .wpeo-table > tbody td:before, .wpeo-wrap .wpeo-table > tbody th:before {
		color: #3495f0;
	}
}

.wpeo-wrap .wpeo-tooltip.tooltip-primary {
	background: #3495f0;
}

.wpeo-wrap .wpeo-tooltip.tooltip-primary.tooltip-top::before {
	border-color: #3495f0 transparent transparent transparent;
}

.wpeo-wrap .wpeo-tooltip.tooltip-primary.tooltip-right::before {
	border-color: transparent #3495f0 transparent transparent;
}

.wpeo-wrap .wpeo-tooltip.tooltip-primary.tooltip-bottom::before {
	border-color: transparent transparent #3495f0 transparent;
}

.wpeo-wrap .wpeo-tooltip.tooltip-primary.tooltip-left::before {
	border-color: transparent transparent transparent #3495f0;
}

.wpeo-wrap .wpeo-tooltip.tooltip-light {
	background: #ececec;
	color: rgba(0, 0, 0, 0.6);
}

.wpeo-wrap .wpeo-tooltip.tooltip-light.tooltip-top::before {
	border-color: #ececec transparent transparent transparent;
}

.wpeo-wrap .wpeo-tooltip.tooltip-light.tooltip-right::before {
	border-color: transparent #ececec transparent transparent;
}

.wpeo-wrap .wpeo-tooltip.tooltip-light.tooltip-bottom::before {
	border-color: transparent transparent #ececec transparent;
}

.wpeo-wrap .wpeo-tooltip.tooltip-light.tooltip-left::before {
	border-color: transparent transparent transparent #ececec;
}

.wpeo-wrap .wpeo-tooltip.tooltip-red {
	background: #e05353;
}

.wpeo-wrap .wpeo-tooltip.tooltip-red.tooltip-top::before {
	border-color: #e05353 transparent transparent transparent;
}

.wpeo-wrap .wpeo-tooltip.tooltip-red.tooltip-right::before {
	border-color: transparent #e05353 transparent transparent;
}

.wpeo-wrap .wpeo-tooltip.tooltip-red.tooltip-bottom::before {
	border-color: transparent transparent #e05353 transparent;
}

.wpeo-wrap .wpeo-tooltip.tooltip-red.tooltip-left::before {
	border-color: transparent transparent transparent #e05353;
}

.wpeo-wrap.corrective-task .wpeo-project-task {
	width: 100% !important;
}

.wpeo-wrap.corrective-task .wpeo-project-task .wpeo-done-point {
	width: auto;
}

.wpeo-wrap.corrective-task .wpeo-project-task .list-task .wpeo-project-task {
	width: 100%;
}

.wpeo-wrap.corrective-task .wpeo-project-task .completed-point {
	width: auto !important;
	border: 1px solid #000 !important;
	border-radius: 0 !important;
}

.wpeo-wrap.corrective-task .wpeo-project-task .wpeo-task-point-use-toggle > p {
	background: #fff;
}

/*---
  Animations from animate.css
  From v6.x.x
---*/
.wpeo-wrap .animated {
	-webkit-animation-duration: 1s;
	animation-duration: 1s;
	-webkit-animation-fill-mode: both;
	animation-fill-mode: both;
}

@-webkit-keyframes bounceIn {
0, 20%, 40%, 60%, 80%, to {
						   -webkit-animation-timing-function: cubic-bezier(0.215, 0.61, 0.355, 1);
						   animation-timing-function: cubic-bezier(0.215, 0.61, 0.355, 1);
					   }
0% {
	transform: scale3d(1, 1, 1);
}
20% {
	transform: scale3d(1.4, 1.4, 1.4);
}
40% {
	transform: scale3d(0.9, 0.9, 0.9);
}
60% {
	transform: scale3d(1.2, 1.2, 1.2);
}
80% {
	transform: scale3d(0.97, 0.97, 0.97);
}
to {
	transform: scale3d(1, 1, 1);
}
}

@keyframes bounceIn {
0, 20%, 40%, 60%, 80%, to {
						   -webkit-animation-timing-function: cubic-bezier(0.215, 0.61, 0.355, 1);
						   animation-timing-function: cubic-bezier(0.215, 0.61, 0.355, 1);
					   }
0% {
	transform: scale3d(1, 1, 1);
}
20% {
	transform: scale3d(1.4, 1.4, 1.4);
}
40% {
	transform: scale3d(0.9, 0.9, 0.9);
}
60% {
	transform: scale3d(1.2, 1.2, 1.2);
}
80% {
	transform: scale3d(0.97, 0.97, 0.97);
}
to {
	transform: scale3d(1, 1, 1);
}
}

.wpeo-wrap .bounce-in {
	-webkit-animation-name: bounceIn;
	animation-name: bounceIn;
}

@-webkit-keyframes bounceInLight {
0, 20%, 40%, 60%, 80%, to {
						   -webkit-animation-timing-function: cubic-bezier(0.215, 0.61, 0.355, 1);
						   animation-timing-function: cubic-bezier(0.215, 0.61, 0.355, 1);
					   }
0% {
	transform: scale3d(1, 1, 1);
}
20% {
	transform: scale3d(1.2, 1.2, 1.2);
}
40% {
	transform: scale3d(0.9, 0.9, 0.9);
}
60% {
	transform: scale3d(1.1, 1.1, 1.1);
}
80% {
	transform: scale3d(0.97, 0.97, 0.97);
}
to {
	transform: scale3d(1, 1, 1);
}
}

@keyframes bounceInLight {
0, 20%, 40%, 60%, 80%, to {
						   -webkit-animation-timing-function: cubic-bezier(0.215, 0.61, 0.355, 1);
						   animation-timing-function: cubic-bezier(0.215, 0.61, 0.355, 1);
					   }
0% {
	transform: scale3d(1, 1, 1);
}
20% {
	transform: scale3d(1.2, 1.2, 1.2);
}
40% {
	transform: scale3d(0.9, 0.9, 0.9);
}
60% {
	transform: scale3d(1.1, 1.1, 1.1);
}
80% {
	transform: scale3d(0.97, 0.97, 0.97);
}
to {
	transform: scale3d(1, 1, 1);
}
}

.wpeo-wrap .bounce-in-light, .wpeo-wrap .wpeo-button-pulse:hover .animated {
	-webkit-animation-name: bounceInLight;
	animation-name: bounceInLight;
}

@-webkit-keyframes rotate {
	0% {
		transform: rotate(0deg);
	}
	100% {
		transform: rotate(90deg);
	}
}

@keyframes rotate {
	0% {
		transform: rotate(0deg);
	}
	100% {
		transform: rotate(90deg);
	}
}

.wpeo-wrap .rotate {
	-webkit-animation-name: rotate;
	animation-name: rotate;
}

@-webkit-keyframes downAndUp {
	0% {
		transform: translateY(0px);
	}
	50% {
		transform: translateY(4px);
	}
	100% {
		transform: translateY(0px);
	}
}

@keyframes downAndUp {
	0% {
		transform: translateY(0px);
	}
	50% {
		transform: translateY(4px);
	}
	100% {
		transform: translateY(0px);
	}
}

.wpeo-wrap .down-and-up {
	-webkit-animation-name: downAndUp;
	animation-name: downAndUp;
}

@-webkit-keyframes spin {
	0% {
		transform: translate(-50%, -50%) rotate(0deg);
	}
	100% {
		transform: translate(-50%, -50%) rotate(360deg);
	}
}

@keyframes spin {
	0% {
		transform: translate(-50%, -50%) rotate(0deg);
	}
	100% {
		transform: translate(-50%, -50%) rotate(360deg);
	}
}

@-webkit-keyframes progressSuccess {
0, 20%, 40%, 60%, 80%, to {
						   -webkit-animation-timing-function: cubic-bezier(0.215, 0.61, 0.355, 1);
						   animation-timing-function: cubic-bezier(0.215, 0.61, 0.355, 1);
					   }
0% {
	transform: translate(-50%, -50%) scale3d(1, 1, 1);
}
20% {
	transform: translate(-50%, -50%) scale3d(1.4, 1.4, 1.4);
}
40% {
	transform: translate(-50%, -50%) scale3d(0.9, 0.9, 0.9);
}
60% {
	transform: translate(-50%, -50%) scale3d(1.2, 1.2, 1.2);
}
80% {
	transform: translate(-50%, -50%) scale3d(0.97, 0.97, 0.97);
}
to {
	transform: translate(-50%, -50%) scale3d(1, 1, 1);
}
}

@keyframes progressSuccess {
0, 20%, 40%, 60%, 80%, to {
						   -webkit-animation-timing-function: cubic-bezier(0.215, 0.61, 0.355, 1);
						   animation-timing-function: cubic-bezier(0.215, 0.61, 0.355, 1);
					   }
0% {
	transform: translate(-50%, -50%) scale3d(1, 1, 1);
}
20% {
	transform: translate(-50%, -50%) scale3d(1.4, 1.4, 1.4);
}
40% {
	transform: translate(-50%, -50%) scale3d(0.9, 0.9, 0.9);
}
60% {
	transform: translate(-50%, -50%) scale3d(1.2, 1.2, 1.2);
}
80% {
	transform: translate(-50%, -50%) scale3d(0.97, 0.97, 0.97);
}
to {
	transform: translate(-50%, -50%) scale3d(1, 1, 1);
}
}

.wpeo-wrap .progress-success {
	-webkit-animation-name: progressSuccess;
	animation-name: progressSuccess;
}

@-webkit-keyframes progressError {
	from {
		transform: translate(-50%, -50%);
	}
	15% {
		transform: translate(-50%, -50%) translate3d(-25%, 0, 0) rotate3d(0, 0, 1, -5deg);
	}
	30% {
		transform: translate(-50%, -50%) translate3d(20%, 0, 0) rotate3d(0, 0, 1, 3deg);
	}
	45% {
		transform: translate(-50%, -50%) translate3d(-15%, 0, 0) rotate3d(0, 0, 1, -3deg);
	}
	60% {
		transform: translate(-50%, -50%) translate3d(10%, 0, 0) rotate3d(0, 0, 1, 2deg);
	}
	75% {
		transform: translate(-50%, -50%) translate3d(-5%, 0, 0) rotate3d(0, 0, 1, -1deg);
	}
	to {
		transform: translate(-50%, -50%);
	}
}

@keyframes progressError {
	from {
		transform: translate(-50%, -50%);
	}
	15% {
		transform: translate(-50%, -50%) translate3d(-25%, 0, 0) rotate3d(0, 0, 1, -5deg);
	}
	30% {
		transform: translate(-50%, -50%) translate3d(20%, 0, 0) rotate3d(0, 0, 1, 3deg);
	}
	45% {
		transform: translate(-50%, -50%) translate3d(-15%, 0, 0) rotate3d(0, 0, 1, -3deg);
	}
	60% {
		transform: translate(-50%, -50%) translate3d(10%, 0, 0) rotate3d(0, 0, 1, 2deg);
	}
	75% {
		transform: translate(-50%, -50%) translate3d(-5%, 0, 0) rotate3d(0, 0, 1, -1deg);
	}
	to {
		transform: translate(-50%, -50%);
	}
}

.wpeo-wrap .progress-error {
	-webkit-animation-name: progressError;
	animation-name: progressError;
}

/*.evaluation-method.wpeo-modal .modal-container {*/
/*	max-height: 600px;*/
/*}*/
/**/
/*.evaluation-method.wpeo-modal .cotation {*/
/*	float: left;*/
/*}*/

.wpeo-wrap .wpeo-button-pulse {
	width: 40px;
	min-width: 40px;
	height: 40px;
	line-height: 40px;
	background: rgba(0, 0, 0, 0.1);
	display: block;
	text-align: center;
	border-radius: 4px;
	padding: 0 !important;
	position: relative;
	transition: all 0.2s ease-out;
}

.wpeo-wrap .wpeo-button-pulse .button-icon {
	display: inline-block;
}

.wpeo-wrap .wpeo-button-pulse .button-float-icon {
	position: absolute;
	top: -6px;
	right: -6px;
	background: #d6d6d6;
	padding: 0;
	border-radius: 50%;
	width: 20px;
	height: 20px;
	line-height: 20px;
	font-size: 10px;
}

.wpeo-wrap .wpeo-button-pulse:hover {
	background: rgba(0, 0, 0, 0.3);
	cursor: pointer;
}

.owl-carousel {
	width: 100% !important;
}

.owl-carousel .owl-item img {
	width: 100% !important;
}

.owl-carousel .owl-dots {
	text-align: center;
}

.owl-carousel .owl-dots .owl-dot {
	display: inline-block;
}

.owl-carousel .owl-dots .owl-dot span {
	display: inline-block;
	width: 8px;
	height: 8px;
	border-radius: 50%;
	margin: 0 0.2em;
	background: rgba(0, 0, 0, 0.3);
}

.owl-carousel .owl-dots .owl-dot.active span {
	background: #3495f0;
}

input[type="checkbox"],
input[type="radio"] {
	width: auto !important;
}

.wpeo-form .wpeo-autocomplete .autocomplete-label {
	padding: 0 1.5em;
}

/*--------------------------------------------------------------
# Import Main Menu
--------------------------------------------------------------*/
.toplevel_page_digi-setup #adminmenuwrap, .toplevel_page_digi-setup #wpadminbar, .toplevel_page_digi-setup #adminmenumain, .toplevel_page_digi-setup #screen-meta-links, .toplevel_page_digi-setup #wpfooter,
.admin_page_digirisk-du #adminmenuwrap,
.admin_page_digirisk-du #wpadminbar,
.admin_page_digirisk-du #adminmenumain,
.admin_page_digirisk-du #screen-meta-links,
.admin_page_digirisk-du #wpfooter,
.admin_page_digirisk-causerie #adminmenuwrap,
.admin_page_digirisk-causerie #wpadminbar,
.admin_page_digirisk-causerie #adminmenumain,
.admin_page_digirisk-causerie #screen-meta-links,
.admin_page_digirisk-causerie #wpfooter,
.toplevel_page_digirisk-welcome #adminmenuwrap,
.toplevel_page_digirisk-welcome #wpadminbar,
.toplevel_page_digirisk-welcome #adminmenumain,
.toplevel_page_digirisk-welcome #screen-meta-links,
.toplevel_page_digirisk-welcome #wpfooter,
.profile_page_digirisk-users #adminmenuwrap,
.profile_page_digirisk-users #wpadminbar,
.profile_page_digirisk-users #adminmenumain,
.profile_page_digirisk-users #screen-meta-links,
.profile_page_digirisk-users #wpfooter,
.toplevel_page_digirisk-simple-risk-evaluation #adminmenuwrap,
.toplevel_page_digirisk-simple-risk-evaluation #wpadminbar,
.toplevel_page_digirisk-simple-risk-evaluation #adminmenumain,
.toplevel_page_digirisk-simple-risk-evaluation #screen-meta-links,
.toplevel_page_digirisk-simple-risk-evaluation #wpfooter,
.users_page_digirisk-users #adminmenuwrap,
.users_page_digirisk-users #wpadminbar,
.users_page_digirisk-users #adminmenumain,
.users_page_digirisk-users #screen-meta-links,
.users_page_digirisk-users #wpfooter,
.document-unique_page_digirisk-accident #adminmenuwrap,
.document-unique_page_digirisk-accident #wpadminbar,
.document-unique_page_digirisk-accident #adminmenumain,
.document-unique_page_digirisk-accident #screen-meta-links,
.document-unique_page_digirisk-accident #wpfooter,
.admin_page_digirisk-accident #adminmenuwrap,
.admin_page_digirisk-accident #wpadminbar,
.admin_page_digirisk-accident #adminmenumain,
.admin_page_digirisk-accident #screen-meta-links,
.admin_page_digirisk-accident #wpfooter,
.settings_page_digirisk-setting #adminmenuwrap,
.settings_page_digirisk-setting #wpadminbar,
.settings_page_digirisk-setting #adminmenumain,
.settings_page_digirisk-setting #screen-meta-links,
.settings_page_digirisk-setting #wpfooter,
.tools_page_digirisk-tools #adminmenuwrap,
.tools_page_digirisk-tools #wpadminbar,
.tools_page_digirisk-tools #adminmenumain,
.tools_page_digirisk-tools #screen-meta-links,
.tools_page_digirisk-tools #wpfooter,
.document-unique_page_digirisk-handle-sorter #adminmenuwrap,
.document-unique_page_digirisk-handle-sorter #wpadminbar,
.document-unique_page_digirisk-handle-sorter #adminmenumain,
.document-unique_page_digirisk-handle-sorter #screen-meta-links,
.document-unique_page_digirisk-handle-sorter #wpfooter,
.document-unique_page_digirisk-handle-risk #adminmenuwrap,
.document-unique_page_digirisk-handle-risk #wpadminbar,
.document-unique_page_digirisk-handle-risk #adminmenumain,
.document-unique_page_digirisk-handle-risk #screen-meta-links,
.document-unique_page_digirisk-handle-risk #wpfooter,
.document-unique_page_digirisk-permis-feu #adminmenuwrap,
.document-unique_page_digirisk-permis-feu #wpadminbar,
.document-unique_page_digirisk-permis-feu #adminmenumain,
.document-unique_page_digirisk-permis-feu #screen-meta-links,
.document-unique_page_digirisk-permis-feu #wpfooter,
.admin_page_digirisk-permis-feu #adminmenuwrap,
.admin_page_digirisk-permis-feu #wpadminbar,
.admin_page_digirisk-permis-feu #adminmenumain,
.admin_page_digirisk-permis-feu #screen-meta-links,
.admin_page_digirisk-permis-feu #wpfooter,
.admin_page_digirisk-prevention #adminmenuwrap,
.admin_page_digirisk-prevention #wpadminbar,
.admin_page_digirisk-prevention #adminmenumain,
.admin_page_digirisk-prevention #screen-meta-links,
.admin_page_digirisk-prevention #wpfooter,
.document-unique_page_digirisk-prevention #adminmenuwrap,
.document-unique_page_digirisk-prevention #wpadminbar,
.document-unique_page_digirisk-prevention #adminmenumain,
.document-unique_page_digirisk-prevention #screen-meta-links,
.document-unique_page_digirisk-prevention #wpfooter,
.admin_page_digirisk-handle-risk #adminmenuwrap,
.admin_page_digirisk-handle-risk #wpadminbar,
.admin_page_digirisk-handle-risk #adminmenumain,
.admin_page_digirisk-handle-risk #screen-meta-links,
.admin_page_digirisk-handle-risk #wpfooter,
.admin_page_digirisk-handle-sorter #adminmenuwrap,
.admin_page_digirisk-handle-sorter #wpadminbar,
.admin_page_digirisk-handle-sorter #adminmenumain,
.admin_page_digirisk-handle-sorter #screen-meta-links,
.admin_page_digirisk-handle-sorter #wpfooter,
.document-unique_page_digirisk-causerie #adminmenuwrap,
.document-unique_page_digirisk-causerie #wpadminbar,
.document-unique_page_digirisk-causerie #adminmenumain,
.document-unique_page_digirisk-causerie #screen-meta-links,
.document-unique_page_digirisk-causerie #wpfooter,
.toplevel_page_digirisk-dashboard-sites #adminmenuwrap,
.toplevel_page_digirisk-dashboard-sites #wpadminbar,
.toplevel_page_digirisk-dashboard-sites #adminmenumain,
.toplevel_page_digirisk-dashboard-sites #screen-meta-links,
.toplevel_page_digirisk-dashboard-sites #wpfooter,
.admin_page_digirisk-dashboard-add #adminmenuwrap,
.admin_page_digirisk-dashboard-add #wpadminbar,
.admin_page_digirisk-dashboard-add #adminmenumain,
.admin_page_digirisk-dashboard-add #screen-meta-links,
.admin_page_digirisk-dashboard-add #wpfooter,
.toplevel_page_digirisk-dashboard-duer #adminmenuwrap,
.toplevel_page_digirisk-dashboard-duer #wpadminbar,
.toplevel_page_digirisk-dashboard-duer #adminmenumain,
.toplevel_page_digirisk-dashboard-duer #screen-meta-links,
.toplevel_page_digirisk-dashboard-duer #wpfooter,
.toplevel_page_digirisk-dashboard-model #adminmenuwrap,
.toplevel_page_digirisk-dashboard-model #wpadminbar,
.toplevel_page_digirisk-dashboard-model #adminmenumain,
.toplevel_page_digirisk-dashboard-model #screen-meta-links,
.toplevel_page_digirisk-dashboard-model #wpfooter,
.admin_page_digirisk-dashboard-duer #adminmenuwrap,
.admin_page_digirisk-dashboard-duer #wpadminbar,
.admin_page_digirisk-dashboard-duer #adminmenumain,
.admin_page_digirisk-dashboard-duer #screen-meta-links,
.admin_page_digirisk-dashboard-duer #wpfooter,
.admin_page_digirisk-dashboard-model #adminmenuwrap,
.admin_page_digirisk-dashboard-model #wpadminbar,
.admin_page_digirisk-dashboard-model #adminmenumain,
.admin_page_digirisk-dashboard-model #screen-meta-links,
.admin_page_digirisk-dashboard-model #wpfooter {
	display: none !important;
}

.toplevel_page_digi-setup #wpcontent,
.admin_page_digirisk-du #wpcontent,
.admin_page_digirisk-causerie #wpcontent,
.toplevel_page_digirisk-welcome #wpcontent,
.profile_page_digirisk-users #wpcontent,
.toplevel_page_digirisk-simple-risk-evaluation #wpcontent,
.users_page_digirisk-users #wpcontent,
.document-unique_page_digirisk-accident #wpcontent,
.admin_page_digirisk-accident #wpcontent,
.settings_page_digirisk-setting #wpcontent,
.tools_page_digirisk-tools #wpcontent,
.document-unique_page_digirisk-handle-sorter #wpcontent,
.document-unique_page_digirisk-handle-risk #wpcontent,
.document-unique_page_digirisk-permis-feu #wpcontent,
.admin_page_digirisk-permis-feu #wpcontent,
.admin_page_digirisk-prevention #wpcontent,
.document-unique_page_digirisk-prevention #wpcontent,
.admin_page_digirisk-handle-risk #wpcontent,
.admin_page_digirisk-handle-sorter #wpcontent,
.document-unique_page_digirisk-causerie #wpcontent,
.toplevel_page_digirisk-dashboard-sites #wpcontent,
.admin_page_digirisk-dashboard-add #wpcontent,
.toplevel_page_digirisk-dashboard-duer #wpcontent,
.toplevel_page_digirisk-dashboard-model #wpcontent,
.admin_page_digirisk-dashboard-duer #wpcontent,
.admin_page_digirisk-dashboard-model #wpcontent {
	margin-left: 0 !important;
	padding-left: 0;
}

.toplevel_page_digi-setup .content-wrap,
.admin_page_digirisk-du .content-wrap,
.admin_page_digirisk-causerie .content-wrap,
.toplevel_page_digirisk-welcome .content-wrap,
.profile_page_digirisk-users .content-wrap,
.toplevel_page_digirisk-simple-risk-evaluation .content-wrap,
.users_page_digirisk-users .content-wrap,
.document-unique_page_digirisk-accident .content-wrap,
.admin_page_digirisk-accident .content-wrap,
.settings_page_digirisk-setting .content-wrap,
.tools_page_digirisk-tools .content-wrap,
.document-unique_page_digirisk-handle-sorter .content-wrap,
.document-unique_page_digirisk-handle-risk .content-wrap,
.document-unique_page_digirisk-permis-feu .content-wrap,
.admin_page_digirisk-permis-feu .content-wrap,
.admin_page_digirisk-prevention .content-wrap,
.document-unique_page_digirisk-prevention .content-wrap,
.admin_page_digirisk-handle-risk .content-wrap,
.admin_page_digirisk-handle-sorter .content-wrap,
.document-unique_page_digirisk-causerie .content-wrap,
.toplevel_page_digirisk-dashboard-sites .content-wrap,
.admin_page_digirisk-dashboard-add .content-wrap,
.toplevel_page_digirisk-dashboard-duer .content-wrap,
.toplevel_page_digirisk-dashboard-model .content-wrap,
.admin_page_digirisk-dashboard-duer .content-wrap,
.admin_page_digirisk-dashboard-model .content-wrap {
	transition: margin-left 0.2s ease-in;
}

.toplevel_page_digi-setup .content-wrap.content-reduce,
.admin_page_digirisk-du .content-wrap.content-reduce,
.admin_page_digirisk-causerie .content-wrap.content-reduce,
.toplevel_page_digirisk-welcome .content-wrap.content-reduce,
.profile_page_digirisk-users .content-wrap.content-reduce,
.toplevel_page_digirisk-simple-risk-evaluation .content-wrap.content-reduce,
.users_page_digirisk-users .content-wrap.content-reduce,
.document-unique_page_digirisk-accident .content-wrap.content-reduce,
.admin_page_digirisk-accident .content-wrap.content-reduce,
.settings_page_digirisk-setting .content-wrap.content-reduce,
.tools_page_digirisk-tools .content-wrap.content-reduce,
.document-unique_page_digirisk-handle-sorter .content-wrap.content-reduce,
.document-unique_page_digirisk-handle-risk .content-wrap.content-reduce,
.document-unique_page_digirisk-permis-feu .content-wrap.content-reduce,
.admin_page_digirisk-permis-feu .content-wrap.content-reduce,
.admin_page_digirisk-prevention .content-wrap.content-reduce,
.document-unique_page_digirisk-prevention .content-wrap.content-reduce,
.admin_page_digirisk-handle-risk .content-wrap.content-reduce,
.admin_page_digirisk-handle-sorter .content-wrap.content-reduce,
.document-unique_page_digirisk-causerie .content-wrap.content-reduce,
.toplevel_page_digirisk-dashboard-sites .content-wrap.content-reduce,
.admin_page_digirisk-dashboard-add .content-wrap.content-reduce,
.toplevel_page_digirisk-dashboard-duer .content-wrap.content-reduce,
.toplevel_page_digirisk-dashboard-model .content-wrap.content-reduce,
.admin_page_digirisk-dashboard-duer .content-wrap.content-reduce,
.admin_page_digirisk-dashboard-model .content-wrap.content-reduce {
	margin-left: 50px;
}

.toplevel_page_digi-setup .nav-wrap,
.admin_page_digirisk-du .nav-wrap,
.admin_page_digirisk-causerie .nav-wrap,
.toplevel_page_digirisk-welcome .nav-wrap,
.profile_page_digirisk-users .nav-wrap,
.toplevel_page_digirisk-simple-risk-evaluation .nav-wrap,
.users_page_digirisk-users .nav-wrap,
.document-unique_page_digirisk-accident .nav-wrap,
.admin_page_digirisk-accident .nav-wrap,
.settings_page_digirisk-setting .nav-wrap,
.tools_page_digirisk-tools .nav-wrap,
.document-unique_page_digirisk-handle-sorter .nav-wrap,
.document-unique_page_digirisk-handle-risk .nav-wrap,
.document-unique_page_digirisk-permis-feu .nav-wrap,
.admin_page_digirisk-permis-feu .nav-wrap,
.admin_page_digirisk-prevention .nav-wrap,
.document-unique_page_digirisk-prevention .nav-wrap,
.admin_page_digirisk-handle-risk .nav-wrap,
.admin_page_digirisk-handle-sorter .nav-wrap,
.document-unique_page_digirisk-causerie .nav-wrap,
.toplevel_page_digirisk-dashboard-sites .nav-wrap,
.admin_page_digirisk-dashboard-add .nav-wrap,
.toplevel_page_digirisk-dashboard-duer .nav-wrap,
.toplevel_page_digirisk-dashboard-model .nav-wrap,
.admin_page_digirisk-dashboard-duer .nav-wrap,
.admin_page_digirisk-dashboard-model .nav-wrap {
	background: #1c1d1b;
	width: 200px;
	position: fixed;
	left: 0;
	top: 0;
	bottom: 0px;
	transition: width 0.2s ease-in;
}

.toplevel_page_digi-setup .nav-wrap.wrap-reduce,
.admin_page_digirisk-du .nav-wrap.wrap-reduce,
.admin_page_digirisk-causerie .nav-wrap.wrap-reduce,
.toplevel_page_digirisk-welcome .nav-wrap.wrap-reduce,
.profile_page_digirisk-users .nav-wrap.wrap-reduce,
.toplevel_page_digirisk-simple-risk-evaluation .nav-wrap.wrap-reduce,
.users_page_digirisk-users .nav-wrap.wrap-reduce,
.document-unique_page_digirisk-accident .nav-wrap.wrap-reduce,
.admin_page_digirisk-accident .nav-wrap.wrap-reduce,
.settings_page_digirisk-setting .nav-wrap.wrap-reduce,
.tools_page_digirisk-tools .nav-wrap.wrap-reduce,
.document-unique_page_digirisk-handle-sorter .nav-wrap.wrap-reduce,
.document-unique_page_digirisk-handle-risk .nav-wrap.wrap-reduce,
.document-unique_page_digirisk-permis-feu .nav-wrap.wrap-reduce,
.admin_page_digirisk-permis-feu .nav-wrap.wrap-reduce,
.admin_page_digirisk-prevention .nav-wrap.wrap-reduce,
.document-unique_page_digirisk-prevention .nav-wrap.wrap-reduce,
.admin_page_digirisk-handle-risk .nav-wrap.wrap-reduce,
.admin_page_digirisk-handle-sorter .nav-wrap.wrap-reduce,
.document-unique_page_digirisk-causerie .nav-wrap.wrap-reduce,
.toplevel_page_digirisk-dashboard-sites .nav-wrap.wrap-reduce,
.admin_page_digirisk-dashboard-add .nav-wrap.wrap-reduce,
.toplevel_page_digirisk-dashboard-duer .nav-wrap.wrap-reduce,
.toplevel_page_digirisk-dashboard-model .nav-wrap.wrap-reduce,
.admin_page_digirisk-dashboard-duer .nav-wrap.wrap-reduce,
.admin_page_digirisk-dashboard-model .nav-wrap.wrap-reduce {
	width: 50px;
}

.toplevel_page_digi-setup .nav-wrap.wrap-reduce .nav-menu,
.admin_page_digirisk-du .nav-wrap.wrap-reduce .nav-menu,
.admin_page_digirisk-causerie .nav-wrap.wrap-reduce .nav-menu,
.toplevel_page_digirisk-welcome .nav-wrap.wrap-reduce .nav-menu,
.profile_page_digirisk-users .nav-wrap.wrap-reduce .nav-menu,
.toplevel_page_digirisk-simple-risk-evaluation .nav-wrap.wrap-reduce .nav-menu,
.users_page_digirisk-users .nav-wrap.wrap-reduce .nav-menu,
.document-unique_page_digirisk-accident .nav-wrap.wrap-reduce .nav-menu,
.admin_page_digirisk-accident .nav-wrap.wrap-reduce .nav-menu,
.settings_page_digirisk-setting .nav-wrap.wrap-reduce .nav-menu,
.tools_page_digirisk-tools .nav-wrap.wrap-reduce .nav-menu,
.document-unique_page_digirisk-handle-sorter .nav-wrap.wrap-reduce .nav-menu,
.document-unique_page_digirisk-handle-risk .nav-wrap.wrap-reduce .nav-menu,
.document-unique_page_digirisk-permis-feu .nav-wrap.wrap-reduce .nav-menu,
.admin_page_digirisk-permis-feu .nav-wrap.wrap-reduce .nav-menu,
.admin_page_digirisk-prevention .nav-wrap.wrap-reduce .nav-menu,
.document-unique_page_digirisk-prevention .nav-wrap.wrap-reduce .nav-menu,
.admin_page_digirisk-handle-risk .nav-wrap.wrap-reduce .nav-menu,
.admin_page_digirisk-handle-sorter .nav-wrap.wrap-reduce .nav-menu,
.document-unique_page_digirisk-causerie .nav-wrap.wrap-reduce .nav-menu,
.toplevel_page_digirisk-dashboard-sites .nav-wrap.wrap-reduce .nav-menu,
.admin_page_digirisk-dashboard-add .nav-wrap.wrap-reduce .nav-menu,
.toplevel_page_digirisk-dashboard-duer .nav-wrap.wrap-reduce .nav-menu,
.toplevel_page_digirisk-dashboard-model .nav-wrap.wrap-reduce .nav-menu,
.admin_page_digirisk-dashboard-duer .nav-wrap.wrap-reduce .nav-menu,
.admin_page_digirisk-dashboard-model .nav-wrap.wrap-reduce .nav-menu {
	width: 50px;
}

.toplevel_page_digi-setup .nav-wrap.wrap-reduce .nav-menu a,
.admin_page_digirisk-du .nav-wrap.wrap-reduce .nav-menu a,
.admin_page_digirisk-causerie .nav-wrap.wrap-reduce .nav-menu a,
.toplevel_page_digirisk-welcome .nav-wrap.wrap-reduce .nav-menu a,
.profile_page_digirisk-users .nav-wrap.wrap-reduce .nav-menu a,
.toplevel_page_digirisk-simple-risk-evaluation .nav-wrap.wrap-reduce .nav-menu a,
.users_page_digirisk-users .nav-wrap.wrap-reduce .nav-menu a,
.document-unique_page_digirisk-accident .nav-wrap.wrap-reduce .nav-menu a,
.admin_page_digirisk-accident .nav-wrap.wrap-reduce .nav-menu a,
.settings_page_digirisk-setting .nav-wrap.wrap-reduce .nav-menu a,
.tools_page_digirisk-tools .nav-wrap.wrap-reduce .nav-menu a,
.document-unique_page_digirisk-handle-sorter .nav-wrap.wrap-reduce .nav-menu a,
.document-unique_page_digirisk-handle-risk .nav-wrap.wrap-reduce .nav-menu a,
.document-unique_page_digirisk-permis-feu .nav-wrap.wrap-reduce .nav-menu a,
.admin_page_digirisk-permis-feu .nav-wrap.wrap-reduce .nav-menu a,
.admin_page_digirisk-prevention .nav-wrap.wrap-reduce .nav-menu a,
.document-unique_page_digirisk-prevention .nav-wrap.wrap-reduce .nav-menu a,
.admin_page_digirisk-handle-risk .nav-wrap.wrap-reduce .nav-menu a,
.admin_page_digirisk-handle-sorter .nav-wrap.wrap-reduce .nav-menu a,
.document-unique_page_digirisk-causerie .nav-wrap.wrap-reduce .nav-menu a,
.toplevel_page_digirisk-dashboard-sites .nav-wrap.wrap-reduce .nav-menu a,
.admin_page_digirisk-dashboard-add .nav-wrap.wrap-reduce .nav-menu a,
.toplevel_page_digirisk-dashboard-duer .nav-wrap.wrap-reduce .nav-menu a,
.toplevel_page_digirisk-dashboard-model .nav-wrap.wrap-reduce .nav-menu a,
.admin_page_digirisk-dashboard-duer .nav-wrap.wrap-reduce .nav-menu a,
.admin_page_digirisk-dashboard-model .nav-wrap.wrap-reduce .nav-menu a {
	width: 50px;
}

.toplevel_page_digi-setup .nav-wrap.wrap-reduce .nav-menu a span,
.admin_page_digirisk-du .nav-wrap.wrap-reduce .nav-menu a span,
.admin_page_digirisk-causerie .nav-wrap.wrap-reduce .nav-menu a span,
.toplevel_page_digirisk-welcome .nav-wrap.wrap-reduce .nav-menu a span,
.profile_page_digirisk-users .nav-wrap.wrap-reduce .nav-menu a span,
.toplevel_page_digirisk-simple-risk-evaluation .nav-wrap.wrap-reduce .nav-menu a span,
.users_page_digirisk-users .nav-wrap.wrap-reduce .nav-menu a span,
.document-unique_page_digirisk-accident .nav-wrap.wrap-reduce .nav-menu a span,
.admin_page_digirisk-accident .nav-wrap.wrap-reduce .nav-menu a span,
.settings_page_digirisk-setting .nav-wrap.wrap-reduce .nav-menu a span,
.tools_page_digirisk-tools .nav-wrap.wrap-reduce .nav-menu a span,
.document-unique_page_digirisk-handle-sorter .nav-wrap.wrap-reduce .nav-menu a span,
.document-unique_page_digirisk-handle-risk .nav-wrap.wrap-reduce .nav-menu a span,
.document-unique_page_digirisk-permis-feu .nav-wrap.wrap-reduce .nav-menu a span,
.admin_page_digirisk-permis-feu .nav-wrap.wrap-reduce .nav-menu a span,
.admin_page_digirisk-prevention .nav-wrap.wrap-reduce .nav-menu a span,
.document-unique_page_digirisk-prevention .nav-wrap.wrap-reduce .nav-menu a span,
.admin_page_digirisk-handle-risk .nav-wrap.wrap-reduce .nav-menu a span,
.admin_page_digirisk-handle-sorter .nav-wrap.wrap-reduce .nav-menu a span,
.document-unique_page_digirisk-causerie .nav-wrap.wrap-reduce .nav-menu a span,
.toplevel_page_digirisk-dashboard-sites .nav-wrap.wrap-reduce .nav-menu a span,
.admin_page_digirisk-dashboard-add .nav-wrap.wrap-reduce .nav-menu a span,
.toplevel_page_digirisk-dashboard-duer .nav-wrap.wrap-reduce .nav-menu a span,
.toplevel_page_digirisk-dashboard-model .nav-wrap.wrap-reduce .nav-menu a span,
.admin_page_digirisk-dashboard-duer .nav-wrap.wrap-reduce .nav-menu a span,
.admin_page_digirisk-dashboard-model .nav-wrap.wrap-reduce .nav-menu a span {
	display: none;
}

.toplevel_page_digi-setup .nav-wrap.wrap-reduce .nav-menu a div,
.admin_page_digirisk-du .nav-wrap.wrap-reduce .nav-menu a div,
.admin_page_digirisk-causerie .nav-wrap.wrap-reduce .nav-menu a div,
.toplevel_page_digirisk-welcome .nav-wrap.wrap-reduce .nav-menu a div,
.profile_page_digirisk-users .nav-wrap.wrap-reduce .nav-menu a div,
.toplevel_page_digirisk-simple-risk-evaluation .nav-wrap.wrap-reduce .nav-menu a div,
.users_page_digirisk-users .nav-wrap.wrap-reduce .nav-menu a div,
.document-unique_page_digirisk-accident .nav-wrap.wrap-reduce .nav-menu a div,
.admin_page_digirisk-accident .nav-wrap.wrap-reduce .nav-menu a div,
.settings_page_digirisk-setting .nav-wrap.wrap-reduce .nav-menu a div,
.tools_page_digirisk-tools .nav-wrap.wrap-reduce .nav-menu a div,
.document-unique_page_digirisk-handle-sorter .nav-wrap.wrap-reduce .nav-menu a div,
.document-unique_page_digirisk-handle-risk .nav-wrap.wrap-reduce .nav-menu a div,
.document-unique_page_digirisk-permis-feu .nav-wrap.wrap-reduce .nav-menu a div,
.admin_page_digirisk-permis-feu .nav-wrap.wrap-reduce .nav-menu a div,
.admin_page_digirisk-prevention .nav-wrap.wrap-reduce .nav-menu a div,
.document-unique_page_digirisk-prevention .nav-wrap.wrap-reduce .nav-menu a div,
.admin_page_digirisk-handle-risk .nav-wrap.wrap-reduce .nav-menu a div,
.admin_page_digirisk-handle-sorter .nav-wrap.wrap-reduce .nav-menu a div,
.document-unique_page_digirisk-causerie .nav-wrap.wrap-reduce .nav-menu a div,
.toplevel_page_digirisk-dashboard-sites .nav-wrap.wrap-reduce .nav-menu a div,
.admin_page_digirisk-dashboard-add .nav-wrap.wrap-reduce .nav-menu a div,
.toplevel_page_digirisk-dashboard-duer .nav-wrap.wrap-reduce .nav-menu a div,
.toplevel_page_digirisk-dashboard-model .nav-wrap.wrap-reduce .nav-menu a div,
.admin_page_digirisk-dashboard-duer .nav-wrap.wrap-reduce .nav-menu a div,
.admin_page_digirisk-dashboard-model .nav-wrap.wrap-reduce .nav-menu a div {
	padding-left: 16px;
	text-align: center;
	margin: auto;
}

.toplevel_page_digi-setup .nav-wrap.wrap-reduce .nav-menu a i,
.admin_page_digirisk-du .nav-wrap.wrap-reduce .nav-menu a i,
.admin_page_digirisk-causerie .nav-wrap.wrap-reduce .nav-menu a i,
.toplevel_page_digirisk-welcome .nav-wrap.wrap-reduce .nav-menu a i,
.profile_page_digirisk-users .nav-wrap.wrap-reduce .nav-menu a i,
.toplevel_page_digirisk-simple-risk-evaluation .nav-wrap.wrap-reduce .nav-menu a i,
.users_page_digirisk-users .nav-wrap.wrap-reduce .nav-menu a i,
.document-unique_page_digirisk-accident .nav-wrap.wrap-reduce .nav-menu a i,
.admin_page_digirisk-accident .nav-wrap.wrap-reduce .nav-menu a i,
.settings_page_digirisk-setting .nav-wrap.wrap-reduce .nav-menu a i,
.tools_page_digirisk-tools .nav-wrap.wrap-reduce .nav-menu a i,
.document-unique_page_digirisk-handle-sorter .nav-wrap.wrap-reduce .nav-menu a i,
.document-unique_page_digirisk-handle-risk .nav-wrap.wrap-reduce .nav-menu a i,
.document-unique_page_digirisk-permis-feu .nav-wrap.wrap-reduce .nav-menu a i,
.admin_page_digirisk-permis-feu .nav-wrap.wrap-reduce .nav-menu a i,
.admin_page_digirisk-prevention .nav-wrap.wrap-reduce .nav-menu a i,
.document-unique_page_digirisk-prevention .nav-wrap.wrap-reduce .nav-menu a i,
.admin_page_digirisk-handle-risk .nav-wrap.wrap-reduce .nav-menu a i,
.admin_page_digirisk-handle-sorter .nav-wrap.wrap-reduce .nav-menu a i,
.document-unique_page_digirisk-causerie .nav-wrap.wrap-reduce .nav-menu a i,
.toplevel_page_digirisk-dashboard-sites .nav-wrap.wrap-reduce .nav-menu a i,
.admin_page_digirisk-dashboard-add .nav-wrap.wrap-reduce .nav-menu a i,
.toplevel_page_digirisk-dashboard-duer .nav-wrap.wrap-reduce .nav-menu a i,
.toplevel_page_digirisk-dashboard-model .nav-wrap.wrap-reduce .nav-menu a i,
.admin_page_digirisk-dashboard-duer .nav-wrap.wrap-reduce .nav-menu a i,
.admin_page_digirisk-dashboard-model .nav-wrap.wrap-reduce .nav-menu a i {
	margin: auto;
}

.toplevel_page_digi-setup .nav-wrap.wrap-reduce #logo,
.admin_page_digirisk-du .nav-wrap.wrap-reduce #logo,
.admin_page_digirisk-causerie .nav-wrap.wrap-reduce #logo,
.toplevel_page_digirisk-welcome .nav-wrap.wrap-reduce #logo,
.profile_page_digirisk-users .nav-wrap.wrap-reduce #logo,
.toplevel_page_digirisk-simple-risk-evaluation .nav-wrap.wrap-reduce #logo,
.users_page_digirisk-users .nav-wrap.wrap-reduce #logo,
.document-unique_page_digirisk-accident .nav-wrap.wrap-reduce #logo,
.admin_page_digirisk-accident .nav-wrap.wrap-reduce #logo,
.settings_page_digirisk-setting .nav-wrap.wrap-reduce #logo,
.tools_page_digirisk-tools .nav-wrap.wrap-reduce #logo,
.document-unique_page_digirisk-handle-sorter .nav-wrap.wrap-reduce #logo,
.document-unique_page_digirisk-handle-risk .nav-wrap.wrap-reduce #logo,
.document-unique_page_digirisk-permis-feu .nav-wrap.wrap-reduce #logo,
.admin_page_digirisk-permis-feu .nav-wrap.wrap-reduce #logo,
.admin_page_digirisk-prevention .nav-wrap.wrap-reduce #logo,
.document-unique_page_digirisk-prevention .nav-wrap.wrap-reduce #logo,
.admin_page_digirisk-handle-risk .nav-wrap.wrap-reduce #logo,
.admin_page_digirisk-handle-sorter .nav-wrap.wrap-reduce #logo,
.document-unique_page_digirisk-causerie .nav-wrap.wrap-reduce #logo,
.toplevel_page_digirisk-dashboard-sites .nav-wrap.wrap-reduce #logo,
.admin_page_digirisk-dashboard-add .nav-wrap.wrap-reduce #logo,
.toplevel_page_digirisk-dashboard-duer .nav-wrap.wrap-reduce #logo,
.toplevel_page_digirisk-dashboard-model .nav-wrap.wrap-reduce #logo,
.admin_page_digirisk-dashboard-duer .nav-wrap.wrap-reduce #logo,
.admin_page_digirisk-dashboard-model .nav-wrap.wrap-reduce #logo {
	height: 40px;
}

.toplevel_page_digi-setup .nav-wrap.wrap-reduce #logo h1,
.admin_page_digirisk-du .nav-wrap.wrap-reduce #logo h1,
.admin_page_digirisk-causerie .nav-wrap.wrap-reduce #logo h1,
.toplevel_page_digirisk-welcome .nav-wrap.wrap-reduce #logo h1,
.profile_page_digirisk-users .nav-wrap.wrap-reduce #logo h1,
.toplevel_page_digirisk-simple-risk-evaluation .nav-wrap.wrap-reduce #logo h1,
.users_page_digirisk-users .nav-wrap.wrap-reduce #logo h1,
.document-unique_page_digirisk-accident .nav-wrap.wrap-reduce #logo h1,
.admin_page_digirisk-accident .nav-wrap.wrap-reduce #logo h1,
.settings_page_digirisk-setting .nav-wrap.wrap-reduce #logo h1,
.tools_page_digirisk-tools .nav-wrap.wrap-reduce #logo h1,
.document-unique_page_digirisk-handle-sorter .nav-wrap.wrap-reduce #logo h1,
.document-unique_page_digirisk-handle-risk .nav-wrap.wrap-reduce #logo h1,
.document-unique_page_digirisk-permis-feu .nav-wrap.wrap-reduce #logo h1,
.admin_page_digirisk-permis-feu .nav-wrap.wrap-reduce #logo h1,
.admin_page_digirisk-prevention .nav-wrap.wrap-reduce #logo h1,
.document-unique_page_digirisk-prevention .nav-wrap.wrap-reduce #logo h1,
.admin_page_digirisk-handle-risk .nav-wrap.wrap-reduce #logo h1,
.admin_page_digirisk-handle-sorter .nav-wrap.wrap-reduce #logo h1,
.document-unique_page_digirisk-causerie .nav-wrap.wrap-reduce #logo h1,
.toplevel_page_digirisk-dashboard-sites .nav-wrap.wrap-reduce #logo h1,
.admin_page_digirisk-dashboard-add .nav-wrap.wrap-reduce #logo h1,
.toplevel_page_digirisk-dashboard-duer .nav-wrap.wrap-reduce #logo h1,
.toplevel_page_digirisk-dashboard-model .nav-wrap.wrap-reduce #logo h1,
.admin_page_digirisk-dashboard-duer .nav-wrap.wrap-reduce #logo h1,
.admin_page_digirisk-dashboard-model .nav-wrap.wrap-reduce #logo h1 {
	height: inherit;
}

.toplevel_page_digi-setup .nav-wrap.wrap-reduce #logo a,
.admin_page_digirisk-du .nav-wrap.wrap-reduce #logo a,
.admin_page_digirisk-causerie .nav-wrap.wrap-reduce #logo a,
.toplevel_page_digirisk-welcome .nav-wrap.wrap-reduce #logo a,
.profile_page_digirisk-users .nav-wrap.wrap-reduce #logo a,
.toplevel_page_digirisk-simple-risk-evaluation .nav-wrap.wrap-reduce #logo a,
.users_page_digirisk-users .nav-wrap.wrap-reduce #logo a,
.document-unique_page_digirisk-accident .nav-wrap.wrap-reduce #logo a,
.admin_page_digirisk-accident .nav-wrap.wrap-reduce #logo a,
.settings_page_digirisk-setting .nav-wrap.wrap-reduce #logo a,
.tools_page_digirisk-tools .nav-wrap.wrap-reduce #logo a,
.document-unique_page_digirisk-handle-sorter .nav-wrap.wrap-reduce #logo a,
.document-unique_page_digirisk-handle-risk .nav-wrap.wrap-reduce #logo a,
.document-unique_page_digirisk-permis-feu .nav-wrap.wrap-reduce #logo a,
.admin_page_digirisk-permis-feu .nav-wrap.wrap-reduce #logo a,
.admin_page_digirisk-prevention .nav-wrap.wrap-reduce #logo a,
.document-unique_page_digirisk-prevention .nav-wrap.wrap-reduce #logo a,
.admin_page_digirisk-handle-risk .nav-wrap.wrap-reduce #logo a,
.admin_page_digirisk-handle-sorter .nav-wrap.wrap-reduce #logo a,
.document-unique_page_digirisk-causerie .nav-wrap.wrap-reduce #logo a,
.toplevel_page_digirisk-dashboard-sites .nav-wrap.wrap-reduce #logo a,
.admin_page_digirisk-dashboard-add .nav-wrap.wrap-reduce #logo a,
.toplevel_page_digirisk-dashboard-duer .nav-wrap.wrap-reduce #logo a,
.toplevel_page_digirisk-dashboard-model .nav-wrap.wrap-reduce #logo a,
.admin_page_digirisk-dashboard-duer .nav-wrap.wrap-reduce #logo a,
.admin_page_digirisk-dashboard-model .nav-wrap.wrap-reduce #logo a {
	height: inherit;
}

.toplevel_page_digi-setup .nav-wrap.wrap-reduce #logo img,
.admin_page_digirisk-du .nav-wrap.wrap-reduce #logo img,
.admin_page_digirisk-causerie .nav-wrap.wrap-reduce #logo img,
.toplevel_page_digirisk-welcome .nav-wrap.wrap-reduce #logo img,
.profile_page_digirisk-users .nav-wrap.wrap-reduce #logo img,
.toplevel_page_digirisk-simple-risk-evaluation .nav-wrap.wrap-reduce #logo img,
.users_page_digirisk-users .nav-wrap.wrap-reduce #logo img,
.document-unique_page_digirisk-accident .nav-wrap.wrap-reduce #logo img,
.admin_page_digirisk-accident .nav-wrap.wrap-reduce #logo img,
.settings_page_digirisk-setting .nav-wrap.wrap-reduce #logo img,
.tools_page_digirisk-tools .nav-wrap.wrap-reduce #logo img,
.document-unique_page_digirisk-handle-sorter .nav-wrap.wrap-reduce #logo img,
.document-unique_page_digirisk-handle-risk .nav-wrap.wrap-reduce #logo img,
.document-unique_page_digirisk-permis-feu .nav-wrap.wrap-reduce #logo img,
.admin_page_digirisk-permis-feu .nav-wrap.wrap-reduce #logo img,
.admin_page_digirisk-prevention .nav-wrap.wrap-reduce #logo img,
.document-unique_page_digirisk-prevention .nav-wrap.wrap-reduce #logo img,
.admin_page_digirisk-handle-risk .nav-wrap.wrap-reduce #logo img,
.admin_page_digirisk-handle-sorter .nav-wrap.wrap-reduce #logo img,
.document-unique_page_digirisk-causerie .nav-wrap.wrap-reduce #logo img,
.toplevel_page_digirisk-dashboard-sites .nav-wrap.wrap-reduce #logo img,
.admin_page_digirisk-dashboard-add .nav-wrap.wrap-reduce #logo img,
.toplevel_page_digirisk-dashboard-duer .nav-wrap.wrap-reduce #logo img,
.toplevel_page_digirisk-dashboard-model .nav-wrap.wrap-reduce #logo img,
.admin_page_digirisk-dashboard-duer .nav-wrap.wrap-reduce #logo img,
.admin_page_digirisk-dashboard-model .nav-wrap.wrap-reduce #logo img {
	height: inherit;
}

.toplevel_page_digi-setup .nav-wrap #logo,
.admin_page_digirisk-du .nav-wrap #logo,
.admin_page_digirisk-causerie .nav-wrap #logo,
.toplevel_page_digirisk-welcome .nav-wrap #logo,
.profile_page_digirisk-users .nav-wrap #logo,
.toplevel_page_digirisk-simple-risk-evaluation .nav-wrap #logo,
.users_page_digirisk-users .nav-wrap #logo,
.document-unique_page_digirisk-accident .nav-wrap #logo,
.admin_page_digirisk-accident .nav-wrap #logo,
.settings_page_digirisk-setting .nav-wrap #logo,
.tools_page_digirisk-tools .nav-wrap #logo,
.document-unique_page_digirisk-handle-sorter .nav-wrap #logo,
.document-unique_page_digirisk-handle-risk .nav-wrap #logo,
.document-unique_page_digirisk-permis-feu .nav-wrap #logo,
.admin_page_digirisk-permis-feu .nav-wrap #logo,
.admin_page_digirisk-prevention .nav-wrap #logo,
.document-unique_page_digirisk-prevention .nav-wrap #logo,
.admin_page_digirisk-handle-risk .nav-wrap #logo,
.admin_page_digirisk-handle-sorter .nav-wrap #logo,
.document-unique_page_digirisk-causerie .nav-wrap #logo,
.toplevel_page_digirisk-dashboard-sites .nav-wrap #logo,
.admin_page_digirisk-dashboard-add .nav-wrap #logo,
.toplevel_page_digirisk-dashboard-duer .nav-wrap #logo,
.toplevel_page_digirisk-dashboard-model .nav-wrap #logo,
.admin_page_digirisk-dashboard-duer .nav-wrap #logo,
.admin_page_digirisk-dashboard-model .nav-wrap #logo {
	transition: width 0.2s ease-in;
	margin: auto;
	text-align: center;
}

.toplevel_page_digi-setup .nav-wrap .nav-menu,
.admin_page_digirisk-du .nav-wrap .nav-menu,
.admin_page_digirisk-causerie .nav-wrap .nav-menu,
.toplevel_page_digirisk-welcome .nav-wrap .nav-menu,
.profile_page_digirisk-users .nav-wrap .nav-menu,
.toplevel_page_digirisk-simple-risk-evaluation .nav-wrap .nav-menu,
.users_page_digirisk-users .nav-wrap .nav-menu,
.document-unique_page_digirisk-accident .nav-wrap .nav-menu,
.admin_page_digirisk-accident .nav-wrap .nav-menu,
.settings_page_digirisk-setting .nav-wrap .nav-menu,
.tools_page_digirisk-tools .nav-wrap .nav-menu,
.document-unique_page_digirisk-handle-sorter .nav-wrap .nav-menu,
.document-unique_page_digirisk-handle-risk .nav-wrap .nav-menu,
.document-unique_page_digirisk-permis-feu .nav-wrap .nav-menu,
.admin_page_digirisk-permis-feu .nav-wrap .nav-menu,
.admin_page_digirisk-prevention .nav-wrap .nav-menu,
.document-unique_page_digirisk-prevention .nav-wrap .nav-menu,
.admin_page_digirisk-handle-risk .nav-wrap .nav-menu,
.admin_page_digirisk-handle-sorter .nav-wrap .nav-menu,
.document-unique_page_digirisk-causerie .nav-wrap .nav-menu,
.toplevel_page_digirisk-dashboard-sites .nav-wrap .nav-menu,
.admin_page_digirisk-dashboard-add .nav-wrap .nav-menu,
.toplevel_page_digirisk-dashboard-duer .nav-wrap .nav-menu,
.toplevel_page_digirisk-dashboard-model .nav-wrap .nav-menu,
.admin_page_digirisk-dashboard-duer .nav-wrap .nav-menu,
.admin_page_digirisk-dashboard-model .nav-wrap .nav-menu {
	transition: width 0.2s ease-in;
	overflow: hidden;
}

.toplevel_page_digi-setup .nav-wrap .nav-menu .item,
.admin_page_digirisk-du .nav-wrap .nav-menu .item,
.admin_page_digirisk-causerie .nav-wrap .nav-menu .item,
.toplevel_page_digirisk-welcome .nav-wrap .nav-menu .item,
.profile_page_digirisk-users .nav-wrap .nav-menu .item,
.toplevel_page_digirisk-simple-risk-evaluation .nav-wrap .nav-menu .item,
.users_page_digirisk-users .nav-wrap .nav-menu .item,
.document-unique_page_digirisk-accident .nav-wrap .nav-menu .item,
.admin_page_digirisk-accident .nav-wrap .nav-menu .item,
.settings_page_digirisk-setting .nav-wrap .nav-menu .item,
.tools_page_digirisk-tools .nav-wrap .nav-menu .item,
.document-unique_page_digirisk-handle-sorter .nav-wrap .nav-menu .item,
.document-unique_page_digirisk-handle-risk .nav-wrap .nav-menu .item,
.document-unique_page_digirisk-permis-feu .nav-wrap .nav-menu .item,
.admin_page_digirisk-permis-feu .nav-wrap .nav-menu .item,
.admin_page_digirisk-prevention .nav-wrap .nav-menu .item,
.document-unique_page_digirisk-prevention .nav-wrap .nav-menu .item,
.admin_page_digirisk-handle-risk .nav-wrap .nav-menu .item,
.admin_page_digirisk-handle-sorter .nav-wrap .nav-menu .item,
.document-unique_page_digirisk-causerie .nav-wrap .nav-menu .item,
.toplevel_page_digirisk-dashboard-sites .nav-wrap .nav-menu .item,
.admin_page_digirisk-dashboard-add .nav-wrap .nav-menu .item,
.toplevel_page_digirisk-dashboard-duer .nav-wrap .nav-menu .item,
.toplevel_page_digirisk-dashboard-model .nav-wrap .nav-menu .item,
.admin_page_digirisk-dashboard-duer .nav-wrap .nav-menu .item,
.admin_page_digirisk-dashboard-model .nav-wrap .nav-menu .item {
	transition: width 0.2s ease-in;
	display: block;
	transition: background .1s ease,box-shadow .1s ease,color .1s ease;
	text-decoration: none;
}

.toplevel_page_digi-setup .nav-wrap .nav-menu .item:before,
.admin_page_digirisk-du .nav-wrap .nav-menu .item:before,
.admin_page_digirisk-causerie .nav-wrap .nav-menu .item:before,
.toplevel_page_digirisk-welcome .nav-wrap .nav-menu .item:before,
.profile_page_digirisk-users .nav-wrap .nav-menu .item:before,
.toplevel_page_digirisk-simple-risk-evaluation .nav-wrap .nav-menu .item:before,
.users_page_digirisk-users .nav-wrap .nav-menu .item:before,
.document-unique_page_digirisk-accident .nav-wrap .nav-menu .item:before,
.admin_page_digirisk-accident .nav-wrap .nav-menu .item:before,
.settings_page_digirisk-setting .nav-wrap .nav-menu .item:before,
.tools_page_digirisk-tools .nav-wrap .nav-menu .item:before,
.document-unique_page_digirisk-handle-sorter .nav-wrap .nav-menu .item:before,
.document-unique_page_digirisk-handle-risk .nav-wrap .nav-menu .item:before,
.document-unique_page_digirisk-permis-feu .nav-wrap .nav-menu .item:before,
.admin_page_digirisk-permis-feu .nav-wrap .nav-menu .item:before,
.admin_page_digirisk-prevention .nav-wrap .nav-menu .item:before,
.document-unique_page_digirisk-prevention .nav-wrap .nav-menu .item:before,
.admin_page_digirisk-handle-risk .nav-wrap .nav-menu .item:before,
.admin_page_digirisk-handle-sorter .nav-wrap .nav-menu .item:before,
.document-unique_page_digirisk-causerie .nav-wrap .nav-menu .item:before,
.toplevel_page_digirisk-dashboard-sites .nav-wrap .nav-menu .item:before,
.admin_page_digirisk-dashboard-add .nav-wrap .nav-menu .item:before,
.toplevel_page_digirisk-dashboard-duer .nav-wrap .nav-menu .item:before,
.toplevel_page_digirisk-dashboard-model .nav-wrap .nav-menu .item:before,
.admin_page_digirisk-dashboard-duer .nav-wrap .nav-menu .item:before,
.admin_page_digirisk-dashboard-model .nav-wrap .nav-menu .item:before {
	position: absolute;
	content: '';
	width: 100%;
	height: 1px;
	background: rgba(255, 255, 255, 0.08);
}

.toplevel_page_digi-setup .nav-wrap .nav-menu .item:hover,
.admin_page_digirisk-du .nav-wrap .nav-menu .item:hover,
.admin_page_digirisk-causerie .nav-wrap .nav-menu .item:hover,
.toplevel_page_digirisk-welcome .nav-wrap .nav-menu .item:hover,
.profile_page_digirisk-users .nav-wrap .nav-menu .item:hover,
.toplevel_page_digirisk-simple-risk-evaluation .nav-wrap .nav-menu .item:hover,
.users_page_digirisk-users .nav-wrap .nav-menu .item:hover,
.document-unique_page_digirisk-accident .nav-wrap .nav-menu .item:hover,
.admin_page_digirisk-accident .nav-wrap .nav-menu .item:hover,
.settings_page_digirisk-setting .nav-wrap .nav-menu .item:hover,
.tools_page_digirisk-tools .nav-wrap .nav-menu .item:hover,
.document-unique_page_digirisk-handle-sorter .nav-wrap .nav-menu .item:hover,
.document-unique_page_digirisk-handle-risk .nav-wrap .nav-menu .item:hover,
.document-unique_page_digirisk-permis-feu .nav-wrap .nav-menu .item:hover,
.admin_page_digirisk-permis-feu .nav-wrap .nav-menu .item:hover,
.admin_page_digirisk-prevention .nav-wrap .nav-menu .item:hover,
.document-unique_page_digirisk-prevention .nav-wrap .nav-menu .item:hover,
.admin_page_digirisk-handle-risk .nav-wrap .nav-menu .item:hover,
.admin_page_digirisk-handle-sorter .nav-wrap .nav-menu .item:hover,
.document-unique_page_digirisk-causerie .nav-wrap .nav-menu .item:hover,
.toplevel_page_digirisk-dashboard-sites .nav-wrap .nav-menu .item:hover,
.admin_page_digirisk-dashboard-add .nav-wrap .nav-menu .item:hover,
.toplevel_page_digirisk-dashboard-duer .nav-wrap .nav-menu .item:hover,
.toplevel_page_digirisk-dashboard-model .nav-wrap .nav-menu .item:hover,
.admin_page_digirisk-dashboard-duer .nav-wrap .nav-menu .item:hover,
.admin_page_digirisk-dashboard-model .nav-wrap .nav-menu .item:hover {
	background: rgba(255, 255, 255, 0.15);
}

.toplevel_page_digi-setup .nav-wrap .nav-menu .item div,
.admin_page_digirisk-du .nav-wrap .nav-menu .item div,
.admin_page_digirisk-causerie .nav-wrap .nav-menu .item div,
.toplevel_page_digirisk-welcome .nav-wrap .nav-menu .item div,
.profile_page_digirisk-users .nav-wrap .nav-menu .item div,
.toplevel_page_digirisk-simple-risk-evaluation .nav-wrap .nav-menu .item div,
.users_page_digirisk-users .nav-wrap .nav-menu .item div,
.document-unique_page_digirisk-accident .nav-wrap .nav-menu .item div,
.admin_page_digirisk-accident .nav-wrap .nav-menu .item div,
.settings_page_digirisk-setting .nav-wrap .nav-menu .item div,
.tools_page_digirisk-tools .nav-wrap .nav-menu .item div,
.document-unique_page_digirisk-handle-sorter .nav-wrap .nav-menu .item div,
.document-unique_page_digirisk-handle-risk .nav-wrap .nav-menu .item div,
.document-unique_page_digirisk-permis-feu .nav-wrap .nav-menu .item div,
.admin_page_digirisk-permis-feu .nav-wrap .nav-menu .item div,
.admin_page_digirisk-prevention .nav-wrap .nav-menu .item div,
.document-unique_page_digirisk-prevention .nav-wrap .nav-menu .item div,
.admin_page_digirisk-handle-risk .nav-wrap .nav-menu .item div,
.admin_page_digirisk-handle-sorter .nav-wrap .nav-menu .item div,
.document-unique_page_digirisk-causerie .nav-wrap .nav-menu .item div,
.toplevel_page_digirisk-dashboard-sites .nav-wrap .nav-menu .item div,
.admin_page_digirisk-dashboard-add .nav-wrap .nav-menu .item div,
.toplevel_page_digirisk-dashboard-duer .nav-wrap .nav-menu .item div,
.toplevel_page_digirisk-dashboard-model .nav-wrap .nav-menu .item div,
.admin_page_digirisk-dashboard-duer .nav-wrap .nav-menu .item div,
.admin_page_digirisk-dashboard-model .nav-wrap .nav-menu .item div {
	padding: 16px;
	padding-left: 20px;
	font-size: 1.05em;
	display: block;
	color: #eee;
	text-decoration: none;
	font-weight: bolder;
}

.toplevel_page_digi-setup .nav-wrap .nav-menu .item div.disabled,
.admin_page_digirisk-du .nav-wrap .nav-menu .item div.disabled,
.admin_page_digirisk-causerie .nav-wrap .nav-menu .item div.disabled,
.toplevel_page_digirisk-welcome .nav-wrap .nav-menu .item div.disabled,
.profile_page_digirisk-users .nav-wrap .nav-menu .item div.disabled,
.toplevel_page_digirisk-simple-risk-evaluation .nav-wrap .nav-menu .item div.disabled,
.users_page_digirisk-users .nav-wrap .nav-menu .item div.disabled,
.document-unique_page_digirisk-accident .nav-wrap .nav-menu .item div.disabled,
.admin_page_digirisk-accident .nav-wrap .nav-menu .item div.disabled,
.settings_page_digirisk-setting .nav-wrap .nav-menu .item div.disabled,
.tools_page_digirisk-tools .nav-wrap .nav-menu .item div.disabled,
.document-unique_page_digirisk-handle-sorter .nav-wrap .nav-menu .item div.disabled,
.document-unique_page_digirisk-handle-risk .nav-wrap .nav-menu .item div.disabled,
.document-unique_page_digirisk-permis-feu .nav-wrap .nav-menu .item div.disabled,
.admin_page_digirisk-permis-feu .nav-wrap .nav-menu .item div.disabled,
.admin_page_digirisk-prevention .nav-wrap .nav-menu .item div.disabled,
.document-unique_page_digirisk-prevention .nav-wrap .nav-menu .item div.disabled,
.admin_page_digirisk-handle-risk .nav-wrap .nav-menu .item div.disabled,
.admin_page_digirisk-handle-sorter .nav-wrap .nav-menu .item div.disabled,
.document-unique_page_digirisk-causerie .nav-wrap .nav-menu .item div.disabled,
.toplevel_page_digirisk-dashboard-sites .nav-wrap .nav-menu .item div.disabled,
.admin_page_digirisk-dashboard-add .nav-wrap .nav-menu .item div.disabled,
.toplevel_page_digirisk-dashboard-duer .nav-wrap .nav-menu .item div.disabled,
.toplevel_page_digirisk-dashboard-model .nav-wrap .nav-menu .item div.disabled,
.admin_page_digirisk-dashboard-duer .nav-wrap .nav-menu .item div.disabled,
.admin_page_digirisk-dashboard-model .nav-wrap .nav-menu .item div.disabled {
	background: rgba(0, 0, 0, 0.5);
	opacity: 0.4;
}

.toplevel_page_digi-setup .nav-wrap .nav-menu .item.item-active a,
.admin_page_digirisk-du .nav-wrap .nav-menu .item.item-active a,
.admin_page_digirisk-causerie .nav-wrap .nav-menu .item.item-active a,
.toplevel_page_digirisk-welcome .nav-wrap .nav-menu .item.item-active a,
.profile_page_digirisk-users .nav-wrap .nav-menu .item.item-active a,
.toplevel_page_digirisk-simple-risk-evaluation .nav-wrap .nav-menu .item.item-active a,
.users_page_digirisk-users .nav-wrap .nav-menu .item.item-active a,
.document-unique_page_digirisk-accident .nav-wrap .nav-menu .item.item-active a,
.admin_page_digirisk-accident .nav-wrap .nav-menu .item.item-active a,
.settings_page_digirisk-setting .nav-wrap .nav-menu .item.item-active a,
.tools_page_digirisk-tools .nav-wrap .nav-menu .item.item-active a,
.document-unique_page_digirisk-handle-sorter .nav-wrap .nav-menu .item.item-active a,
.document-unique_page_digirisk-handle-risk .nav-wrap .nav-menu .item.item-active a,
.document-unique_page_digirisk-permis-feu .nav-wrap .nav-menu .item.item-active a,
.admin_page_digirisk-permis-feu .nav-wrap .nav-menu .item.item-active a,
.admin_page_digirisk-prevention .nav-wrap .nav-menu .item.item-active a,
.document-unique_page_digirisk-prevention .nav-wrap .nav-menu .item.item-active a,
.admin_page_digirisk-handle-risk .nav-wrap .nav-menu .item.item-active a,
.admin_page_digirisk-handle-sorter .nav-wrap .nav-menu .item.item-active a,
.document-unique_page_digirisk-causerie .nav-wrap .nav-menu .item.item-active a,
.toplevel_page_digirisk-dashboard-sites .nav-wrap .nav-menu .item.item-active a,
.admin_page_digirisk-dashboard-add .nav-wrap .nav-menu .item.item-active a,
.toplevel_page_digirisk-dashboard-duer .nav-wrap .nav-menu .item.item-active a,
.toplevel_page_digirisk-dashboard-model .nav-wrap .nav-menu .item.item-active a,
.admin_page_digirisk-dashboard-duer .nav-wrap .nav-menu .item.item-active a,
.admin_page_digirisk-dashboard-model .nav-wrap .nav-menu .item.item-active a {
	background: rgba(255, 255, 255, 0.15);
}

.toplevel_page_digi-setup .nav-wrap .nav-menu .item i,
.admin_page_digirisk-du .nav-wrap .nav-menu .item i,
.admin_page_digirisk-causerie .nav-wrap .nav-menu .item i,
.toplevel_page_digirisk-welcome .nav-wrap .nav-menu .item i,
.profile_page_digirisk-users .nav-wrap .nav-menu .item i,
.toplevel_page_digirisk-simple-risk-evaluation .nav-wrap .nav-menu .item i,
.users_page_digirisk-users .nav-wrap .nav-menu .item i,
.document-unique_page_digirisk-accident .nav-wrap .nav-menu .item i,
.admin_page_digirisk-accident .nav-wrap .nav-menu .item i,
.settings_page_digirisk-setting .nav-wrap .nav-menu .item i,
.tools_page_digirisk-tools .nav-wrap .nav-menu .item i,
.document-unique_page_digirisk-handle-sorter .nav-wrap .nav-menu .item i,
.document-unique_page_digirisk-handle-risk .nav-wrap .nav-menu .item i,
.document-unique_page_digirisk-permis-feu .nav-wrap .nav-menu .item i,
.admin_page_digirisk-permis-feu .nav-wrap .nav-menu .item i,
.admin_page_digirisk-prevention .nav-wrap .nav-menu .item i,
.document-unique_page_digirisk-prevention .nav-wrap .nav-menu .item i,
.admin_page_digirisk-handle-risk .nav-wrap .nav-menu .item i,
.admin_page_digirisk-handle-sorter .nav-wrap .nav-menu .item i,
.document-unique_page_digirisk-causerie .nav-wrap .nav-menu .item i,
.toplevel_page_digirisk-dashboard-sites .nav-wrap .nav-menu .item i,
.admin_page_digirisk-dashboard-add .nav-wrap .nav-menu .item i,
.toplevel_page_digirisk-dashboard-duer .nav-wrap .nav-menu .item i,
.toplevel_page_digirisk-dashboard-model .nav-wrap .nav-menu .item i,
.admin_page_digirisk-dashboard-duer .nav-wrap .nav-menu .item i,
.admin_page_digirisk-dashboard-model .nav-wrap .nav-menu .item i {
	margin-right: 10px;
}

.toplevel_page_digi-setup .nav-wrap .nav-menu.item-bottom,
.admin_page_digirisk-du .nav-wrap .nav-menu.item-bottom,
.admin_page_digirisk-causerie .nav-wrap .nav-menu.item-bottom,
.toplevel_page_digirisk-welcome .nav-wrap .nav-menu.item-bottom,
.profile_page_digirisk-users .nav-wrap .nav-menu.item-bottom,
.toplevel_page_digirisk-simple-risk-evaluation .nav-wrap .nav-menu.item-bottom,
.users_page_digirisk-users .nav-wrap .nav-menu.item-bottom,
.document-unique_page_digirisk-accident .nav-wrap .nav-menu.item-bottom,
.admin_page_digirisk-accident .nav-wrap .nav-menu.item-bottom,
.settings_page_digirisk-setting .nav-wrap .nav-menu.item-bottom,
.tools_page_digirisk-tools .nav-wrap .nav-menu.item-bottom,
.document-unique_page_digirisk-handle-sorter .nav-wrap .nav-menu.item-bottom,
.document-unique_page_digirisk-handle-risk .nav-wrap .nav-menu.item-bottom,
.document-unique_page_digirisk-permis-feu .nav-wrap .nav-menu.item-bottom,
.admin_page_digirisk-permis-feu .nav-wrap .nav-menu.item-bottom,
.admin_page_digirisk-prevention .nav-wrap .nav-menu.item-bottom,
.document-unique_page_digirisk-prevention .nav-wrap .nav-menu.item-bottom,
.admin_page_digirisk-handle-risk .nav-wrap .nav-menu.item-bottom,
.admin_page_digirisk-handle-sorter .nav-wrap .nav-menu.item-bottom,
.document-unique_page_digirisk-causerie .nav-wrap .nav-menu.item-bottom,
.toplevel_page_digirisk-dashboard-sites .nav-wrap .nav-menu.item-bottom,
.admin_page_digirisk-dashboard-add .nav-wrap .nav-menu.item-bottom,
.toplevel_page_digirisk-dashboard-duer .nav-wrap .nav-menu.item-bottom,
.toplevel_page_digirisk-dashboard-model .nav-wrap .nav-menu.item-bottom,
.admin_page_digirisk-dashboard-duer .nav-wrap .nav-menu.item-bottom,
.admin_page_digirisk-dashboard-model .nav-wrap .nav-menu.item-bottom {
	color: red;
	display: block;
	position: absolute;
	width: 200px;
	bottom: 0;
}

.toplevel_page_digi-setup .content-wrap,
.admin_page_digirisk-du .content-wrap,
.admin_page_digirisk-causerie .content-wrap,
.toplevel_page_digirisk-welcome .content-wrap,
.profile_page_digirisk-users .content-wrap,
.toplevel_page_digirisk-simple-risk-evaluation .content-wrap,
.users_page_digirisk-users .content-wrap,
.document-unique_page_digirisk-accident .content-wrap,
.admin_page_digirisk-accident .content-wrap,
.settings_page_digirisk-setting .content-wrap,
.tools_page_digirisk-tools .content-wrap,
.document-unique_page_digirisk-handle-sorter .content-wrap,
.document-unique_page_digirisk-handle-risk .content-wrap,
.document-unique_page_digirisk-permis-feu .content-wrap,
.admin_page_digirisk-permis-feu .content-wrap,
.admin_page_digirisk-prevention .content-wrap,
.document-unique_page_digirisk-prevention .content-wrap,
.admin_page_digirisk-handle-risk .content-wrap,
.admin_page_digirisk-handle-sorter .content-wrap,
.document-unique_page_digirisk-causerie .content-wrap,
.toplevel_page_digirisk-dashboard-sites .content-wrap,
.admin_page_digirisk-dashboard-add .content-wrap,
.toplevel_page_digirisk-dashboard-duer .content-wrap,
.toplevel_page_digirisk-dashboard-model .content-wrap,
.admin_page_digirisk-dashboard-duer .content-wrap,
.admin_page_digirisk-dashboard-model .content-wrap {
	background: #fafafa;
	margin-left: 200px;
}

.toplevel_page_digi-setup .content-wrap .digirisk-wrap,
.admin_page_digirisk-du .content-wrap .digirisk-wrap,
.admin_page_digirisk-causerie .content-wrap .digirisk-wrap,
.toplevel_page_digirisk-welcome .content-wrap .digirisk-wrap,
.profile_page_digirisk-users .content-wrap .digirisk-wrap,
.toplevel_page_digirisk-simple-risk-evaluation .content-wrap .digirisk-wrap,
.users_page_digirisk-users .content-wrap .digirisk-wrap,
.document-unique_page_digirisk-accident .content-wrap .digirisk-wrap,
.admin_page_digirisk-accident .content-wrap .digirisk-wrap,
.settings_page_digirisk-setting .content-wrap .digirisk-wrap,
.tools_page_digirisk-tools .content-wrap .digirisk-wrap,
.document-unique_page_digirisk-handle-sorter .content-wrap .digirisk-wrap,
.document-unique_page_digirisk-handle-risk .content-wrap .digirisk-wrap,
.document-unique_page_digirisk-permis-feu .content-wrap .digirisk-wrap,
.admin_page_digirisk-permis-feu .content-wrap .digirisk-wrap,
.admin_page_digirisk-prevention .content-wrap .digirisk-wrap,
.document-unique_page_digirisk-prevention .content-wrap .digirisk-wrap,
.admin_page_digirisk-handle-risk .content-wrap .digirisk-wrap,
.admin_page_digirisk-handle-sorter .content-wrap .digirisk-wrap,
.document-unique_page_digirisk-causerie .content-wrap .digirisk-wrap,
.toplevel_page_digirisk-dashboard-sites .content-wrap .digirisk-wrap,
.admin_page_digirisk-dashboard-add .content-wrap .digirisk-wrap,
.toplevel_page_digirisk-dashboard-duer .content-wrap .digirisk-wrap,
.toplevel_page_digirisk-dashboard-model .content-wrap .digirisk-wrap,
.admin_page_digirisk-dashboard-duer .content-wrap .digirisk-wrap,
.admin_page_digirisk-dashboard-model .content-wrap .digirisk-wrap {
	padding-left: 20px;
}

.toplevel_page_digi-setup .content-wrap .digirisk-wrap.wrap,
.admin_page_digirisk-du .content-wrap .digirisk-wrap.wrap,
.admin_page_digirisk-causerie .content-wrap .digirisk-wrap.wrap,
.toplevel_page_digirisk-welcome .content-wrap .digirisk-wrap.wrap,
.profile_page_digirisk-users .content-wrap .digirisk-wrap.wrap,
.toplevel_page_digirisk-simple-risk-evaluation .content-wrap .digirisk-wrap.wrap,
.users_page_digirisk-users .content-wrap .digirisk-wrap.wrap,
.document-unique_page_digirisk-accident .content-wrap .digirisk-wrap.wrap,
.admin_page_digirisk-accident .content-wrap .digirisk-wrap.wrap,
.settings_page_digirisk-setting .content-wrap .digirisk-wrap.wrap,
.tools_page_digirisk-tools .content-wrap .digirisk-wrap.wrap,
.document-unique_page_digirisk-handle-sorter .content-wrap .digirisk-wrap.wrap,
.document-unique_page_digirisk-handle-risk .content-wrap .digirisk-wrap.wrap,
.document-unique_page_digirisk-permis-feu .content-wrap .digirisk-wrap.wrap,
.admin_page_digirisk-permis-feu .content-wrap .digirisk-wrap.wrap,
.admin_page_digirisk-prevention .content-wrap .digirisk-wrap.wrap,
.document-unique_page_digirisk-prevention .content-wrap .digirisk-wrap.wrap,
.admin_page_digirisk-handle-risk .content-wrap .digirisk-wrap.wrap,
.admin_page_digirisk-handle-sorter .content-wrap .digirisk-wrap.wrap,
.document-unique_page_digirisk-causerie .content-wrap .digirisk-wrap.wrap,
.toplevel_page_digirisk-dashboard-sites .content-wrap .digirisk-wrap.wrap,
.admin_page_digirisk-dashboard-add .content-wrap .digirisk-wrap.wrap,
.toplevel_page_digirisk-dashboard-duer .content-wrap .digirisk-wrap.wrap,
.toplevel_page_digirisk-dashboard-model .content-wrap .digirisk-wrap.wrap,
.admin_page_digirisk-dashboard-duer .content-wrap .digirisk-wrap.wrap,
.admin_page_digirisk-dashboard-model .content-wrap .digirisk-wrap.wrap {
	margin-right: 0;
}

.toplevel_page_digi-setup .content-wrap #top-header,
.admin_page_digirisk-du .content-wrap #top-header,
.admin_page_digirisk-causerie .content-wrap #top-header,
.toplevel_page_digirisk-welcome .content-wrap #top-header,
.profile_page_digirisk-users .content-wrap #top-header,
.toplevel_page_digirisk-simple-risk-evaluation .content-wrap #top-header,
.users_page_digirisk-users .content-wrap #top-header,
.document-unique_page_digirisk-accident .content-wrap #top-header,
.admin_page_digirisk-accident .content-wrap #top-header,
.settings_page_digirisk-setting .content-wrap #top-header,
.tools_page_digirisk-tools .content-wrap #top-header,
.document-unique_page_digirisk-handle-sorter .content-wrap #top-header,
.document-unique_page_digirisk-handle-risk .content-wrap #top-header,
.document-unique_page_digirisk-permis-feu .content-wrap #top-header,
.admin_page_digirisk-permis-feu .content-wrap #top-header,
.admin_page_digirisk-prevention .content-wrap #top-header,
.document-unique_page_digirisk-prevention .content-wrap #top-header,
.admin_page_digirisk-handle-risk .content-wrap #top-header,
.admin_page_digirisk-handle-sorter .content-wrap #top-header,
.document-unique_page_digirisk-causerie .content-wrap #top-header,
.toplevel_page_digirisk-dashboard-sites .content-wrap #top-header,
.admin_page_digirisk-dashboard-add .content-wrap #top-header,
.toplevel_page_digirisk-dashboard-duer .content-wrap #top-header,
.toplevel_page_digirisk-dashboard-model .content-wrap #top-header,
.admin_page_digirisk-dashboard-duer .content-wrap #top-header,
.admin_page_digirisk-dashboard-model .content-wrap #top-header {
	background-color: white;
	border-bottom: solid black 2px;
	min-height: 60px;
	/** Bouton Ajouter dans le header */
}

.toplevel_page_digi-setup .content-wrap #top-header .nav-header,
.admin_page_digirisk-du .content-wrap #top-header .nav-header,
.admin_page_digirisk-causerie .content-wrap #top-header .nav-header,
.toplevel_page_digirisk-welcome .content-wrap #top-header .nav-header,
.profile_page_digirisk-users .content-wrap #top-header .nav-header,
.toplevel_page_digirisk-simple-risk-evaluation .content-wrap #top-header .nav-header,
.users_page_digirisk-users .content-wrap #top-header .nav-header,
.document-unique_page_digirisk-accident .content-wrap #top-header .nav-header,
.admin_page_digirisk-accident .content-wrap #top-header .nav-header,
.settings_page_digirisk-setting .content-wrap #top-header .nav-header,
.tools_page_digirisk-tools .content-wrap #top-header .nav-header,
.document-unique_page_digirisk-handle-sorter .content-wrap #top-header .nav-header,
.document-unique_page_digirisk-handle-risk .content-wrap #top-header .nav-header,
.document-unique_page_digirisk-permis-feu .content-wrap #top-header .nav-header,
.admin_page_digirisk-permis-feu .content-wrap #top-header .nav-header,
.admin_page_digirisk-prevention .content-wrap #top-header .nav-header,
.document-unique_page_digirisk-prevention .content-wrap #top-header .nav-header,
.admin_page_digirisk-handle-risk .content-wrap #top-header .nav-header,
.admin_page_digirisk-handle-sorter .content-wrap #top-header .nav-header,
.document-unique_page_digirisk-causerie .content-wrap #top-header .nav-header,
.toplevel_page_digirisk-dashboard-sites .content-wrap #top-header .nav-header,
.admin_page_digirisk-dashboard-add .content-wrap #top-header .nav-header,
.toplevel_page_digirisk-dashboard-duer .content-wrap #top-header .nav-header,
.toplevel_page_digirisk-dashboard-model .content-wrap #top-header .nav-header,
.admin_page_digirisk-dashboard-duer .content-wrap #top-header .nav-header,
.admin_page_digirisk-dashboard-model .content-wrap #top-header .nav-header {
	margin: 0;
}

.toplevel_page_digi-setup .content-wrap #top-header .nav-right,
.admin_page_digirisk-du .content-wrap #top-header .nav-right,
.admin_page_digirisk-causerie .content-wrap #top-header .nav-right,
.toplevel_page_digirisk-welcome .content-wrap #top-header .nav-right,
.profile_page_digirisk-users .content-wrap #top-header .nav-right,
.toplevel_page_digirisk-simple-risk-evaluation .content-wrap #top-header .nav-right,
.users_page_digirisk-users .content-wrap #top-header .nav-right,
.document-unique_page_digirisk-accident .content-wrap #top-header .nav-right,
.admin_page_digirisk-accident .content-wrap #top-header .nav-right,
.settings_page_digirisk-setting .content-wrap #top-header .nav-right,
.tools_page_digirisk-tools .content-wrap #top-header .nav-right,
.document-unique_page_digirisk-handle-sorter .content-wrap #top-header .nav-right,
.document-unique_page_digirisk-handle-risk .content-wrap #top-header .nav-right,
.document-unique_page_digirisk-permis-feu .content-wrap #top-header .nav-right,
.admin_page_digirisk-permis-feu .content-wrap #top-header .nav-right,
.admin_page_digirisk-prevention .content-wrap #top-header .nav-right,
.document-unique_page_digirisk-prevention .content-wrap #top-header .nav-right,
.admin_page_digirisk-handle-risk .content-wrap #top-header .nav-right,
.admin_page_digirisk-handle-sorter .content-wrap #top-header .nav-right,
.document-unique_page_digirisk-causerie .content-wrap #top-header .nav-right,
.toplevel_page_digirisk-dashboard-sites .content-wrap #top-header .nav-right,
.admin_page_digirisk-dashboard-add .content-wrap #top-header .nav-right,
.toplevel_page_digirisk-dashboard-duer .content-wrap #top-header .nav-right,
.toplevel_page_digirisk-dashboard-model .content-wrap #top-header .nav-right,
.admin_page_digirisk-dashboard-duer .content-wrap #top-header .nav-right,
.admin_page_digirisk-dashboard-model .content-wrap #top-header .nav-right {
	line-height: 50px;
	font-size: 1.2em;
}

.toplevel_page_digi-setup .content-wrap #top-header .nav-right li img,
.admin_page_digirisk-du .content-wrap #top-header .nav-right li img,
.admin_page_digirisk-causerie .content-wrap #top-header .nav-right li img,
.toplevel_page_digirisk-welcome .content-wrap #top-header .nav-right li img,
.profile_page_digirisk-users .content-wrap #top-header .nav-right li img,
.toplevel_page_digirisk-simple-risk-evaluation .content-wrap #top-header .nav-right li img,
.users_page_digirisk-users .content-wrap #top-header .nav-right li img,
.document-unique_page_digirisk-accident .content-wrap #top-header .nav-right li img,
.admin_page_digirisk-accident .content-wrap #top-header .nav-right li img,
.settings_page_digirisk-setting .content-wrap #top-header .nav-right li img,
.tools_page_digirisk-tools .content-wrap #top-header .nav-right li img,
.document-unique_page_digirisk-handle-sorter .content-wrap #top-header .nav-right li img,
.document-unique_page_digirisk-handle-risk .content-wrap #top-header .nav-right li img,
.document-unique_page_digirisk-permis-feu .content-wrap #top-header .nav-right li img,
.admin_page_digirisk-permis-feu .content-wrap #top-header .nav-right li img,
.admin_page_digirisk-prevention .content-wrap #top-header .nav-right li img,
.document-unique_page_digirisk-prevention .content-wrap #top-header .nav-right li img,
.admin_page_digirisk-handle-risk .content-wrap #top-header .nav-right li img,
.admin_page_digirisk-handle-sorter .content-wrap #top-header .nav-right li img,
.document-unique_page_digirisk-causerie .content-wrap #top-header .nav-right li img,
.toplevel_page_digirisk-dashboard-sites .content-wrap #top-header .nav-right li img,
.admin_page_digirisk-dashboard-add .content-wrap #top-header .nav-right li img,
.toplevel_page_digirisk-dashboard-duer .content-wrap #top-header .nav-right li img,
.toplevel_page_digirisk-dashboard-model .content-wrap #top-header .nav-right li img,
.admin_page_digirisk-dashboard-duer .content-wrap #top-header .nav-right li img,
.admin_page_digirisk-dashboard-model .content-wrap #top-header .nav-right li img {
	vertical-align: middle;
}

.toplevel_page_digi-setup .content-wrap #top-header .page-title,
.admin_page_digirisk-du .content-wrap #top-header .page-title,
.admin_page_digirisk-causerie .content-wrap #top-header .page-title,
.toplevel_page_digirisk-welcome .content-wrap #top-header .page-title,
.profile_page_digirisk-users .content-wrap #top-header .page-title,
.toplevel_page_digirisk-simple-risk-evaluation .content-wrap #top-header .page-title,
.users_page_digirisk-users .content-wrap #top-header .page-title,
.document-unique_page_digirisk-accident .content-wrap #top-header .page-title,
.admin_page_digirisk-accident .content-wrap #top-header .page-title,
.settings_page_digirisk-setting .content-wrap #top-header .page-title,
.tools_page_digirisk-tools .content-wrap #top-header .page-title,
.document-unique_page_digirisk-handle-sorter .content-wrap #top-header .page-title,
.document-unique_page_digirisk-handle-risk .content-wrap #top-header .page-title,
.document-unique_page_digirisk-permis-feu .content-wrap #top-header .page-title,
.admin_page_digirisk-permis-feu .content-wrap #top-header .page-title,
.admin_page_digirisk-prevention .content-wrap #top-header .page-title,
.document-unique_page_digirisk-prevention .content-wrap #top-header .page-title,
.admin_page_digirisk-handle-risk .content-wrap #top-header .page-title,
.admin_page_digirisk-handle-sorter .content-wrap #top-header .page-title,
.document-unique_page_digirisk-causerie .content-wrap #top-header .page-title,
.toplevel_page_digirisk-dashboard-sites .content-wrap #top-header .page-title,
.admin_page_digirisk-dashboard-add .content-wrap #top-header .page-title,
.toplevel_page_digirisk-dashboard-duer .content-wrap #top-header .page-title,
.toplevel_page_digirisk-dashboard-model .content-wrap #top-header .page-title,
.admin_page_digirisk-dashboard-duer .content-wrap #top-header .page-title,
.admin_page_digirisk-dashboard-model .content-wrap #top-header .page-title {
	margin: 0;
	font-size: 2em;
	padding-top: 20px;
	padding-left: 20px;
}

.toplevel_page_digi-setup .content-wrap #top-header .wpeo-button,
.admin_page_digirisk-du .content-wrap #top-header .wpeo-button,
.admin_page_digirisk-causerie .content-wrap #top-header .wpeo-button,
.toplevel_page_digirisk-welcome .content-wrap #top-header .wpeo-button,
.profile_page_digirisk-users .content-wrap #top-header .wpeo-button,
.toplevel_page_digirisk-simple-risk-evaluation .content-wrap #top-header .wpeo-button,
.users_page_digirisk-users .content-wrap #top-header .wpeo-button,
.document-unique_page_digirisk-accident .content-wrap #top-header .wpeo-button,
.admin_page_digirisk-accident .content-wrap #top-header .wpeo-button,
.settings_page_digirisk-setting .content-wrap #top-header .wpeo-button,
.tools_page_digirisk-tools .content-wrap #top-header .wpeo-button,
.document-unique_page_digirisk-handle-sorter .content-wrap #top-header .wpeo-button,
.document-unique_page_digirisk-handle-risk .content-wrap #top-header .wpeo-button,
.document-unique_page_digirisk-permis-feu .content-wrap #top-header .wpeo-button,
.admin_page_digirisk-permis-feu .content-wrap #top-header .wpeo-button,
.admin_page_digirisk-prevention .content-wrap #top-header .wpeo-button,
.document-unique_page_digirisk-prevention .content-wrap #top-header .wpeo-button,
.admin_page_digirisk-handle-risk .content-wrap #top-header .wpeo-button,
.admin_page_digirisk-handle-sorter .content-wrap #top-header .wpeo-button,
.document-unique_page_digirisk-causerie .content-wrap #top-header .wpeo-button,
.toplevel_page_digirisk-dashboard-sites .content-wrap #top-header .wpeo-button,
.admin_page_digirisk-dashboard-add .content-wrap #top-header .wpeo-button,
.toplevel_page_digirisk-dashboard-duer .content-wrap #top-header .wpeo-button,
.toplevel_page_digirisk-dashboard-model .content-wrap #top-header .wpeo-button,
.admin_page_digirisk-dashboard-duer .content-wrap #top-header .wpeo-button,
.admin_page_digirisk-dashboard-model .content-wrap #top-header .wpeo-button {
	margin-top: 12px;
	margin-left: 20px;
}

.toplevel_page_digi-setup .content-wrap #top-header .alignright,
.admin_page_digirisk-du .content-wrap #top-header .alignright,
.admin_page_digirisk-causerie .content-wrap #top-header .alignright,
.toplevel_page_digirisk-welcome .content-wrap #top-header .alignright,
.profile_page_digirisk-users .content-wrap #top-header .alignright,
.toplevel_page_digirisk-simple-risk-evaluation .content-wrap #top-header .alignright,
.users_page_digirisk-users .content-wrap #top-header .alignright,
.document-unique_page_digirisk-accident .content-wrap #top-header .alignright,
.admin_page_digirisk-accident .content-wrap #top-header .alignright,
.settings_page_digirisk-setting .content-wrap #top-header .alignright,
.tools_page_digirisk-tools .content-wrap #top-header .alignright,
.document-unique_page_digirisk-handle-sorter .content-wrap #top-header .alignright,
.document-unique_page_digirisk-handle-risk .content-wrap #top-header .alignright,
.document-unique_page_digirisk-permis-feu .content-wrap #top-header .alignright,
.admin_page_digirisk-permis-feu .content-wrap #top-header .alignright,
.admin_page_digirisk-prevention .content-wrap #top-header .alignright,
.document-unique_page_digirisk-prevention .content-wrap #top-header .alignright,
.admin_page_digirisk-handle-risk .content-wrap #top-header .alignright,
.admin_page_digirisk-handle-sorter .content-wrap #top-header .alignright,
.document-unique_page_digirisk-causerie .content-wrap #top-header .alignright,
.toplevel_page_digirisk-dashboard-sites .content-wrap #top-header .alignright,
.admin_page_digirisk-dashboard-add .content-wrap #top-header .alignright,
.toplevel_page_digirisk-dashboard-duer .content-wrap #top-header .alignright,
.toplevel_page_digirisk-dashboard-model .content-wrap #top-header .alignright,
.admin_page_digirisk-dashboard-duer .content-wrap #top-header .alignright,
.admin_page_digirisk-dashboard-model .content-wrap #top-header .alignright {
	padding-right: 20px;
	margin: 0;
}

.toplevel_page_digi-setup .content-wrap .wrap-frais-pro,
.admin_page_digirisk-du .content-wrap .wrap-frais-pro,
.admin_page_digirisk-causerie .content-wrap .wrap-frais-pro,
.toplevel_page_digirisk-welcome .content-wrap .wrap-frais-pro,
.profile_page_digirisk-users .content-wrap .wrap-frais-pro,
.toplevel_page_digirisk-simple-risk-evaluation .content-wrap .wrap-frais-pro,
.users_page_digirisk-users .content-wrap .wrap-frais-pro,
.document-unique_page_digirisk-accident .content-wrap .wrap-frais-pro,
.admin_page_digirisk-accident .content-wrap .wrap-frais-pro,
.settings_page_digirisk-setting .content-wrap .wrap-frais-pro,
.tools_page_digirisk-tools .content-wrap .wrap-frais-pro,
.document-unique_page_digirisk-handle-sorter .content-wrap .wrap-frais-pro,
.document-unique_page_digirisk-handle-risk .content-wrap .wrap-frais-pro,
.document-unique_page_digirisk-permis-feu .content-wrap .wrap-frais-pro,
.admin_page_digirisk-permis-feu .content-wrap .wrap-frais-pro,
.admin_page_digirisk-prevention .content-wrap .wrap-frais-pro,
.document-unique_page_digirisk-prevention .content-wrap .wrap-frais-pro,
.admin_page_digirisk-handle-risk .content-wrap .wrap-frais-pro,
.admin_page_digirisk-handle-sorter .content-wrap .wrap-frais-pro,
.document-unique_page_digirisk-causerie .content-wrap .wrap-frais-pro,
.toplevel_page_digirisk-dashboard-sites .content-wrap .wrap-frais-pro,
.admin_page_digirisk-dashboard-add .content-wrap .wrap-frais-pro,
.toplevel_page_digirisk-dashboard-duer .content-wrap .wrap-frais-pro,
.toplevel_page_digirisk-dashboard-model .content-wrap .wrap-frais-pro,
.admin_page_digirisk-dashboard-duer .content-wrap .wrap-frais-pro,
.admin_page_digirisk-dashboard-model .content-wrap .wrap-frais-pro {
	padding-right: 0;
	padding-left: 20px;
}

/*--------------------------------------------------------------
# Temporaire à ranger
--------------------------------------------------------------*/
.toplevel_page_digirisk .top-content > div {
	text-align: center;
}

.toplevel_page_digirisk .modal-interface .modal-container {
	height: 250px;
}

.toplevel_page_digirisk .modal-interface .modal-content {
	margin-top: 20px;
	height: 120px;
	padding-left: 5px;
}

.wpeo-button.button-main {
	background: #3495f0;
	border-color: #3495f0;
}

.wpeo-button.button-main i {
	color: white;
}

/*.wpeo-modal.modal-signature .modal-container .modal-content {*/
/*	overflow: hidden;*/
/*}*/

canvas {
	border: solid black 1px;
	height: 90%;
	width: 99%;
}

.causerie-wrap .float.right {
	float: right;
}

.digirisk-wrap .comment-container .comment .content {
	word-break: break-word;
}

.digirisk-wrap.wpeo-wrap .flex-table .table-footer .wpeo-autocomplete .autocomplete-label {
	padding: 0.2em;
}

.wrap-causerie .header {
	margin-top: 50px;
}

.wrap-causerie .section {
	background-color: #FFF;
	padding: 10px;
}

.wrap-causerie #stats-causerie {
	border: none;
}

.wpeo-wrap.causerie-wrap .step .step-list .step:after, .wpeo-wrap.permis-feu-wrap .step .step-list .step:after, .wpeo-wrap.prevention-wrap .step .step-list .step:after {
	position: relative;
	z-index: 90;
	display: block;
	margin: auto;
	content: '';
	width: 30px;
	height: 30px;
	top: 8px;
	background: #d6d6d6;
	border-radius: 50%;
	transition: all 0.2s ease-out;
}

.wpeo-wrap .step .step-list .step:after {
	position: relative;
	z-index: 90;
	display: block;
	margin: auto;
	content: '';
	width: 14px;
	height: 14px;
	background: #d6d6d6;
	border-radius: 50%;
	transition: all 0.2s ease-out;
}

.phone-bloc {
	display: flex;
}

.document-unique_page_digirisk-causerie .wpeo-wrap .wpeo-button-pulse {
	height: 36px !important;
	width: 36px !important;
}

.wpeo-wrap img.signature {
	top: 0;
	bottom: 0;
	left: 0;
	right: 0;
	margin: auto;
}

#user_switching.notice {
	display: none;
}

.toplevel_page_digirisk-welcome .top-content {
	margin-top: 80px;
	margin-bottom: 50px;
}

.toplevel_page_digirisk-welcome .top-content div {
	margin: auto;
	text-align: center;
}

.toplevel_page_digirisk-welcome h2 {
	font-weight: 700;
	font-style: normal;
	text-transform: uppercase;
	font-size: 26.6px;
	text-align: center;
}

.toplevel_page_digirisk-welcome h3 {
	font-weight: 700;
	font-style: normal;
	text-transform: uppercase;
	font-size: 20px;
}

.dropdown-sites {
	overflow-y: scroll;
	max-height: 500px;
}

.sorter-page table.treetable tbody tr td {
	padding: 10px;
}

.sorter-page table.treetable tbody tr td:hover {
	background-color: rgba(0, 0, 0, 0.2);
	cursor: pointer;
}

.digirisk_page_digirisk-permis-feu .wpeo-autocomplete .autocomplete-search-input, .digirisk_page_digirisk-prevention .wpeo-autocomplete .autocomplete-search-input {
	padding: 1em;
}

/*--------------------------------------------------------------
# Fin temporaire à ranger
--------------------------------------------------------------*/

@charset "UTF-8";
/*!
	EOXIA FRAMEWORK CSS
	Created by Eoxia

	version: 1.2.0
*/
/*--------------------------------------------------------------
>>> TABLE OF CONTENTS:
----------------------------------------------------------------
# fonticons
# Normalize
# Modules
	## Notification

--------------------------------------------------------------*/
/*--------------------------------------------------------------
# Fonticons
--------------------------------------------------------------*/
/*!
 * Font Awesome Pro 5.0.10 by @fontawesome - https://fontawesome.com
 * License - https://fontawesome.com/license (Commercial License)
 */
.fa,
.fas,
.far,
.fal,
.fab {
	-moz-osx-font-smoothing: grayscale;
	-webkit-font-smoothing: antialiased;
	display: inline-block;
	font-style: normal;
	font-variant: normal;
	text-rendering: auto;
	line-height: 1;
}

/*--------------------------------------------------------------
# Normalize
--------------------------------------------------------------*/
.wpeo-wrap html {
	font-family: sans-serif;
	-webkit-text-size-adjust: 100%;
	-ms-text-size-adjust: 100%;
}

.wpeo-wrap body {
	margin: 0;
}

.wpeo-wrap article,
.wpeo-wrap aside,
.wpeo-wrap details,
.wpeo-wrap figcaption,
.wpeo-wrap figure,
.wpeo-wrap footer,
.wpeo-wrap header,
.wpeo-wrap main,
.wpeo-wrap menu,
.wpeo-wrap nav,
.wpeo-wrap section,
.wpeo-wrap summary {
	display: block;
}

.wpeo-wrap audio,
.wpeo-wrap canvas,
.wpeo-wrap progress,
.wpeo-wrap video {
	display: inline-block;
	vertical-align: baseline;
}

.wpeo-wrap audio:not([controls]) {
	display: none;
	height: 0;
}

.wpeo-wrap [hidden],
.wpeo-wrap template {
	display: none;
}

.wpeo-wrap a {
	background-color: transparent;
	text-decoration: none;
	/*--colortextlink: white;*/
}

.wpeo-wrap a:active,
.wpeo-wrap a:hover {
	outline: 0;
}

.wpeo-wrap abbr[title] {
	border-bottom: 1px dotted;
}

.wpeo-wrap b,
.wpeo-wrap strong {
	font-weight: bold;
}

.wpeo-wrap dfn {
	font-style: italic;
}

.wpeo-wrap h1 {
	font-size: 2em;
	margin: 0.67em 0;
}

.wpeo-wrap mark {
	background: #ff0;
	color: #000;
}

.wpeo-wrap small {
	font-size: 80%;
}

.wpeo-wrap sub,
.wpeo-wrap sup {
	font-size: 75%;
	line-height: 0;
	position: relative;
	vertical-align: baseline;
}

.wpeo-wrap sup {
	top: -0.5em;
}

.wpeo-wrap sub {
	bottom: -0.25em;
}

.wpeo-wrap img {
	border: 0;
}

.wpeo-wrap svg:not(:root) {
	overflow: hidden;
}

.wpeo-wrap figure {
	margin: 1em 40px;
}

.wpeo-wrap hr {
	-webkit-box-sizing: content-box;
	box-sizing: content-box;
	height: 0;
}

.wpeo-wrap pre {
	overflow: auto;
}

.wpeo-wrap code,
.wpeo-wrap kbd,
.wpeo-wrap pre,
.wpeo-wrap samp {
	font-family: monospace, monospace;
	font-size: 1em;
}

.wpeo-wrap button,
.wpeo-wrap input,
.wpeo-wrap optgroup,
.wpeo-wrap select,
.wpeo-wrap textarea {
	color: inherit;
	font: inherit;
	margin: 0;
	-webkit-box-shadow: none;
	box-shadow: none;
}

.wpeo-wrap button {
	overflow: visible;
}

.wpeo-wrap button,
.wpeo-wrap select {
	text-transform: none;
}

.wpeo-wrap button,
.wpeo-wrap html input[type="button"],
.wpeo-wrap input[type="reset"],
.wpeo-wrap input[type="submit"] {
	-webkit-appearance: button;
	cursor: pointer;
}

.wpeo-wrap button[disabled],
.wpeo-wrap html input[disabled] {
	cursor: default;
}

.wpeo-wrap button::-moz-focus-inner,
.wpeo-wrap input::-moz-focus-inner {
	border: 0;
	padding: 0;
}

.wpeo-wrap input {
	line-height: normal;
}

.wpeo-wrap input[type="checkbox"],
.wpeo-wrap input[type="radio"] {
	-webkit-box-sizing: border-box;
	box-sizing: border-box;
	padding: 0;
}

.wpeo-wrap input[type="number"]::-webkit-inner-spin-button,
.wpeo-wrap input[type="number"]::-webkit-outer-spin-button {
	height: auto;
}

.wpeo-wrap input[type="search"]::-webkit-search-cancel-button,
.wpeo-wrap input[type="search"]::-webkit-search-decoration {
	-webkit-appearance: none;
}

.wpeo-wrap fieldset {
	border: 1px solid #c0c0c0;
	margin: 0 2px;
	padding: 0.35em 0.625em 0.75em;
}

.wpeo-wrap legend {
	border: 0;
	padding: 0;
}

.wpeo-wrap textarea {
	overflow: auto;
}

.wpeo-wrap optgroup {
	font-weight: bold;
}

.wpeo-wrap table {
	border-collapse: collapse;
	border-spacing: 0;
}

.wpeo-wrap td,
.wpeo-wrap th {
	padding: 0;
}

.wpeo-wrap * {
	-webkit-box-sizing: border-box;
	box-sizing: border-box;
}

/*--------------------------------------------------------------
# Core Modules
--------------------------------------------------------------*/
.eo-custom-page #adminmenuwrap,
.eo-custom-page #wpadminbar,
.eo-custom-page #adminmenumain,
.eo-custom-page #screen-meta-links,
.eo-custom-page #wpfooter {
	display: none !important;
}

body.eo-custom-page.admin-bar {
	position: relative;
	top: -32px;
}

body.eo-custom-page #wpcontent {
	margin-left: 0 !important;
	padding-left: 0;
}

body.eo-custom-page .nav-wrap {
	display: -webkit-box;
	display: -ms-flexbox;
	display: flex;
	-webkit-box-orient: vertical;
	-webkit-box-direction: normal;
	-ms-flex-direction: column;
	flex-direction: column;
	position: absolute;
	left: 0;
	top: 0;
	width: 200px;
	height: 100%;
	-webkit-transition: all 0.2s ease-out;
	transition: all 0.2s ease-out;
}

body.eo-custom-page .nav-wrap::before {
	display: block;
	content: '';
	position: fixed;
	top: 0;
	left: 0;
	width: 200px;
	height: 100%;
	background: #1c1d1b;
	z-index: -1;
	-webkit-transition: all 0.2s ease-out;
	transition: all 0.2s ease-out;
}

body.eo-custom-page .nav-wrap #logo {
	padding: 0.5em;
	text-align: center;
	border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

body.eo-custom-page .nav-wrap #logo h1 {
	height: 100px;
	display: -webkit-box;
	display: -ms-flexbox;
	display: flex;
	margin: 0;
}

body.eo-custom-page .nav-wrap #logo a {
	margin: auto;
}

body.eo-custom-page .nav-wrap #logo img {
	max-width: 50px;
	width: 100%;
	-webkit-transition: all 0.2s ease-out;
	transition: all 0.2s ease-out;
}

body.eo-custom-page .nav-wrap .nav-menu.nav-bottom {
	margin-top: 2em;
}

body.eo-custom-page .nav-wrap.wrap-reduce {
	width: 40px;
}

body.eo-custom-page .nav-wrap.wrap-reduce::before {
	width: 40px;
}

body.eo-custom-page .nav-wrap.wrap-reduce .nav-menu.nav-bottom .item {
	padding: 0;
}

/** Menu elements */
body.eo-custom-page .nav-menu .item {
	color: #fff;
	text-decoration: none;
	font-size: 14px;
}

body.eo-custom-page .nav-menu .item:focus {
	outline: none;
}

body.eo-custom-page .nav-menu .item:hover > div {
	background: rgba(255, 255, 255, 0.2);
}

body.eo-custom-page .nav-menu .item.item-active > div {
	background: rgba(255, 255, 255, 0.2);
}

body.eo-custom-page .nav-menu .item > div {
	padding: 0.7em;
	border-bottom: 1px solid rgba(255, 255, 255, 0.1);
	-webkit-transition: all 0.2s ease-out;
	transition: all 0.2s ease-out;
	display: -webkit-box;
	display: -ms-flexbox;
	display: flex;
}

body.eo-custom-page .nav-menu .item .nav-icon {
	margin-right: 0.6em;
	min-width: 20px;
	line-height: 1.3;
}

body.eo-custom-page .nav-menu .item .item-label {
	-webkit-transition: all 0.2s ease-out;
	transition: all 0.2s ease-out;
	white-space: nowrap;
}

body.eo-custom-page .nav-wrap.wrap-reduce .nav-menu .item > div {
	text-align: center;
}

body.eo-custom-page .nav-wrap.wrap-reduce .nav-menu .item .item-label {
	opacity: 0;
	pointer-events: none;
	-webkit-transform: translateX(-10px);
	transform: translateX(-10px);
}

body.eo-custom-page .nav-wrap.wrap-reduce .nav-menu .item .nav-icon {
	margin-right: 0;
}

/** Menu du bas */
body.eo-custom-page .nav-menu.nav-bottom .item {
	padding: 0 0.6em;
	display: block;
	margin-bottom: 0.5em;
}

body.eo-custom-page .nav-menu.nav-bottom .item > div {
	border-bottom: 0;
}

body.eo-custom-page .nav-menu.nav-bottom .item:not(.minimize-menu) > div {
	background: rgba(255, 255, 255, 0.2);
	color: #fff;
	border-radius: 6px;
}

body.eo-custom-page .nav-menu.nav-bottom .item:not(.minimize-menu):hover > div {
	background: rgba(255, 255, 255, 0.4);
}

body.eo-custom-page .nav-menu.nav-bottom .item.minimize-menu > div {
	color: rgba(255, 255, 255, 0.6);
}

body.eo-custom-page .nav-menu.nav-bottom .item.minimize-menu:hover > div {
	background: none;
	color: white;
}

body.eo-custom-page .nav-menu.nav-bottom .item.minimize-menu .item-label {
	-webkit-transition: none;
	transition: none;
}

body.eo-custom-page .content-wrap {
	margin-left: 200px;
	padding: 0;
	-webkit-transition: all 0.2s ease-out;
	transition: all 0.2s ease-out;
}

body.eo-custom-page .content-wrap.content-reduce {
	margin-left: 40px;
}

body.eo-custom-page .content-wrap #top-header {
	display: -webkit-box;
	display: -ms-flexbox;
	display: flex;
	-ms-flex-wrap: wrap;
	flex-wrap: wrap;
	padding: 1em;
	border-bottom: 1px solid rgba(0, 0, 0, 0.2);
}

body.eo-custom-page .content-wrap #top-header ul, body.eo-custom-page .content-wrap #top-header li {
	margin: 0;
}

body.eo-custom-page .content-wrap #top-header .nav-left {
	display: -webkit-box;
	display: -ms-flexbox;
	display: flex;
	max-width: 70%;
	margin: auto 0 auto 0;
}

body.eo-custom-page .content-wrap #top-header .nav-left > * {
	margin-top: auto;
	margin-bottom: auto;
}

body.eo-custom-page .content-wrap #top-header .nav-right {
	max-width: 30%;
	margin: auto 0 auto auto;
}

body.eo-custom-page .content-wrap #top-header .nav-bottom {
	width: 100%;
	margin-top: 0.5em;
}

body.eo-custom-page .content-wrap #top-header .nav-left .page-title {
	font-size: 22px;
	margin-right: 0.6em;
}

body.eo-custom-page .content-wrap #top-header .nav-left .nav-header li {
	margin: 0;
}

body.eo-custom-page .content-wrap #top-header .nav-right .navigation-avatar img {
	border-radius: 50%;
}

body.eo-custom-page .content-wrap .eo-wrap {
	margin: 0;
}

body.eo-custom-page .content-wrap .wpeo-wrap {
	margin: 0;
	padding: 1em;
}

body.eo-custom-page .content-wrap .wpeo-box {
	background: #fff;
	padding: 1.4em;
}

/*--------------------------------------------------------------
# Modules
--------------------------------------------------------------*/
/*--------------------------------------------------------------
	## Grid Layout
--------------------------------------------------------------*/
/*--------------------------------------------------------------
	Module : Grid
	Version : 1.0.0

	.wpeo-grid -> classe de base du mobule
	.grid-x -> Définit le nombre d'élément par ligne
		.grid-x -> Sur un élément, multiplie sa taille par le nombre X
	.grid-padding-x -> ajoute du padding
--------------------------------------------------------------*/
.wpeo-grid {
	display: -webkit-box;
	display: -ms-flexbox;
	display: flex;
	-webkit-box-orient: horizontal;
	-webkit-box-direction: normal;
	-ms-flex-flow: row wrap;
	flex-flow: row wrap;
	-webkit-box-flex: 0;
	-ms-flex: 0 1 auto;
	flex: 0 1 auto;
	margin-left: -0.5em;
	margin-right: -0.5em;
	width: 100%;
}

.wpeo-grid > * {
	padding: 0.5em !important;
}

.wpeo-grid.grid-1 > * {
	width: 100%;
}

.wpeo-grid.grid-1 > .grid-1 {
	width: 100%;
}

.wpeo-grid.grid-2 > * {
	width: 50%;
}

.wpeo-grid.grid-2 > .grid-1 {
	width: 50%;
}

.wpeo-grid.grid-2 > .grid-2 {
	width: 100%;
}

.wpeo-grid.grid-3 > * {
	width: 33.33333%;
}

.wpeo-grid.grid-3 > .grid-1 {
	width: 33.33333%;
}

.wpeo-grid.grid-3 > .grid-2 {
	width: 66.66667%;
}

.wpeo-grid.grid-3 > .grid-3 {
	width: 100%;
}

.wpeo-grid.grid-4 > * {
	width: 25%;
}

.wpeo-grid.grid-4 > .grid-1 {
	width: 25%;
}

.wpeo-grid.grid-4 > .grid-2 {
	width: 50%;
}

.wpeo-grid.grid-4 > .grid-3 {
	width: 75%;
}

.wpeo-grid.grid-4 > .grid-4 {
	width: 100%;
}

.wpeo-grid.grid-5 > * {
	width: 20%;
}

.wpeo-grid.grid-5 > .grid-1 {
	width: 20%;
}

.wpeo-grid.grid-5 > .grid-2 {
	width: 40%;
}

.wpeo-grid.grid-5 > .grid-3 {
	width: 60%;
}

.wpeo-grid.grid-5 > .grid-4 {
	width: 80%;
}

.wpeo-grid.grid-5 > .grid-5 {
	width: 100%;
}

.wpeo-grid.grid-6 > * {
	width: 16.66667%;
}

.wpeo-grid.grid-6 > .grid-1 {
	width: 16.66667%;
}

.wpeo-grid.grid-6 > .grid-2 {
	width: 33.33333%;
}

.wpeo-grid.grid-6 > .grid-3 {
	width: 50%;
}

.wpeo-grid.grid-6 > .grid-4 {
	width: 66.66667%;
}

.wpeo-grid.grid-6 > .grid-5 {
	width: 83.33333%;
}

.wpeo-grid.grid-6 > .grid-6 {
	width: 100%;
}

@media (max-width: 770px) {
	.wpeo-grid.grid-1 > * {
		width: 100%;
	}
	.wpeo-grid.grid-2 > *, .wpeo-grid.grid-3 > *, .wpeo-grid.grid-4 > *, .wpeo-grid.grid-5 > *, .wpeo-grid.grid-6 > * {
		width: 50%;
	}
}

@media (max-width: 480px) {
	.wpeo-grid.grid-1 > *, .wpeo-grid.grid-2 > *, .wpeo-grid.grid-3 > *, .wpeo-grid.grid-4 > *, .wpeo-grid.grid-5 > *, .wpeo-grid.grid-6 > * {
		width: 100%;
	}
}

/** Grid Padding */
.wpeo-grid.grid-padding-0 {
	margin-left: 0;
	margin-right: 0;
}

.wpeo-grid.grid-padding-0 > * {
	padding: 0 !important;
}

.wpeo-grid.grid-padding-1 {
	margin-left: -0.2em;
	margin-right: -0.2em;
}

.wpeo-grid.grid-padding-1 > * {
	padding: 0.2em !important;
}

.wpeo-grid.grid-padding-2 {
	margin-left: -0.5em;
	margin-right: -0.5em;
}

.wpeo-grid.grid-padding-2 > * {
	padding: 0.5em !important;
}

.wpeo-grid.grid-padding-3 {
	margin-left: -1em;
	margin-right: -1em;
}

.wpeo-grid.grid-padding-3 > * {
	padding: 1em !important;
}

.wpeo-gridlayout {
	display: grid;
	grid-gap: 1em 1em;
	grid-template-columns: repeat(4, 1fr);
}

/** Définition des tailles */
.wpeo-gridlayout {
	/** Du parent */
	/** Chaque enfant peut modifier sa propre taille */
	/** Chaque enfant peut modifier sa propre taille */
	/** Chaque enfant peut modifier sa propre taille */
	/** Chaque enfant peut modifier sa propre taille */
	/** Chaque enfant peut modifier sa propre taille */
	/** Chaque enfant peut modifier sa propre taille */
}

.wpeo-gridlayout.grid-1 {
	grid-template-columns: repeat(1, 1fr);
}

.wpeo-gridlayout.grid-1 > .gridw-1 {
	grid-column: auto/span 1;
}

.wpeo-gridlayout.grid-2 {
	grid-template-columns: repeat(2, 1fr);
}

.wpeo-gridlayout.grid-2 > .gridw-1 {
	grid-column: auto/span 1;
}

.wpeo-gridlayout.grid-2 > .gridw-2 {
	grid-column: auto/span 2;
}

@media (max-width: 770px) {
	.wpeo-gridlayout.grid-2 > .gridw-2 {
		grid-column: auto / span 2;
	}
}

.wpeo-gridlayout.grid-3 {
	grid-template-columns: repeat(3, 1fr);
}

.wpeo-gridlayout.grid-3 > .gridw-1 {
	grid-column: auto/span 1;
}

.wpeo-gridlayout.grid-3 > .gridw-2 {
	grid-column: auto/span 2;
}

@media (max-width: 770px) {
	.wpeo-gridlayout.grid-3 > .gridw-2 {
		grid-column: auto / span 2;
	}
}

.wpeo-gridlayout.grid-3 > .gridw-3 {
	grid-column: auto/span 3;
}

@media (max-width: 770px) {
	.wpeo-gridlayout.grid-3 > .gridw-3 {
		grid-column: auto / span 2;
	}
}

.wpeo-gridlayout.grid-4 {
	grid-template-columns: repeat(4, 1fr);
}

.wpeo-gridlayout.grid-4 > .gridw-1 {
	grid-column: auto/span 1;
}

.wpeo-gridlayout.grid-4 > .gridw-2 {
	grid-column: auto/span 2;
}

@media (max-width: 770px) {
	.wpeo-gridlayout.grid-4 > .gridw-2 {
		grid-column: auto / span 2;
	}
}

.wpeo-gridlayout.grid-4 > .gridw-3 {
	grid-column: auto/span 3;
}

@media (max-width: 770px) {
	.wpeo-gridlayout.grid-4 > .gridw-3 {
		grid-column: auto / span 2;
	}
}

.wpeo-gridlayout.grid-4 > .gridw-4 {
	grid-column: auto/span 4;
}

@media (max-width: 770px) {
	.wpeo-gridlayout.grid-4 > .gridw-4 {
		grid-column: auto / span 2;
	}
}

.wpeo-gridlayout.grid-5 {
	grid-template-columns: repeat(5, 1fr);
}

.wpeo-gridlayout.grid-5 > .gridw-1 {
	grid-column: auto/span 1;
}

.wpeo-gridlayout.grid-5 > .gridw-2 {
	grid-column: auto/span 2;
}

@media (max-width: 770px) {
	.wpeo-gridlayout.grid-5 > .gridw-2 {
		grid-column: auto / span 2;
	}
}

.wpeo-gridlayout.grid-5 > .gridw-3 {
	grid-column: auto/span 3;
}

@media (max-width: 770px) {
	.wpeo-gridlayout.grid-5 > .gridw-3 {
		grid-column: auto / span 2;
	}
}

.wpeo-gridlayout.grid-5 > .gridw-4 {
	grid-column: auto/span 4;
}

@media (max-width: 770px) {
	.wpeo-gridlayout.grid-5 > .gridw-4 {
		grid-column: auto / span 2;
	}
}

.wpeo-gridlayout.grid-5 > .gridw-5 {
	grid-column: auto/span 5;
}

@media (max-width: 770px) {
	.wpeo-gridlayout.grid-5 > .gridw-5 {
		grid-column: auto / span 2;
	}
}

.wpeo-gridlayout.grid-6 {
	grid-template-columns: repeat(6, 1fr);
}

.wpeo-gridlayout.grid-6 > .gridw-1 {
	grid-column: auto/span 1;
}

.wpeo-gridlayout.grid-6 > .gridw-2 {
	grid-column: auto/span 2;
}

@media (max-width: 770px) {
	.wpeo-gridlayout.grid-6 > .gridw-2 {
		grid-column: auto / span 2;
	}
}

.wpeo-gridlayout.grid-6 > .gridw-3 {
	grid-column: auto/span 3;
}

@media (max-width: 770px) {
	.wpeo-gridlayout.grid-6 > .gridw-3 {
		grid-column: auto / span 2;
	}
}

.wpeo-gridlayout.grid-6 > .gridw-4 {
	grid-column: auto/span 4;
}

@media (max-width: 770px) {
	.wpeo-gridlayout.grid-6 > .gridw-4 {
		grid-column: auto / span 2;
	}
}

.wpeo-gridlayout.grid-6 > .gridw-5 {
	grid-column: auto/span 5;
}

@media (max-width: 770px) {
	.wpeo-gridlayout.grid-6 > .gridw-5 {
		grid-column: auto / span 2;
	}
}

.wpeo-gridlayout.grid-6 > .gridw-6 {
	grid-column: auto/span 6;
}

@media (max-width: 770px) {
	.wpeo-gridlayout.grid-6 > .gridw-6 {
		grid-column: auto / span 2;
	}
}

/** Mages */
.wpeo-gridlayout.grid-margin-0 {
	margin: 0em 0;
}

.wpeo-gridlayout.grid-margin-1 {
	margin: 1em 0;
}

.wpeo-gridlayout.grid-margin-2 {
	margin: 2em 0;
}

.wpeo-gridlayout.grid-margin-3 {
	margin: 3em 0;
}

.wpeo-gridlayout.grid-margin-4 {
	margin: 4em 0;
}

.wpeo-gridlayout.grid-margin-5 {
	margin: 5em 0;
}

.wpeo-gridlayout.grid-margin-6 {
	margin: 6em 0;
}

/** Gouttières */
.wpeo-gridlayout.grid-gap-0 {
	grid-gap: 0em 0em;
}

.wpeo-gridlayout.grid-gap-1 {
	grid-gap: 1em 1em;
}

.wpeo-gridlayout.grid-gap-2 {
	grid-gap: 2em 2em;
}

.wpeo-gridlayout.grid-gap-3 {
	grid-gap: 3em 3em;
}

.wpeo-gridlayout.grid-gap-4 {
	grid-gap: 4em 4em;
}

.wpeo-gridlayout.grid-gap-5 {
	grid-gap: 5em 5em;
}

.wpeo-gridlayout.grid-gap-6 {
	grid-gap: 6em 6em;
}

/** Définition des hauteur des enfants */
.wpeo-gridlayout > .gridh-1 {
	grid-row: auto/span 1;
}

@media (max-width: 770px) {
	.wpeo-gridlayout > .gridh-1 {
		grid-row: auto / span 1 !important;
	}
}

.wpeo-gridlayout > .gridh-2 {
	grid-row: auto/span 2;
}

@media (max-width: 770px) {
	.wpeo-gridlayout > .gridh-2 {
		grid-row: auto / span 1 !important;
	}
}

.wpeo-gridlayout > .gridh-3 {
	grid-row: auto/span 3;
}

@media (max-width: 770px) {
	.wpeo-gridlayout > .gridh-3 {
		grid-row: auto / span 1 !important;
	}
}

.wpeo-gridlayout > .gridh-4 {
	grid-row: auto/span 4;
}

@media (max-width: 770px) {
	.wpeo-gridlayout > .gridh-4 {
		grid-row: auto / span 1 !important;
	}
}

.wpeo-gridlayout > .gridh-5 {
	grid-row: auto/span 5;
}

@media (max-width: 770px) {
	.wpeo-gridlayout > .gridh-5 {
		grid-row: auto / span 1 !important;
	}
}

.wpeo-gridlayout > .gridh-6 {
	grid-row: auto/span 6;
}

@media (max-width: 770px) {
	.wpeo-gridlayout > .gridh-6 {
		grid-row: auto / span 1 !important;
	}
}

/** Media queries */
@media (max-width: 770px) {
	.wpeo-gridlayout {
		grid-template-columns: repeat(2, 1fr) !important;
	}
}

@media (max-width: 480px) {
	.wpeo-gridlayout {
		grid-template-columns: repeat(1, 1fr) !important;
	}
}

@media (max-width: 480px) {
	.wpeo-gridlayout > * {
		grid-column: auto / span 1 !important;
	}
}

/*--------------------------------------------------------------
	## Animation
--------------------------------------------------------------*/
/*--------------------------------------------------------------
	Module : Animations
	Version : 1.2.0

	.wpeo-animate => classe de base pour le module
		.animate-hover => Permet de lancer l'animation au survol
		.animate-on => ajouter cette classe lors d'un event js pour animer l'element
--------------------------------------------------------------*/
.wpeo-animate {
	-webkit-animation-fill-mode: both;
	animation-fill-mode: both;
	-webkit-animation-duration: 1s;
	animation-duration: 1s;
}

.wpeo-animate.flipOutX, .wpeo-animate.flipOutY, .wpeo-animate.bounceIn, .wpeo-animate.bounceOut {
	-webkit-animation-duration: .75s;
	animation-duration: .75s;
}

.wpeo-animate.animate-hover:hover.bounce, .wpeo-animate.animate-on.bounce {
	-webkit-animation-name: bounce;
	animation-name: bounce;
	-webkit-transform-origin: center bottom;
	transform-origin: center bottom;
}

.wpeo-animate.animate-hover:hover.flash, .wpeo-animate.animate-on.flash {
	-webkit-animation-name: flash;
	animation-name: flash;
}

.wpeo-animate.animate-hover:hover.headShake, .wpeo-animate.animate-on.headShake {
	-webkit-animation-timing-function: ease-in-out;
	animation-timing-function: ease-in-out;
	-webkit-animation-name: headShake;
	animation-name: headShake;
}

.wpeo-animate.animate-hover:hover.jello, .wpeo-animate.animate-on.jello {
	-webkit-animation-name: jello;
	animation-name: jello;
	-webkit-transform-origin: center;
	transform-origin: center;
}

.wpeo-animate.animate-hover:hover.pulse, .wpeo-animate.animate-on.pulse {
	-webkit-animation-name: pulse;
	animation-name: pulse;
}

.wpeo-animate.animate-hover:hover.rubberBand, .wpeo-animate.animate-on.rubberBand {
	-webkit-animation-name: rubberBand;
	animation-name: rubberBand;
}

.wpeo-animate.animate-hover:hover.shake, .wpeo-animate.animate-on.shake {
	-webkit-animation-name: shake;
	animation-name: shake;
}

.wpeo-animate.animate-hover:hover.swing, .wpeo-animate.animate-on.swing {
	-webkit-transform-origin: top center;
	transform-origin: top center;
	-webkit-animation-name: swing;
	animation-name: swing;
}

.wpeo-animate.animate-hover:hover.tada, .wpeo-animate.animate-on.tada {
	-webkit-animation-name: tada;
	animation-name: tada;
}

.wpeo-animate.animate-hover:hover.wobble, .wpeo-animate.animate-on.wobble {
	-webkit-animation-name: wobble;
	animation-name: wobble;
}

.wpeo-animate.animate-hover:hover.bounceIn, .wpeo-animate.animate-on.bounceIn {
	-webkit-animation-name: bounceIn;
	animation-name: bounceIn;
}

.wpeo-animate.animate-hover:hover.bounceInDown, .wpeo-animate.animate-on.bounceInDown {
	-webkit-animation-name: bounceInDown;
	animation-name: bounceInDown;
}

.wpeo-animate.animate-hover:hover.bounceInLeft, .wpeo-animate.animate-on.bounceInLeft {
	-webkit-animation-name: bounceInLeft;
	animation-name: bounceInLeft;
}

.wpeo-animate.animate-hover:hover.bounceInRight, .wpeo-animate.animate-on.bounceInRight {
	-webkit-animation-name: bounceInRight;
	animation-name: bounceInRight;
}

.wpeo-animate.animate-hover:hover.bounceInUp, .wpeo-animate.animate-on.bounceInUp {
	-webkit-animation-name: bounceInUp;
	animation-name: bounceInUp;
}

.wpeo-animate.animate-hover:hover.bounceOut, .wpeo-animate.animate-on.bounceOut {
	-webkit-animation-name: bounceOut;
	animation-name: bounceOut;
}

.wpeo-animate.animate-hover:hover.bounceOutDown, .wpeo-animate.animate-on.bounceOutDown {
	-webkit-animation-name: bounceOutDown;
	animation-name: bounceOutDown;
}

.wpeo-animate.animate-hover:hover.bounceOutLeft, .wpeo-animate.animate-on.bounceOutLeft {
	-webkit-animation-name: bounceOutLeft;
	animation-name: bounceOutLeft;
}

.wpeo-animate.animate-hover:hover.bounceOutRight, .wpeo-animate.animate-on.bounceOutRight {
	-webkit-animation-name: bounceOutRight;
	animation-name: bounceOutRight;
}

.wpeo-animate.animate-hover:hover.bounceOutUp, .wpeo-animate.animate-on.bounceOutUp {
	-webkit-animation-name: bounceOutUp;
	animation-name: bounceOutUp;
}

.wpeo-animate.animate-hover:hover.fadeIn, .wpeo-animate.animate-on.fadeIn {
	-webkit-animation-name: fadeIn;
	animation-name: fadeIn;
}

.wpeo-animate.animate-hover:hover.fadeInDown, .wpeo-animate.animate-on.fadeInDown {
	-webkit-animation-name: fadeInDown;
	animation-name: fadeInDown;
}

.wpeo-animate.animate-hover:hover.fadeInDownBig, .wpeo-animate.animate-on.fadeInDownBig {
	-webkit-animation-name: fadeInDownBig;
	animation-name: fadeInDownBig;
}

.wpeo-animate.animate-hover:hover.fadeInLeft, .wpeo-animate.animate-on.fadeInLeft {
	-webkit-animation-name: fadeInLeft;
	animation-name: fadeInLeft;
}

.wpeo-animate.animate-hover:hover.fadeInLeftBig, .wpeo-animate.animate-on.fadeInLeftBig {
	-webkit-animation-name: fadeInLeftBig;
	animation-name: fadeInLeftBig;
}

.wpeo-animate.animate-hover:hover.fadeInRight, .wpeo-animate.animate-on.fadeInRight {
	-webkit-animation-name: fadeInRight;
	animation-name: fadeInRight;
}

.wpeo-animate.animate-hover:hover.fadeInRightBig, .wpeo-animate.animate-on.fadeInRightBig {
	-webkit-animation-name: fadeInRightBig;
	animation-name: fadeInRightBig;
}

.wpeo-animate.animate-hover:hover.fadeInUp, .wpeo-animate.animate-on.fadeInUp {
	-webkit-animation-name: fadeInUp;
	animation-name: fadeInUp;
}

.wpeo-animate.animate-hover:hover.fadeInUpBig, .wpeo-animate.animate-on.fadeInUpBig {
	-webkit-animation-name: fadeInUpBig;
	animation-name: fadeInUpBig;
}

.wpeo-animate.animate-hover:hover.fadeOut, .wpeo-animate.animate-on.fadeOut {
	-webkit-animation-name: fadeOut;
	animation-name: fadeOut;
}

.wpeo-animate.animate-hover:hover.fadeOutDown, .wpeo-animate.animate-on.fadeOutDown {
	-webkit-animation-name: fadeOutDown;
	animation-name: fadeOutDown;
}

.wpeo-animate.animate-hover:hover.fadeOutDownBig, .wpeo-animate.animate-on.fadeOutDownBig {
	-webkit-animation-name: fadeOutDownBig;
	animation-name: fadeOutDownBig;
}

.wpeo-animate.animate-hover:hover.fadeOutLeft, .wpeo-animate.animate-on.fadeOutLeft {
	-webkit-animation-name: fadeOutLeft;
	animation-name: fadeOutLeft;
}

.wpeo-animate.animate-hover:hover.fadeOutLeftBig, .wpeo-animate.animate-on.fadeOutLeftBig {
	-webkit-animation-name: fadeOutLeftBig;
	animation-name: fadeOutLeftBig;
}

.wpeo-animate.animate-hover:hover.fadeOutRight, .wpeo-animate.animate-on.fadeOutRight {
	-webkit-animation-name: fadeOutRight;
	animation-name: fadeOutRight;
}

.wpeo-animate.animate-hover:hover.fadeOutRightBig, .wpeo-animate.animate-on.fadeOutRightBig {
	-webkit-animation-name: fadeOutRightBig;
	animation-name: fadeOutRightBig;
}

.wpeo-animate.animate-hover:hover.fadeOutUp, .wpeo-animate.animate-on.fadeOutUp {
	-webkit-animation-name: fadeOutUp;
	animation-name: fadeOutUp;
}

.wpeo-animate.animate-hover:hover.fadeOutUpBig, .wpeo-animate.animate-on.fadeOutUpBig {
	-webkit-animation-name: fadeOutUpBig;
	animation-name: fadeOutUpBig;
}

.wpeo-animate.animate-hover:hover.slideInDown, .wpeo-animate.animate-on.slideInDown {
	-webkit-animation-name: slideInDown;
	animation-name: slideInDown;
}

.wpeo-animate.animate-hover:hover.slideInLeft, .wpeo-animate.animate-on.slideInLeft {
	-webkit-animation-name: slideInLeft;
	animation-name: slideInLeft;
}

.wpeo-animate.animate-hover:hover.slideInRight, .wpeo-animate.animate-on.slideInRight {
	-webkit-animation-name: slideInRight;
	animation-name: slideInRight;
}

.wpeo-animate.animate-hover:hover.slideInUp, .wpeo-animate.animate-on.slideInUp {
	-webkit-animation-name: slideInUp;
	animation-name: slideInUp;
}

.wpeo-animate.animate-hover:hover.slideOutDown, .wpeo-animate.animate-on.slideOutDown {
	-webkit-animation-name: slideOutDown;
	animation-name: slideOutDown;
}

.wpeo-animate.animate-hover:hover.slideOutLeft, .wpeo-animate.animate-on.slideOutLeft {
	-webkit-animation-name: slideOutLeft;
	animation-name: slideOutLeft;
}

.wpeo-animate.animate-hover:hover.slideOutRight, .wpeo-animate.animate-on.slideOutRight {
	-webkit-animation-name: slideOutRight;
	animation-name: slideOutRight;
}

.wpeo-animate.animate-hover:hover.slideOutUp, .wpeo-animate.animate-on.slideOutUp {
	-webkit-animation-name: slideOutUp;
	animation-name: slideOutUp;
}

.wpeo-animate.animate-hover:hover.bounceInLight, .wpeo-animate.animate-on.bounceInLight {
	-webkit-animation-name: bounceInLight;
	animation-name: bounceInLight;
}

.wpeo-animate.animate-hover:hover.rotate, .wpeo-animate.animate-on.rotate {
	-webkit-animation-name: rotate;
	animation-name: rotate;
}

.wpeo-animate.animate-hover:hover.downAndUp, .wpeo-animate.animate-on.downAndUp {
	-webkit-animation-name: downAndUp;
	animation-name: downAndUp;
}

/* Import des animations */
@-webkit-keyframes bounce {
	from, 20%, 53%, 80%, to {
		-webkit-animation-timing-function: cubic-bezier(0.215, 0.61, 0.355, 1);
		animation-timing-function: cubic-bezier(0.215, 0.61, 0.355, 1);
		-webkit-transform: translate3d(0, 0, 0);
		transform: translate3d(0, 0, 0);
	}
	40%, 43% {
		-webkit-animation-timing-function: cubic-bezier(0.755, 0.05, 0.855, 0.06);
		animation-timing-function: cubic-bezier(0.755, 0.05, 0.855, 0.06);
		-webkit-transform: translate3d(0, -30px, 0);
		transform: translate3d(0, -30px, 0);
	}
	70% {
		-webkit-animation-timing-function: cubic-bezier(0.755, 0.05, 0.855, 0.06);
		animation-timing-function: cubic-bezier(0.755, 0.05, 0.855, 0.06);
		-webkit-transform: translate3d(0, -15px, 0);
		transform: translate3d(0, -15px, 0);
	}
	90% {
		-webkit-transform: translate3d(0, -4px, 0);
		transform: translate3d(0, -4px, 0);
	}
}
@keyframes bounce {
	from, 20%, 53%, 80%, to {
		-webkit-animation-timing-function: cubic-bezier(0.215, 0.61, 0.355, 1);
		animation-timing-function: cubic-bezier(0.215, 0.61, 0.355, 1);
		-webkit-transform: translate3d(0, 0, 0);
		transform: translate3d(0, 0, 0);
	}
	40%, 43% {
		-webkit-animation-timing-function: cubic-bezier(0.755, 0.05, 0.855, 0.06);
		animation-timing-function: cubic-bezier(0.755, 0.05, 0.855, 0.06);
		-webkit-transform: translate3d(0, -30px, 0);
		transform: translate3d(0, -30px, 0);
	}
	70% {
		-webkit-animation-timing-function: cubic-bezier(0.755, 0.05, 0.855, 0.06);
		animation-timing-function: cubic-bezier(0.755, 0.05, 0.855, 0.06);
		-webkit-transform: translate3d(0, -15px, 0);
		transform: translate3d(0, -15px, 0);
	}
	90% {
		-webkit-transform: translate3d(0, -4px, 0);
		transform: translate3d(0, -4px, 0);
	}
}

@-webkit-keyframes flash {
	from, 50%, to {
		opacity: 1;
	}
	25%, 75% {
		opacity: 0;
	}
}

@keyframes flash {
	from, 50%, to {
		opacity: 1;
	}
	25%, 75% {
		opacity: 0;
	}
}

@-webkit-keyframes headShake {
	0% {
		-webkit-transform: translateX(0);
		transform: translateX(0);
	}
	6.5% {
		-webkit-transform: translateX(-6px) rotateY(-9deg);
		transform: translateX(-6px) rotateY(-9deg);
	}
	18.5% {
		-webkit-transform: translateX(5px) rotateY(7deg);
		transform: translateX(5px) rotateY(7deg);
	}
	31.5% {
		-webkit-transform: translateX(-3px) rotateY(-5deg);
		transform: translateX(-3px) rotateY(-5deg);
	}
	43.5% {
		-webkit-transform: translateX(2px) rotateY(3deg);
		transform: translateX(2px) rotateY(3deg);
	}
	50% {
		-webkit-transform: translateX(0);
		transform: translateX(0);
	}
}

@keyframes headShake {
	0% {
		-webkit-transform: translateX(0);
		transform: translateX(0);
	}
	6.5% {
		-webkit-transform: translateX(-6px) rotateY(-9deg);
		transform: translateX(-6px) rotateY(-9deg);
	}
	18.5% {
		-webkit-transform: translateX(5px) rotateY(7deg);
		transform: translateX(5px) rotateY(7deg);
	}
	31.5% {
		-webkit-transform: translateX(-3px) rotateY(-5deg);
		transform: translateX(-3px) rotateY(-5deg);
	}
	43.5% {
		-webkit-transform: translateX(2px) rotateY(3deg);
		transform: translateX(2px) rotateY(3deg);
	}
	50% {
		-webkit-transform: translateX(0);
		transform: translateX(0);
	}
}

@-webkit-keyframes jello {
	from, 11.1%, to {
		-webkit-transform: none;
		transform: none;
	}
	22.2% {
		-webkit-transform: skewX(-12.5deg) skewY(-12.5deg);
		transform: skewX(-12.5deg) skewY(-12.5deg);
	}
	33.3% {
		-webkit-transform: skewX(6.25deg) skewY(6.25deg);
		transform: skewX(6.25deg) skewY(6.25deg);
	}
	44.4% {
		-webkit-transform: skewX(-3.125deg) skewY(-3.125deg);
		transform: skewX(-3.125deg) skewY(-3.125deg);
	}
	55.5% {
		-webkit-transform: skewX(1.5625deg) skewY(1.5625deg);
		transform: skewX(1.5625deg) skewY(1.5625deg);
	}
	66.6% {
		-webkit-transform: skewX(-0.78125deg) skewY(-0.78125deg);
		transform: skewX(-0.78125deg) skewY(-0.78125deg);
	}
	77.7% {
		-webkit-transform: skewX(0.39063deg) skewY(0.39063deg);
		transform: skewX(0.39063deg) skewY(0.39063deg);
	}
	88.8% {
		-webkit-transform: skewX(-0.19531deg) skewY(-0.19531deg);
		transform: skewX(-0.19531deg) skewY(-0.19531deg);
	}
}

@keyframes jello {
	from, 11.1%, to {
		-webkit-transform: none;
		transform: none;
	}
	22.2% {
		-webkit-transform: skewX(-12.5deg) skewY(-12.5deg);
		transform: skewX(-12.5deg) skewY(-12.5deg);
	}
	33.3% {
		-webkit-transform: skewX(6.25deg) skewY(6.25deg);
		transform: skewX(6.25deg) skewY(6.25deg);
	}
	44.4% {
		-webkit-transform: skewX(-3.125deg) skewY(-3.125deg);
		transform: skewX(-3.125deg) skewY(-3.125deg);
	}
	55.5% {
		-webkit-transform: skewX(1.5625deg) skewY(1.5625deg);
		transform: skewX(1.5625deg) skewY(1.5625deg);
	}
	66.6% {
		-webkit-transform: skewX(-0.78125deg) skewY(-0.78125deg);
		transform: skewX(-0.78125deg) skewY(-0.78125deg);
	}
	77.7% {
		-webkit-transform: skewX(0.39063deg) skewY(0.39063deg);
		transform: skewX(0.39063deg) skewY(0.39063deg);
	}
	88.8% {
		-webkit-transform: skewX(-0.19531deg) skewY(-0.19531deg);
		transform: skewX(-0.19531deg) skewY(-0.19531deg);
	}
}

/* originally authored by Nick Pettit - https://github.com/nickpettit/glide */
@-webkit-keyframes pulse {
	from {
		-webkit-transform: scale3d(1, 1, 1);
		transform: scale3d(1, 1, 1);
	}
	50% {
		-webkit-transform: scale3d(1.05, 1.05, 1.05);
		transform: scale3d(1.05, 1.05, 1.05);
	}
	to {
		-webkit-transform: scale3d(1, 1, 1);
		transform: scale3d(1, 1, 1);
	}
}
@keyframes pulse {
	from {
		-webkit-transform: scale3d(1, 1, 1);
		transform: scale3d(1, 1, 1);
	}
	50% {
		-webkit-transform: scale3d(1.05, 1.05, 1.05);
		transform: scale3d(1.05, 1.05, 1.05);
	}
	to {
		-webkit-transform: scale3d(1, 1, 1);
		transform: scale3d(1, 1, 1);
	}
}

@-webkit-keyframes rubberBand {
	from {
		-webkit-transform: scale3d(1, 1, 1);
		transform: scale3d(1, 1, 1);
	}
	30% {
		-webkit-transform: scale3d(1.25, 0.75, 1);
		transform: scale3d(1.25, 0.75, 1);
	}
	40% {
		-webkit-transform: scale3d(0.75, 1.25, 1);
		transform: scale3d(0.75, 1.25, 1);
	}
	50% {
		-webkit-transform: scale3d(1.15, 0.85, 1);
		transform: scale3d(1.15, 0.85, 1);
	}
	65% {
		-webkit-transform: scale3d(0.95, 1.05, 1);
		transform: scale3d(0.95, 1.05, 1);
	}
	75% {
		-webkit-transform: scale3d(1.05, 0.95, 1);
		transform: scale3d(1.05, 0.95, 1);
	}
	to {
		-webkit-transform: scale3d(1, 1, 1);
		transform: scale3d(1, 1, 1);
	}
}

@keyframes rubberBand {
	from {
		-webkit-transform: scale3d(1, 1, 1);
		transform: scale3d(1, 1, 1);
	}
	30% {
		-webkit-transform: scale3d(1.25, 0.75, 1);
		transform: scale3d(1.25, 0.75, 1);
	}
	40% {
		-webkit-transform: scale3d(0.75, 1.25, 1);
		transform: scale3d(0.75, 1.25, 1);
	}
	50% {
		-webkit-transform: scale3d(1.15, 0.85, 1);
		transform: scale3d(1.15, 0.85, 1);
	}
	65% {
		-webkit-transform: scale3d(0.95, 1.05, 1);
		transform: scale3d(0.95, 1.05, 1);
	}
	75% {
		-webkit-transform: scale3d(1.05, 0.95, 1);
		transform: scale3d(1.05, 0.95, 1);
	}
	to {
		-webkit-transform: scale3d(1, 1, 1);
		transform: scale3d(1, 1, 1);
	}
}

@-webkit-keyframes shake {
	from, to {
		-webkit-transform: translate3d(0, 0, 0);
		transform: translate3d(0, 0, 0);
	}
	10%, 30%, 50%, 70%, 90% {
		-webkit-transform: translate3d(-10px, 0, 0);
		transform: translate3d(-10px, 0, 0);
	}
	20%, 40%, 60%, 80% {
		-webkit-transform: translate3d(10px, 0, 0);
		transform: translate3d(10px, 0, 0);
	}
}

@keyframes shake {
	from, to {
		-webkit-transform: translate3d(0, 0, 0);
		transform: translate3d(0, 0, 0);
	}
	10%, 30%, 50%, 70%, 90% {
		-webkit-transform: translate3d(-10px, 0, 0);
		transform: translate3d(-10px, 0, 0);
	}
	20%, 40%, 60%, 80% {
		-webkit-transform: translate3d(10px, 0, 0);
		transform: translate3d(10px, 0, 0);
	}
}

@-webkit-keyframes swing {
	20% {
		-webkit-transform: rotate3d(0, 0, 1, 15deg);
		transform: rotate3d(0, 0, 1, 15deg);
	}
	40% {
		-webkit-transform: rotate3d(0, 0, 1, -10deg);
		transform: rotate3d(0, 0, 1, -10deg);
	}
	60% {
		-webkit-transform: rotate3d(0, 0, 1, 5deg);
		transform: rotate3d(0, 0, 1, 5deg);
	}
	80% {
		-webkit-transform: rotate3d(0, 0, 1, -5deg);
		transform: rotate3d(0, 0, 1, -5deg);
	}
	to {
		-webkit-transform: rotate3d(0, 0, 1, 0deg);
		transform: rotate3d(0, 0, 1, 0deg);
	}
}

@keyframes swing {
	20% {
		-webkit-transform: rotate3d(0, 0, 1, 15deg);
		transform: rotate3d(0, 0, 1, 15deg);
	}
	40% {
		-webkit-transform: rotate3d(0, 0, 1, -10deg);
		transform: rotate3d(0, 0, 1, -10deg);
	}
	60% {
		-webkit-transform: rotate3d(0, 0, 1, 5deg);
		transform: rotate3d(0, 0, 1, 5deg);
	}
	80% {
		-webkit-transform: rotate3d(0, 0, 1, -5deg);
		transform: rotate3d(0, 0, 1, -5deg);
	}
	to {
		-webkit-transform: rotate3d(0, 0, 1, 0deg);
		transform: rotate3d(0, 0, 1, 0deg);
	}
}

@-webkit-keyframes tada {
	from {
		-webkit-transform: scale3d(1, 1, 1);
		transform: scale3d(1, 1, 1);
	}
	10%, 20% {
		-webkit-transform: scale3d(0.9, 0.9, 0.9) rotate3d(0, 0, 1, -3deg);
		transform: scale3d(0.9, 0.9, 0.9) rotate3d(0, 0, 1, -3deg);
	}
	30%, 50%, 70%, 90% {
		-webkit-transform: scale3d(1.1, 1.1, 1.1) rotate3d(0, 0, 1, 3deg);
		transform: scale3d(1.1, 1.1, 1.1) rotate3d(0, 0, 1, 3deg);
	}
	40%, 60%, 80% {
		-webkit-transform: scale3d(1.1, 1.1, 1.1) rotate3d(0, 0, 1, -3deg);
		transform: scale3d(1.1, 1.1, 1.1) rotate3d(0, 0, 1, -3deg);
	}
	to {
		-webkit-transform: scale3d(1, 1, 1);
		transform: scale3d(1, 1, 1);
	}
}

@keyframes tada {
	from {
		-webkit-transform: scale3d(1, 1, 1);
		transform: scale3d(1, 1, 1);
	}
	10%, 20% {
		-webkit-transform: scale3d(0.9, 0.9, 0.9) rotate3d(0, 0, 1, -3deg);
		transform: scale3d(0.9, 0.9, 0.9) rotate3d(0, 0, 1, -3deg);
	}
	30%, 50%, 70%, 90% {
		-webkit-transform: scale3d(1.1, 1.1, 1.1) rotate3d(0, 0, 1, 3deg);
		transform: scale3d(1.1, 1.1, 1.1) rotate3d(0, 0, 1, 3deg);
	}
	40%, 60%, 80% {
		-webkit-transform: scale3d(1.1, 1.1, 1.1) rotate3d(0, 0, 1, -3deg);
		transform: scale3d(1.1, 1.1, 1.1) rotate3d(0, 0, 1, -3deg);
	}
	to {
		-webkit-transform: scale3d(1, 1, 1);
		transform: scale3d(1, 1, 1);
	}
}

/* originally authored by Nick Pettit - https://github.com/nickpettit/glide */
@-webkit-keyframes wobble {
	from {
		-webkit-transform: none;
		transform: none;
	}
	15% {
		-webkit-transform: translate3d(-25%, 0, 0) rotate3d(0, 0, 1, -5deg);
		transform: translate3d(-25%, 0, 0) rotate3d(0, 0, 1, -5deg);
	}
	30% {
		-webkit-transform: translate3d(20%, 0, 0) rotate3d(0, 0, 1, 3deg);
		transform: translate3d(20%, 0, 0) rotate3d(0, 0, 1, 3deg);
	}
	45% {
		-webkit-transform: translate3d(-15%, 0, 0) rotate3d(0, 0, 1, -3deg);
		transform: translate3d(-15%, 0, 0) rotate3d(0, 0, 1, -3deg);
	}
	60% {
		-webkit-transform: translate3d(10%, 0, 0) rotate3d(0, 0, 1, 2deg);
		transform: translate3d(10%, 0, 0) rotate3d(0, 0, 1, 2deg);
	}
	75% {
		-webkit-transform: translate3d(-5%, 0, 0) rotate3d(0, 0, 1, -1deg);
		transform: translate3d(-5%, 0, 0) rotate3d(0, 0, 1, -1deg);
	}
	to {
		-webkit-transform: none;
		transform: none;
	}
}
@keyframes wobble {
	from {
		-webkit-transform: none;
		transform: none;
	}
	15% {
		-webkit-transform: translate3d(-25%, 0, 0) rotate3d(0, 0, 1, -5deg);
		transform: translate3d(-25%, 0, 0) rotate3d(0, 0, 1, -5deg);
	}
	30% {
		-webkit-transform: translate3d(20%, 0, 0) rotate3d(0, 0, 1, 3deg);
		transform: translate3d(20%, 0, 0) rotate3d(0, 0, 1, 3deg);
	}
	45% {
		-webkit-transform: translate3d(-15%, 0, 0) rotate3d(0, 0, 1, -3deg);
		transform: translate3d(-15%, 0, 0) rotate3d(0, 0, 1, -3deg);
	}
	60% {
		-webkit-transform: translate3d(10%, 0, 0) rotate3d(0, 0, 1, 2deg);
		transform: translate3d(10%, 0, 0) rotate3d(0, 0, 1, 2deg);
	}
	75% {
		-webkit-transform: translate3d(-5%, 0, 0) rotate3d(0, 0, 1, -1deg);
		transform: translate3d(-5%, 0, 0) rotate3d(0, 0, 1, -1deg);
	}
	to {
		-webkit-transform: none;
		transform: none;
	}
}

@-webkit-keyframes bounceIn {
	from, 20%, 40%, 60%, 80%, to {
		-webkit-animation-timing-function: cubic-bezier(0.215, 0.61, 0.355, 1);
		animation-timing-function: cubic-bezier(0.215, 0.61, 0.355, 1);
	}
	0% {
		opacity: 0;
		-webkit-transform: scale3d(0.3, 0.3, 0.3);
		transform: scale3d(0.3, 0.3, 0.3);
	}
	20% {
		-webkit-transform: scale3d(1.1, 1.1, 1.1);
		transform: scale3d(1.1, 1.1, 1.1);
	}
	40% {
		-webkit-transform: scale3d(0.9, 0.9, 0.9);
		transform: scale3d(0.9, 0.9, 0.9);
	}
	60% {
		opacity: 1;
		-webkit-transform: scale3d(1.03, 1.03, 1.03);
		transform: scale3d(1.03, 1.03, 1.03);
	}
	80% {
		-webkit-transform: scale3d(0.97, 0.97, 0.97);
		transform: scale3d(0.97, 0.97, 0.97);
	}
	to {
		opacity: 1;
		-webkit-transform: scale3d(1, 1, 1);
		transform: scale3d(1, 1, 1);
	}
}

@keyframes bounceIn {
	from, 20%, 40%, 60%, 80%, to {
		-webkit-animation-timing-function: cubic-bezier(0.215, 0.61, 0.355, 1);
		animation-timing-function: cubic-bezier(0.215, 0.61, 0.355, 1);
	}
	0% {
		opacity: 0;
		-webkit-transform: scale3d(0.3, 0.3, 0.3);
		transform: scale3d(0.3, 0.3, 0.3);
	}
	20% {
		-webkit-transform: scale3d(1.1, 1.1, 1.1);
		transform: scale3d(1.1, 1.1, 1.1);
	}
	40% {
		-webkit-transform: scale3d(0.9, 0.9, 0.9);
		transform: scale3d(0.9, 0.9, 0.9);
	}
	60% {
		opacity: 1;
		-webkit-transform: scale3d(1.03, 1.03, 1.03);
		transform: scale3d(1.03, 1.03, 1.03);
	}
	80% {
		-webkit-transform: scale3d(0.97, 0.97, 0.97);
		transform: scale3d(0.97, 0.97, 0.97);
	}
	to {
		opacity: 1;
		-webkit-transform: scale3d(1, 1, 1);
		transform: scale3d(1, 1, 1);
	}
}

@-webkit-keyframes bounceInDown {
	from, 60%, 75%, 90%, to {
		-webkit-animation-timing-function: cubic-bezier(0.215, 0.61, 0.355, 1);
		animation-timing-function: cubic-bezier(0.215, 0.61, 0.355, 1);
	}
	0% {
		opacity: 0;
		-webkit-transform: translate3d(0, -3000px, 0);
		transform: translate3d(0, -3000px, 0);
	}
	60% {
		opacity: 1;
		-webkit-transform: translate3d(0, 25px, 0);
		transform: translate3d(0, 25px, 0);
	}
	75% {
		-webkit-transform: translate3d(0, -10px, 0);
		transform: translate3d(0, -10px, 0);
	}
	90% {
		-webkit-transform: translate3d(0, 5px, 0);
		transform: translate3d(0, 5px, 0);
	}
	to {
		-webkit-transform: none;
		transform: none;
	}
}

@keyframes bounceInDown {
	from, 60%, 75%, 90%, to {
		-webkit-animation-timing-function: cubic-bezier(0.215, 0.61, 0.355, 1);
		animation-timing-function: cubic-bezier(0.215, 0.61, 0.355, 1);
	}
	0% {
		opacity: 0;
		-webkit-transform: translate3d(0, -3000px, 0);
		transform: translate3d(0, -3000px, 0);
	}
	60% {
		opacity: 1;
		-webkit-transform: translate3d(0, 25px, 0);
		transform: translate3d(0, 25px, 0);
	}
	75% {
		-webkit-transform: translate3d(0, -10px, 0);
		transform: translate3d(0, -10px, 0);
	}
	90% {
		-webkit-transform: translate3d(0, 5px, 0);
		transform: translate3d(0, 5px, 0);
	}
	to {
		-webkit-transform: none;
		transform: none;
	}
}

@-webkit-keyframes bounceInLeft {
	from, 60%, 75%, 90%, to {
		-webkit-animation-timing-function: cubic-bezier(0.215, 0.61, 0.355, 1);
		animation-timing-function: cubic-bezier(0.215, 0.61, 0.355, 1);
	}
	0% {
		opacity: 0;
		-webkit-transform: translate3d(-3000px, 0, 0);
		transform: translate3d(-3000px, 0, 0);
	}
	60% {
		opacity: 1;
		-webkit-transform: translate3d(25px, 0, 0);
		transform: translate3d(25px, 0, 0);
	}
	75% {
		-webkit-transform: translate3d(-10px, 0, 0);
		transform: translate3d(-10px, 0, 0);
	}
	90% {
		-webkit-transform: translate3d(5px, 0, 0);
		transform: translate3d(5px, 0, 0);
	}
	to {
		-webkit-transform: none;
		transform: none;
	}
}

@keyframes bounceInLeft {
	from, 60%, 75%, 90%, to {
		-webkit-animation-timing-function: cubic-bezier(0.215, 0.61, 0.355, 1);
		animation-timing-function: cubic-bezier(0.215, 0.61, 0.355, 1);
	}
	0% {
		opacity: 0;
		-webkit-transform: translate3d(-3000px, 0, 0);
		transform: translate3d(-3000px, 0, 0);
	}
	60% {
		opacity: 1;
		-webkit-transform: translate3d(25px, 0, 0);
		transform: translate3d(25px, 0, 0);
	}
	75% {
		-webkit-transform: translate3d(-10px, 0, 0);
		transform: translate3d(-10px, 0, 0);
	}
	90% {
		-webkit-transform: translate3d(5px, 0, 0);
		transform: translate3d(5px, 0, 0);
	}
	to {
		-webkit-transform: none;
		transform: none;
	}
}

@-webkit-keyframes bounceInRight {
	from, 60%, 75%, 90%, to {
		-webkit-animation-timing-function: cubic-bezier(0.215, 0.61, 0.355, 1);
		animation-timing-function: cubic-bezier(0.215, 0.61, 0.355, 1);
	}
	from {
		opacity: 0;
		-webkit-transform: translate3d(3000px, 0, 0);
		transform: translate3d(3000px, 0, 0);
	}
	60% {
		opacity: 1;
		-webkit-transform: translate3d(-25px, 0, 0);
		transform: translate3d(-25px, 0, 0);
	}
	75% {
		-webkit-transform: translate3d(10px, 0, 0);
		transform: translate3d(10px, 0, 0);
	}
	90% {
		-webkit-transform: translate3d(-5px, 0, 0);
		transform: translate3d(-5px, 0, 0);
	}
	to {
		-webkit-transform: none;
		transform: none;
	}
}

@keyframes bounceInRight {
	from, 60%, 75%, 90%, to {
		-webkit-animation-timing-function: cubic-bezier(0.215, 0.61, 0.355, 1);
		animation-timing-function: cubic-bezier(0.215, 0.61, 0.355, 1);
	}
	from {
		opacity: 0;
		-webkit-transform: translate3d(3000px, 0, 0);
		transform: translate3d(3000px, 0, 0);
	}
	60% {
		opacity: 1;
		-webkit-transform: translate3d(-25px, 0, 0);
		transform: translate3d(-25px, 0, 0);
	}
	75% {
		-webkit-transform: translate3d(10px, 0, 0);
		transform: translate3d(10px, 0, 0);
	}
	90% {
		-webkit-transform: translate3d(-5px, 0, 0);
		transform: translate3d(-5px, 0, 0);
	}
	to {
		-webkit-transform: none;
		transform: none;
	}
}

@-webkit-keyframes bounceInUp {
	from, 60%, 75%, 90%, to {
		-webkit-animation-timing-function: cubic-bezier(0.215, 0.61, 0.355, 1);
		animation-timing-function: cubic-bezier(0.215, 0.61, 0.355, 1);
	}
	from {
		opacity: 0;
		-webkit-transform: translate3d(0, 3000px, 0);
		transform: translate3d(0, 3000px, 0);
	}
	60% {
		opacity: 1;
		-webkit-transform: translate3d(0, -20px, 0);
		transform: translate3d(0, -20px, 0);
	}
	75% {
		-webkit-transform: translate3d(0, 10px, 0);
		transform: translate3d(0, 10px, 0);
	}
	90% {
		-webkit-transform: translate3d(0, -5px, 0);
		transform: translate3d(0, -5px, 0);
	}
	to {
		-webkit-transform: translate3d(0, 0, 0);
		transform: translate3d(0, 0, 0);
	}
}

@keyframes bounceInUp {
	from, 60%, 75%, 90%, to {
		-webkit-animation-timing-function: cubic-bezier(0.215, 0.61, 0.355, 1);
		animation-timing-function: cubic-bezier(0.215, 0.61, 0.355, 1);
	}
	from {
		opacity: 0;
		-webkit-transform: translate3d(0, 3000px, 0);
		transform: translate3d(0, 3000px, 0);
	}
	60% {
		opacity: 1;
		-webkit-transform: translate3d(0, -20px, 0);
		transform: translate3d(0, -20px, 0);
	}
	75% {
		-webkit-transform: translate3d(0, 10px, 0);
		transform: translate3d(0, 10px, 0);
	}
	90% {
		-webkit-transform: translate3d(0, -5px, 0);
		transform: translate3d(0, -5px, 0);
	}
	to {
		-webkit-transform: translate3d(0, 0, 0);
		transform: translate3d(0, 0, 0);
	}
}

@-webkit-keyframes bounceOut {
	20% {
		-webkit-transform: scale3d(0.9, 0.9, 0.9);
		transform: scale3d(0.9, 0.9, 0.9);
	}
	50%, 55% {
		opacity: 1;
		-webkit-transform: scale3d(1.1, 1.1, 1.1);
		transform: scale3d(1.1, 1.1, 1.1);
	}
	to {
		opacity: 0;
		-webkit-transform: scale3d(0.3, 0.3, 0.3);
		transform: scale3d(0.3, 0.3, 0.3);
	}
}

@keyframes bounceOut {
	20% {
		-webkit-transform: scale3d(0.9, 0.9, 0.9);
		transform: scale3d(0.9, 0.9, 0.9);
	}
	50%, 55% {
		opacity: 1;
		-webkit-transform: scale3d(1.1, 1.1, 1.1);
		transform: scale3d(1.1, 1.1, 1.1);
	}
	to {
		opacity: 0;
		-webkit-transform: scale3d(0.3, 0.3, 0.3);
		transform: scale3d(0.3, 0.3, 0.3);
	}
}

@-webkit-keyframes bounceOutDown {
	20% {
		-webkit-transform: translate3d(0, 10px, 0);
		transform: translate3d(0, 10px, 0);
	}
	40%, 45% {
		opacity: 1;
		-webkit-transform: translate3d(0, -20px, 0);
		transform: translate3d(0, -20px, 0);
	}
	to {
		opacity: 0;
		-webkit-transform: translate3d(0, 2000px, 0);
		transform: translate3d(0, 2000px, 0);
	}
}

@keyframes bounceOutDown {
	20% {
		-webkit-transform: translate3d(0, 10px, 0);
		transform: translate3d(0, 10px, 0);
	}
	40%, 45% {
		opacity: 1;
		-webkit-transform: translate3d(0, -20px, 0);
		transform: translate3d(0, -20px, 0);
	}
	to {
		opacity: 0;
		-webkit-transform: translate3d(0, 2000px, 0);
		transform: translate3d(0, 2000px, 0);
	}
}

@-webkit-keyframes bounceOutLeft {
	20% {
		opacity: 1;
		-webkit-transform: translate3d(20px, 0, 0);
		transform: translate3d(20px, 0, 0);
	}
	to {
		opacity: 0;
		-webkit-transform: translate3d(-2000px, 0, 0);
		transform: translate3d(-2000px, 0, 0);
	}
}

@keyframes bounceOutLeft {
	20% {
		opacity: 1;
		-webkit-transform: translate3d(20px, 0, 0);
		transform: translate3d(20px, 0, 0);
	}
	to {
		opacity: 0;
		-webkit-transform: translate3d(-2000px, 0, 0);
		transform: translate3d(-2000px, 0, 0);
	}
}

@-webkit-keyframes bounceOutRight {
	20% {
		opacity: 1;
		-webkit-transform: translate3d(-20px, 0, 0);
		transform: translate3d(-20px, 0, 0);
	}
	to {
		opacity: 0;
		-webkit-transform: translate3d(2000px, 0, 0);
		transform: translate3d(2000px, 0, 0);
	}
}

@keyframes bounceOutRight {
	20% {
		opacity: 1;
		-webkit-transform: translate3d(-20px, 0, 0);
		transform: translate3d(-20px, 0, 0);
	}
	to {
		opacity: 0;
		-webkit-transform: translate3d(2000px, 0, 0);
		transform: translate3d(2000px, 0, 0);
	}
}

@-webkit-keyframes bounceOutUp {
	20% {
		-webkit-transform: translate3d(0, -10px, 0);
		transform: translate3d(0, -10px, 0);
	}
	40%, 45% {
		opacity: 1;
		-webkit-transform: translate3d(0, 20px, 0);
		transform: translate3d(0, 20px, 0);
	}
	to {
		opacity: 0;
		-webkit-transform: translate3d(0, -2000px, 0);
		transform: translate3d(0, -2000px, 0);
	}
}

@keyframes bounceOutUp {
	20% {
		-webkit-transform: translate3d(0, -10px, 0);
		transform: translate3d(0, -10px, 0);
	}
	40%, 45% {
		opacity: 1;
		-webkit-transform: translate3d(0, 20px, 0);
		transform: translate3d(0, 20px, 0);
	}
	to {
		opacity: 0;
		-webkit-transform: translate3d(0, -2000px, 0);
		transform: translate3d(0, -2000px, 0);
	}
}

@-webkit-keyframes fadeIn {
	from {
		opacity: 0;
	}
	to {
		opacity: 1;
	}
}

@keyframes fadeIn {
	from {
		opacity: 0;
	}
	to {
		opacity: 1;
	}
}

@-webkit-keyframes fadeInDown {
	from {
		opacity: 0;
		-webkit-transform: translate3d(0, -100%, 0);
		transform: translate3d(0, -100%, 0);
	}
	to {
		opacity: 1;
		-webkit-transform: none;
		transform: none;
	}
}

@keyframes fadeInDown {
	from {
		opacity: 0;
		-webkit-transform: translate3d(0, -100%, 0);
		transform: translate3d(0, -100%, 0);
	}
	to {
		opacity: 1;
		-webkit-transform: none;
		transform: none;
	}
}

@-webkit-keyframes fadeInDownBig {
	from {
		opacity: 0;
		-webkit-transform: translate3d(0, -2000px, 0);
		transform: translate3d(0, -2000px, 0);
	}
	to {
		opacity: 1;
		-webkit-transform: none;
		transform: none;
	}
}

@keyframes fadeInDownBig {
	from {
		opacity: 0;
		-webkit-transform: translate3d(0, -2000px, 0);
		transform: translate3d(0, -2000px, 0);
	}
	to {
		opacity: 1;
		-webkit-transform: none;
		transform: none;
	}
}

@-webkit-keyframes fadeInLeft {
	from {
		opacity: 0;
		-webkit-transform: translate3d(-100%, 0, 0);
		transform: translate3d(-100%, 0, 0);
	}
	to {
		opacity: 1;
		-webkit-transform: none;
		transform: none;
	}
}

@keyframes fadeInLeft {
	from {
		opacity: 0;
		-webkit-transform: translate3d(-100%, 0, 0);
		transform: translate3d(-100%, 0, 0);
	}
	to {
		opacity: 1;
		-webkit-transform: none;
		transform: none;
	}
}

@-webkit-keyframes fadeInLeftBig {
	from {
		opacity: 0;
		-webkit-transform: translate3d(-2000px, 0, 0);
		transform: translate3d(-2000px, 0, 0);
	}
	to {
		opacity: 1;
		-webkit-transform: none;
		transform: none;
	}
}

@keyframes fadeInLeftBig {
	from {
		opacity: 0;
		-webkit-transform: translate3d(-2000px, 0, 0);
		transform: translate3d(-2000px, 0, 0);
	}
	to {
		opacity: 1;
		-webkit-transform: none;
		transform: none;
	}
}

@-webkit-keyframes fadeInRight {
	from {
		opacity: 0;
		-webkit-transform: translate3d(100%, 0, 0);
		transform: translate3d(100%, 0, 0);
	}
	to {
		opacity: 1;
		-webkit-transform: none;
		transform: none;
	}
}

@keyframes fadeInRight {
	from {
		opacity: 0;
		-webkit-transform: translate3d(100%, 0, 0);
		transform: translate3d(100%, 0, 0);
	}
	to {
		opacity: 1;
		-webkit-transform: none;
		transform: none;
	}
}

@-webkit-keyframes fadeInRightBig {
	from {
		opacity: 0;
		-webkit-transform: translate3d(2000px, 0, 0);
		transform: translate3d(2000px, 0, 0);
	}
	to {
		opacity: 1;
		-webkit-transform: none;
		transform: none;
	}
}

@keyframes fadeInRightBig {
	from {
		opacity: 0;
		-webkit-transform: translate3d(2000px, 0, 0);
		transform: translate3d(2000px, 0, 0);
	}
	to {
		opacity: 1;
		-webkit-transform: none;
		transform: none;
	}
}

@-webkit-keyframes fadeInUp {
	from {
		opacity: 0;
		-webkit-transform: translate3d(0, 100%, 0);
		transform: translate3d(0, 100%, 0);
	}
	to {
		opacity: 1;
		-webkit-transform: none;
		transform: none;
	}
}

@keyframes fadeInUp {
	from {
		opacity: 0;
		-webkit-transform: translate3d(0, 100%, 0);
		transform: translate3d(0, 100%, 0);
	}
	to {
		opacity: 1;
		-webkit-transform: none;
		transform: none;
	}
}

@-webkit-keyframes fadeInUpBig {
	from {
		opacity: 0;
		-webkit-transform: translate3d(0, 2000px, 0);
		transform: translate3d(0, 2000px, 0);
	}
	to {
		opacity: 1;
		-webkit-transform: none;
		transform: none;
	}
}

@keyframes fadeInUpBig {
	from {
		opacity: 0;
		-webkit-transform: translate3d(0, 2000px, 0);
		transform: translate3d(0, 2000px, 0);
	}
	to {
		opacity: 1;
		-webkit-transform: none;
		transform: none;
	}
}

@-webkit-keyframes fadeOut {
	from {
		opacity: 1;
	}
	to {
		opacity: 0;
	}
}

@keyframes fadeOut {
	from {
		opacity: 1;
	}
	to {
		opacity: 0;
	}
}

@-webkit-keyframes fadeOutDown {
	from {
		opacity: 1;
	}
	to {
		opacity: 0;
		-webkit-transform: translate3d(0, 100%, 0);
		transform: translate3d(0, 100%, 0);
	}
}

@keyframes fadeOutDown {
	from {
		opacity: 1;
	}
	to {
		opacity: 0;
		-webkit-transform: translate3d(0, 100%, 0);
		transform: translate3d(0, 100%, 0);
	}
}

@-webkit-keyframes fadeOutDownBig {
	from {
		opacity: 1;
	}
	to {
		opacity: 0;
		-webkit-transform: translate3d(0, 2000px, 0);
		transform: translate3d(0, 2000px, 0);
	}
}

@keyframes fadeOutDownBig {
	from {
		opacity: 1;
	}
	to {
		opacity: 0;
		-webkit-transform: translate3d(0, 2000px, 0);
		transform: translate3d(0, 2000px, 0);
	}
}

@-webkit-keyframes fadeOutLeft {
	from {
		opacity: 1;
	}
	to {
		opacity: 0;
		-webkit-transform: translate3d(-100%, 0, 0);
		transform: translate3d(-100%, 0, 0);
	}
}

@keyframes fadeOutLeft {
	from {
		opacity: 1;
	}
	to {
		opacity: 0;
		-webkit-transform: translate3d(-100%, 0, 0);
		transform: translate3d(-100%, 0, 0);
	}
}

@-webkit-keyframes fadeOutLeftBig {
	from {
		opacity: 1;
	}
	to {
		opacity: 0;
		-webkit-transform: translate3d(-2000px, 0, 0);
		transform: translate3d(-2000px, 0, 0);
	}
}

@keyframes fadeOutLeftBig {
	from {
		opacity: 1;
	}
	to {
		opacity: 0;
		-webkit-transform: translate3d(-2000px, 0, 0);
		transform: translate3d(-2000px, 0, 0);
	}
}

@-webkit-keyframes fadeOutRight {
	from {
		opacity: 1;
	}
	to {
		opacity: 0;
		-webkit-transform: translate3d(100%, 0, 0);
		transform: translate3d(100%, 0, 0);
	}
}

@keyframes fadeOutRight {
	from {
		opacity: 1;
	}
	to {
		opacity: 0;
		-webkit-transform: translate3d(100%, 0, 0);
		transform: translate3d(100%, 0, 0);
	}
}

@-webkit-keyframes fadeOutRightBig {
	from {
		opacity: 1;
	}
	to {
		opacity: 0;
		-webkit-transform: translate3d(2000px, 0, 0);
		transform: translate3d(2000px, 0, 0);
	}
}

@keyframes fadeOutRightBig {
	from {
		opacity: 1;
	}
	to {
		opacity: 0;
		-webkit-transform: translate3d(2000px, 0, 0);
		transform: translate3d(2000px, 0, 0);
	}
}

@-webkit-keyframes fadeOutUp {
	from {
		opacity: 1;
	}
	to {
		opacity: 0;
		-webkit-transform: translate3d(0, -100%, 0);
		transform: translate3d(0, -100%, 0);
	}
}

@keyframes fadeOutUp {
	from {
		opacity: 1;
	}
	to {
		opacity: 0;
		-webkit-transform: translate3d(0, -100%, 0);
		transform: translate3d(0, -100%, 0);
	}
}

@-webkit-keyframes fadeOutUpBig {
	from {
		opacity: 1;
	}
	to {
		opacity: 0;
		-webkit-transform: translate3d(0, -2000px, 0);
		transform: translate3d(0, -2000px, 0);
	}
}

@keyframes fadeOutUpBig {
	from {
		opacity: 1;
	}
	to {
		opacity: 0;
		-webkit-transform: translate3d(0, -2000px, 0);
		transform: translate3d(0, -2000px, 0);
	}
}

@-webkit-keyframes slideInDown {
	from {
		-webkit-transform: translate3d(0, -100%, 0);
		transform: translate3d(0, -100%, 0);
		visibility: visible;
	}
	to {
		-webkit-transform: translate3d(0, 0, 0);
		transform: translate3d(0, 0, 0);
	}
}

@keyframes slideInDown {
	from {
		-webkit-transform: translate3d(0, -100%, 0);
		transform: translate3d(0, -100%, 0);
		visibility: visible;
	}
	to {
		-webkit-transform: translate3d(0, 0, 0);
		transform: translate3d(0, 0, 0);
	}
}

@-webkit-keyframes slideInLeft {
	from {
		-webkit-transform: translate3d(-100%, 0, 0);
		transform: translate3d(-100%, 0, 0);
		visibility: visible;
	}
	to {
		-webkit-transform: translate3d(0, 0, 0);
		transform: translate3d(0, 0, 0);
	}
}

@keyframes slideInLeft {
	from {
		-webkit-transform: translate3d(-100%, 0, 0);
		transform: translate3d(-100%, 0, 0);
		visibility: visible;
	}
	to {
		-webkit-transform: translate3d(0, 0, 0);
		transform: translate3d(0, 0, 0);
	}
}

@-webkit-keyframes slideInRight {
	from {
		-webkit-transform: translate3d(100%, 0, 0);
		transform: translate3d(100%, 0, 0);
		visibility: visible;
	}
	to {
		-webkit-transform: translate3d(0, 0, 0);
		transform: translate3d(0, 0, 0);
	}
}

@keyframes slideInRight {
	from {
		-webkit-transform: translate3d(100%, 0, 0);
		transform: translate3d(100%, 0, 0);
		visibility: visible;
	}
	to {
		-webkit-transform: translate3d(0, 0, 0);
		transform: translate3d(0, 0, 0);
	}
}

@-webkit-keyframes slideInUp {
	from {
		-webkit-transform: translate3d(0, 100%, 0);
		transform: translate3d(0, 100%, 0);
		visibility: visible;
	}
	to {
		-webkit-transform: translate3d(0, 0, 0);
		transform: translate3d(0, 0, 0);
	}
}

@keyframes slideInUp {
	from {
		-webkit-transform: translate3d(0, 100%, 0);
		transform: translate3d(0, 100%, 0);
		visibility: visible;
	}
	to {
		-webkit-transform: translate3d(0, 0, 0);
		transform: translate3d(0, 0, 0);
	}
}

@-webkit-keyframes slideOutDown {
	from {
		-webkit-transform: translate3d(0, 0, 0);
		transform: translate3d(0, 0, 0);
	}
	to {
		visibility: hidden;
		-webkit-transform: translate3d(0, 100%, 0);
		transform: translate3d(0, 100%, 0);
	}
}

@keyframes slideOutDown {
	from {
		-webkit-transform: translate3d(0, 0, 0);
		transform: translate3d(0, 0, 0);
	}
	to {
		visibility: hidden;
		-webkit-transform: translate3d(0, 100%, 0);
		transform: translate3d(0, 100%, 0);
	}
}

@-webkit-keyframes slideOutLeft {
	from {
		-webkit-transform: translate3d(0, 0, 0);
		transform: translate3d(0, 0, 0);
	}
	to {
		visibility: hidden;
		-webkit-transform: translate3d(-100%, 0, 0);
		transform: translate3d(-100%, 0, 0);
	}
}

@keyframes slideOutLeft {
	from {
		-webkit-transform: translate3d(0, 0, 0);
		transform: translate3d(0, 0, 0);
	}
	to {
		visibility: hidden;
		-webkit-transform: translate3d(-100%, 0, 0);
		transform: translate3d(-100%, 0, 0);
	}
}

@-webkit-keyframes slideOutRight {
	from {
		-webkit-transform: translate3d(0, 0, 0);
		transform: translate3d(0, 0, 0);
	}
	to {
		visibility: hidden;
		-webkit-transform: translate3d(100%, 0, 0);
		transform: translate3d(100%, 0, 0);
	}
}

@keyframes slideOutRight {
	from {
		-webkit-transform: translate3d(0, 0, 0);
		transform: translate3d(0, 0, 0);
	}
	to {
		visibility: hidden;
		-webkit-transform: translate3d(100%, 0, 0);
		transform: translate3d(100%, 0, 0);
	}
}

@-webkit-keyframes slideOutUp {
	from {
		-webkit-transform: translate3d(0, 0, 0);
		transform: translate3d(0, 0, 0);
	}
	to {
		visibility: hidden;
		-webkit-transform: translate3d(0, -100%, 0);
		transform: translate3d(0, -100%, 0);
	}
}

@keyframes slideOutUp {
	from {
		-webkit-transform: translate3d(0, 0, 0);
		transform: translate3d(0, 0, 0);
	}
	to {
		visibility: hidden;
		-webkit-transform: translate3d(0, -100%, 0);
		transform: translate3d(0, -100%, 0);
	}
}

@-webkit-keyframes bounceInLight {
0, 20%, 40%, 60%, 80%, to {
						   -webkit-animation-timing-function: cubic-bezier(0.215, 0.61, 0.355, 1);
						   animation-timing-function: cubic-bezier(0.215, 0.61, 0.355, 1);
					   }
0% {
	-webkit-transform: scale3d(1, 1, 1);
	transform: scale3d(1, 1, 1);
}
20% {
	-webkit-transform: scale3d(1.2, 1.2, 1.2);
	transform: scale3d(1.2, 1.2, 1.2);
}
40% {
	-webkit-transform: scale3d(0.9, 0.9, 0.9);
	transform: scale3d(0.9, 0.9, 0.9);
}
60% {
	-webkit-transform: scale3d(1.1, 1.1, 1.1);
	transform: scale3d(1.1, 1.1, 1.1);
}
80% {
	-webkit-transform: scale3d(0.97, 0.97, 0.97);
	transform: scale3d(0.97, 0.97, 0.97);
}
to {
	-webkit-transform: scale3d(1, 1, 1);
	transform: scale3d(1, 1, 1);
}
}

@keyframes bounceInLight {
0, 20%, 40%, 60%, 80%, to {
						   -webkit-animation-timing-function: cubic-bezier(0.215, 0.61, 0.355, 1);
						   animation-timing-function: cubic-bezier(0.215, 0.61, 0.355, 1);
					   }
0% {
	-webkit-transform: scale3d(1, 1, 1);
	transform: scale3d(1, 1, 1);
}
20% {
	-webkit-transform: scale3d(1.2, 1.2, 1.2);
	transform: scale3d(1.2, 1.2, 1.2);
}
40% {
	-webkit-transform: scale3d(0.9, 0.9, 0.9);
	transform: scale3d(0.9, 0.9, 0.9);
}
60% {
	-webkit-transform: scale3d(1.1, 1.1, 1.1);
	transform: scale3d(1.1, 1.1, 1.1);
}
80% {
	-webkit-transform: scale3d(0.97, 0.97, 0.97);
	transform: scale3d(0.97, 0.97, 0.97);
}
to {
	-webkit-transform: scale3d(1, 1, 1);
	transform: scale3d(1, 1, 1);
}
}

@-webkit-keyframes downAndUp {
	0% {
		-webkit-transform: translateY(0px);
		transform: translateY(0px);
	}
	50% {
		-webkit-transform: translateY(4px);
		transform: translateY(4px);
	}
	100% {
		-webkit-transform: translateY(0px);
		transform: translateY(0px);
	}
}

@keyframes downAndUp {
	0% {
		-webkit-transform: translateY(0px);
		transform: translateY(0px);
	}
	50% {
		-webkit-transform: translateY(4px);
		transform: translateY(4px);
	}
	100% {
		-webkit-transform: translateY(0px);
		transform: translateY(0px);
	}
}

@-webkit-keyframes rotate {
	0% {
		-webkit-transform: rotate(0deg);
		transform: rotate(0deg);
	}
	100% {
		-webkit-transform: rotate(90deg);
		transform: rotate(90deg);
	}
}

@keyframes rotate {
	0% {
		-webkit-transform: rotate(0deg);
		transform: rotate(0deg);
	}
	100% {
		-webkit-transform: rotate(90deg);
		transform: rotate(90deg);
	}
}

/*--------------------------------------------------------------
	Module : Autocomplete
	Version : 1.2.0

	.wpeo-autocomplete => classe de base pour le module
		.autocomplete-loading -> Fais apparaître la barre de Chargement
		.autocomplete-full -> Fais apparaître la croix
--------------------------------------------------------------*/
.wpeo-autocomplete {
	position: relative;
}

.wpeo-autocomplete.autocomplete-active .autocomplete-search-list {
	opacity: 1;
	pointer-events: auto;
	-webkit-transform: translateY(0);
	transform: translateY(0);
}

/** Couleur */
.wpeo-autocomplete .autocomplete-label {
	background: #ececec;
}

.wpeo-autocomplete .autocomplete-search-list {
	background: #ececec;
}

.wpeo-autocomplete.autocomplete-light .autocomplete-label {
	background: #fff;
}

.wpeo-autocomplete.autocomplete-light .autocomplete-search-list {
	background: #fff;
}

/** Label */
.wpeo-autocomplete .autocomplete-label {
	display: block;
	padding: 1em;
	margin: 0;
	position: relative;
	-webkit-box-shadow: 0 2px 2px 0px rgba(0, 0, 0, 0.3);
	box-shadow: 0 2px 2px 0px rgba(0, 0, 0, 0.3);
	-webkit-transition: all 0.2s ease-out;
	transition: all 0.2s ease-out;
	border: 1px solid transparent;
}

.wpeo-autocomplete .autocomplete-label:hover {
	cursor: text;
	border: 1px solid #898de5;
}

.wpeo-autocomplete .autocomplete-search-input {
	background-color: transparent;
	border: 0;
	width: 100%;
	padding: 0 1.6em;
	font-size: 14px;
	font-weight: 400;
}

.wpeo-autocomplete .autocomplete-search-input:focus {
	outline: none;
	-webkit-box-shadow: none;
	box-shadow: none;
}

.wpeo-autocomplete .autocomplete-icon-before, .wpeo-autocomplete .autocomplete-icon-after {
	position: absolute;
	top: 50%;
}

.wpeo-autocomplete .autocomplete-icon-before {
	left: 1em;
	-webkit-transform: translateY(-50%);
	transform: translateY(-50%);
}

/** Chargement */
.wpeo-autocomplete .autocomplete-label .autocomplete-loading, .wpeo-autocomplete .autocomplete-label .autocomplete-loading-background {
	display: block;
	content: '';
	position: absolute;
	bottom: 0;
	left: 0;
	-webkit-transition: width 0.6s linear;
	transition: width 0.6s linear;
	height: 3px;
}

.wpeo-autocomplete .autocomplete-label .autocomplete-loading {
	width: 10%;
	background: #898de5;
}

.wpeo-autocomplete .autocomplete-label .autocomplete-loading-background {
	width: 100%;
	background: rgba(137, 141, 229, 0.4);
}

/** Croix */
.wpeo-autocomplete .autocomplete-icon-after {
	right: 0;
	padding: 1em;
	color: rgba(0, 0, 0, 0.4);
	-webkit-transition: all 0.2s ease-out;
	transition: all 0.2s ease-out;
	pointer-events: none;
	opacity: 0;
	-webkit-transform: translateY(-50%) translateX(10px);
	transform: translateY(-50%) translateX(10px);
	-webkit-transform-origin: 50%;
	transform-origin: 50%;
}

.wpeo-autocomplete .autocomplete-icon-after:hover {
	color: rgba(0, 0, 0, 0.8);
	cursor: pointer;
}

.wpeo-autocomplete.autocomplete-full .autocomplete-icon-after {
	opacity: 1;
	pointer-events: all;
	-webkit-transform: translateY(-50%) translateX(0);
	transform: translateY(-50%) translateX(0);
}

/** Liste de résultats */
.wpeo-autocomplete .autocomplete-search-list {
	opacity: 0;
	-webkit-transition: all 0.2s ease-out;
	transition: all 0.2s ease-out;
	-webkit-transform: translateY(-10px);
	transform: translateY(-10px);
	margin: 0;
	padding: 0;
	position: absolute;
	-webkit-box-shadow: 0 2px 2px 0px rgba(0, 0, 0, 0.3);
	box-shadow: 0 2px 2px 0px rgba(0, 0, 0, 0.3);
	border-top: 1px solid rgba(0, 0, 0, 0.1);
	z-index: 99;
	width: 300px;
	pointer-events: none;
}

.wpeo-autocomplete .autocomplete-search-list .autocomplete-result, .wpeo-autocomplete .autocomplete-search-list .autocomplete-result-text {
	list-style-type: none;
	padding: 0.6em 2.6em;
	display: -webkit-box;
	display: -ms-flexbox;
	display: flex;
	-webkit-box-orient: horizontal;
	-webkit-box-direction: normal;
	-ms-flex-flow: row wrap;
	flex-flow: row wrap;
	-webkit-box-flex: 0;
	-ms-flex: 0 1 auto;
	flex: 0 1 auto;
	margin: 0;
}

.wpeo-autocomplete .autocomplete-search-list .autocomplete-result {
	-webkit-transition: all 0.2s ease-out;
	transition: all 0.2s ease-out;
}

.wpeo-autocomplete .autocomplete-search-list .autocomplete-result:hover {
	background: rgba(0, 0, 0, 0.05);
	cursor: pointer;
}

.wpeo-autocomplete .autocomplete-search-list .autocomplete-result-image {
	margin-right: 1em;
	width: auto;
}

.wpeo-autocomplete .autocomplete-search-list .autocomplete-result-image.autocomplete-image-rounded {
	border-radius: 50%;
}

.wpeo-autocomplete .autocomplete-search-list .autocomplete-result-container {
	margin: auto 0;
}

.wpeo-autocomplete .autocomplete-search-list .autocomplete-result-title, .wpeo-autocomplete .autocomplete-search-list .autocomplete-result-subtitle {
	display: block;
}

.wpeo-autocomplete .autocomplete-search-list .autocomplete-result-title {
	font-size: 14px;
}

.wpeo-autocomplete .autocomplete-search-list .autocomplete-result-subtitle {
	font-size: 12px;
	color: rgba(0, 0, 0, 0.5);
}

/** Taille de la liste */
.wpeo-autocomplete.autocomplete-small .autocomplete-search-list {
	width: 200px;
}

.wpeo-autocomplete.autocomplete-medium .autocomplete-search-list {
	width: 300px;
}

.wpeo-autocomplete.autocomplete-large .autocomplete-search-list {
	width: 400px;
}

/*--------------------------------------------------------------
	Module : Dropdown
	Version : 1.0.0

	.wpeo-dropdown -> classe de base du mobule
		.grid -> Affichage en grille
		.list -> Affichage en liste
--------------------------------------------------------------*/
.wpeo-dropdown {
	position: relative;
	display: inline-block;
}

.wpeo-dropdown .dropdown-toggle {
	display: inline-block;
}

.wpeo-dropdown .dropdown-toggle span ~ *[class*="icon"] {
	margin-left: 10px;
}

.wpeo-dropdown .dropdown-content {
	opacity: 0;
	pointer-events: none;
	-webkit-transform: translateY(-10px);
	transform: translateY(-10px);
	-webkit-transition: all 0.2s ease-out;
	transition: all 0.2s ease-out;
	position: absolute;
	background: #fff;
	z-index: 99;
	border: 1px solid rgba(0, 0, 0, 0.1);
	-webkit-box-shadow: 0 0 10px 0px rgba(0, 0, 0, 0.3);
	box-shadow: 0 0 10px 0px rgba(0, 0, 0, 0.3);
	width: 220px;
	padding: 0.6em;
}

.wpeo-dropdown.dropdown-active .dropdown-content {
	opacity: 1;
	pointer-events: auto;
	-webkit-transform: translateY(0);
	transform: translateY(0);
}

.wpeo-dropdown ul, .wpeo-dropdown li {
	margin: 0;
	list-style-type: none;
	padding: 0;
}

.wpeo-dropdown .dropdown-item {
	display: block;
	color: rgba(0, 0, 0, 0.7);
	text-decoration: none;
}

.wpeo-dropdown .dropdown-item .dropdown-result-title, .wpeo-dropdown .dropdown-item .dropdown-result-subtitle {
	display: block;
}

.wpeo-dropdown .dropdown-item .dropdown-result-title {
	font-size: 14px;
}

.wpeo-dropdown .dropdown-item .dropdown-result-subtitle {
	font-size: 12px;
	color: rgba(0, 0, 0, 0.5);
}

/** Taille */
.wpeo-dropdown.dropdown-small .dropdown-content {
	width: 60px;
}

.wpeo-dropdown.dropdown-medium .dropdown-content {
	width: 220px;
}

.wpeo-dropdown.dropdown-large .dropdown-content {
	width: 360px;
}

/** Padding */
.wpeo-dropdown.dropdown-padding-0 .dropdown-content {
	padding: 0;
}

.wpeo-dropdown.dropdown-padding-1 .dropdown-content {
	padding: 0.6em;
}

.wpeo-dropdown.dropdown-padding-2 .dropdown-content {
	padding: 1.2em;
}

/** Alignement */
.wpeo-dropdown.dropdown-left .dropdown-content {
	left: 0;
}

.wpeo-dropdown.dropdown-right .dropdown-content {
	right: 0;
}

.wpeo-dropdown.dropdown-horizontal.dropdown-left .dropdown-content {
	left: 100%;
}

.wpeo-dropdown.dropdown-horizontal.dropdown-right .dropdown-content {
	right: 100%;
}

/** Sens */
.wpeo-dropdown.dropdown-horizontal .dropdown-content {
	top: 0;
	width: auto !important;
	display: -webkit-box;
	display: -ms-flexbox;
	display: flex;
	-ms-flex-wrap: nowrap;
	flex-wrap: nowrap;
	-webkit-box-orient: horizontal;
	-webkit-box-direction: normal;
	-ms-flex-direction: row;
	flex-direction: row;
}

/** Disable */
.wpeo-dropdown .dropdown-item.dropdown-item-disable {
	opacity: 0.6;
	cursor: default !important;
	pointer-events: none;
}

.wpeo-dropdown .dropdown-content .dropdown-item {
	padding: 0.6em;
	/*background: #fff;*/
	-webkit-transition: all 0.2s ease-out;
	transition: all 0.2s ease-out;
}

.wpeo-dropdown .dropdown-content .dropdown-item:hover {
	cursor: pointer;
	background: rgba(0, 0, 0, 0.1);
}

.wpeo-dropdown.dropdown-list .dropdown-content {
	text-align: left;
}

.wpeo-dropdown.dropdown-list .dropdown-item::after {
	display: block;
	content: '';
	clear: both;
}

.dropdown-item::before {
	display: none;
	content: '';
}

.wpeo-dropdown.dropdown-list .dropdown-item img {
	float: left;
	margin-right: 0.4em;
}

.wpeo-dropdown.dropdown-grid .dropdown-item {
	padding: 0;
}

.wpeo-dropdown.dropdown-grid .dropdown-item img {
	width: 100%;
	height: auto;
	display: block;
}

/*--------------------------------------------------------------
	Module : Form
	Version : 1.0.0
	--------------------------------------------------------------*/
/** Reset des champs de base */
.wpeo-form input, .wpeo-form textarea, .wpeo-form select {
	border: 0;
	font-size: 14px;
	background: transparent;
	padding: 0;
	margin: 0;
	width: 100%;
	padding: 1em;
	-webkit-box-shadow: none;
	box-shadow: none;
}

.wpeo-form input:focus, .wpeo-form input:active, .wpeo-form textarea:focus, .wpeo-form textarea:active, .wpeo-form select:focus, .wpeo-form select:active {
	outline: none;
	-webkit-box-shadow: none;
	box-shadow: none;
}

.wpeo-form input[type="submit"] {
	width: auto;
}

/** compatibilité Date */
.wpeo-form .group-date .mysql-date {
	display: none;
}

/* Compatibility Dropdown */
.wpeo-form .wpeo-dropdown {
	display: block;
	width: 100%;
}

.wpeo-form .wpeo-dropdown .dropdown-toggle {
	width: 100%;
	display: block;
	-webkit-transition: all 0.2s ease-out;
	transition: all 0.2s ease-out;
	border: 0;
	font-size: 14px;
	padding: 1em 2em 1em 1em;
	margin: 0;
}

.wpeo-form .wpeo-dropdown .dropdown-toggle:hover {
	cursor: pointer;
}

.wpeo-form .wpeo-dropdown .dropdown-toggle > .svg-inline--fa {
	position: absolute;
	right: 1em;
	top: 50%;
	-webkit-transform: translateY(-50%);
	transform: translateY(-50%);
}

.wpeo-form .form-element.disable .dropdown-toggle > .svg-inline--fa {
	display: none;
}

/* Compatibility Autocomplete */
.wpeo-form .wpeo-autocomplete {
	display: block;
	width: 100%;
}

.wpeo-form .wpeo-autocomplete .autocomplete-label {
	-webkit-box-shadow: none;
	box-shadow: none;
	padding: 0.74em 1em;
}

.wpeo-form.form-light .wpeo-autocomplete .autocomplete-label {
	background: #fff;
}

.wpeo-form.form-light .wpeo-autocomplete .autocomplete-label:hover {
	background: #ececec;
}

/** Général */
.wpeo-form .form-element input[type="radio"].form-field {
	display: inline-block;
	width: auto;
}

/** Design */
.wpeo-form .form-element input[type="radio"].form-field {
	-webkit-appearance: none;
	-moz-appearance: none;
	appearance: none;
	border-radius: 50%;
	width: 16px;
	height: 16px;
	padding: 0;
	border: 0;
	background: transparent !important;
	border: 1px solid rgba(0, 0, 0, 0.4);
	-webkit-transition: 0.2s all linear;
	transition: 0.2s all linear;
	outline: none;
	position: relative;
	top: 2px;
}

.wpeo-form .form-element input[type="radio"].form-field::before {
	display: none !important;
	content: '' !important;
}

.wpeo-form .form-element input[type="radio"].form-field:hover {
	cursor: pointer;
	border: 1px solid #898de5;
	-webkit-box-shadow: 0 0 0 1px #898de5 inset;
	box-shadow: 0 0 0 1px #898de5 inset;
	background: transparent !important;
}

.wpeo-form .form-element input[type="radio"].form-field:checked {
	border: 1px solid #898de5;
	-webkit-box-shadow: 0 0 0 4px #898de5 inset;
	box-shadow: 0 0 0 4px #898de5 inset;
}

.wpeo-form .form-element input[type="radio"].form-field + label {
	text-transform: none;
	font-weight: 400;
	font-size: 14px;
	display: inline-block;
	margin-right: 1em;
}

.wpeo-form .form-element input[type="radio"].form-field + label:hover {
	cursor: pointer;
}

.wpeo-form .form-element input[type="radio"].form-field + label:active {
	outline: none;
}

/** Général */
.wpeo-form .form-element input[type="checkbox"].form-field {
	display: inline-block;
	width: auto;
}

/** Design */
.wpeo-form .form-element input[type="checkbox"].form-field {
	width: auto;
	visibility: hidden;
	display: none;
}

.wpeo-form .form-element input[type="checkbox"].form-field + label {
	text-transform: none;
	font-weight: 400;
	font-size: 14px;
	display: inline-block;
	margin-right: 1em;
	position: relative;
}

.wpeo-form .form-element input[type="checkbox"].form-field + label:hover {
	cursor: pointer;
}

.wpeo-form .form-element input[type="checkbox"].form-field + label:active {
	outline: none;
}

.wpeo-form .form-element input[type="checkbox"].form-field + label::before {
	display: inline-block;
	content: '';
	width: 14px;
	height: 14px;
	background: transparent;
	-webkit-box-shadow: 0 0 0 1px rgba(0, 0, 0, 0.4);
	box-shadow: 0 0 0 1px rgba(0, 0, 0, 0.4);
	border: 2px solid #fff;
	-webkit-transition: all 0.2s ease-out;
	transition: all 0.2s ease-out;
	margin-right: 0.6em;
}

.wpeo-form .form-element input[type="checkbox"].form-field:not(:checked) + label:hover::before {
	-webkit-box-shadow: 0 0 0 2px #898de5;
	box-shadow: 0 0 0 2px #898de5;
}

.wpeo-form .form-element input[type="checkbox"].form-field:checked + label::before {
	-webkit-box-shadow: 0 0 0 2px #898de5;
	box-shadow: 0 0 0 2px #898de5;
	background: #898de5;
}

/** Formulaire design */
.wpeo-form .form-element {
	width: 100%;
}

.wpeo-form .form-element .form-label {
	display: block;
	font-size: 12px;
	text-transform: uppercase;
	font-weight: 900;
	margin: 0.6em 0;
	color: rgba(0, 0, 0, 0.8);
}

.wpeo-form .form-element .form-field-container {
	display: block;
	display: -webkit-box;
	display: -ms-flexbox;
	display: flex;
	-webkit-box-orient: horizontal;
	-webkit-box-direction: normal;
	-ms-flex-flow: row wrap;
	flex-flow: row wrap;
	-webkit-box-flex: 0;
	-ms-flex: 0 1 auto;
	flex: 0 1 auto;
	-ms-flex-wrap: nowrap;
	flex-wrap: nowrap;
}

.wpeo-form .form-element .form-field-container:hover {
	-webkit-box-shadow: none;
	box-shadow: none;
}

.wpeo-form .form-element .form-field {
	-webkit-transition: all 0.2s ease-out;
	transition: all 0.2s ease-out;
	border-radius: 0;
}

.wpeo-form .form-element .form-field:hover {
	-webkit-box-shadow: none;
	box-shadow: none;
}

.wpeo-form .form-element .form-sublabel {
	font-size: 12px;
	font-style: italic;
	color: rgba(0, 0, 0, 0.6);
}

/** Alignement */
.wpeo-form .form-element .form-field-inline {
	margin-right: 0.4em;
}

.wpeo-form .form-element.form-align-vertical .form-field-container {
	-webkit-box-orient: vertical;
	-webkit-box-direction: normal;
	-ms-flex-direction: column;
	flex-direction: column;
}

.wpeo-form .form-element.form-align-horizontal .form-field-container {
	-ms-flex-wrap: wrap;
	flex-wrap: wrap;
}

/** Couleur */
.wpeo-form .form-element .form-field-container .form-field, .wpeo-form .form-element .form-field-container [class*="form-field-icon"] {
	background: #ececec;
}

.wpeo-form .form-element .form-field-container:hover .form-field, .wpeo-form .form-element .form-field-container:hover [class*="form-field-icon"] {
	background: #dfdfdf;
}

.wpeo-form .form-element [class*="form-field-label"] {
	background: #dfdfdf;
}

.wpeo-form.form-light .form-element .form-field-container .form-field, .wpeo-form.form-light .form-element .form-field-container [class*="form-field-icon"] {
	background: #fff;
}

.wpeo-form.form-light .form-element .form-field-container:hover .form-field, .wpeo-form.form-light .form-element .form-field-container:hover [class*="form-field-icon"] {
	background: #ececec;
}

.wpeo-form.form-light .form-element [class*="form-field-label"] {
	background: #ececec;
}

/** Icone */
.wpeo-form .form-element [class*="form-field-icon"] {
	padding: 0.8em 0 0.8em 0.8em;
	color: rgba(0, 0, 0, 0.4);
	font-size: 16px;
	-webkit-transition: all 0.2s ease-out;
	transition: all 0.2s ease-out;
}

.wpeo-form .form-element [class*="form-field-icon"] [class*="fa"] {
	vertical-align: middle;
}

.wpeo-form .form-element .form-field-icon-prev {
	padding: 0.8em 0 0.8em 0.8em;
}

.wpeo-form .form-element .form-field-icon-next {
	padding: 0.8em 0.8em 0.8em 0;
}

/** Previous & next label */
.wpeo-form .form-element [class*="form-field-label"] {
	padding: 1.2em 1em;
	font-size: 12px;
}

.wpeo-form .form-element .form-field-label-prev {
	border-right: 1px solid rgba(0, 0, 0, 0.1);
}

.wpeo-form .form-element .form-field-label-next {
	border-left: 1px solid rgba(0, 0, 0, 0.1);
}

/** Required */
.wpeo-form .form-element.form-element-required .form-label::after {
	display: inline-block;
	content: '*';
	color: #e05353;
	padding: 0 0.4em;
}

/** Erreur sur un champs */
.wpeo-form .form-element.form-element-error .form-field-container {
	border: 1px solid #e05353;
}

/** Champs disabled */
.wpeo-form .form-element.form-element-disable .form-field-container {
	opacity: 0.6;
	pointer-events: none;
}

/* Simple */
/* Moderne */
/*--------------------------------------------------------------
	Module : Button
	Version : 1.2.0

 * Les boutons se forment grâce à leurs classes CSS
 * .button -> classe de base pour un bouton
 * 		.primary
 * 		.light
 * 		.dark
 * 		.red
 * 		.yellow
 * 		.blue
 * 		.green
 * 		.transparent
 * .bordered -> Change l'affichage du bouton. Fonctionne avec les même couleurs
 * .strong -> texte en gras
 * .uppercase -> texte en majuscule
 * .float-right -> float right
 * .float-left -> float left
 * .square-30 -> bouton carré 30px
 * .square-40 -> bouton carré 40px
 * .square-50 -> bouton carré 50px
 * .square-60 -> bouton carré 60px
 * .margin -> margin haut et bas sur le bouton
 * .radius-1 -> Arrondis les bords
 * .radius-3 -> Arrondis les bords
 * .radius-3 -> Arrondis les bords
 * .rounded -> bouton en forme de rond
 * .disable -> désactive les actions sur le bouton
 * .size-small -> petite taille
 * .size-large -> grande taille
 * .progress -> Active le progress button
 * 		.load -> le bouton affiche une icone de chargement
 * 		.success -> le bouton affiche une icone succès
 * 		.error -> Le bouton affiche une icone erreur
--------------------------------------------------------------*/
/*.wpeo-button {*/
/*	display: inline-block;*/
/*	border: 0;*/
/*	-webkit-box-shadow: none;*/
/*	box-shadow: none;*/
/*	background: none;*/
/*	text-decoration: none;*/
/*	padding: 6px 14px;*/
/*	line-height: 1.4;*/
/*	vertical-align: middle;*/
/*	height: auto;*/
/*	border-radius: 0;*/
/*	-webkit-transition: all 0.2s ease-out;*/
/*	transition: all 0.2s ease-out;*/
/*	position: relative;*/
/*	border-width: 3px;*/
/*	border-style: solid;*/
/*	font-size: 16px;*/
/*	background: #898de5;*/
/*	border-color: #898de5;*/
/*	color: #fff;*/
/*}*/
/**/
/*.wpeo-button:focus, .wpeo-button:visited {*/
/*	color: #fff;*/
/*}*/
/**/
/*.wpeo-button:hover {*/
/*	color: #fff;*/
/*	-webkit-box-shadow: inset 0 -2.6em rgba(255, 255, 255, 0.25);*/
/*	box-shadow: inset 0 -2.6em rgba(255, 255, 255, 0.25);*/
/*	cursor: pointer;*/
/*}*/
/**/
/*.wpeo-button:focus, .wpeo-button:active {*/
/*	outline: none;*/
/*}*/
/**/
/*/** Colors */*/
/*.wpeo-button {*/
/*	/* par défaut */*/
/*	background: #898de5;*/
/*	border-color: #898de5;*/
/*	color: #fff;*/
/*}*/
/**/
/*.wpeo-button.button-main {*/
/*	background: #898de5;*/
/*	border-color: #898de5;*/
/*	color: #fff;*/
/*}*/
/**/
/*.wpeo-button.button-light {*/
/*	background: #fff;*/
/*	border-color: #fff;*/
/*	color: #333333;*/
/*}*/
/**/
/*.wpeo-button.button-light:hover {*/
/*	-webkit-box-shadow: inset 0 -2.6em rgba(0, 0, 0, 0.1);*/
/*	box-shadow: inset 0 -2.6em rgba(0, 0, 0, 0.1);*/
/*	color: #333333;*/
/*}*/
/**/
/*.wpeo-button.button-dark {*/
/*	background: #2b2b2b;*/
/*	border-color: #2b2b2b;*/
/*}*/
/**/
/*.wpeo-button.button-grey {*/
/*	background: #ececec;*/
/*	border-color: #ececec;*/
/*	color: #333333;*/
/*}*/
/**/
/*.wpeo-button.button-grey:hover {*/
/*	-webkit-box-shadow: inset 0 -2.6em rgba(0, 0, 0, 0.1);*/
/*	box-shadow: inset 0 -2.6em rgba(0, 0, 0, 0.1);*/
/*	color: #333333;*/
/*}*/
/**/
/*.wpeo-button.button-red {*/
/*	background: #e05353;*/
/*	border-color: #e05353;*/
/*}*/
/**/
/*.wpeo-button.button-yellow {*/
/*	background: #e9ad4f;*/
/*	border-color: #e9ad4f;*/
/*}*/
/**/
/*.wpeo-button.button-blue {*/
/*	background: #0d8aff;*/
/*	border-color: #0d8aff;*/
/*}*/
/**/
/*.wpeo-button.button-green {*/
/*	background: #47e58e;*/
/*	border-color: #47e58e;*/
/*}*/
/**/
/*.wpeo-button.button-transparent {*/
/*	background: transparent;*/
/*	border-color: transparent;*/
/*	color: rgba(51, 51, 51, 0.4);*/
/*}*/
/**/
/*.wpeo-button.button-transparent:hover {*/
/*	color: #333333;*/
/*	-webkit-box-shadow: inset 0 -2.6em rgba(255, 255, 255, 0);*/
/*	box-shadow: inset 0 -2.6em rgba(255, 255, 255, 0);*/
/*}*/
/**/
/*.wpeo-button.button-bordered {*/
/*	background: none;*/
/*	/* Par defaut */*/
/*	border-color: #898de5;*/
/*	color: #898de5;*/
/*}*/
/**/
/*.wpeo-button.button-bordered.button-main {*/
/*	border-color: #898de5;*/
/*	color: #898de5;*/
/*}*/
/**/
/*.wpeo-button.button-bordered.button-main:hover {*/
/*	-webkit-box-shadow: inset 0 -2.6em #898de5;*/
/*	box-shadow: inset 0 -2.6em #898de5;*/
/*	color: #fff;*/
/*}*/
/**/
/*.wpeo-button.button-bordered.button-light {*/
/*	border-color: #fff;*/
/*	color: #fff;*/
/*}*/
/**/
/*.wpeo-button.button-bordered.button-light:hover {*/
/*	-webkit-box-shadow: inset 0 -2.6em #fff;*/
/*	box-shadow: inset 0 -2.6em #fff;*/
/*	color: #333333;*/
/*}*/
/**/
/*.wpeo-button.button-bordered.button-dark {*/
/*	border-color: #2b2b2b;*/
/*	color: #2b2b2b;*/
/*}*/
/**/
/*.wpeo-button.button-bordered.button-dark:hover {*/
/*	-webkit-box-shadow: inset 0 -2.6em #2b2b2b;*/
/*	box-shadow: inset 0 -2.6em #2b2b2b;*/
/*}*/
/**/
/*.wpeo-button.button-bordered.button-grey {*/
/*	border-color: #ececec;*/
/*	color: #a0a0a0;*/
/*}*/
/**/
/*.wpeo-button.button-bordered.button-grey:hover {*/
/*	-webkit-box-shadow: inset 0 -2.6em #ececec;*/
/*	box-shadow: inset 0 -2.6em #ececec;*/
/*	color: #333333;*/
/*}*/
/**/
/*.wpeo-button.button-bordered.button-red {*/
/*	border-color: #e05353;*/
/*	color: #e05353;*/
/*}*/
/**/
/*.wpeo-button.button-bordered.button-red:hover {*/
/*	-webkit-box-shadow: inset 0 -2.6em #e05353;*/
/*	box-shadow: inset 0 -2.6em #e05353;*/
/*}*/
/**/
/*.wpeo-button.button-bordered.button-yellow {*/
/*	border-color: #e9ad4f;*/
/*	color: #e9ad4f;*/
/*}*/
/**/
/*.wpeo-button.button-bordered.button-yellow:hover {*/
/*	-webkit-box-shadow: inset 0 -2.6em #e9ad4f;*/
/*	box-shadow: inset 0 -2.6em #e9ad4f;*/
/*}*/
/**/
/*.wpeo-button.button-bordered.button-blue {*/
/*	border-color: #0d8aff;*/
/*	color: #0d8aff;*/
/*}*/
/**/
/*.wpeo-button.button-bordered.button-blue:hover {*/
/*	-webkit-box-shadow: inset 0 -2.6em #0d8aff;*/
/*	box-shadow: inset 0 -2.6em #0d8aff;*/
/*}*/
/**/
/*.wpeo-button.button-bordered.button-green {*/
/*	border-color: #47e58e;*/
/*	color: #47e58e;*/
/*}*/
/**/
/*.wpeo-button.button-bordered.button-green:hover {*/
/*	-webkit-box-shadow: inset 0 -2.6em #47e58e;*/
/*	box-shadow: inset 0 -2.6em #47e58e;*/
/*}*/
/**/
/*.wpeo-button.button-bordered:hover {*/
/*	-webkit-box-shadow: inset 0 -2.6em #898de5;*/
/*	box-shadow: inset 0 -2.6em #898de5;*/
/*	color: #fff;*/
/*}*/
/**/
/*.wpeo-button .button-icon, .wpeo-button span {*/
/*	position: relative;*/
/*}*/
/**/
/*.wpeo-button .button-icon ~ span {*/
/*	margin-left: 10px;*/
/*}*/
/**/
/*.wpeo-button span ~ .button-icon {*/
/*	margin-left: 10px;*/
/*}*/
/**/
/*.wpeo-button.button-strong span {*/
/*	font-weight: 800;*/
/*}*/
/**/
/*.wpeo-button.button-uppercase span {*/
/*	text-transform: uppercase;*/
/*	font-size: 14px;*/
/*}*/
/**/
/*.wpeo-button[class*="button-square-"] {*/
/*	text-align: center;*/
/*	overflow: hidden;*/
/*	padding: 0;*/
/*}*/
/**/
/*.wpeo-button.button-square-30 {*/
/*	width: 30px;*/
/*	height: 30px;*/
/*	line-height: 24px;*/
/*}*/
/**/
/*.wpeo-button.button-square-30 .button-icon {*/
/*	font-size: 12px;*/
/*}*/
/**/
/*.wpeo-button.button-square-40 {*/
/*	width: 40px;*/
/*	height: 40px;*/
/*	line-height: 34px;*/
/*}*/
/**/
/*.wpeo-button.button-square-50 {*/
/*	width: 50px;*/
/*	height: 50px;*/
/*	line-height: 44px;*/
/*}*/
/**/
/*.wpeo-button.button-square-60 {*/
/*	width: 60px;*/
/*	height: 60px;*/
/*	line-height: 54px;*/
/*}*/
/**/
/*.wpeo-button.button-float-left {*/
/*	float: left;*/
/*}*/
/**/
/*.wpeo-button.button-float-right {*/
/*	float: right;*/
/*}*/
/**/
/*.wpeo-button.button-margin {*/
/*	margin: 1em 0;*/
/*}*/
/**/
/*.wpeo-button.button-radius-1 {*/
/*	border-radius: 2px;*/
/*}*/
/**/
/*.wpeo-button.button-radius-2 {*/
/*	border-radius: 4px;*/
/*}*/
/**/
/*.wpeo-button.button-radius-3 {*/
/*	border-radius: 6px;*/
/*}*/
/**/
/*.wpeo-button.button-rounded {*/
/*	border-radius: 50%;*/
/*}*/
/**/
/*.wpeo-button.button-disable {*/
/*	background: #ececec !important;*/
/*	border-color: #ececec !important;*/
/*	color: rgba(0, 0, 0, 0.4) !important;*/
/*	pointer-events: none;*/
/*}*/
/**/
/*.wpeo-button.button-disable:hover {*/
/*	-webkit-box-shadow: none !important;*/
/*	box-shadow: none !important;*/
/*}*/
/**/
/*.wpeo-button.button-disable.button-event {*/
/*	pointer-events: all;*/
/*}*/
/**/
/*.wpeo-button.button-size-small {*/
/*	font-size: 14px;*/
/*}*/
/**/
/*.wpeo-button.button-size-small.button-uppercase span {*/
/*	font-size: 12px;*/
/*}*/
/**/
/*.wpeo-button.button-size-large {*/
/*	font-size: 18px;*/
/*}*/
/**/
/*.wpeo-button.button-size-large.button-uppercase span {*/
/*	font-size: 16px;*/
/*}*/
/**/
/*/** Progress */*/
/*.wpeo-button.button-progress {*/
/*	position: relative;*/
/*}*/
/**/
/*.wpeo-button.button-progress::before {*/
/*	display: inline-block;*/
/*	-webkit-font-smoothing: antialiased;*/
/*	-moz-osx-font-smoothing: grayscale;*/
/*	display: inline-block;*/
/*	font-style: normal;*/
/*	font-variant: normal;*/
/*	font-weight: normal;*/
/*	line-height: 1;*/
/*	vertical-align: -.125em;*/
/*	font-family: 'Font Awesome 5 Free', 'Font Awesome 5 Pro';*/
/*	font-weight: 900;*/
/*	content: '';*/
/*	opacity: 0;*/
/*	-webkit-transform: translate(-50%, -90%);*/
/*	transform: translate(-50%, -90%);*/
/*	font-size: 16px;*/
/*	-webkit-animation-duration: 1s;*/
/*	animation-duration: 1s;*/
/*	-webkit-animation-fill-mode: both;*/
/*	animation-fill-mode: both;*/
/*	position: absolute;*/
/*	left: 50%;*/
/*	top: 50%;*/
/*	z-index: 99;*/
/*	-webkit-transition: all 0.2s ease-out;*/
/*	transition: all 0.2s ease-out;*/
/*}*/
/**/
/*.wpeo-button.button-progress .button-icon, .wpeo-button.button-progress span {*/
/*	-webkit-transition: all 0.2s ease-out;*/
/*	transition: all 0.2s ease-out;*/
/*	display: inline-block;*/
/*}*/
/**/
/*.wpeo-button.button-progress.button-success, .wpeo-button.button-progress.button-load, .wpeo-button.button-progress.button-error {*/
/*	pointer-events: none;*/
/*}*/
/**/
/*.wpeo-button.button-progress.button-success::before, .wpeo-button.button-progress.button-load::before, .wpeo-button.button-progress.button-error::before {*/
/*	opacity: 1;*/
/*	-webkit-transform: translate(-50%, -50%);*/
/*	transform: translate(-50%, -50%);*/
/*}*/
/**/
/*.wpeo-button.button-progress.button-success .button-icon, .wpeo-button.button-progress.button-success span, .wpeo-button.button-progress.button-load .button-icon, .wpeo-button.button-progress.button-load span, .wpeo-button.button-progress.button-error .button-icon, .wpeo-button.button-progress.button-error span {*/
/*	opacity: 0;*/
/*	-webkit-transform: translate(0, 40%);*/
/*	transform: translate(0, 40%);*/
/*}*/
/**/
/*.wpeo-button.button-progress.button-success {*/
/*	background: #47e58e;*/
/*	border-color: #47e58e;*/
/*	color: #fff;*/
/*}*/
/**/
/*.wpeo-button.button-progress.button-success:before {*/
/*	content: "\f00c";*/
/*	-webkit-animation-name: progressSuccess;*/
/*	animation-name: progressSuccess;*/
/*}*/
/**/
/*.wpeo-button.button-progress.button-error {*/
/*	background: #e05353;*/
/*	border-color: #e05353;*/
/*	color: #fff;*/
/*}*/
/**/
/*.wpeo-button.button-progress.button-error:before {*/
/*	content: "\f00d";*/
/*	-webkit-animation-name: progressError;*/
/*	animation-name: progressError;*/
/*}*/
/**/
/*.wpeo-button.button-progress.button-load {*/
/*	background: #ececec;*/
/*}*/
/**/
/*.wpeo-button.button-progress.button-load:before {*/
/*	opacity: 1;*/
/*	border: 3px solid #fff;*/
/*	border-top: 3px solid #898de5;*/
/*	border-radius: 50%;*/
/*	width: 20px;*/
/*	height: 20px;*/
/*	-webkit-animation: spin 1s ease-out infinite;*/
/*	animation: spin 1s ease-out infinite;*/
/*}*/
/**/
/*@-webkit-keyframes progressSuccess {*/
/*0, 20%, 40%, 60%, 80%, to {*/
/*						   -webkit-animation-timing-function: cubic-bezier(0.215, 0.61, 0.355, 1);*/
/*						   animation-timing-function: cubic-bezier(0.215, 0.61, 0.355, 1);*/
/*					   }*/
/*0% {*/
/*	-webkit-transform: translate(-50%, -50%) scale3d(1, 1, 1);*/
/*	transform: translate(-50%, -50%) scale3d(1, 1, 1);*/
/*}*/
/*20% {*/
/*	-webkit-transform: translate(-50%, -50%) scale3d(1.4, 1.4, 1.4);*/
/*	transform: translate(-50%, -50%) scale3d(1.4, 1.4, 1.4);*/
/*}*/
/*40% {*/
/*	-webkit-transform: translate(-50%, -50%) scale3d(0.9, 0.9, 0.9);*/
/*	transform: translate(-50%, -50%) scale3d(0.9, 0.9, 0.9);*/
/*}*/
/*60% {*/
/*	-webkit-transform: translate(-50%, -50%) scale3d(1.2, 1.2, 1.2);*/
/*	transform: translate(-50%, -50%) scale3d(1.2, 1.2, 1.2);*/
/*}*/
/*80% {*/
/*	-webkit-transform: translate(-50%, -50%) scale3d(0.97, 0.97, 0.97);*/
/*	transform: translate(-50%, -50%) scale3d(0.97, 0.97, 0.97);*/
/*}*/
/*to {*/
/*	-webkit-transform: translate(-50%, -50%) scale3d(1, 1, 1);*/
/*	transform: translate(-50%, -50%) scale3d(1, 1, 1);*/
/*}*/
/*}*/
/**/
/*@keyframes progressSuccess {*/
/*0, 20%, 40%, 60%, 80%, to {*/
/*						   -webkit-animation-timing-function: cubic-bezier(0.215, 0.61, 0.355, 1);*/
/*						   animation-timing-function: cubic-bezier(0.215, 0.61, 0.355, 1);*/
/*					   }*/
/*0% {*/
/*	-webkit-transform: translate(-50%, -50%) scale3d(1, 1, 1);*/
/*	transform: translate(-50%, -50%) scale3d(1, 1, 1);*/
/*}*/
/*20% {*/
/*	-webkit-transform: translate(-50%, -50%) scale3d(1.4, 1.4, 1.4);*/
/*	transform: translate(-50%, -50%) scale3d(1.4, 1.4, 1.4);*/
/*}*/
/*40% {*/
/*	-webkit-transform: translate(-50%, -50%) scale3d(0.9, 0.9, 0.9);*/
/*	transform: translate(-50%, -50%) scale3d(0.9, 0.9, 0.9);*/
/*}*/
/*60% {*/
/*	-webkit-transform: translate(-50%, -50%) scale3d(1.2, 1.2, 1.2);*/
/*	transform: translate(-50%, -50%) scale3d(1.2, 1.2, 1.2);*/
/*}*/
/*80% {*/
/*	-webkit-transform: translate(-50%, -50%) scale3d(0.97, 0.97, 0.97);*/
/*	transform: translate(-50%, -50%) scale3d(0.97, 0.97, 0.97);*/
/*}*/
/*to {*/
/*	-webkit-transform: translate(-50%, -50%) scale3d(1, 1, 1);*/
/*	transform: translate(-50%, -50%) scale3d(1, 1, 1);*/
/*}*/
/*}*/
/**/
/*@-webkit-keyframes progressError {*/
/*	from {*/
/*		-webkit-transform: translate(-50%, -50%);*/
/*		transform: translate(-50%, -50%);*/
/*	}*/
/*	15% {*/
/*		-webkit-transform: translate(-50%, -50%) translate3d(-25%, 0, 0) rotate3d(0, 0, 1, -5deg);*/
/*		transform: translate(-50%, -50%) translate3d(-25%, 0, 0) rotate3d(0, 0, 1, -5deg);*/
/*	}*/
/*	30% {*/
/*		-webkit-transform: translate(-50%, -50%) translate3d(20%, 0, 0) rotate3d(0, 0, 1, 3deg);*/
/*		transform: translate(-50%, -50%) translate3d(20%, 0, 0) rotate3d(0, 0, 1, 3deg);*/
/*	}*/
/*	45% {*/
/*		-webkit-transform: translate(-50%, -50%) translate3d(-15%, 0, 0) rotate3d(0, 0, 1, -3deg);*/
/*		transform: translate(-50%, -50%) translate3d(-15%, 0, 0) rotate3d(0, 0, 1, -3deg);*/
/*	}*/
/*	60% {*/
/*		-webkit-transform: translate(-50%, -50%) translate3d(10%, 0, 0) rotate3d(0, 0, 1, 2deg);*/
/*		transform: translate(-50%, -50%) translate3d(10%, 0, 0) rotate3d(0, 0, 1, 2deg);*/
/*	}*/
/*	75% {*/
/*		-webkit-transform: translate(-50%, -50%) translate3d(-5%, 0, 0) rotate3d(0, 0, 1, -1deg);*/
/*		transform: translate(-50%, -50%) translate3d(-5%, 0, 0) rotate3d(0, 0, 1, -1deg);*/
/*	}*/
/*	to {*/
/*		-webkit-transform: translate(-50%, -50%);*/
/*		transform: translate(-50%, -50%);*/
/*	}*/
/*}*/
/**/
/*@keyframes progressError {*/
/*	from {*/
/*		-webkit-transform: translate(-50%, -50%);*/
/*		transform: translate(-50%, -50%);*/
/*	}*/
/*	15% {*/
/*		-webkit-transform: translate(-50%, -50%) translate3d(-25%, 0, 0) rotate3d(0, 0, 1, -5deg);*/
/*		transform: translate(-50%, -50%) translate3d(-25%, 0, 0) rotate3d(0, 0, 1, -5deg);*/
/*	}*/
/*	30% {*/
/*		-webkit-transform: translate(-50%, -50%) translate3d(20%, 0, 0) rotate3d(0, 0, 1, 3deg);*/
/*		transform: translate(-50%, -50%) translate3d(20%, 0, 0) rotate3d(0, 0, 1, 3deg);*/
/*	}*/
/*	45% {*/
/*		-webkit-transform: translate(-50%, -50%) translate3d(-15%, 0, 0) rotate3d(0, 0, 1, -3deg);*/
/*		transform: translate(-50%, -50%) translate3d(-15%, 0, 0) rotate3d(0, 0, 1, -3deg);*/
/*	}*/
/*	60% {*/
/*		-webkit-transform: translate(-50%, -50%) translate3d(10%, 0, 0) rotate3d(0, 0, 1, 2deg);*/
/*		transform: translate(-50%, -50%) translate3d(10%, 0, 0) rotate3d(0, 0, 1, 2deg);*/
/*	}*/
/*	75% {*/
/*		-webkit-transform: translate(-50%, -50%) translate3d(-5%, 0, 0) rotate3d(0, 0, 1, -1deg);*/
/*		transform: translate(-50%, -50%) translate3d(-5%, 0, 0) rotate3d(0, 0, 1, -1deg);*/
/*	}*/
/*	to {*/
/*		-webkit-transform: translate(-50%, -50%);*/
/*		transform: translate(-50%, -50%);*/
/*	}*/
/*}*/
/**/
/*@-webkit-keyframes spin {*/
/*	0% {*/
/*		-webkit-transform: translate(-50%, -50%) rotate(0deg);*/
/*		transform: translate(-50%, -50%) rotate(0deg);*/
/*	}*/
/*	100% {*/
/*		-webkit-transform: translate(-50%, -50%) rotate(360deg);*/
/*		transform: translate(-50%, -50%) rotate(360deg);*/
/*	}*/
/*}*/
/**/
/*@keyframes spin {*/
/*	0% {*/
/*		-webkit-transform: translate(-50%, -50%) rotate(0deg);*/
/*		transform: translate(-50%, -50%) rotate(0deg);*/
/*	}*/
/*	100% {*/
/*		-webkit-transform: translate(-50%, -50%) rotate(360deg);*/
/*		transform: translate(-50%, -50%) rotate(360deg);*/
/*	}*/
/*}*/

/*--------------------------------------------------------------
	Module : Loader
	Version : 1.0.0

	.wpeo-loader => classe de base pour le module
	.loader-spin => icone de chargement
--------------------------------------------------------------*/
.wpeo-loader {
	-webkit-transition: all 0.2s ease-out;
	transition: all 0.2s ease-out;
	pointer-events: none;
	opacity: 0.5;
	position: relative;
}

.wpeo-loader .loader-spin {
	position: absolute;
	border: 3px solid #a7a7a7;
	border-top: 3px solid #1d2285;
	border-radius: 50%;
	width: 20px;
	height: 20px;
	z-index: 99;
	left: 50%;
	top: 50%;
	margin: 0 !important;
	padding: 0 !important;
	-webkit-animation: loader-spin 1s ease-out infinite;
	animation: loader-spin 1s ease-out infinite;
}

@-webkit-keyframes loader-spin {
	0% {
		-webkit-transform: translate(-50%, -50%) rotate(0deg);
		transform: translate(-50%, -50%) rotate(0deg);
	}
	100% {
		-webkit-transform: translate(-50%, -50%) rotate(360deg);
		transform: translate(-50%, -50%) rotate(360deg);
	}
}

@keyframes loader-spin {
	0% {
		-webkit-transform: translate(-50%, -50%) rotate(0deg);
		transform: translate(-50%, -50%) rotate(0deg);
	}
	100% {
		-webkit-transform: translate(-50%, -50%) rotate(360deg);
		transform: translate(-50%, -50%) rotate(360deg);
	}
}

/*--------------------------------------------------------------
	Module : Notice
	Version : 1.0.0

	.wpeo-notice -> classe de base du mobule
--------------------------------------------------------------*/
/* General */
.wpeo-notice {
	position: relative;
	font-size: 1em;
	padding: 1em;
	overflow: hidden;
	border-radius: 3px;
	border: solid #eee 1px;
	margin: 1em 0;
	display: -webkit-box;
	display: -ms-flexbox;
	display: flex;
	-webkit-transition: all 0.2s ease-out;
	transition: all 0.2s ease-out;
	-webkit-box-align: center;
	-ms-flex-align: center;
	align-items: center;
}

.wpeo-notice::before {
	display: block;
	-webkit-font-smoothing: antialiased;
	-moz-osx-font-smoothing: grayscale;
	display: inline-block;
	font-style: normal;
	font-variant: normal;
	font-weight: normal;
	line-height: 1;
	vertical-align: -.125em;
	font-family: 'Font Awesome 5 Free', 'Font Awesome 5 Pro';
	font-weight: 900;
	font-weight: 900;
	font-size: 24px;
}

.wpeo-notice .notice-content {
	width: 100%;
	padding: 0 1em;
	color: rgba(0, 0, 0, 0.6);
}

.wpeo-notice .notice-title {
	font-size: 20px;
	font-weight: 600;
	color: rgba(0, 0, 0, 0.9);
}

.wpeo-notice .notice-subtitle {
	font-size: 14px;
}

.wpeo-notice .notice-close {
	color: rgba(0, 0, 0, 0.3);
	-webkit-transition: all 0.2s ease-out;
	transition: all 0.2s ease-out;
}

.wpeo-notice .notice-close:hover {
	color: #898de5;
	cursor: pointer;
}

.wpeo-notice ul {
	padding: 0 0 0 1.4em;
	margin: 0.4em 0;
}

/** Status */
/** Info */
.wpeo-notice.notice-info {
	border-left: solid #0d8aff 6px;
	color: #0d8aff;
	background: rgba(13, 138, 255, 0.05);
}

.wpeo-notice.notice-info::before {
	content: "\f05a";
}

.wpeo-notice.notice-info .notice-title, .wpeo-notice.notice-info .notice-subtitle, .wpeo-notice.notice-info a {
	color: #0d8aff;
}

/** Error */
.wpeo-notice.notice-error {
	border-left: solid #e05353 6px;
	color: #e05353;
	background: rgba(224, 83, 83, 0.05);
}

.wpeo-notice.notice-error::before {
	content: "\f057";
}

.wpeo-notice.notice-error .notice-title, .wpeo-notice.notice-error .notice-subtitle, .wpeo-notice.notice-error a {
	color: #e05353;
}

/** Warning */
.wpeo-notice.notice-warning {
	border-left: solid #e9ad4f 6px;
	color: #e9ad4f;
	background: rgba(233, 173, 79, 0.05);
}

.wpeo-notice.notice-warning::before {
	content: "\f071";
}

.wpeo-notice.notice-warning .notice-title, .wpeo-notice.notice-warning .notice-subtitle, .wpeo-notice.notice-warning a {
	color: #e9ad4f;
}

/** Success */
.wpeo-notice.notice-success {
	border-left: solid #47e58e 6px;
	color: #47e58e;
	background: rgba(71, 229, 142, 0.05);
}

.wpeo-notice.notice-success::before {
	content: "\f058";
}

.wpeo-notice.notice-success .notice-title, .wpeo-notice.notice-success .notice-subtitle, .wpeo-notice.notice-success a {
	color: #47e58e;
}

/*--------------------------------------------------------------
	Module : Notification
	Version : 1.0.0

	.wpeo-notification -> classe de base du mobule
	.notification-active -> lance l'apparition de la notification
--------------------------------------------------------------*/
/* General */
.wpeo-notification {
	position: fixed;
	background: rgba(255, 255, 255, 0.6);
	-webkit-transition: all 0.2s ease-out;
	transition: all 0.2s ease-out;
	z-index: 900;
	padding: 1em;
	bottom: 3em;
	right: 1em;
	max-width: 600px;
	-webkit-box-shadow: 0 0 14px 1px rgba(0, 0, 0, 0.2);
	box-shadow: 0 0 14px 1px rgba(0, 0, 0, 0.2);
	display: -webkit-box;
	display: -ms-flexbox;
	display: flex;
	opacity: 0;
	pointer-events: none;
}

.wpeo-notification:hover {
	background: white;
	-webkit-box-shadow: 0 0 14px 1px rgba(0, 0, 0, 0.4);
	box-shadow: 0 0 14px 1px rgba(0, 0, 0, 0.4);
	cursor: pointer;
}

.wpeo-notification.notification-active {
	-webkit-animation: notification 0.8s ease-out;
	animation: notification 0.8s ease-out;
	-webkit-animation-fill-mode: forwards;
	animation-fill-mode: forwards;
	opacity: 1;
	pointer-events: all;
}

/* Content */
.wpeo-notification {
	/* Thumbnail */
	/* Icon */
	/* Title */
	/* Close button */
}

.wpeo-notification > * {
	margin: auto 0.2em;
}

.wpeo-notification .notification-thumbnail {
	width: 40px;
	height: 40px;
	background: rgba(0, 0, 0, 0.1);
	border-radius: 50%;
	display: inline-block;
	overflow: hidden;
	margin-right: 0.4em;
}

.wpeo-notification .notification-thumbnail img {
	width: 100%;
	height: auto;
}

.wpeo-notification .notification-icon {
	margin-right: 0.4em;
}

.wpeo-notification .notification-title {
	font-size: 14px;
	color: rgba(0, 0, 0, 0.7);
}

.wpeo-notification .notification-title a {
	color: #898de5;
	text-decoration: none;
}

.wpeo-notification .notification-title a:hover {
	text-decoration: underline;
}

.wpeo-notification .notification-close {
	padding-left: 0.4em;
	color: rgba(0, 0, 0, 0.2);
	-webkit-transition: all 0.2s ease-out;
	transition: all 0.2s ease-out;
	margin-left: auto;
}

.wpeo-notification .notification-close:hover {
	color: #898de5;
}

/** Couleur */
.wpeo-notification .notification-icon {
	color: rgba(0, 0, 0, 0.3);
}

.wpeo-notification.notification-green {
	border-left: 4px solid #47e58e;
}

.wpeo-notification.notification-green .notification-icon {
	color: #47e58e;
}

.wpeo-notification.notification-orange {
	border-left: 4px solid #e9ad4f;
}

.wpeo-notification.notification-orange .notification-icon {
	color: #e9ad4f;
}

.wpeo-notification.notification-red {
	border-left: 4px solid #e05353;
}

.wpeo-notification.notification-red .notification-icon {
	color: #e05353;
}

.wpeo-notification.notification-blue {
	border-left: 4px solid #0d8aff;
}

.wpeo-notification.notification-blue .notification-icon {
	color: #0d8aff;
}

@-webkit-keyframes notification {
	from, 60%, 75%, 90%, to {
		-webkit-animation-timing-function: cubic-bezier(0.215, 0.61, 0.355, 1);
		animation-timing-function: cubic-bezier(0.215, 0.61, 0.355, 1);
	}
	0% {
		opacity: 0;
		-webkit-transform: translate3d(0, -40px, 0);
		transform: translate3d(0, -40px, 0);
	}
	60% {
		opacity: 1;
		-webkit-transform: translate3d(0, 10px, 0);
		transform: translate3d(0, 10px, 0);
	}
	75% {
		-webkit-transform: translate3d(0, -10px, 0);
		transform: translate3d(0, -10px, 0);
	}
	90% {
		-webkit-transform: translate3d(0, 5px, 0);
		transform: translate3d(0, 5px, 0);
	}
	to {
		-webkit-transform: none;
		transform: none;
	}
}

@keyframes notification {
	from, 60%, 75%, 90%, to {
		-webkit-animation-timing-function: cubic-bezier(0.215, 0.61, 0.355, 1);
		animation-timing-function: cubic-bezier(0.215, 0.61, 0.355, 1);
	}
	0% {
		opacity: 0;
		-webkit-transform: translate3d(0, -40px, 0);
		transform: translate3d(0, -40px, 0);
	}
	60% {
		opacity: 1;
		-webkit-transform: translate3d(0, 10px, 0);
		transform: translate3d(0, 10px, 0);
	}
	75% {
		-webkit-transform: translate3d(0, -10px, 0);
		transform: translate3d(0, -10px, 0);
	}
	90% {
		-webkit-transform: translate3d(0, 5px, 0);
		transform: translate3d(0, 5px, 0);
	}
	to {
		-webkit-transform: none;
		transform: none;
	}
}

/*--------------------------------------------------------------
	Module : Modal
	Version : 1.0.0

	.wpeo-modal -> classe de base du mobule
	.modalactive -> lance l'apparition de la modal
	.no-modal-close -> désactive l'icone fermeture
--------------------------------------------------------------*/
/*.wpeo-modal {*/
/*	position: fixed;*/
/*	top: 0;*/
/*	left: 0;*/
/*	width: 100%;*/
/*	height: 100%;*/
/*	z-index: 99998;*/
/*	background: rgba(39, 42, 53, 0.9);*/
/*	opacity: 0;*/
/*	pointer-events: none;*/
/*	-webkit-transition: all 0.2s ease-out;*/
/*	transition: all 0.2s ease-out;*/
/*}*/
/**/
/*.wpeo-modal.modal-active {*/
/*	opacity: 1;*/
/*	pointer-events: auto;*/
/*}*/
/**/
/*.wpeo-modal.modal-active .modal-container {*/
/*	-webkit-transform: translate(-50%, -50%);*/
/*	transform: translate(-50%, -50%);*/
/*}*/
/**/
/*.wpeo-modal.modal-force-display .modal-close {*/
/*	display: none;*/
/*}*/
/**/
/*.wpeo-modal .modal-container {*/
/*	position: absolute;*/
/*	-webkit-transition: all 0.2s ease-out;*/
/*	transition: all 0.2s ease-out;*/
/*	width: 100%;*/
/*	max-width: 860px;*/
/*	height: 100%;*/
/*	max-height: 560px;*/
/*	background: #fff;*/
/*	padding: 2em;*/
/*	margin: auto;*/
/*	top: 50%;*/
/*	left: 50%;*/
/*	-webkit-transform: translate(-50%, -60%);*/
/*	transform: translate(-50%, -60%);*/
/*}*/
/**/
/*@media (max-width: 480px) {*/
/*	.wpeo-modal .modal-container {*/
/*		padding: 1em;*/
/*	}*/
/*}*/
/**/
/*.wpeo-modal .modal-container .modal-header {*/
/*	height: 10%;*/
/*	display: -webkit-box;*/
/*	display: -ms-flexbox;*/
/*	display: flex;*/
/*}*/
/**/
/*.wpeo-modal .modal-container .modal-content {*/
/*	height: 78%;*/
/*}*/
/**/
/*.wpeo-modal .modal-container .modal-footer {*/
/*	height: 12%;*/
/*}*/
/**/
/*.wpeo-modal .modal-container .modal-header .modal-title, .wpeo-modal .modal-container .modal-header .modal-close {*/
/*	margin: auto 0;*/
/*}*/
/**/
/*.wpeo-modal .modal-container .modal-header .modal-title {*/
/*	text-transform: uppercase;*/
/*	font-size: 18px;*/
/*	white-space: normal;*/
/*}*/
/**/
/*@media (max-width: 770px) {*/
/*	.wpeo-modal .modal-container .modal-header .modal-title {*/
/*		font-size: 16px;*/
/*	}*/
/*}*/
/**/
/*@media (max-width: 480px) {*/
/*	.wpeo-modal .modal-container .modal-header .modal-title {*/
/*		font-size: 14px;*/
/*	}*/
/*}*/
/**/
/*.wpeo-modal .modal-container .modal-header .modal-close {*/
/*	margin-left: auto;*/
/*	color: rgba(0, 0, 0, 0.3);*/
/*	padding: 4px;*/
/*	-webkit-transition: all 0.2s ease-out;*/
/*	transition: all 0.2s ease-out;*/
/*}*/
/**/
/*.wpeo-modal .modal-container .modal-header .modal-close:hover {*/
/*	cursor: pointer;*/
/*	color: #898de5;*/
/*}*/
/**/
/*.wpeo-modal .modal-container .modal-content {*/
/*	overflow-y: auto;*/
/*	font-size: 14px;*/
/*}*/
/**/
/*@media (max-width: 480px) {*/
/*	.wpeo-modal .modal-container .modal-content {*/
/*		font-size: 12px;*/
/*	}*/
/*}*/
/**/
/*.wpeo-modal .modal-container .modal-footer {*/
/*	text-align: right;*/
/*	padding-top: 1em;*/
/*}*/
/**/
/*.wpeo-modal .modal-container .modal-footer.left {*/
/*	text-align: left;*/
/*}*/
/**/
/*.wpeo-modal .modal-container .modal-footer.center {*/
/*	text-align: center;*/
/*}*/

/*--------------------------------------------------------------
	Module : Pagination
	Version : 1.0.0

	.wpeo-pagination -> classe de base du mobule
--------------------------------------------------------------*/
.wpeo-pagination {
	display: -webkit-box;
	display: -ms-flexbox;
	display: flex;
	-webkit-box-orient: horizontal;
	-webkit-box-direction: normal;
	-ms-flex-flow: row wrap;
	flex-flow: row wrap;
	-webkit-box-flex: 0;
	-ms-flex: 0 1 auto;
	flex: 0 1 auto;
	margin: 0;
	padding: 0;
}

.wpeo-pagination .pagination-element {
	margin: 0;
	list-style-type: none;
	padding: 0 0.2em;
}

.wpeo-pagination .pagination-element a {
	padding: 0.2em 0.8em;
	display: inline-block;
	background: #fff;
	-webkit-transition: all 0.2s ease-out;
	transition: all 0.2s ease-out;
	border-radius: 4px;
	text-decoration: none;
	color: rgba(0, 0, 0, 0.8);
	font-size: 12px;
}

.wpeo-pagination .pagination-element a:hover {
	background: rgba(0, 0, 0, 0.1);
}

.wpeo-pagination .pagination-element .pagination-icon {
	font-size: 12px;
}

/* Pagination active */
.wpeo-pagination .pagination-element.pagination-current a {
	background: #898de5;
	color: #fff;
}

.wpeo-pagination .pagination-element.pagination-current a:hover {
	background: #898de5;
}

/* Prev & Next */
.wpeo-pagination .pagination-element.pagination-prev, .wpeo-pagination .pagination-element.pagination-next {
	font-weight: 700;
}

.wpeo-pagination .pagination-element.pagination-prev a, .wpeo-pagination .pagination-element.pagination-next a {
	background: rgba(0, 0, 0, 0.05);
}

.wpeo-pagination .pagination-element.pagination-prev a:hover, .wpeo-pagination .pagination-element.pagination-next a:hover {
	background: rgba(0, 0, 0, 0.15);
}

.wpeo-pagination .pagination-element.pagination-prev .pagination-icon {
	margin-right: 0.4em;
}

.wpeo-pagination .pagination-element.pagination-next .pagination-icon {
	margin-left: 0.4em;
}

/*--------------------------------------------------------------
	Module : Popover
	Version : 1.0.0

	.wpeo-popover -> classe de base du mobule
	.popover-primary -> popover sur fond rouge
	.popover-light -> popover sur fond rouge
	.popover-red -> popover sur fond rouge
--------------------------------------------------------------*/
.wpeo-popover {
	display: block;
	position: absolute;
	bottom: 0;
	left: 0;
	opacity: 0;
	pointer-events: none;
	z-index: 99999;
	white-space: nowrap;
	background: #2b2b2b;
	color: #fff;
	border-radius: 6px;
	font-size: 0.8rem;
	padding: 0 1em;
	height: 2.2em;
	line-height: 2.2em;
}

.wpeo-popover::before {
	display: block;
	content: '';
	width: 0;
	height: 0;
	border-style: solid;
	position: absolute;
}

.wpeo-popover:focus {
	outline: none;
}

/* Couleurs */
.wpeo-popover.popover-dark {
	background: #2b2b2b;
}

.wpeo-popover.popover-dark.tooltip-top::before {
	border-color: #2b2b2b transparent transparent transparent;
}

.wpeo-popover.popover-dark.tooltip-right::before {
	border-color: transparent #2b2b2b transparent transparent;
}

.wpeo-popover.popover-dark.tooltip-bottom::before {
	border-color: transparent transparent #2b2b2b transparent;
}

.wpeo-popover.popover-dark.tooltip-left::before {
	border-color: transparent transparent transparent #2b2b2b;
}

.wpeo-popover.popover-primary {
	background: #898de5;
}

.wpeo-popover.popover-primary.tooltip-top::before {
	border-color: #898de5 transparent transparent transparent;
}

.wpeo-popover.popover-primary.tooltip-right::before {
	border-color: transparent #898de5 transparent transparent;
}

.wpeo-popover.popover-primary.tooltip-bottom::before {
	border-color: transparent transparent #898de5 transparent;
}

.wpeo-popover.popover-primary.tooltip-left::before {
	border-color: transparent transparent transparent #898de5;
}

.wpeo-popover.popover-light {
	background: #ececec;
	color: rgba(0, 0, 0, 0.6);
}

.wpeo-popover.popover-light.tooltip-top::before {
	border-color: #ececec transparent transparent transparent;
}

.wpeo-popover.popover-light.tooltip-right::before {
	border-color: transparent #ececec transparent transparent;
}

.wpeo-popover.popover-light.tooltip-bottom::before {
	border-color: transparent transparent #ececec transparent;
}

.wpeo-popover.popover-light.tooltip-left::before {
	border-color: transparent transparent transparent #ececec;
}

.wpeo-popover.popover-red {
	background: #e05353;
}

.wpeo-popover.popover-red.tooltip-top::before {
	border-color: #e05353 transparent transparent transparent;
}

.wpeo-popover.popover-red.tooltip-right::before {
	border-color: transparent #e05353 transparent transparent;
}

.wpeo-popover.popover-red.tooltip-bottom::before {
	border-color: transparent transparent #e05353 transparent;
}

.wpeo-popover.popover-red.tooltip-left::before {
	border-color: transparent transparent transparent #e05353;
}

/* Position de la fleche */
.wpeo-popover.popover-top::before {
	border-width: 6px 6px 0 6px;
	border-color: #2b2b2b transparent transparent transparent;
	bottom: -6px;
	left: 50%;
	-webkit-transform: translateX(-50%);
	transform: translateX(-50%);
}

.wpeo-popover.popover-right::before {
	border-width: 6px 6px 6px 0;
	border-color: transparent #2b2b2b transparent transparent;
	top: 50%;
	-webkit-transform: translateY(-50%);
	transform: translateY(-50%);
	left: -6px;
}

.wpeo-popover.popover-bottom::before {
	border-width: 0 6px 6px 6px;
	border-color: transparent transparent #2b2b2b transparent;
	top: -6px;
	left: 50%;
	-webkit-transform: translateX(-50%);
	transform: translateX(-50%);
}

.wpeo-popover.popover-left::before {
	border-width: 6px 0 6px 6px;
	border-color: transparent transparent transparent #2b2b2b;
	top: 50%;
	-webkit-transform: translateY(-50%);
	transform: translateY(-50%);
	right: -6px;
}

/*--------------------------------------------------------------
	Module : Tab
	Version : 1.0.0

	.wpeo-tab -> classe de base du mobule
--------------------------------------------------------------*/
/* Liste */
.wpeo-tab .tab-list {
	margin: 0;
	padding: 0;
	display: -webkit-box;
	display: -ms-flexbox;
	display: flex;
	-webkit-box-orient: horizontal;
	-webkit-box-direction: normal;
	-ms-flex-flow: row wrap;
	flex-flow: row wrap;
	-webkit-box-flex: 0;
	-ms-flex: 0 1 auto;
	flex: 0 1 auto;
}

.wpeo-tab .tab-list .tab-element {
	list-style-type: none;
	padding: 1.6em 2.4em;
	margin: 0;
	text-transform: uppercase;
	font-size: 12px !important;
	position: relative;
	background: rgba(0, 0, 0, 0.1);
	color: rgba(0, 0, 0, 0.6);
	-webkit-transition: all 0.2s ease-out;
	transition: all 0.2s ease-out;
	/* Active */
	/* Disabled */
	/* Icon */
}

.wpeo-tab .tab-list .tab-element::before {
	display: block;
	content: '';
	position: absolute;
	top: 0;
	left: 0;
	width: 0px;
	height: 2px;
	background: #898de5;
	-webkit-transition: all 0.2s ease-out;
	transition: all 0.2s ease-out;
}

.wpeo-tab .tab-list .tab-element:hover {
	cursor: pointer;
}

.wpeo-tab .tab-list .tab-element:hover:not(.tab-active) {
	background: rgba(0, 0, 0, 0.2);
}

.wpeo-tab .tab-list .tab-element > a {
	color: rgba(0, 0, 0, 0.6);
}

.wpeo-tab .tab-list .tab-element.tab-active {
	background: #fff;
	color: #898de5;
}

.wpeo-tab .tab-list .tab-element.tab-active > a {
	color: #898de5;
}

.wpeo-tab .tab-list .tab-element.tab-active::before {
	width: 100%;
}

.wpeo-tab .tab-list .tab-element.tab-disabled {
	color: rgba(0, 0, 0, 0.2);
	pointer-events: none;
}

.wpeo-tab .tab-list .tab-element.tab-disabled > a {
	color: rgba(0, 0, 0, 0.4);
}

.wpeo-tab .tab-list .tab-element .tab-icon {
	display: block;
	text-align: center;
	font-size: 20px;
	margin-bottom: 0.4em;
}

/* Content */
.wpeo-tab .tab-container {
	position: relative;
}

.wpeo-tab .tab-container .tab-content {
	display: block;
	width: 100%;
	padding: 2em;
	position: absolute;
	top: 0;
	left: 0;
	width: 100%;
	opacity: 0;
	pointer-events: none;
	background: #fff;
	-webkit-transition: all 0.2s ease-out;
	transition: all 0.2s ease-out;
	z-index: 10;
}

.wpeo-tab .tab-container .tab-content.tab-active {
	opacity: 1;
	pointer-events: all;
	position: static;
}

/* Dropdown Compatibility */
.wpeo-tab .tab-list .tab-element.wpeo-dropdown {
	padding: 0;
}

.wpeo-tab .tab-list .tab-element.wpeo-dropdown .dropdown-toggle {
	padding: 1.6em 2.4em;
}

/* Liste des effets */
/*--------------------------------------------------------------
	Templates
	Version : 1.0.0

--------------------------------------------------------------*/
/* Vertical */
.wpeo-tab.tab-vertical {
	display: -webkit-box;
	display: -ms-flexbox;
	display: flex;
	-webkit-box-orient: horizontal;
	-webkit-box-direction: normal;
	-ms-flex-flow: row wrap;
	flex-flow: row wrap;
	-webkit-box-flex: 0;
	-ms-flex: 0 1 auto;
	flex: 0 1 auto;
	-ms-flex-wrap: nowrap;
	flex-wrap: nowrap;
}

.wpeo-tab.tab-vertical .tab-list {
	-webkit-box-orient: vertical;
	-webkit-box-direction: normal;
	-ms-flex-direction: column;
	flex-direction: column;
	min-width: 180px;
}

.wpeo-tab.tab-vertical .tab-list .tab-element {
	/* Active */
	/* Icon */
}

.wpeo-tab.tab-vertical .tab-list .tab-element::before {
	width: 2px;
	height: 0;
}

.wpeo-tab.tab-vertical .tab-list .tab-element.tab-active {
	color: #898de5;
}

.wpeo-tab.tab-vertical .tab-list .tab-element.tab-active a {
	color: #898de5;
}

.wpeo-tab.tab-vertical .tab-list .tab-element.tab-active::before {
	height: 100%;
}

.wpeo-tab.tab-vertical .tab-list .tab-element .tab-icon {
	display: inline-block;
	margin: 0 0.4em 0 0;
	font-size: 14px;
	text-align: left;
}

.wpeo-tab.tab-vertical .tab-container {
	width: 100%;
}

/*--------------------------------------------------------------
	Effets
	Version : 1.0.0

--------------------------------------------------------------*/
/* FADE VERTICAL */
.wpeo-tab.tab-fade-vertical .tab-container .tab-content {
	-webkit-transform: translateY(-40px);
	transform: translateY(-40px);
	-webkit-animation: 0.3s linear 0s disappear_tab;
	animation: 0.3s linear 0s disappear_tab;
}

.wpeo-tab.tab-fade-vertical .tab-container .tab-content.tab-active {
	-webkit-transform: translateY(0);
	transform: translateY(0);
	-webkit-animation: 0.2s ease-out 0s appear_tab;
	animation: 0.2s ease-out 0s appear_tab;
}

@-webkit-keyframes disappear_tab {
	from {
		opacity: 1;
		-webkit-transform: translateY(0px);
		transform: translateY(0px);
	}
	to {
		opacity: 0;
		-webkit-transform: translateY(40px);
		transform: translateY(40px);
	}
}

@keyframes disappear_tab {
	from {
		opacity: 1;
		-webkit-transform: translateY(0px);
		transform: translateY(0px);
	}
	to {
		opacity: 0;
		-webkit-transform: translateY(40px);
		transform: translateY(40px);
	}
}

@-webkit-keyframes appear_tab {
	from {
		opacity: 0;
		-webkit-transform: translateY(-40px);
		transform: translateY(-40px);
	}
	to {
		opacity: 1;
		-webkit-transform: translateY(0);
		transform: translateY(0);
	}
}

@keyframes appear_tab {
	from {
		opacity: 0;
		-webkit-transform: translateY(-40px);
		transform: translateY(-40px);
	}
	to {
		opacity: 1;
		-webkit-transform: translateY(0);
		transform: translateY(0);
	}
}

/* FADE HORIZONTAL */
.wpeo-tab.tab-fade-horizontal .tab-container .tab-content {
	-webkit-transform: translateX(-40px);
	transform: translateX(-40px);
	-webkit-animation: 0.4s linear 0s disappear_tab_2;
	animation: 0.4s linear 0s disappear_tab_2;
}

.wpeo-tab.tab-fade-horizontal .tab-container .tab-content.tab-active {
	-webkit-transform: translateX(0);
	transform: translateX(0);
	-webkit-animation: 0.2s ease-out 0s appear_tab_2;
	animation: 0.2s ease-out 0s appear_tab_2;
}

@-webkit-keyframes disappear_tab_2 {
	from {
		opacity: 1;
		-webkit-transform: translateX(0px);
		transform: translateX(0px);
	}
	to {
		opacity: 0;
		-webkit-transform: translateX(40px);
		transform: translateX(40px);
	}
}

@keyframes disappear_tab_2 {
	from {
		opacity: 1;
		-webkit-transform: translateX(0px);
		transform: translateX(0px);
	}
	to {
		opacity: 0;
		-webkit-transform: translateX(40px);
		transform: translateX(40px);
	}
}

@-webkit-keyframes appear_tab_2 {
	from {
		opacity: 0;
		-webkit-transform: translateX(-40px);
		transform: translateX(-40px);
	}
	to {
		opacity: 1;
		-webkit-transform: translateX(0);
		transform: translateX(0);
	}
}

@keyframes appear_tab_2 {
	from {
		opacity: 0;
		-webkit-transform: translateX(-40px);
		transform: translateX(-40px);
	}
	to {
		opacity: 1;
		-webkit-transform: translateX(0);
		transform: translateX(0);
	}
}

/*--------------------------------------------------------------
	Module : Table
	Version : 1.0.0

	.wpeo-table -> classe de base du mobule
--------------------------------------------------------------*/
/*--------------------------------------------------------------
	Table Standard
--------------------------------------------------------------*/
.wpeo-table {
	width: 100%;
	border: 1px solid rgba(0, 0, 0, 0.1);
	font-size: 14px;
	border-collapse: collapse;
}

.wpeo-table td, .wpeo-table th, .wpeo-table tr {
	text-align: left;
	vertical-align: middle;
}

.wpeo-table th {
	font-weight: 700;
}

.wpeo-table th, .wpeo-table td {
	padding: 0.8em 0.6em;
}

@media (max-width: 770px) {
	.wpeo-table th, .wpeo-table td {
		padding: 0.4em;
	}
}

/* Header, footer */
.wpeo-table > thead, .wpeo-table > tfoot {
	background: #898de5;
	color: #fff;
}

/* Body */
.wpeo-table > tbody tr:nth-child(odd) {
	background: #fff;
}

.wpeo-table > tbody tr:nth-child(even) {
	background: rgba(0, 0, 0, 0.05);
}

/* Responsive */
@media (max-width: 480px) {
	.wpeo-table > thead {
		display: none;
	}
	.wpeo-table > tbody tr {
		border-bottom: 1px solid rgba(0, 0, 0, 0.1);
	}
	.wpeo-table > tbody tr:nth-child(even) {
		background: #fff;
	}
	.wpeo-table > tbody tr:last-child {
		border-bottom: 0;
	}
	.wpeo-table > tbody td, .wpeo-table > tbody th {
		display: block;
		width: 100%;
		font-size: 14px;
		padding: 0.4em 0.6em;
	}
	.wpeo-table > tbody td:before, .wpeo-table > tbody th:before {
		content: attr(data-title);
		display: inline-block;
		color: #898de5;
		width: 100%;
		max-width: 160px;
	}
}

/*--------------------------------------------------------------
	Table Flex
--------------------------------------------------------------*/
.wpeo-table.table-flex {
	display: -webkit-box;
	display: -ms-flexbox;
	display: flex;
	-webkit-box-orient: vertical;
	-webkit-box-direction: normal;
	-ms-flex-direction: column;
	flex-direction: column;
	font-size: 14px;
}

/** Ligne */
.wpeo-table.table-flex .table-row {
	display: -webkit-box;
	display: -ms-flexbox;
	display: flex;
	-ms-flex-wrap: nowrap;
	flex-wrap: nowrap;
	-webkit-box-orient: horizontal;
	-webkit-box-direction: normal;
	-ms-flex-direction: row;
	flex-direction: row;
}

.wpeo-table.table-flex .table-row:not(.table-header):nth-of-type(odd) {
	background: rgba(0, 0, 0, 0.05);
}

/** Ligne entete */
.wpeo-table.table-flex .table-row.table-header {
	background: #898de5;
}

.wpeo-table.table-flex .table-row.table-header .table-cell {
	font-weight: 700;
	color: #fff;
}

/** Cellule */
.wpeo-table.table-flex .table-cell {
	margin: auto 0;
	width: 100%;
	padding: 0.8em 0.6em;
	text-align: center;
}

@media (max-width: 770px) {
	.wpeo-table.table-flex .table-cell {
		padding: 0.4em;
	}
}

/** Taille générale des cellules */
.wpeo-table.table-flex.table-1 .table-cell {
	width: 100%;
}

.wpeo-table.table-flex.table-2 .table-cell {
	width: 50%;
}

.wpeo-table.table-flex.table-3 .table-cell {
	width: 33.33333%;
}

.wpeo-table.table-flex.table-4 .table-cell {
	width: 25%;
}

.wpeo-table.table-flex.table-5 .table-cell {
	width: 20%;
}

.wpeo-table.table-flex.table-6 .table-cell {
	width: 16.66667%;
}

.wpeo-table.table-flex.table-7 .table-cell {
	width: 14.28571%;
}

.wpeo-table.table-flex.table-8 .table-cell {
	width: 12.5%;
}

.wpeo-table.table-flex.table-9 .table-cell {
	width: 11.11111%;
}

.wpeo-table.table-flex.table-10 .table-cell {
	width: 10%;
}

/** Taille spécifiques des cellules */
.wpeo-table.table-flex .table-cell.table-25 {
	max-width: 25px;
	min-width: 25px;
}

.wpeo-table.table-flex .table-cell.table-50 {
	max-width: 50px;
	min-width: 50px;
}

.wpeo-table.table-flex .table-cell.table-75 {
	max-width: 75px;
	min-width: 75px;
}

.wpeo-table.table-flex .table-cell.table-100 {
	max-width: 100px;
	min-width: 100px;
}

.wpeo-table.table-flex .table-cell.table-125 {
	max-width: 125px;
	min-width: 125px;
}

.wpeo-table.table-flex .table-cell.table-150 {
	max-width: 150px;
	min-width: 150px;
}

.wpeo-table.table-flex .table-cell.table-175 {
	max-width: 175px;
	min-width: 175px;
}

.wpeo-table.table-flex .table-cell.table-200 {
	max-width: 200px;
	min-width: 200px;
}

.wpeo-table.table-flex .table-cell.table-225 {
	max-width: 225px;
	min-width: 225px;
}

.wpeo-table.table-flex .table-cell.table-250 {
	max-width: 250px;
	min-width: 250px;
}

.wpeo-table.table-flex .table-cell.table-275 {
	max-width: 275px;
	min-width: 275px;
}

.wpeo-table.table-flex .table-cell.table-300 {
	max-width: 300px;
	min-width: 300px;
}

.wpeo-table.table-flex .table-cell.table-325 {
	max-width: 325px;
	min-width: 325px;
}

.wpeo-table.table-flex .table-cell.table-350 {
	max-width: 350px;
	min-width: 350px;
}

.wpeo-table.table-flex .table-cell.table-375 {
	max-width: 375px;
	min-width: 375px;
}

.wpeo-table.table-flex .table-cell.table-400 {
	max-width: 400px;
	min-width: 400px;
}

.wpeo-table.table-flex .table-cell.table-425 {
	max-width: 425px;
	min-width: 425px;
}

.wpeo-table.table-flex .table-cell.table-450 {
	max-width: 450px;
	min-width: 450px;
}

.wpeo-table.table-flex .table-cell.table-475 {
	max-width: 475px;
	min-width: 475px;
}

.wpeo-table.table-flex .table-cell.table-500 {
	max-width: 500px;
	min-width: 500px;
}

.wpeo-table.table-flex .table-cell.table-full {
	width: 100%;
}

/** Différentes classes */
.wpeo-table.table-flex .table-cell.table-end {
	text-align: right;
	margin-left: auto;
}

.wpeo-table.table-flex .table-cell.table-padding-0 {
	padding: 0;
}

/** Responsive mobile */
@media (max-width: 480px) {
	.wpeo-table.table-flex .table-row {
		-webkit-box-orient: vertical;
		-webkit-box-direction: normal;
		-ms-flex-direction: column;
		flex-direction: column;
	}
	.wpeo-table.table-flex .table-cell {
		width: 100%;
	}
}

/*--------------------------------------------------------------
	Module : Tooltip
	Version : 1.0.0

	.wpeo-tooltip -> classe de base du mobule
	.tooltip-primary -> tooltip sur fond rouge
	.tooltip-light -> tooltip sur fond rouge
	.tooltip-red -> tooltip sur fond rouge
--------------------------------------------------------------*/
.wpeo-tooltip {
	display: block;
	position: absolute;
	bottom: 0;
	left: 0;
	opacity: 0;
	pointer-events: none;
	z-index: 99999;
	white-space: nowrap;
	background: #2b2b2b;
	color: #fff;
	border-radius: 6px;
	font-size: 0.8rem;
	padding: 0 1em;
	height: 2.2em;
	line-height: 2.2em;
}

.wpeo-tooltip::before {
	display: block;
	content: '';
	width: 0;
	height: 0;
	border-style: solid;
	position: absolute;
}

.wpeo-tooltip:focus {
	outline: none;
}

/* Couleurs */
.wpeo-tooltip.tooltip-dark {
	background: #2b2b2b;
}

.wpeo-tooltip.tooltip-dark.tooltip-top::before {
	border-color: #2b2b2b transparent transparent transparent;
}

.wpeo-tooltip.tooltip-dark.tooltip-right::before {
	border-color: transparent #2b2b2b transparent transparent;
}

.wpeo-tooltip.tooltip-dark.tooltip-bottom::before {
	border-color: transparent transparent #2b2b2b transparent;
}

.wpeo-tooltip.tooltip-dark.tooltip-left::before {
	border-color: transparent transparent transparent #2b2b2b;
}

.wpeo-tooltip.tooltip-primary {
	background: #898de5;
}

.wpeo-tooltip.tooltip-primary.tooltip-top::before {
	border-color: #898de5 transparent transparent transparent;
}

.wpeo-tooltip.tooltip-primary.tooltip-right::before {
	border-color: transparent #898de5 transparent transparent;
}

.wpeo-tooltip.tooltip-primary.tooltip-bottom::before {
	border-color: transparent transparent #898de5 transparent;
}

.wpeo-tooltip.tooltip-primary.tooltip-left::before {
	border-color: transparent transparent transparent #898de5;
}

.wpeo-tooltip.tooltip-light {
	background: #ececec;
	color: rgba(0, 0, 0, 0.6);
}

.wpeo-tooltip.tooltip-light.tooltip-top::before {
	border-color: #ececec transparent transparent transparent;
}

.wpeo-tooltip.tooltip-light.tooltip-right::before {
	border-color: transparent #ececec transparent transparent;
}

.wpeo-tooltip.tooltip-light.tooltip-bottom::before {
	border-color: transparent transparent #ececec transparent;
}

.wpeo-tooltip.tooltip-light.tooltip-left::before {
	border-color: transparent transparent transparent #ececec;
}

.wpeo-tooltip.tooltip-red {
	background: #e05353;
}

.wpeo-tooltip.tooltip-red.tooltip-top::before {
	border-color: #e05353 transparent transparent transparent;
}

.wpeo-tooltip.tooltip-red.tooltip-right::before {
	border-color: transparent #e05353 transparent transparent;
}

.wpeo-tooltip.tooltip-red.tooltip-bottom::before {
	border-color: transparent transparent #e05353 transparent;
}

.wpeo-tooltip.tooltip-red.tooltip-left::before {
	border-color: transparent transparent transparent #e05353;
}

/* Position de la fleche */
.wpeo-tooltip.tooltip-top::before {
	border-width: 6px 6px 0 6px;
	border-color: #2b2b2b transparent transparent transparent;
	bottom: -6px;
	left: 50%;
	-webkit-transform: translateX(-50%);
	transform: translateX(-50%);
}

.wpeo-tooltip.tooltip-right::before {
	border-width: 6px 6px 6px 0;
	border-color: transparent #2b2b2b transparent transparent;
	top: 50%;
	-webkit-transform: translateY(-50%);
	transform: translateY(-50%);
	left: -6px;
}

.wpeo-tooltip.tooltip-bottom::before {
	border-width: 0 6px 6px 6px;
	border-color: transparent transparent #2b2b2b transparent;
	top: -6px;
	left: 50%;
	-webkit-transform: translateX(-50%);
	transform: translateX(-50%);
}

.wpeo-tooltip.tooltip-left::before {
	border-width: 6px 0 6px 6px;
	border-color: transparent transparent transparent #2b2b2b;
	top: 50%;
	-webkit-transform: translateY(-50%);
	transform: translateY(-50%);
	right: -6px;
}

/*--------------------------------------------------------------
	Module : Util
	Version : 1.0.0

	Contient toutes les classes utilitaires
	.wpeo-util-hidden -> Masque l'élément
--------------------------------------------------------------*/
.wpeo-util-hidden {
	display: none !important;
}

/*--------------------------------------------------------------
# Tmp Jimmy Module Screen Options
--------------------------------------------------------------*/
.wpeo-screen-options {
	text-align: right;
	width: 100%;
}

.wpeo-screen-options .content {
	width: 99%;
	text-align: left;
	margin-left: 20px;
}

.wpeo-screen-options .content .wpeo-form {
	padding: 20px;
	margin: 0;
	background-color: #3495f0;
	border: 1px solid #ccd0d4;
}

.wpeo-screen-options .content .wpeo-form li {
	width: 20%;
}

/*.wpeo-modal .xdsoft_datetimepicker {*/
/*	z-index: 99999 !important;*/
/*}*/

/*li.unit::marker {*/
/*	font-size: 0px;*/
/*}*/
/**/
/*.photodigiriskdolibarr {*/
/*	background-color: rgb(27,28,35);*/
/*	width: 50px;*/
/*}*/
/**/
/*.unit-container img.photo.photowithmargin {*/
/*	width: 50px;*/
/*	margin: 0px;*/
/*}*/
/**/
/*.digitest img {*/
/*	height: 14px;*/
/*}*/
/**/
/*.linkElement {*/
/*	display: flex;*/
/*	width: 100%;*/
/*}*/
/**/
/*.dropdown-toggle::after {*/
/*	display : none;*/
/*	content: '';*/
/*}*/
/**/
/*.risk-evaluation-header {*/
/*	display : flex;*/
/*}*/
/**/
/*.cotation-standard {*/
/*	display : flex;*/
/*}*/

/*.modal-content {*/
/*	display : grid;*/
/*}*/

/*.risk-evaluation .risk-evaluation-photo {*/
/*	background: none !important;*/
/*}*/
/**/
/*.selected-cotation {*/
/*	border: 5px solid green;*/
/*	border-radius: 5px;*/
/*}*/
/**/
/*.risk-content {*/
/*	display: flex;*/
/*}*/
/**/
/*.risk-category {*/
/*	display: grid;*/
/*	margin-right: 10px;*/
/*}*/
/**/
/*.risk-description {*/
/*	display: grid;*/
/*}*/
/**/
/*.risk-evaluation-container {*/
/*	display: flex;*/
/*}*/

<?php
/* Copyright (C) 2021 EOXIA <dev@eoxia.com>
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
 * \file    lib/digiriskdolibarr_function.lib.php
 * \ingroup digiriskdolibarr
 * \brief   Library files with common functions for Digiriskdolibarr
 */

/**
 *  Show photos of an object (nbmax maximum), into several columns
 *
 *  @param		string	$modulepart		'product', 'ticket', ...
 *  @param      string	$sdir        	Directory to scan (full absolute path)
 *  @param      int		$size        	0=original size, 1='small' use thumbnail if possible
 *  @param      int		$nbmax       	Nombre maximum de photos (0=pas de max)
 *  @param      int		$nbbyrow     	Number of image per line or -1 to use div. Used only if size=1.
 * 	@param		int		$showfilename	1=Show filename
 * 	@param		int		$showaction		1=Show icon with action links (resize, delete)
 * 	@param		int		$maxHeight		Max height of original image when size='small' (so we can use original even if small requested). If 0, always use 'small' thumb image.
 * 	@param		int		$maxWidth		Max width of original image when size='small'
 *  @param      int     $nolink         Do not add a href link to view enlarged imaged into a new tab
 *  @param      int     $notitle        Do not add title tag on image
 *  @param		int		$usesharelink	Use the public shared link of image (if not available, the 'nophoto' image will be shown instead)
 *  @return     string					Html code to show photo. Number of photos shown is saved in this->nbphoto
 */
function digirisk_show_photos($modulepart, $sdir, $size = 0, $nbmax = 0, $nbbyrow = 5, $showfilename = 0, $showaction = 0, $maxHeight = 120, $maxWidth = 160, $nolink = 0, $notitle = 0, $usesharelink = 0, $subdir = "", $object = null)
{
	global $conf, $user, $langs;

	include_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
	include_once DOL_DOCUMENT_ROOT.'/core/lib/images.lib.php';

	$sortfield = 'position_name';
	$sortorder = 'desc';

	if (is_object($object)) {
		$dir = $sdir.'/'.$object->ref.'/';
		$pdir = $subdir . '/'.$object->ref.'/';
	} else {
		$dir = $sdir.'/';
		$pdir = $subdir . '/';
	}

	// Defined relative dir to DOL_DATA_ROOT
	$relativedir = '';
	if ($dir)
	{
		$relativedir = preg_replace('/^'.preg_quote(DOL_DATA_ROOT, '/').'/', '', $dir);
		$relativedir = preg_replace('/^[\\/]/', '', $relativedir);
		$relativedir = preg_replace('/[\\/]$/', '', $relativedir);
	}

	$dirthumb = $dir.'thumbs/';
	$pdirthumb = $pdir.'thumbs/';

	$return = '<!-- Photo -->'."\n";
	$nbphoto = 0;

	$filearray = dol_dir_list($dir, "files", 0, '', '(\.meta|_preview.*\.png)$', $sortfield, (strtolower($sortorder) == 'desc' ?SORT_DESC:SORT_ASC), 1);
	//echo '<pre>'; print_r( $pdirthumb ); echo '</pre>';
	if (count($filearray))
	{
		if ($sortfield && $sortorder)
		{
			$filearray = dol_sort_array($filearray, $sortfield, $sortorder);
		}

		foreach ($filearray as $key => $val)
		{
			$photo = '';
			$file = $val['name'];

			//if (! utf8_check($file)) $file=utf8_encode($file);	// To be sure file is stored in UTF8 in memory

			//if (dol_is_file($dir.$file) && image_format_supported($file) >= 0)
			if (image_format_supported($file) >= 0)
			{
				$nbphoto++;
				$photo = $file;
				$viewfilename = $file;

				if ($size == 1 || $size == 'small') {   // Format vignette
					// Find name of thumb file
					$photo_vignette = basename(getImageFileNameForSize($dir.$file, '_small'));

					if (!dol_is_file($dirthumb.$photo_vignette)) $photo_vignette = '';

					// Get filesize of original file
					$imgarray = dol_getImageSize($dir.$photo);

					if ($nbbyrow > 0)
					{
						if ($nbphoto == 1) $return .= '<table class="valigntop center centpercent" style="border: 0; padding: 2px; border-spacing: 2px; border-collapse: separate;">';

						if ($nbphoto % $nbbyrow == 1) $return .= '<tr class="center valignmiddle" style="border: 1px">';
						$return .= '<td style="width: '.ceil(100 / $nbbyrow).'%" class="photo">';
					}
					elseif ($nbbyrow < 0) $return .= '<div class="inline-block">';

					$return .= "\n";

					$relativefile = preg_replace('/^\//', '', $pdir.$photo);
					if (empty($nolink))
					{
						$urladvanced = getAdvancedPreviewUrl($modulepart, $relativefile, 0, 'entity='.$conf->entity);
						if ($urladvanced) $return .= '<a href="'.$urladvanced.'">';
						else $return .= '<a href="'.DOL_URL_ROOT.'/viewimage.php?modulepart='.$modulepart.'&entity='.$conf->entity.'&file='.urlencode($pdir.$photo).'" class="aphoto" target="_blank">';
					}

					// Show image (width height=$maxHeight)
					// Si fichier vignette disponible et image source trop grande, on utilise la vignette, sinon on utilise photo origine
					$alt = $langs->transnoentitiesnoconv('File').': '.$relativefile;
					$alt .= ' - '.$langs->transnoentitiesnoconv('Size').': '.$imgarray['width'].'x'.$imgarray['height'];
					if ($notitle) $alt = '';

					if ($usesharelink)
					{
						if ($val['share'])
						{
							if (empty($maxHeight) || $photo_vignette && $imgarray['height'] > $maxHeight)
							{
								$return .= '<!-- Show original file (thumb not yet available with shared links) -->';
								$return .= '<img class="photo photowithmargin clicked-photo-preview" height="'.$maxHeight.'" src="'.DOL_URL_ROOT.'/viewimage.php?hashp='.urlencode($val['share']).'" title="'.dol_escape_htmltag($alt).'">';
							}
							else {
								$return .= '<!-- Show original file -->';
								$return .= '<img class="photo photowithmargin clicked-photo-preview" height="'.$maxHeight.'" src="'.DOL_URL_ROOT.'/viewimage.php?hashp='.urlencode($val['share']).'" title="'.dol_escape_htmltag($alt).'">';
							}
						}
						else
						{
							$return .= '<!-- Show nophoto file (because file is not shared) -->';
							$return .= '<img class="photo photowithmargin" height="'.$maxHeight.'" src="'.DOL_URL_ROOT.'/public/theme/common/nophoto.png" title="'.dol_escape_htmltag($alt).'">';
						}
					}
					else
					{
						if ($photo_vignette && $imgarray['height'] > $maxHeight)
						{
							$return .= '<!-- Show thumb -->';
							$return .= '<img class="photo clicked-photo-preview"  width="'.$maxHeight.'" src="'.DOL_URL_ROOT.'/viewimage.php?modulepart='.$modulepart.'&entity='.$conf->entity.'&file='.urlencode($pdirthumb.$photo_vignette).'" title="'.dol_escape_htmltag($alt).'">';
						}
						else {
							$return .= '<!-- Show original file -->';
							$return .= '<img class="photo photowithmargin clicked-photo-preview" width="'.$maxHeight.'" src="'.DOL_URL_ROOT.'/viewimage.php?modulepart='.$modulepart.'&entity='.$conf->entity.'&file='.urlencode($pdir.$photo).'" title="'.dol_escape_htmltag($alt).'">';
						}
					}
					$return .= '<input type="hidden" class="filename" value="'.$photo.'">';

					if (empty($nolink)) $return .= '</a>';
					$return .= "\n";
					if ($showfilename) $return .= '<br>'.$viewfilename;
					if ($showaction)
					{
						$return .= '<br>';
						// On propose la generation de la vignette si elle n'existe pas et si la taille est superieure aux limites
						if ($photo_vignette && (image_format_supported($photo) > 0) && ($object->imgWidth > $maxWidth || $object->imgHeight > $maxHeight))
						{
							$return .= '<a href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=addthumb&amp;file='.urlencode($pdir.$viewfilename).'">'.img_picto($langs->trans('GenerateThumb'), 'refresh').'&nbsp;&nbsp;</a>';
						}
					}
					$return .= "\n";

					if ($nbbyrow > 0)
					{
						$return .= '</td>';
						if (($nbphoto % $nbbyrow) == 0) $return .= '</tr>';
					}
					elseif ($nbbyrow < 0) $return .= '</div>';
				}

				if (empty($size)) {     // Format origine
					$return .= '<img class="photo photowithmargin" src="'.DOL_URL_ROOT.'/viewimage.php?modulepart='.$modulepart.'&entity='.$conf->entity.'&file='.urlencode($pdir.$photo).'">';

					if ($showfilename) $return .= '<br>'.$viewfilename;
				}

				// On continue ou on arrete de boucler ?
				if ($nbmax && $nbphoto >= $nbmax) break;
			}
		}

		if ($size == 1 || $size == 'small')
		{
			if ($nbbyrow > 0)
			{
				// Ferme tableau
				while ($nbphoto % $nbbyrow)
				{
					$return .= '<td style="width: '.ceil(100 / $nbbyrow).'%">&nbsp;</td>';
					$nbphoto++;
				}

				if ($nbphoto) $return .= '</table>';
			}
		}
	}
	if (is_object($object)){
		$object->nbphoto = $nbphoto;
	}
	return $return;
}

/**
 *      Return a string to show the box with list of available documents for object.
 *      This also set the property $this->numoffiles
 *
 *      @param      string				$modulepart         Module the files are related to ('propal', 'facture', 'facture_fourn', 'mymodule', 'mymodule:nameofsubmodule', 'mymodule_temp', ...)
 *      @param      string				$modulesubdir       Existing (so sanitized) sub-directory to scan (Example: '0/1/10', 'FA/DD/MM/YY/9999'). Use '' if file is not into subdir of module.
 *      @param      string				$filedir            Directory to scan
 *      @param      string				$urlsource          Url of origin page (for return)
 *      @param      int|string[]        $genallowed         Generation is allowed (1/0 or array list of templates)
 *      @param      int					$delallowed         Remove is allowed (1/0)
 *      @param      string				$modelselected      Model to preselect by default
 *      @param      integer				$allowgenifempty	Allow generation even if list of template ($genallowed) is empty (show however a warning)
 *      @param      integer				$forcenomultilang	Do not show language option (even if MAIN_MULTILANGS defined)
 *      @param      int					$iconPDF            Deprecated, see getDocumentsLink
 * 		@param		int					$notused	        Not used
 * 		@param		integer				$noform				Do not output html form tags
 * 		@param		string				$param				More param on http links
 * 		@param		string				$title				Title to show on top of form. Example: '' (Default to "Documents") or 'none'
 * 		@param		string				$buttonlabel		Label on submit button
 * 		@param		string				$codelang			Default language code to use on lang combo box if multilang is enabled
 * 		@param		string				$morepicto			Add more HTML content into cell with picto
 *      @param      Object              $object             Object when method is called from an object card.
 *      @param		int					$hideifempty		Hide section of generated files if there is no file
 *      @param      string              $removeaction       (optional) The action to remove a file
 *      @param      int                 $active             (optional) To show gen button disabled
 *      @param      string              $tooltiptext       (optional) Tooltip text when gen button disabled
 * 		@return		string              					Output string with HTML array of documents (might be empty string)
 */
function digiriskshowdocuments($modulepart, $modulesubdir, $filedir, $urlsource, $genallowed, $delallowed = 0, $modelselected = '', $allowgenifempty = 1, $forcenomultilang = 0, $notused = 0, $noform = 0, $param = '', $title = '', $buttonlabel = '', $codelang = '', $morepicto = '', $object = null, $hideifempty = 0, $removeaction = 'remove_file', $active = 1, $tooltiptext = '')
{
	global $db, $langs, $conf, $user, $hookmanager, $form;

	if (!is_object($form)) $form = new Form($this->db);

	include_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

	// Add entity in $param if not already exists
	if (!preg_match('/entity\=[0-9]+/', $param)) {
		$param .= ($param ? '&' : '').'entity='.(!empty($object->entity) ? $object->entity : $conf->entity);
	}

	$hookmanager->initHooks(array('formfile'));

	// Get list of files
	$file_list = null;
	if (!empty($filedir))
	{
		$file_list = dol_dir_list($filedir, 'files', 0, '(\.odt|\.zip)', '', 'date', SORT_DESC, 1);
	}
	if ($hideifempty && empty($file_list)) return '';

	$out = '';
	$forname = 'builddoc';
	$headershown = 0;
	$showempty = 0;

	$out .= "\n".'<!-- Start show_document -->'."\n";

	$titletoshow = $langs->trans("Documents");
	if (!empty($title)) $titletoshow = ($title == 'none' ? '' : $title);

	// Show table
	if ($genallowed)
	{
		$submodulepart = $modulepart;
		// modulepart = 'nameofmodule' or 'nameofmodule:NameOfObject'
		$tmp = explode(':', $modulepart);
		if (!empty($tmp[1])) {
			$modulepart = $tmp[0];
			$submodulepart = $tmp[1];
		}

		// For normalized external modules.
		$file = dol_buildpath('/'.$modulepart.'/core/modules/'.$modulepart.'/digiriskdocuments/'.strtolower($submodulepart).'/modules_'.strtolower($submodulepart).'.php', 0);
		include_once $file;

		$class = 'ModeleODT'.$submodulepart;

		if (class_exists($class))
		{
			if (preg_match('/specimen/', $param)) {
				$type = strtolower($class) . 'specimen';
				$modellist = array();

				include_once DOL_DOCUMENT_ROOT . '/core/lib/functions2.lib.php';
				$modellist = getListOfModels($db, $type, 0);
			} else {
				$modellist = call_user_func($class.'::liste_modeles', $db, 100);
			}
		}
		else
		{
			dol_print_error($db, "Bad value for modulepart '".$modulepart."' in showdocuments");
			return -1;
		}

		// Set headershown to avoid to have table opened a second time later
		$headershown = 1;

		if (empty($buttonlabel)) $buttonlabel = $langs->trans('Generate');

		if ($conf->browser->layout == 'phone') $urlsource .= '#'.$forname.'_form'; // So we switch to form after a generation
		if (empty($noform)) $out .= '<form action="'.$urlsource.(empty($conf->global->MAIN_JUMP_TAG) ? '' : '#builddoc').'" id="'.$forname.'_form" method="post">';
		$out .= '<input type="hidden" name="action" value="builddoc">';
		$out .= '<input type="hidden" name="token" value="'.newToken().'">';

		$out .= load_fiche_titre($titletoshow, '', '');
		$out .= '<div class="div-table-responsive-no-min">';
		$out .= '<table class="liste formdoc noborder centpercent">';

		$out .= '<tr class="liste_titre">';

		$addcolumforpicto = ($delallowed || $printer || $morepicto);
		$colspan = (3 + ($addcolumforpicto ? 1 : 0)); $colspanmore = 0;

		$out .= '<th colspan="'.$colspan.'" class="formdoc liste_titre maxwidthonsmartphone center">';
		// Model
		if (!empty($modellist))
		{
			asort($modellist);
			$out .= '<span class="hideonsmartphone">'.$langs->trans('Model').' </span>';
			$modellist = array_filter($modellist, 'remove_index');
			if (is_array($modellist) && count($modellist) == 1)    // If there is only one element
			{
				$arraykeys = array_keys($modellist);
				$arrayvalues = preg_replace('/template_/','', array_values($modellist)[0]);
				$modellist[$arraykeys[0]] = $arrayvalues;
				$modelselected = $arraykeys[0];
			}
			$morecss = 'maxwidth200';
			if ($conf->browser->layout == 'phone') $morecss = 'maxwidth100';
			$out .= $form->selectarray('model', $modellist, $modelselected, $showempty, 0, 0, '', 0, 0, 0, '', $morecss);

			if ($conf->use_javascript_ajax)
			{
				$out .= ajax_combobox('model');
			}
		}
		else
		{
			$out .= '<div class="float">'.$langs->trans("Files").'</div>';
		}

		// Button
		if ($active) {
			$genbutton = '<input class="button buttongen" id="'.$forname.'_generatebutton" name="'.$forname.'_generatebutton"';
			$genbutton .= ' type="submit" value="'.$buttonlabel.'"';
		} else {
			$genbutton = '<input class="button buttongen disabled" id="'.$forname.'_generatebutton" name="'.$forname.'_generatebutton"';
			$genbutton .= '  value="'.$buttonlabel.'"';
		}

		if (!$allowgenifempty && !is_array($modellist) && empty($modellist)) $genbutton .= ' disabled';
		$genbutton .= '>';
		if ($allowgenifempty && !is_array($modellist) && empty($modellist) && empty($conf->dol_no_mouse_hover) && $modulepart != 'unpaid')
		{
			$langs->load("errors");
			$genbutton .= ' '.img_warning($langs->transnoentitiesnoconv("WarningNoDocumentModelActivated"));
		}
		if (!$allowgenifempty && !is_array($modellist) && empty($modellist) && empty($conf->dol_no_mouse_hover) && $modulepart != 'unpaid') $genbutton = '';
		if (empty($modellist) && !$showempty && $modulepart != 'unpaid') $genbutton = '';
		$out .= $genbutton;
		if (!$active) {
			$htmltooltip = '';
			$htmltooltip .= $tooltiptext;

			$out .= '<span class="center">';
			$out .= $form->textwithpicto($langs->trans('Help'), $htmltooltip, 1, 0);
			$out .= '</span>';
		}

		$out .= '</th>';

		if (!empty($hookmanager->hooks['formfile']))
		{
			foreach ($hookmanager->hooks['formfile'] as $module)
			{
				if (method_exists($module, 'formBuilddocLineOptions'))
				{
					$colspanmore++;
					$out .= '<th></th>';
				}
			}
		}
		$out .= '</tr>';

		// Execute hooks
		$parameters = array('colspan'=>($colspan + $colspanmore), 'socid'=>(isset($GLOBALS['socid']) ? $GLOBALS['socid'] : ''), 'id'=>(isset($GLOBALS['id']) ? $GLOBALS['id'] : ''), 'modulepart'=>$modulepart);
		if (is_object($hookmanager))
		{
			$reshook = $hookmanager->executeHooks('formBuilddocOptions', $parameters, $GLOBALS['object']);
			$out .= $hookmanager->resPrint;
		}
	}

	// Get list of files
	if (!empty($filedir))
	{
		$link_list = array();
		if (is_object($object) && $object->id > 0)
		{
			require_once DOL_DOCUMENT_ROOT.'/core/class/link.class.php';
			$link = new Link($db);
			$sortfield = $sortorder = null;
			$res = $link->fetchAll($link_list, $object->element, $object->id, $sortfield, $sortorder);
		}

		$out .= '<!-- html.formfile::showdocuments -->'."\n";

		// Show title of array if not already shown
		if ((!empty($file_list) || !empty($link_list) || preg_match('/^massfilesarea/', $modulepart))
			&& !$headershown)
		{
			$headershown = 1;
			$out .= '<div class="titre">'.$titletoshow.'</div>'."\n";
			$out .= '<div class="div-table-responsive-no-min">';
			$out .= '<table class="noborder centpercent" id="'.$modulepart.'_table">'."\n";
		}

		// Loop on each file found
		if (is_array($file_list))
		{
			foreach ($file_list as $file)
			{
				// Define relative path for download link (depends on module)
				$relativepath = $file["name"]; // Cas general
				if ($modulesubdir) $relativepath = $modulesubdir."/".$file["name"]; // Cas propal, facture...

				$out .= '<tr class="oddeven">';

				$documenturl = DOL_URL_ROOT.'/document.php';
				if (isset($conf->global->DOL_URL_ROOT_DOCUMENT_PHP)) $documenturl = $conf->global->DOL_URL_ROOT_DOCUMENT_PHP; // To use another wrapper

				// Show file name with link to download
				$out .= '<td class="minwidth200">';
				$out .= '<a class="documentdownload paddingright" href="'.$documenturl.'?modulepart='.$modulepart.'&amp;file='.urlencode($relativepath).($param ? '&'.$param : '').'"';

				$mime = dol_mimetype($relativepath, '', 0);
				if (preg_match('/text/', $mime)) $out .= ' target="_blank"';
				$out .= '>';
				$out .= img_mime($file["name"], $langs->trans("File").': '.$file["name"]);
				$out .= dol_trunc($file["name"], 150);
				$out .= '</a>'."\n";
				$out .= '</td>';

				// Show file size
				$size = (!empty($file['size']) ? $file['size'] : dol_filesize($filedir."/".$file["name"]));
				$out .= '<td class="nowrap right">'.dol_print_size($size, 1, 1).'</td>';

				// Show file date
				$date = (!empty($file['date']) ? $file['date'] : dol_filemtime($filedir."/".$file["name"]));
				$out .= '<td class="nowrap right">'.dol_print_date($date, 'dayhour', 'tzuser').'</td>';

				if ($delallowed || $morepicto)
				{
					$out .= '<td class="right nowraponall">';
					if ($delallowed)
					{
						$tmpurlsource = preg_replace('/#[a-zA-Z0-9_]*$/', '', $urlsource);
						$out .= '<a href="'.$tmpurlsource.((strpos($tmpurlsource, '?') === false) ? '?' : '&amp;').'action='.$removeaction.'&amp;file='.urlencode($relativepath);
						$out .= ($param ? '&amp;'.$param : '');
						$out .= '">'.img_picto($langs->trans("Delete"), 'delete').'</a>';
					}
					if ($morepicto)
					{
						$morepicto = preg_replace('/__FILENAMEURLENCODED__/', urlencode($relativepath), $morepicto);
						$out .= $morepicto;
					}
					$out .= '</td>';
				}

				if (is_object($hookmanager))
				{
					$parameters = array('colspan'=>($colspan + $colspanmore), 'socid'=>(isset($GLOBALS['socid']) ? $GLOBALS['socid'] : ''), 'id'=>(isset($GLOBALS['id']) ? $GLOBALS['id'] : ''), 'modulepart'=>$modulepart, 'relativepath'=>$relativepath);
					$res = $hookmanager->executeHooks('formBuilddocLineOptions', $parameters, $file);
					if (empty($res))
					{
						$out .= $hookmanager->resPrint; // Complete line
						$out .= '</tr>';
					}
					else
					{
						$out = $hookmanager->resPrint; // Replace all $out
					}
				}
			}
		}
		// Loop on each link found
//		if (is_array($link_list))
//		{
//			$colspan = 2;
//
//			foreach ($link_list as $file)
//			{
//				$out .= '<tr class="oddeven">';
//				$out .= '<td colspan="'.$colspan.'" class="maxwidhtonsmartphone">';
//				$out .= '<a data-ajax="false" href="'.$file->url.'" target="_blank">';
//				$out .= $file->label;
//				$out .= '</a>';
//				$out .= '</td>';
//				$out .= '<td class="right">';
//				$out .= dol_print_date($file->datea, 'dayhour');
//				$out .= '</td>';
//				if ($delallowed || $printer || $morepicto) $out .= '<td></td>';
//				$out .= '</tr>'."\n";
//			}
//		}

		if (count($file_list) == 0 && count($link_list) == 0 && $headershown)
		{
			$out .= '<tr><td colspan="'.(3 + ($addcolumforpicto ? 1 : 0)).'" class="opacitymedium">'.$langs->trans("None").'</td></tr>'."\n";
		}
	}

	if ($headershown)
	{
		// Affiche pied du tableau
		$out .= "</table>\n";
		$out .= "</div>\n";
		if ($genallowed)
		{
			if (empty($noform)) $out .= '</form>'."\n";
		}
	}
	$out .= '<!-- End show_document -->'."\n";

	return $out;
}

/**
 *	Exclude index.php files from list of models for document generation
 *
 * @param   string $model
 * @return  '' or $model
 */
function remove_index($model) {
	if (preg_match('/index.php/',$model)) {
		return '';
	} else {
		return $model;
	}
}

/**
 *	Show HTML header HTML + BODY + Top menu + left menu + DIV
 *
 * @param 	string 	$head				Optionnal head lines
 * @param 	string 	$title				HTML title
 * @param	string	$help_url			Url links to help page
 * 		                            	Syntax is: For a wiki page: EN:EnglishPage|FR:FrenchPage|ES:SpanishPage
 *                                  	For other external page: http://server/url
 * @param	string	$target				Target to use on links
 * @param 	int    	$disablejs			More content into html header
 * @param 	int    	$disablehead		More content into html header
 * @param 	array  	$arrayofjs			Array of complementary js files
 * @param 	array  	$arrayofcss			Array of complementary css files
 * @param	string	$morequerystring	Query string to add to the link "print" to get same parameters (use only if autodetect fails)
 * @param   string  $morecssonbody      More CSS on body tag.
 * @param	string	$replacemainareaby	Replace call to main_area() by a print of this string
 * @return	void
 */
function digiriskHeader($head = '', $title = '', $help_url = '', $target = '', $disablejs = 0, $disablehead = 0, $arrayofjs = '', $arrayofcss = '', $morequerystring = '', $morecssonbody = '', $replacemainareaby = '')
{
	global $conf, $langs, $db, $user;

	require_once __DIR__ . '/../class/digiriskelement.class.php';
	require_once __DIR__ . '/../core/modules/digiriskdolibarr/digiriskelement/groupment/mod_groupment_standard.php';
	require_once __DIR__ . '/../core/modules/digiriskdolibarr/digiriskelement/workunit/mod_workunit_standard.php';

	$mod_groupment = new $conf->global->DIGIRISKDOLIBARR_GROUPMENT_ADDON();
	$mod_workunit = new $conf->global->DIGIRISKDOLIBARR_WORKUNIT_ADDON();

	// html header
	$tmpcsstouse = 'sidebar-collapse'.($morecssonbody ? ' '.$morecssonbody : '');
	// If theme MD and classic layer, we open the menulayer by default.
	if ($conf->theme == 'md' && !in_array($conf->browser->layout, array('phone', 'tablet')) && empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER))
	{
		global $mainmenu;
		if ($mainmenu != 'website') $tmpcsstouse = $morecssonbody; // We do not use sidebar-collpase by default to have menuhider open by default.
	}

	if (!empty($conf->global->MAIN_OPTIMIZEFORCOLORBLIND)) {
		$tmpcsstouse .= ' colorblind-'.strip_tags($conf->global->MAIN_OPTIMIZEFORCOLORBLIND);
	}

	print '<body id="mainbody" class="'.$tmpcsstouse.'">'."\n";

	llxHeader('', $title, $help_url, '', '', '', $arrayofjs, $arrayofcss, $morequerystring, $morecssonbody);

	//Body navigation digirisk
	$object  = new DigiriskElement($db);
	if ($conf->global->DIGIRISKDOLIBARR_SHOW_HIDDEN_DIGIRISKELEMENT) {
		$objects = $object->fetchAll('',  'ref',  0,  0);
	} else {
		$objects = $object->fetchAll('',  '',  0,  0, array('customsql' => 'status > 0'));
	}
	$results = recurse_tree(0,0,$objects); ?>

	<?php require_once './core/tpl/digiriskdolibarr_medias_gallery_modal.tpl.php'; ?>

	<div id="id-container" class="id-container page-ut-gp-list">
		<div class="side-nav">
			<div class="side-nav-responsive"><i class="fas fa-bars"></i> <?php echo "Navigation UT/GP"; ?></div>
			<div id="id-left">
				<div class="digirisk-wrap wpeo-wrap">
					<div class="navigation-container">
						<div class="society-header">
							<a class="linkElement" href="../digiriskdolibarr/digiriskstandard_card.php?id=<?php echo $conf->global->DIGIRISKDOLIBARR_ACTIVE_STANDARD ?>">
								<span class="icon fas fa-building fa-fw"></span>
								<div class="title"><?php echo $conf->global->MAIN_INFO_SOCIETE_NOM ?></div>
								<?php if ($user->rights->digiriskdolibarr->digiriskelement->write) : ?>
									<div class="add-container">
										<a id="newGroupment" href="../digiriskdolibarr/digiriskelement_card.php?action=create&element_type=groupment&fk_parent=0">
											<div class="wpeo-button button-square-40 button-secondary wpeo-tooltip-event" data-direction="bottom" data-color="light" aria-label="<?php echo $langs->trans('NewGroupment'); ?>"><strong><?php echo $mod_groupment->prefix; ?></strong><span class="button-add animated fas fa-plus-circle"></span></div>
										</a>
										<a id="newWorkunit" href="../digiriskdolibarr/digiriskelement_card.php?action=create&element_type=workunit&fk_parent=0">
											<div class="wpeo-button button-square-40 wpeo-tooltip-event" data-direction="bottom" data-color="light" aria-label="<?php echo $langs->trans('NewWorkUnit'); ?>"><strong><?php echo $mod_workunit->prefix; ?></strong><span class="button-add animated fas fa-plus-circle"></span></div>
										</a>
									</div>
								<?php endif; ?>
							</a>
						</div>
						<?php if (!empty($objects) && $objects > 0) : ?>
							<div class="toolbar">
								<div class="toggle-plus tooltip hover" aria-label="<?php echo $langs->trans('UnwrapAll'); ?>"><span class="icon fas fa-plus-square"></span></div>
								<div class="toggle-minus tooltip hover" aria-label="<?php echo $langs->trans('WrapAll'); ?>"><span class="icon fas fa-minus-square"></span></div>
							</div>
						<?php else : ?>
							<div class="society-header">
								<a id="newGroupment" href="../digiriskdolibarr/digiriskelement_card.php?action=create&element_type=groupment&fk_parent=0">
									<div class="wpeo-button button-square-40 button-secondary wpeo-tooltip-event" data-direction="bottom" data-color="light" aria-label="<?php echo $langs->trans('NewGroupment'); ?>"><strong><?php echo $mod_groupment->prefix; ?></strong><span class="button-add animated fas fa-plus-circle"></span></div>
								</a>
								<a id="newWorkunit" href="../digiriskdolibarr/digiriskelement_card.php?action=create&element_type=workunit&fk_parent=0">
									<div class="wpeo-button button-square-40 wpeo-tooltip-event" data-direction="bottom" data-color="light" aria-label="<?php echo $langs->trans('NewWorkUnit'); ?>"><strong><?php echo $mod_workunit->prefix; ?></strong><span class="button-add animated fas fa-plus-circle"></span></div>
								</a>
							</div>
						<?php endif; ?>

						<ul class="workunit-list">
							<?php display_recurse_tree($results) ?>
							<script>
								// Get previous menu to display it
								var MENU = localStorage.menu;
								if (MENU == null || MENU == '') {
									MENU = new Set()
								} else {
									MENU = JSON.parse(MENU);
									MENU = new Set(MENU);
								}

								MENU.forEach((id) =>  {
									jQuery( '#menu'+id).removeClass( 'fa-chevron-right').addClass( 'fa-chevron-down' );
									jQuery( '#unit'+id ).addClass( 'toggled' );
								});

								<?php $object->fetch(GETPOST('id')); ?>;
								var idParent = <?php echo json_encode($object->fk_parent) ;?> ;

								jQuery( '#menu'+idParent).removeClass( 'fa-chevron-right').addClass( 'fa-chevron-down' );
								jQuery( '#unit'+idParent ).addClass( 'toggled' );

								// Set active unit active
								jQuery( '.digirisk-wrap .navigation-container .unit.active' ).removeClass( 'active' );

								var params = new window.URLSearchParams(window.location.search);
								var id = params.get('id');
								if (document.URL.match(/digiriskelement/)) {
									jQuery( '#unit'  + id ).addClass( 'active' );
									jQuery( '#unit'  +id  ).closest( '.unit' ).attr( 'value', id );
								};
							</script>
						</ul>
					</div>
				</div>
			</div>
		</div>
	<?php
	// main area
	if ($replacemainareaby)
	{
		print $replacemainareaby;
		return;
	}
	main_area($title);
}

/**
 *	Recursive tree process
 *
 * @param	DigiriskElement $parent Element Parent of Digirisk Element object
 * @param 	int             $niveau Depth of tree
 * @param 	array           $array  Global Digirisk Element list
 * @return	array           $result Global Digirisk Element list after recursive process
 */
function recurse_tree($parent, $niveau, $array) {
	$result = array();
	foreach ($array as $noeud) {
		if ($parent == $noeud->fk_parent) {
			$result[$noeud->id] = array(
				'id'       => $noeud->id,
				'depth'    => array('depth'.$noeud->id => $niveau),
				'object'   => $noeud,
				'children' => recurse_tree($noeud->id, ($niveau + 1), $array),
			);
		}
	}
	return $result;
}

/**
 *	Display Recursive tree process
 *
 * @param	array $result Global Digirisk Element list after recursive process
 * @return	void
 */
function display_recurse_tree($results) {
	include_once DOL_DOCUMENT_ROOT.'/core/lib/images.lib.php';

	global $conf, $langs, $user, $db;

	require_once __DIR__ . '/../core/modules/digiriskdolibarr/digiriskelement/groupment/mod_groupment_standard.php';
	require_once __DIR__ . '/../core/modules/digiriskdolibarr/digiriskelement/workunit/mod_workunit_standard.php';
	require_once DOL_DOCUMENT_ROOT . '/ecm/class/ecmdirectory.class.php';

	$action = GETPOST('action');
	$ecmdir = new EcmDirectory($db);
	$error = 0;

	if (!$error && $action == "addFiles" && GETPOST('digiriskelement_id')) {

		$digiriskelement_id = GETPOST('digiriskelement_id');
		$filenames = GETPOST('filenames');
		$digiriskelement = new DigiriskElement($db);
		$digiriskelement->fetch($digiriskelement_id);
		$pathToDigiriskElementPhoto = $conf->digiriskdolibarr->multidir_output[$conf->entity] . '/'.$digiriskelement->element_type.'/' . $digiriskelement->ref ;
		$filenames = preg_split('/vVv/', $filenames);
		array_pop($filenames);

		if ( !(empty($filenames))) {
			$digiriskelement->photo = $filenames[0];
			foreach ($filenames as $filename) {

				if (is_file( $conf->ecm->multidir_output[$conf->entity] . '/digiriskdolibarr/medias/' . $filename)) {

					$pathToECMPhoto =  $conf->ecm->multidir_output[$conf->entity] . '/digiriskdolibarr/medias/' . $filename;

					if(!is_dir($conf->digiriskdolibarr->multidir_output[$conf->entity] . '/'.$digiriskelement->element_type.'/')) {
						mkdir($conf->digiriskdolibarr->multidir_output[$conf->entity] . '/'.$digiriskelement->element_type.'/');
					}
					if (!is_dir($pathToDigiriskElementPhoto)) {
						mkdir($pathToDigiriskElementPhoto);
					}
					copy($pathToECMPhoto,$pathToDigiriskElementPhoto . '/' . $filename);

					$destfull = $pathToDigiriskElementPhoto . '/' . $filename;
					// Create thumbs
					vignette($destfull, 480, 270, '_small', 50, "thumbs");
					// Create mini thumbs for image (Ratio is near 16/9)
					vignette($destfull, 128, 72, '_mini', 50, "thumbs");
				}
			}
			$digiriskelement->update($user);
		}
		exit;
	}

	if (!$error && $action == "addToFavorite") {

		$digiriskelement_id = GETPOST('digiriskelement_id');
		$filename = GETPOST('filename');
		$digiriskelement = new DigiriskElement($db);
		$digiriskelement->fetch($digiriskelement_id);
		$digiriskelement->photo = $filename;
		$digiriskelement->update($user, true);
		exit;
	}

	if (!$error && $action == "unlinkFile") {

		$digiriskelement_id = GETPOST('digiriskelement_id');
		$filename = GETPOST('filename');
		$digiriskelement = new DigiriskElement($db);
		$digiriskelement->fetch($digiriskelement_id);

		//edit evaluation
		if ($digiriskelement->id > 0) {
			$pathToDigiriskElementPhoto = $conf->digiriskdolibarr->multidir_output[$conf->entity] . '/'.$digiriskelement->element_type.'/' . $digiriskelement->ref ;
		}


		$files = dol_dir_list($pathToDigiriskElementPhoto);

		foreach ($files as $file) {
			if (is_file($file['fullname']) && $file['name'] == $filename) {
				unlink($file['fullname']);
			}
		}

		$files = dol_dir_list($pathToDigiriskElementPhoto . '/thumbs');
		foreach ($files as $file) {
			if (preg_match('/' . preg_split('/\./',$filename)[0] . '/', $file['name'])) {
				unlink($file['fullname']);
			}
		}
		if ($digiriskelement->photo == $filename) {
			$digiriskelement->photo = '';
			$digiriskelement->update($user, true);
		}
		$urltogo = str_replace('__ID__', $id, $backtopage);
		$urltogo = preg_replace('/--IDFORBACKTOPAGE--/', $id, $urltogo); // New method to autoselect project after a New on another form object creation
		header("Location: ".$urltogo);
		exit;
	}

	if (!$error && $action == "uploadPhoto" && !empty($conf->global->MAIN_UPLOAD_DOC)) {
		// Define relativepath and upload_dir
		$relativepath = 'digiriskdolibarr/medias';
		$upload_dir = $conf->ecm->dir_output.'/'.$relativepath;
		if (is_array($_FILES['userfile']['tmp_name'])) $userfiles = $_FILES['userfile']['tmp_name'];
		else $userfiles = array($_FILES['userfile']['tmp_name']);

		foreach ($userfiles as $key => $userfile) {
			if (empty($_FILES['userfile']['tmp_name'][$key])) {
				$error++;
				if ($_FILES['userfile']['error'][$key] == 1 || $_FILES['userfile']['error'][$key] == 2) {
					setEventMessages($langs->trans('ErrorFileSizeTooLarge'), null, 'errors');
				} else {
					setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("File")), null, 'errors');
				}
			}
		}
		if (!$error) {
			$generatethumbs = 1;
			$res = dol_add_file_process($upload_dir, 0, 1, 'userfile', '', null, '', $generatethumbs);
			if ($res > 0) {
				$result = $ecmdir->changeNbOfFiles('+');
			}
		}
	}

	$mod_groupment = new $conf->global->DIGIRISKDOLIBARR_GROUPMENT_ADDON();
	$mod_workunit = new $conf->global->DIGIRISKDOLIBARR_WORKUNIT_ADDON();

	if ($user->rights->digiriskdolibarr->digiriskelement->read) {
		if ( !empty( $results )) {
		foreach ($results as $element) { ?>
			<?php if ($element['object']->id == $conf->global->DIGIRISKDOLIBARR_DIGIRISKELEMENT_TRASH) : ?>
				<hr>
			<?php endif; ?>
			<li class="unit type-<?php echo $element['object']->element_type; ?>" id="unit<?php  echo $element['object']->id; ?>">
				<div class="unit-container">
					<?php if ($element['object']->element_type == 'groupment' && count($element['children'])) { ?>
						<div class="toggle-unit">
							<i class="toggle-icon fas fa-chevron-right" id="menu<?php echo $element['object']->id;?>"></i>
						</div>
					<?php } else { ?>
						<div class="spacer"></div>
					<?php }
					$pathToThumb = DOL_URL_ROOT.'/viewimage.php?modulepart=digiriskdolibarr&entity='.$conf->entity.'&file='.urlencode($element['object']->element_type.'/'.$element['object']->ref . '/thumbs/');
					$filearray = dol_dir_list($conf->digiriskdolibarr->multidir_output[$conf->entity].'/'.$element['object']->element_type.'/'.$element['object']->ref.'/', "files", 0, '', '(\.odt|_preview.*\.png)$', 'position_name', 'asc', 1);
					if (count($filearray)) {
						print '<span class="floatleft inline-block valignmiddle divphotoref open-medias-linked modal-open digirisk-element digirisk-element-'.$element['object']->id.'" value="'. $element['object']->id.'">';
						 print '<img width="40" class="photo clicked-photo-preview" src="'.DOL_URL_ROOT.'/viewimage.php?modulepart=digiriskdolibarr&entity='.$conf->entity.'&file='.urlencode($element['object']->element_type.'/'.$element['object']->ref . '/thumbs/'. preg_replace('/\./', '_small.', $element['object']->photo)).'" >';
						 print '<input type="hidden" class="filepath-to-digiriskelement" value="'.$pathToThumb.'"/>';
						 print '</span>';
					} else {
						$nophoto = '/public/theme/common/nophoto.png'; ?>
							<div class="open-media-gallery modal-open digiriskelement digirisk-element-<?php echo $element['object']->id ?>" value="<?php  echo $element['object']->id ?>">
								<input type="hidden" class="type-from" value="digiriskelement"/>
								<input type="hidden" class="filepath-to-digiriskelement" value="<?php echo $pathToThumb ?>"/>
								<span class="floatleft inline-block valignmiddle divphotoref"><img width="40" class="photo photowithmargin clicked-photo-preview" alt="No photo" src="<?php echo DOL_URL_ROOT.$nophoto ?>"></span>
							</div>
					<?php } ?>
					<div class="digirisk-element-medias-modal" style="z-index:1500" value="<?php echo $element['object']->id ?>">
							<div class="wpeo-modal"  id="digirisk_element_medias_modal_<?php echo $element['object']->id ?>" value="<?php echo $element['object']->id ?>" style="z-index: 1005 !important">
								<div class="modal-container wpeo-modal-event">
									<!-- Modal-Header -->
									<div class="modal-header">
										<h2 class="modal-title"><?php echo $langs->trans('DigiriskElementMedias') . ' ' . $element['object']->ref ?></h2>
										<div class="wpeo-button open-media-gallery add-media modal-open" value="<?php echo $element['object']->id ?>">
											<input type="hidden" class="type-from" value="digiriskelement"/>
											<span><i class="fas fa-camera"></i>  <?php echo $langs->trans('AddMedia') ?></span>
										</div>
										<div class="modal-close"><i class="fas fa-times"></i></div>
									</div>
									<!-- Modal Content-->
									<div class="modal-content" id="#modalContent<?php echo $element['object']->id ?>">
										<div class="risk-evaluation-container">
											<div class="risk-evaluation-header">
											</div>
											<div class="element-linked-medias element-linked-medias-<?php echo $element['object']->id ?> digirisk-element modal-media-linked">
												<div class="medias"><i class="fas fa-picture-o"></i><?php echo $langs->trans('Medias'); ?></div>
												<?php
												$relativepath = 'digiriskdolibarr/medias/thumbs';
												print digirisk_show_medias_linked('digiriskdolibarr', $conf->digiriskdolibarr->multidir_output[$conf->entity] . '/'.$element['object']->element_type.'/' , 'small', '', 0, 0, 0, 150, 150, 1, 0, 0, $element['object']->element_type, $element['object']);
												?>
											</div>
										</div>
									</div>
									<!-- Modal-Footer -->
									<div class="modal-footer">
										<div class="wpeo-button modal-close button-blue">
											<i class="fas fa-times"></i> <?php echo $langs->trans('CloseModal'); ?>
										</div>
									</div>
								</div>
							</div>
						</div>
					<div class="title" id="scores" value="<?php echo $element['object']->id ?>">
					<?php
						if ($user->rights->digiriskdolibarr->risk->read) : ?>
							<a id="slider" class="linkElement id<?php echo $element['object']->id;?>" href="../digiriskdolibarr/digiriskelement_risk.php?id=<?php echo $element['object']->id; ?>">
								<span class="title-container">
									<span class="ref"><?php echo $element['object']->ref; ?></span>
									<span class="name"><?php echo $element['object']->label; ?></span>
								</span>
							</a>
						<?php else : ?>
							<a id="slider" class="linkElement id<?php echo $element['object']->id;?>" href="../digiriskdolibarr/digiriskelement_card.php?id=<?php echo $element['object']->id; ?>">
								<span class="title-container">
									<span class="ref"><?php echo $element['object']->ref; ?></span>
									<span class="name"><?php echo $element['object']->label; ?></span>
								</span>
							</a>
						<?php endif; ?>
					</div>
					<?php if ($user->rights->digiriskdolibarr->digiriskelement->write) : ?>
						<?php if ($element['object']->element_type == 'groupment') : ?>
							<div class="add-container">
								<a id="newGroupment" href="../digiriskdolibarr/digiriskelement_card.php?action=create&element_type=groupment&fk_parent=<?php echo $element['object']->id; ?>">
									<div
										class="wpeo-button button-secondary button-square-40 wpeo-tooltip-event"
										data-direction="bottom" data-color="light"
										aria-label="<?php echo $langs->trans('NewGroupment'); ?>">
										<strong><?php echo $mod_groupment->prefix; ?></strong>
										<span class="button-add animated fas fa-plus-circle"></span>
									</div>
								</a>
								<a id="newWorkunit" href="../digiriskdolibarr/digiriskelement_card.php?action=create&element_type=workunit&fk_parent=<?php echo $element['object']->id; ?>">
									<div
										class="wpeo-button button-square-40 wpeo-tooltip-event"
										data-direction="bottom" data-color="light"
										aria-label="<?php echo $langs->trans('NewWorkUnit'); ?>">
										<strong><?php echo $mod_workunit->prefix; ?></strong>
										<span class="button-add animated fas fa-plus-circle"></span>
									</div>
								</a>
							</div>
						<?php endif; ?>
					<?php endif; ?>
				</div>
				<ul class="sub-list"><?php display_recurse_tree($element['children']) ?></ul>
			</li>
			<?php if ($element['object']->id == $conf->global->DIGIRISKDOLIBARR_DIGIRISKELEMENT_TRASH) : ?>
				<hr>
			<?php endif; ?>
		<?php }
	}
	} else {
		print $langs->trans('YouDontHaveTheRightToSeeThis');
	}
}

/**
 *  Show tab footer of a card.
 *  Note: $object->next_prev_filter can be set to restrict select to find next or previous record by $form->showrefnav.
 *
 *  @param	Object	$object			Object to show
 *  @param	string	$paramid   		Name of parameter to use to name the id into the URL next/previous link
 *  @param	string	$morehtml  		More html content to output just before the nav bar
 *  @param	int		$shownav	  	Show Condition (navigation is shown if value is 1)
 *  @param	string	$fieldid   		Nom du champ en base a utiliser pour select next et previous (we make the select max and min on this field). Use 'none' for no prev/next search.
 *  @param	string	$fieldref   	Nom du champ objet ref (object->ref) a utiliser pour select next et previous
 *  @param	string	$morehtmlref  	More html to show after ref
 *  @param	string	$moreparam  	More param to add in nav link url.
 *	@param	int		$nodbprefix		Do not include DB prefix to forge table name
 *	@param	string	$morehtmlleft	More html code to show before ref
 *	@param	string	$morehtmlstatus	More html code to show under navigation arrows
 *  @param  int     $onlybanner     Put this to 1, if the card will contains only a banner (this add css 'arearefnobottom' on div)
 *	@param	string	$morehtmlright	More html code to show before navigation arrows
 *  @return	void
 */
function digirisk_banner_tab($object, $paramid, $morehtml = '', $shownav = 1, $fieldid = 'rowid', $fieldref = 'ref', $morehtmlref = '', $moreparam = '', $nodbprefix = 0, $morehtmlleft = '', $morehtmlstatus = '', $onlybanner = 0, $morehtmlright = '')
{
	global $form;

	print '<div class="'.($onlybanner ? 'arearefnobottom ' : 'arearef ').'heightref valignmiddle centpercent">';
	print $form->showrefnav($object, $paramid, $morehtml, $shownav, $fieldid, $fieldref, $morehtmlref, $moreparam, $nodbprefix, $morehtmlleft, $morehtmlstatus, $morehtmlright);
	print '</div>';
	print '<div class="underbanner clearboth"></div>';
}

/**
*  Return a link to the user card (with optionaly the picto)
*  Use this->id,this->lastname, this->firstname
*
*  @param	int		$withpictoimg				Include picto in link (0=No picto, 1=Include picto into link, 2=Only picto, -1=Include photo into link, -2=Only picto photo, -3=Only photo very small)
*  @param	string	$option						On what the link point to ('leave', 'nolink', )
*  @param  integer $infologin      			0=Add default info tooltip, 1=Add complete info tooltip, -1=No info tooltip
*  @param	integer	$notooltip					1=Disable tooltip on picto and name
*  @param	int		$maxlen						Max length of visible user name
*  @param	int		$hidethirdpartylogo			Hide logo of thirdparty if user is external user
*  @param  string  $mode               		''=Show firstname and lastname, 'firstname'=Show only firstname, 'firstelselast'=Show firstname or lastname if not defined, 'login'=Show login
*  @param  string  $morecss            		Add more css on link
*  @param  int     $save_lastsearch_value    	-1=Auto, 0=No save of lastsearch_values when clicking, 1=Save lastsearch_values whenclicking
*  @return	string								String with URL
*/
function getNomUrl($withpictoimg = 0, $option = '', $infologin = 0, $notooltip = 0, $maxlen = 24, $hidethirdpartylogo = 0, $mode = '', $morecss = '', $save_lastsearch_value = -1, $object)
{
	global $langs, $conf, $db, $hookmanager, $user;
	global $dolibarr_main_authentication, $dolibarr_main_demo;
	global $menumanager;

       if (!$user->rights->user->user->lire && $user->id != $user->id) $option = 'nolink';

	if (!empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER) && $withpictoimg) $withpictoimg = 0;

	$result = ''; $label = '';

	if (!empty($user->photo))
	{
		$label .= '<div class="photointooltip">';
		$label .= Form::showphoto('userphoto', $user, 0, 60, 0, 'photowithmargin photologintooltip', 'small', 0, 1); // Force height to 60 so we total height of tooltip can be calculated and collision can be managed
		$label .= '</div><div style="clear: both;"></div>';
	}

	// Info Login
	$label .= '<div class="centpercent">';
	$label .= '<u>'.$langs->trans("User").'</u><br>';
	$label .= '<b>'.$langs->trans('Name').':</b> '.$user->getFullName($langs, '');
	if (!empty($user->login)) $label .= '<br><b>'.$langs->trans('Login').':</b> '.$user->login;
	if (!empty($user->job)) $label .= '<br><b>'.$langs->trans("Job").':</b> '.$user->job;
	$label .= '<br><b>'.$langs->trans("Email").':</b> '.$user->email;
	if (!empty($user->phone)) $label .= '<br><b>'.$langs->trans("Phone").':</b> '.$user->phone;
	if (!empty($user->admin))
		$label .= '<br><b>'.$langs->trans("Administrator").'</b>: '.yn($user->admin);
	if (!empty($user->socid))	// Add thirdparty for external users
	{
		$thirdpartystatic = new Societe($db);
		$thirdpartystatic->fetch($user->socid);
		if (empty($hidethirdpartylogo)) $companylink = ' '.$thirdpartystatic->getNomUrl(2, (($option == 'nolink') ? 'nolink' : '')); // picto only of company
		$company = ' ('.$langs->trans("Company").': '.$thirdpartystatic->name.')';
	}
	$type = ($user->socid ? $langs->trans("External").$company : $langs->trans("Internal"));
	$label .= '<br><b>'.$langs->trans("Type").':</b> '.$type;
	$label .= '<br><b>'.$langs->trans("Status").'</b>: '.$user->getLibStatut(4);
	$label .= '</div>';
	if ($infologin > 0)
	{
		$label .= '<br>';
		$label .= '<br><u>'.$langs->trans("Session").'</u>';
		$label .= '<br><b>'.$langs->trans("IPAddress").'</b>: '.$_SERVER["REMOTE_ADDR"];
		if (!empty($conf->global->MAIN_MODULE_MULTICOMPANY)) $label .= '<br><b>'.$langs->trans("ConnectedOnMultiCompany").':</b> '.$conf->entity.' (user entity '.$user->entity.')';
		$label .= '<br><b>'.$langs->trans("AuthenticationMode").':</b> '.$_SESSION["dol_authmode"].(empty($dolibarr_main_demo) ? '' : ' (demo)');
		$label .= '<br><b>'.$langs->trans("ConnectedSince").':</b> '.dol_print_date($user->datelastlogin, "dayhour", 'tzuser');
		$label .= '<br><b>'.$langs->trans("PreviousConnexion").':</b> '.dol_print_date($user->datepreviouslogin, "dayhour", 'tzuser');
		$label .= '<br><b>'.$langs->trans("CurrentTheme").':</b> '.$conf->theme;
		$label .= '<br><b>'.$langs->trans("CurrentMenuManager").':</b> '.$menumanager->name;
		$s = picto_from_langcode($langs->getDefaultLang());
		$label .= '<br><b>'.$langs->trans("CurrentUserLanguage").':</b> '.($s ? $s.' ' : '').$langs->getDefaultLang();
		$label .= '<br><b>'.$langs->trans("Browser").':</b> '.$conf->browser->name.($conf->browser->version ? ' '.$conf->browser->version : '').' ('.$_SERVER['HTTP_USER_AGENT'].')';
		$label .= '<br><b>'.$langs->trans("Layout").':</b> '.$conf->browser->layout;
		$label .= '<br><b>'.$langs->trans("Screen").':</b> '.$_SESSION['dol_screenwidth'].' x '.$_SESSION['dol_screenheight'];
		if ($conf->browser->layout == 'phone') $label .= '<br><b>'.$langs->trans("Phone").':</b> '.$langs->trans("Yes");
		if (!empty($_SESSION["disablemodules"])) $label .= '<br><b>'.$langs->trans("DisabledModules").':</b> <br>'.join(', ', explode(',', $_SESSION["disablemodules"]));
	}
	if ($infologin < 0) $label = '';

	$url = DOL_URL_ROOT.'/user/card.php?id='.$user->id;
	if ($option == 'leave') $url = DOL_URL_ROOT.'/holiday/list.php?id='.$user->id;

	if ($option != 'nolink')
	{
		// Add param to save lastsearch_values or not
		$add_save_lastsearch_values = ($save_lastsearch_value == 1 ? 1 : 0);
		if ($save_lastsearch_value == -1 && preg_match('/list\.php/', $_SERVER["PHP_SELF"])) $add_save_lastsearch_values = 1;
		if ($add_save_lastsearch_values) $url .= '&save_lastsearch_values=1';
	}

	$linkstart = '<a href="'.$url.'"';
	$linkclose = "";
	if (empty($notooltip))
	{
		if (!empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER))
		{
			$langs->load("users");
			$label = $langs->trans("ShowUser");
			$linkclose .= ' alt="'.dol_escape_htmltag($label, 1).'"';
		}
		$linkclose .= ' title="'.dol_escape_htmltag($label, 1).'"';
		$linkclose .= ' class="classfortooltip'.($morecss ? ' '.$morecss : '').'"';

		/*
		 $hookmanager->initHooks(array('userdao'));
		 $parameters=array('id'=>$user->id);
		 $reshook=$hookmanager->executeHooks('getnomurltooltip',$parameters,$user,$action);    // Note that $action and $object may have been modified by some hooks
		 if ($reshook > 0) $linkclose = $hookmanager->resPrint;
		 */
	}

	$linkstart .= $linkclose.'>';
	$linkend = '</a>';

	//if ($withpictoimg == -1) $result.='<div class="nowrap">';
	$result .= (($option == 'nolink') ? '' : $linkstart);
	if ($withpictoimg)
	{
	  	$paddafterimage = '';
	  	if (abs($withpictoimg) == 1) $paddafterimage = 'style="margin-'.($langs->trans("DIRECTION") == 'rtl' ? 'left' : 'right').': 3px;"';
		// Only picto
		if ($withpictoimg > 0) $picto = '<!-- picto user --><span class="nopadding userimg'.($morecss ? ' '.$morecss : '').'">'.img_object('', 'user', $paddafterimage.' '.($notooltip ? '' : 'class="paddingright classfortooltip"'), 0, 0, $notooltip ? 0 : 1).'</span>';
		// Picto must be a photo
		else $picto = '<!-- picto photo user --><span class="nopadding userimg'.($morecss ? ' '.$morecss : '').'"'.($paddafterimage ? ' '.$paddafterimage : '').'>'.Form::showphoto('userphoto', $user, 0, 0, 0, 'userphoto'.($withpictoimg == -3 ? 'small' : ''), 'mini', 0, 1).'</span>';
		$result .= $picto;
	}
	if ($withpictoimg > -2 && $withpictoimg != 2)
	{
		$initiales = '';
		if (dol_strlen($user->firstname)) {
			$initiales .= str_split($user->firstname, 1)[0];
		}
		if (dol_strlen($user->lastname)) {
			$initiales .= str_split($user->lastname, 1)[0];
		}
		if (empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER)) $result .= '<span class=" nopadding usertext'.((!isset($user->statut) || $user->statut) ? '' : ' strikefordisabled').($morecss ? ' '.$morecss : '').'">';
		if ($mode == 'login') $result .= $initiales;
		else $result .= $initiales;
		if (empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER)) $result .= '</span>';
	}
	$result .= (($option == 'nolink') ? '' : $linkend);
	//if ($withpictoimg == -1) $result.='</div>';

	$result .= $companylink;

	global $action;
	$hookmanager->initHooks(array('userdao'));
	$parameters = array('id'=>$user->id, 'getnomurl'=>$result);
	$reshook = $hookmanager->executeHooks('getNomUrl', $parameters, $user, $action); // Note that $action and $object may have been modified by some hooks
	if ($reshook > 0) $result = $hookmanager->resPrint;
	else $result .= $hookmanager->resPrint;

	return $result;
}

/**
 *	Return clicable name (with picto eventually)
 *
 *	@param	int		$withpicto		0=No picto, 1=Include picto into link, 2=Only picto
 *	@param	string	$option			'withproject' or ''
 *  @param	string	$mode			Mode 'task', 'time', 'contact', 'note', document' define page to link to.
 * 	@param	int		$addlabel		0=Default, 1=Add label into string, >1=Add first chars into string
 *  @param	string	$sep			Separator between ref and label if option addlabel is set
 *  @param	int   	$notooltip		1=Disable tooltip
 *  @param  int     $save_lastsearch_value    -1=Auto, 0=No save of lastsearch_values when clicking, 1=Save lastsearch_values whenclicking
 *	@return	string					Chaine avec URL
 */
function getNomUrlTask($task, $withpicto = 0, $option = '', $mode = 'task', $addlabel = 0, $sep = ' - ', $notooltip = 0, $save_lastsearch_value = -1)
{
	global $conf, $langs, $user;

	if (!empty($conf->dol_no_mouse_hover)) $notooltip = 1; // Force disable tooltips

	$result = '';
	$label = img_picto('', $task->picto).' <u>'.$langs->trans("Task").'</u>';
	if (!empty($task->ref))
		$label .= '<br><b>'.$langs->trans('Ref').':</b> '.$task->ref;
	if (!empty($task->label))
		$label .= '<br><b>'.$langs->trans('LabelTask').':</b> '.$task->label;
	if ($task->date_start || $task->date_end)
	{
		$label .= "<br>".get_date_range($task->date_start, $task->date_end, '', $langs, 0);
	}

	$url = DOL_URL_ROOT.'/projet/tasks/'.$mode.'.php?id='.$task->id.($option == 'withproject' ? '&withproject=1' : '');
	// Add param to save lastsearch_values or not
	$add_save_lastsearch_values = ($save_lastsearch_value == 1 ? 1 : 0);
	if ($save_lastsearch_value == -1 && preg_match('/list\.php/', $_SERVER["PHP_SELF"])) $add_save_lastsearch_values = 1;
	if ($add_save_lastsearch_values) $url .= '&save_lastsearch_values=1';

	$linkclose = '';
	if (empty($notooltip))
	{
		if (!empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER))
		{
			$label = $langs->trans("ShowTask");
			$linkclose .= ' alt="'.dol_escape_htmltag($label, 1).'"';
		}
		$linkclose .= ' title="'.dol_escape_htmltag($label, 1).'"';
		$linkclose .= ' class="classfortooltip nowraponall"';
	} else {
		$linkclose .= ' class="nowraponall"';
	}

	$linkstart = '<a target="_blank" href="'.$url.'"';
	$linkstart .= $linkclose.'>';
	$linkend = '</a>';

	$picto = 'projecttask';

	$result .= $linkstart;
	if ($withpicto) $result .= img_object(($notooltip ? '' : $label), $picto, ($notooltip ? (($withpicto != 2) ? 'class="paddingright"' : '') : 'class="'.(($withpicto != 2) ? 'paddingright ' : '').'classfortooltip"'), 0, 0, $notooltip ? 0 : 1);
	if ($withpicto != 2) $result .= $task->ref;
	$result .= $linkend;
	if ($withpicto != 2) $result .= (($addlabel && $task->label) ? $sep.dol_trunc($task->label, ($addlabel > 1 ? $addlabel : 0)) : '');

	return $result;
}

function show_category_image($object, $upload_dir) {

	global $langs;
	$nbphoto = 0;
	$nbbyrow = 5;

	$maxWidth = 160;
	$maxHeight = 120;

	$pdir = get_exdir($object->id, 2, 0, 0, $object, 'category').$object->id."/photos/";
	$dir = $upload_dir.'/'.$pdir;

	$listofphoto = $object->liste_photos($dir);
	if (is_array($listofphoto) && count($listofphoto))
	{
//		print '<br>';
//		print '<table width="100%" valign="top" align="center">';

		foreach ($listofphoto as $key => $obj)
		{
			$nbphoto++;

//			if ($nbbyrow && ($nbphoto % $nbbyrow == 1)) print '<tr align=center valign=middle border=1>';
//			if ($nbbyrow) print '<td width="'.ceil(100 / $nbbyrow).'%" class="">';


			// Si fichier vignette disponible, on l'utilise, sinon on utilise photo origine
			if ($obj['photo_vignette'])
			{
				$filename = $obj['photo_vignette'];
			} else {
				$filename = $obj['photo'];
			}

			// Nom affiche
			$viewfilename = $obj['photo'];

			// Taille de l'image
			$object->get_image_size($dir.$filename);
			$imgWidth = ($object->imgWidth < $maxWidth) ? $object->imgWidth : $maxWidth;
			$imgHeight = ($object->imgHeight < $maxHeight) ? $object->imgHeight : $maxHeight;

			print '<img border="0" width="'.$imgWidth.'" height="'.$imgHeight.'" src="'.DOL_URL_ROOT.'/viewimage.php?modulepart=category&entity='.$object->entity.'&file='.urlencode($pdir.$filename).'">';

//			if ($nbbyrow) print '</td>';
//			if ($nbbyrow && ($nbphoto % $nbbyrow == 0)) print '</tr>';
		}

		// Ferme tableau
		while ($nbphoto % $nbbyrow)
		{
			$nbphoto++;
		}

//		print '</table>';
	}

	if ($nbphoto < 1)
	{
		print '<div class="opacitymedium">'.$langs->trans("NoPhotoYet")."</div>";
	}

}

/**
* Show header for public page signature
*
* @param  string $title       Title
* @param  string $head        Head array
* @param  int    $disablejs   More content into html header
* @param  int    $disablehead More content into html header
* @param string $arrayofjs Array of complementary js files
* @param string $arrayofcss Array of complementary css files
* @return void
*/
function llxHeaderSignature($title, $head = "", $disablejs = 0, $disablehead = 0, $arrayofjs = '', $arrayofcss = '') {
	global $conf, $mysoc;

	top_htmlhead($head, $title, $disablejs, $disablehead, $arrayofjs, $arrayofcss, 0, 1); // Show html headers

	if (!empty($conf->global->DIGIRISKDOLIBARR_SIGNATURE_SHOW_COMPANY_LOGO)){
		// Define logo and logosmall
		$logosmall = $mysoc->logo_small;
		$logo = $mysoc->logo;
		// Define urllogo
		$urllogo = '';
		if (!empty($logosmall) && is_readable($conf->mycompany->dir_output.'/logos/thumbs/'.$logosmall)) {
			$urllogo = DOL_URL_ROOT.'/viewimage.php?modulepart=mycompany&amp;entity='.$conf->entity.'&amp;file='.urlencode('logos/thumbs/'.$logosmall);
		} elseif (!empty($logo) && is_readable($conf->mycompany->dir_output.'/logos/'.$logo)) {
			$urllogo = DOL_URL_ROOT.'/viewimage.php?modulepart=mycompany&amp;entity='.$conf->entity.'&amp;file='.urlencode('logos/'.$logo);
		}
		// Output html code for logo
		if ($urllogo) {
			print '<div class="center signature-logo">';
			print '<img src="'.$urllogo.'">';
			print '</div>';
		}
		print '<div class="underbanner clearboth"></div>';
	}
}

/**
* Show header for public page ticket
*
* @param  string $title       Title
* @param  string $head        Head array
* @param  int    $disablejs   More content into html header
* @param  int    $disablehead More content into html header
* @param string $arrayofjs Array of complementary js files
* @param string $arrayofcss Array of complementary css files
* @return void
*/
function llxHeaderTicketDigirisk($title, $head = "", $disablejs = 0, $disablehead = 0, $arrayofjs = '', $arrayofcss = '') {
	global $conf, $mysoc;

	top_htmlhead($head, $title, $disablejs, $disablehead, $arrayofjs, $arrayofcss, 0, 1); // Show html headers

	if (!empty($conf->global->DIGIRISKDOLIBARR_TICKET_SHOW_COMPANY_LOGO)){
		// Define logo and logosmall
		$logosmall = $mysoc->logo_small;
		$logo = $mysoc->logo;
		// Define urllogo
		$urllogo = '';
		if (!empty($logosmall) && is_readable($conf->mycompany->dir_output.'/logos/thumbs/'.$logosmall)) {
			$urllogo = DOL_URL_ROOT.'/viewimage.php?modulepart=mycompany&amp;entity='.$conf->entity.'&amp;file='.urlencode('logos/thumbs/'.$logosmall);
		} elseif (!empty($logo) && is_readable($conf->mycompany->dir_output.'/logos/'.$logo)) {
			$urllogo = DOL_URL_ROOT.'/viewimage.php?modulepart=mycompany&amp;entity='.$conf->entity.'&amp;file='.urlencode('logos/'.$logo);
		}
		// Output html code for logo
		if ($urllogo) {
			print '<div class="center signature-logo">';
			print '<img src="'.$urllogo.'">';
			print '</div>';
		}
		print '<div class="underbanner clearboth"></div>';
	}
}

function digirisk_show_medias($modulepart = 'ecm', $sdir, $size = 0, $nbmax = 0, $nbbyrow = 5, $showfilename = 0, $showaction = 0, $maxHeight = 120, $maxWidth = 160, $nolink = 0, $notitle = 0, $usesharelink = 0,$subdir = "")
{
	global $conf, $user, $langs;

	include_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
	include_once DOL_DOCUMENT_ROOT.'/core/lib/images.lib.php';

	$sortfield = 'position_name';
	$sortorder = 'desc';

	$dir = $sdir.'/';
	$pdir = $subdir . '/';


	$return = '<!-- Photo -->'."\n";
	$nbphoto = 0;

	$filearray = dol_dir_list($dir, "files", 0, '', '(\.meta|_preview.*\.png)$', $sortfield, (strtolower($sortorder) == 'desc' ?SORT_DESC:SORT_ASC), 1);
	$j = 0;

	if (count($filearray))
	{
		if ($sortfield && $sortorder)
		{
			$filearray = dol_sort_array($filearray, $sortfield, $sortorder);
		}
		foreach ($filearray as $key => $val)
		{
			if(preg_match('/' . $size . '/', $val['name'])) {

				$file = $val['name'];

				if (image_format_supported($file) >= 0)
				{
					$nbphoto++;

					if ($size == 1 || $size == 'small') {   // Format vignette

						$relativepath = 'digiriskdolibarr/medias/thumbs';
						$modulepart = 'ecm';
						$path = DOL_URL_ROOT.'/document.php?modulepart=' . $modulepart  . '&attachment=0&file=' . str_replace('/', '%2F', $relativepath);
						?>

						<div class="center clickable-photo clickable-photo<?php echo $j; ?>" value="<?php echo $j; ?>" element="risk-evaluation">
							<figure class="photo-image">
								<?php
								$urladvanced = getAdvancedPreviewUrl($modulepart, 'digiriskdolibarr/medias/' . preg_replace('/_' . $size . '/', '', $val['relativename']), 0, 'entity='.$conf->entity); ?>
								<a class="clicked-photo-preview" href="<?php echo $urladvanced; ?>"><i class="fas fa-2x fa-search-plus"></i></a>
								<?php if (image_format_supported($val['name']) >= 0) : ?>
								<?php $fullpath = $path . '/' . $val['relativename'] . '&entity=' . $conf->entity; ?>
								<input class="filename" type="hidden" value="<?php echo preg_replace('/_' . $size . '/', '', $val['name']) ?>">
								<img class="photo photo<?php echo $j ?> maxwidth50" src="<?php echo $fullpath; ?>">
								<?php endif; ?>
							</figure>
							<div class="title"><?php echo preg_replace('/_' . $size . '/', '', $val['name']); ?></div>
						</div><?php
						$j++;
					}
				}
			}
		}
	}

	return $return;
}

function digirisk_show_medias_linked($modulepart = 'ecm', $sdir, $size = 0, $nbmax = 0, $nbbyrow = 5, $showfilename = 0, $showaction = 0, $maxHeight = 120, $maxWidth = 160, $nolink = 0, $notitle = 0, $usesharelink = 0,$subdir = "", $object = null)
{
		global $conf, $user, $langs;

	include_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
	include_once DOL_DOCUMENT_ROOT.'/core/lib/images.lib.php';

	$sortfield = 'position_name';
	$sortorder = 'desc';

	$dir = $sdir.'/'.$object->ref.'/';
	$pdir = $subdir . '/'.$object->ref.'/';

	// Defined relative dir to DOL_DATA_ROOT
	$relativedir = '';
	if ($dir)
	{
		$relativedir = preg_replace('/^'.preg_quote(DOL_DATA_ROOT, '/').'/', '', $dir);
		$relativedir = preg_replace('/^[\\/]/', '', $relativedir);
		$relativedir = preg_replace('/[\\/]$/', '', $relativedir);
	}

	$dirthumb = $dir.'thumbs/';
	$pdirthumb = $pdir.'thumbs/';

	$return = '<!-- Photo -->'."\n";
	$nbphoto = 0;

	$filearray = dol_dir_list($dir, "files", 0, '', '(\.meta|_preview.*\.png)$', $sortfield, (strtolower($sortorder) == 'desc' ?SORT_DESC:SORT_ASC), 1);
	//echo '<pre>'; print_r( $pdirthumb ); echo '</pre>';
	if (count($filearray))
	{
		if ($sortfield && $sortorder)
		{
			$filearray = dol_sort_array($filearray, $sortfield, $sortorder);
		}
		$return .= '<div class=" wpeo-gridlayout grid-4 grid-gap-3 grid-margin-2 valigntop center centpercent" style="height:50%; border: 0; padding: 2px; border-spacing: 2px; border-collapse: separate;">';

		foreach ($filearray as $key => $val)
		{
			$return .= '<div class="media-container">';
			$photo = '';
			$file = $val['name'];

			//if (! utf8_check($file)) $file=utf8_encode($file);	// To be sure file is stored in UTF8 in memory

			//if (dol_is_file($dir.$file) && image_format_supported($file) >= 0)
			if (image_format_supported($file) >= 0)
			{
				$nbphoto++;
				$photo = $file;
				$viewfilename = $file;

				if ($size == 1 || $size == 'small') {   // Format vignette
					// Find name of thumb file
					$photo_vignette = basename(getImageFileNameForSize($dir.$file, '_small'));
					if (!dol_is_file($dirthumb.$photo_vignette)) $photo_vignette = '';

					// Get filesize of original file
					$imgarray = dol_getImageSize($dir.$photo);

					if ($nbbyrow > 0)
					{
						if ($nbphoto == 1) $return .= '<table class="valigntop center centpercent" style="border: 0; padding: 2px; border-spacing: 2px; border-collapse: separate;">';

						if ($nbphoto % $nbbyrow == 1) $return .= '<tr class="center valignmiddle" style="border: 1px">';
						$return .= '<td style="width: '.ceil(100 / $nbbyrow).'%" class="photo">';
					}
					elseif ($nbbyrow < 0) $return .= '<div class="inline-block">';

					$return .= "\n";

					$relativefile = preg_replace('/^\//', '', $pdir.$photo);
					if (empty($nolink))
					{
						$urladvanced = getAdvancedPreviewUrl($modulepart, $relativefile, 0, 'entity='.$conf->entity);
						if ($urladvanced) $return .= '<a href="'.$urladvanced.'">';
						else $return .= '<a href="'.DOL_URL_ROOT.'/viewimage.php?modulepart='.$modulepart.'&entity='.$conf->entity.'&file='.urlencode($pdir.$photo).'" class="aphoto" target="_blank">';
					}

					// Show image (width height=$maxHeight)
					// Si fichier vignette disponible et image source trop grande, on utilise la vignette, sinon on utilise photo origine
					$alt = $langs->transnoentitiesnoconv('File').': '.$relativefile;
					$alt .= ' - '.$langs->transnoentitiesnoconv('Size').': '.$imgarray['width'].'x'.$imgarray['height'];
					if ($notitle) $alt = '';
					if ($usesharelink)
					{
						if ($val['share'])
						{
							if (empty($maxHeight) || $photo_vignette && $imgarray['height'] > $maxHeight)
							{
								$return .= '<!-- Show original file (thumb not yet available with shared links) -->';
								$return .= '<img width="65" height="65" class="photo photowithmargin clicked-photo-preview" height="'.$maxHeight.'" src="'.DOL_URL_ROOT.'/viewimage.php?hashp='.urlencode($val['share']).'" title="'.dol_escape_htmltag($alt).'">';
							}
							else {
								$return .= '<!-- Show original file -->';
								$return .= '<img  width="65" height="65" class="photo photowithmargin clicked-photo-preview" height="'.$maxHeight.'" src="'.DOL_URL_ROOT.'/viewimage.php?hashp='.urlencode($val['share']).'" title="'.dol_escape_htmltag($alt).'">';
							}
						}
						else
						{
							$return .= '<!-- Show nophoto file (because file is not shared) -->';
							$return .= '<img  width="65" height="65" class="photo photowithmargin" height="'.$maxHeight.'" src="'.DOL_URL_ROOT.'/public/theme/common/nophoto.png" title="'.dol_escape_htmltag($alt).'">';
						}
					}
					else
					{
						if (empty($maxHeight) || $photo_vignette && $imgarray['height'] > $maxHeight)
						{
							$return .= '<!-- Show thumb -->';
							$return .= '<img width="'.$maxWidth.'" height="'.$maxHeight.'" class="photo clicked-photo-preview"  src="'.DOL_URL_ROOT.'/viewimage.php?modulepart='.$modulepart.'&entity='.$conf->entity.'&file='.urlencode($pdirthumb.$photo_vignette).'" title="'.dol_escape_htmltag($alt).'">';
						}
						else {
							$return .= '<!-- Show original file -->';
							$return .= '<img width="'.$maxWidth.'" height="'.$maxHeight.'" class="photo photowithmargin  clicked-photo-preview" height="'.$maxHeight.'" src="'.DOL_URL_ROOT.'/viewimage.php?modulepart='.$modulepart.'&entity='.$conf->entity.'&file='.urlencode($pdir.$photo).'" title="'.dol_escape_htmltag($alt).'">';
						}
					}

					if (empty($nolink)) $return .= '</a>';
					$return .= "\n";
					if ($showfilename) $return .= '<br>'.$viewfilename;
					if ($showaction)
					{
						$return .= '<br>';
						// On propose la generation de la vignette si elle n'existe pas et si la taille est superieure aux limites
						if ($photo_vignette && (image_format_supported($photo) > 0) && ($object->imgWidth > $maxWidth || $object->imgHeight > $maxHeight))
						{
							$return .= '<a href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=addthumb&amp;file='.urlencode($pdir.$viewfilename).'">'.img_picto($langs->trans('GenerateThumb'), 'refresh').'&nbsp;&nbsp;</a>';
						}
					}
					$return .= "\n";

					if ($nbbyrow > 0)
					{
						$return .= '</td>';
						if (($nbphoto % $nbbyrow) == 0) $return .= '</tr>';
					}
					elseif ($nbbyrow < 0) $return .= '</div>';
				}

				if (empty($size)) {     // Format origine
					$return .= '<img class="photo photowithmargin" src="'.DOL_URL_ROOT.'/viewimage.php?modulepart='.$modulepart.'&entity='.$conf->entity.'&file='.urlencode($pdir.$photo).'">';

					if ($showfilename) $return .= '<br>'.$viewfilename;
				}

				// On continue ou on arrete de boucler ?
				if ($nbmax && $nbphoto >= $nbmax) break;
			}
			$return .= '<div>
				<div class="wpeo-button button-square-50 button-blue media-gallery-favorite" value="'.$object->id .'">
				<input class="element-linked-id" type="hidden" value="'.($object->id > 0 ? $object->id : 0).'">
				<input class="filename" type="hidden" value="'.$photo.'">
				<i class="'.(GETPOST('favorite') == $photo ? 'fas' : ($object->photo == $photo ? 'fas' : 'far')).' fa-star button-icon"></i>
			</div>
			<div class="wpeo-button button-square-50 button-grey media-gallery-unlink" value="'.$object->id .'">
				<input class="element-linked-id" type="hidden" value="'.($object->id > 0 ? $object->id : 0).'">
				<input class="filename" type="hidden" value="'.$photo.'">
				<i class="fas fa-unlink button-icon"></i>
			</div></div></div>';

		}
		$return .= "</div>\n";

		if ($size == 1 || $size == 'small')
		{
			if ($nbbyrow > 0)
			{
				// Ferme tableau
				while ($nbphoto % $nbbyrow)
				{
					$return .= '<td style="width: '.ceil(100 / $nbbyrow).'%">&nbsp;</td>';
					$nbphoto++;
				}

				if ($nbphoto) $return .= '</table>';
			}
		}
	}
	if (is_object($object)){
		$object->nbphoto = $nbphoto;
	}
	return $return;
}

/**
 * Load list of objects in memory from the database.
 *
 * @param  string      $sortorder    Sort Order
 * @param  string      $sortfield    Sort field
 * @param  int         $limit        limit
 * @param  int         $offset       Offset
 * @param  array       $filter       Filter array. Example array('field'=>'valueforlike', 'customurl'=>...)
 * @param  string      $filtermode   Filter mode (AND or OR)
 * @return array|int                 int <0 if KO, array of pages if OK
 */
function fetchAllSocPeople($sortorder = '', $sortfield = '', $limit = 0, $offset = 0, array $filter = array(), $filtermode = 'AND') {
	global $db;

	dol_syslog(__METHOD__, LOG_DEBUG);

	$records = array();

	$sql = "SELECT c.rowid, c.entity, c.fk_soc, c.ref_ext, c.civility as civility_code, c.lastname, c.firstname,";
	$sql .= " c.address, c.statut, c.zip, c.town,";
	$sql .= " c.fk_pays as country_id,";
	$sql .= " c.fk_departement as state_id,";
	$sql .= " c.birthday,";
	$sql .= " c.poste, c.phone, c.phone_perso, c.phone_mobile, c.fax, c.email,";
	$sql .= " c.socialnetworks,";
	$sql .= " c.photo,";
	$sql .= " c.priv, c.note_private, c.note_public, c.default_lang, c.canvas,";
	$sql .= " c.fk_prospectcontactlevel, c.fk_stcommcontact, st.libelle as stcomm, st.picto as stcomm_picto,";
	$sql .= " c.import_key,";
	$sql .= " c.datec as date_creation, c.tms as date_modification,";
	$sql .= " co.label as country, co.code as country_code,";
	$sql .= " d.nom as state, d.code_departement as state_code,";
	$sql .= " u.rowid as user_id, u.login as user_login,";
	$sql .= " s.nom as socname, s.address as socaddress, s.zip as soccp, s.town as soccity, s.default_lang as socdefault_lang";
	$sql .= " FROM ".MAIN_DB_PREFIX."socpeople as c";
	$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_country as co ON c.fk_pays = co.rowid";
	$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_departements as d ON c.fk_departement = d.rowid";
	$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."user as u ON c.rowid = u.fk_socpeople";
	$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON c.fk_soc = s.rowid";
	$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'c_stcommcontact as st ON c.fk_stcommcontact = st.id';
	$sql .= " WHERE c.entity IN (".getEntity('socpeople').")";
	// Manage filter
	$sqlwhere = array();
	if (count($filter) > 0) {
		foreach ($filter as $key => $value) {
			if ($key == 't.rowid') {
				$sqlwhere[] = $key.'='.$value;
			}
			elseif (strpos($key, 'date') !== false) {
				$sqlwhere[] = $key.' = \''.$db->idate($value).'\'';
			}
			elseif ($key == 'customsql') {
				$sqlwhere[] = $value;
			}
			else {
				$sqlwhere[] = $key.' LIKE \'%'.$db->escape($value).'%\'';
			}
		}
	}
	if (count($sqlwhere) > 0) {
		$sql .= ' AND ('.implode(' '.$filtermode.' ', $sqlwhere).')';
	}

	if (!empty($sortfield)) {
		$sql .= $db->order($sortfield, $sortorder);
	}
	if (!empty($limit)) {
		$sql .= ' '.$db->plimit($limit, $offset);
	}
	$resql = $db->query($sql);
	if ($resql) {
		$num = $db->num_rows($resql);
		$i = 0;
		while ($i < ($limit ? min($limit, $num) : $num))
		{
			$obj = $db->fetch_object($resql);

			$record = new Contact($db);
			$record->setVarsFromFetchObj($obj);

			$records[$record->id] = $record;

			$i++;
		}
		$db->free($resql);

		return $records;
	} else {
		$errors[] = 'Error '.$db->lasterror();
		dol_syslog(__METHOD__.' '.join(',', $errors), LOG_ERR);

		return -1;
	}
}

/**
 *	Return HTML code of the SELECT of list of all contacts (for a third party or all).
 *  This also set the number of contacts found into $this->num
 *
 * @since 9.0 Add afterSelectContactOptions hook
 *
 *	@param	int			$socid      	Id ot third party or 0 for all or -1 for empty list
 *	@param  array|int	$selected   	Array of ID of pre-selected contact id
 *	@param  string		$htmlname  	    Name of HTML field ('none' for a not editable field)
 *	@param  int			$showempty     	0=no empty value, 1=add an empty value, 2=add line 'Internal' (used by user edit), 3=add an empty value only if more than one record into list
 *	@param  string		$exclude        List of contacts id to exclude
 *	@param	string		$limitto		Disable answers that are not id in this array list
 *	@param	integer		$showfunction   Add function into label
 *	@param	string		$moreclass		Add more class to class style
 *	@param	bool		$options_only	Return options only (for ajax treatment)
 *	@param	integer		$showsoc	    Add company into label
 * 	@param	int			$forcecombo		Force to use combo box (so no ajax beautify effect)
 *  @param	array		$events			Event options. Example: array(array('method'=>'getContacts', 'url'=>dol_buildpath('/core/ajax/contacts.php',1), 'htmlname'=>'contactid', 'params'=>array('add-customer-contact'=>'disabled')))
 *  @param	string		$moreparam		Add more parameters onto the select tag. For example 'style="width: 95%"' to avoid select2 component to go over parent container
 *  @param	string		$htmlid			Html id to use instead of htmlname
 *  @param	bool		$multiple		add [] in the name of element and add 'multiple' attribut
 *  @param	integer		$disableifempty Set tag 'disabled' on select if there is no choice
 *	@return	 int						<0 if KO, Nb of contact in list if OK
 */
function digirisk_selectcontacts($socid, $selected = '', $htmlname = 'contactid', $showempty = 0, $exclude = '', $limitto = '', $showfunction = 0, $moreclass = '', $options_only = false, $showsoc = 0, $forcecombo = 0, $events = array(), $moreparam = '', $htmlid = '', $multiple = false, $disableifempty = 0, $exclude_already_add = '')
{
	global $conf, $langs, $hookmanager, $action, $db;

	$langs->loadLangs(array("digiriskdolibarr@digiriskdolibarr", "companies"));

	if (empty($htmlid)) $htmlid = $htmlname;
	$num = 0;

	if ($selected === '') $selected = array();
	elseif (!is_array($selected)) $selected = array($selected);
	$out = '';

	if (!is_object($hookmanager))
	{
		include_once DOL_DOCUMENT_ROOT.'/core/class/hookmanager.class.php';
		$hookmanager = new HookManager($db);
	}

	// We search third parties
	$sql = "SELECT sp.rowid, sp.lastname, sp.statut, sp.firstname, sp.poste, sp.email, sp.phone, sp.phone_perso, sp.phone_mobile, sp.town AS contact_town";
	if ($showsoc > 0 || !empty($conf->global->CONTACT_SHOW_EMAIL_PHONE_TOWN_SELECTLIST)) $sql .= ", s.nom as company, s.town AS company_town";
	$sql .= " FROM ".MAIN_DB_PREFIX."socpeople as sp";
	if ($showsoc > 0 || !empty($conf->global->CONTACT_SHOW_EMAIL_PHONE_TOWN_SELECTLIST)) $sql .= " LEFT OUTER JOIN  ".MAIN_DB_PREFIX."societe as s ON s.rowid=sp.fk_soc";
	$sql .= " WHERE sp.entity IN (".getEntity('socpeople').")";
	if ($socid > 0 || $socid == -1) $sql .= " AND sp.fk_soc=".$socid;
	if (!empty($conf->global->CONTACT_HIDE_INACTIVE_IN_COMBOBOX)) $sql .= " AND sp.statut <> 0";
	$sql .= " ORDER BY sp.lastname ASC";

	//dol_syslog(get_class($this)."::select_contacts", LOG_DEBUG);
	$resql = $db->query($sql);

	if ($resql)
	{
		$num = $db->num_rows($resql);

		if ($conf->use_javascript_ajax && !$forcecombo && !$options_only)
		{
			include_once DOL_DOCUMENT_ROOT.'/core/lib/ajax.lib.php';
			$out .= ajax_combobox($htmlid, $events, $conf->global->CONTACT_USE_SEARCH_TO_SELECT);
		}

		if ($htmlname != 'none' && !$options_only) {
			$out .= '<select class="flat'.($moreclass ? ' '.$moreclass : '').'" id="'.$htmlid.'" name="'.$htmlname.(($num || empty($disableifempty)) ? '' : ' disabled').($multiple ? '[]' : '').'" '.($multiple ? 'multiple' : '').' '.(!empty($moreparam) ? $moreparam : '').'>';
		}

		if (($showempty == 1 || ($showempty == 3 && $num > 1)) && !$multiple) $out .= '<option value="0"'.(in_array(0, $selected) ? ' selected' : '').'>&nbsp;</option>';
		if ($showempty == 2) $out .= '<option value="0"'.(in_array(0, $selected) ? ' selected' : '').'>-- '.$langs->trans("Internal").' --</option>';

		$i = 0;
		if ($num)
		{
			include_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
			$contactstatic = new Contact($db);

			while ($i < $num)
			{
				$obj = $db->fetch_object($resql);

				// Set email (or phones) and town extended infos
				$extendedInfos = '';
				if (!empty($conf->global->CONTACT_SHOW_EMAIL_PHONE_TOWN_SELECTLIST)) {
					$extendedInfos = array();
					$email = trim($obj->email);
					if (!empty($email)) $extendedInfos[] = $email;
					else {
						$phone = trim($obj->phone);
						$phone_perso = trim($obj->phone_perso);
						$phone_mobile = trim($obj->phone_mobile);
						if (!empty($phone)) $extendedInfos[] = $phone;
						if (!empty($phone_perso)) $extendedInfos[] = $phone_perso;
						if (!empty($phone_mobile)) $extendedInfos[] = $phone_mobile;
					}
					$contact_town = trim($obj->contact_town);
					$company_town = trim($obj->company_town);
					if (!empty($contact_town)) $extendedInfos[] = $contact_town;
					elseif (!empty($company_town)) $extendedInfos[] = $company_town;
					$extendedInfos = implode(' - ', $extendedInfos);
					if (!empty($extendedInfos)) $extendedInfos = ' - '.$extendedInfos;
				}

				$contactstatic->id = $obj->rowid;
				$contactstatic->lastname = $obj->lastname;
				$contactstatic->firstname = $obj->firstname;
				if ($obj->statut == 1) {
					if ($htmlname != 'none')
					{
						$disabled = 0;
						 $noTooltip = 0;
						if (is_array($exclude) && count($exclude) && in_array($obj->rowid, $exclude)) $disabled = 1;
						if (is_array($exclude_already_add) && count($exclude_already_add) && in_array($obj->rowid, $exclude_already_add)) $noTooltip = 1;
						if (is_array($limitto) && count($limitto) && !in_array($obj->rowid, $limitto)) $disabled = 1;
						if (!empty($selected) && in_array($obj->rowid, $selected))
						{
							$out .= '<option value="'.$obj->rowid.'"';
							if ($disabled) $out .= ' disabled';
							$out .= ' selected>';
							$out .= $contactstatic->getFullName($langs).$extendedInfos;
							if ($showfunction && $obj->poste) $out .= ' ('.$obj->poste.')';
							if (($showsoc > 0) && $obj->company) $out .= ' - ('.$obj->company.')';
							if ($noTooltip == 0 && $disabled) $out .= ' - ('.$langs->trans('NoEmailContact').')';
							$out .= '</option>';
						} else {
							$out .= '<option value="'.$obj->rowid.'"';
							if ($disabled) $out .= ' disabled';
							$out .= '>';
							$out .= $contactstatic->getFullName($langs).$extendedInfos;
							if ($showfunction && $obj->poste) $out .= ' ('.$obj->poste.')';
							if (($showsoc > 0) && $obj->company) $out .= ' - ('.$obj->company.')';
							if ($noTooltip == 0 && $disabled) $out .= ' - ('.$langs->trans('NoEmailContact').')';
							$out .= '</option>';
						}
					} else {
						if (in_array($obj->rowid, $selected))
						{
							$out .= $contactstatic->getFullName($langs).$extendedInfos;
							if ($showfunction && $obj->poste) $out .= ' ('.$obj->poste.')';
							if (($showsoc > 0) && $obj->company) $out .= ' - ('.$obj->company.')';
						}
					}
				}
				$i++;
			}
		} else {
			$labeltoshow = ($socid != -1) ? ($langs->trans($socid ? "NoContactDefinedForThirdParty" : "NoContactDefined")) : $langs->trans('SelectAThirdPartyFirst');
			$out .= '<option class="disabled" value="-1"'.(($showempty == 2 || $multiple) ? '' : ' selected').' disabled="disabled">';
			$out .= $labeltoshow;
			$out .= '</option>';
		}

		$parameters = array(
			'socid'=>$socid,
			'htmlname'=>$htmlname,
			'resql'=>$resql,
			'out'=>&$out,
			'showfunction'=>$showfunction,
			'showsoc'=>$showsoc,
		);

		//$reshook = $hookmanager->executeHooks('afterSelectContactOptions', $parameters, $this, $action); // Note that $action and $object may have been modified by some hooks

		if ($htmlname != 'none' && !$options_only)
		{
			$out .= '</select>';
		}

		return $out;
	} else {
		dol_print_error($db);
		return -1;
	}
}

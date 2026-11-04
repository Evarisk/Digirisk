/**
 * Initialise l'objet "riskAddStepper" ainsi que la méthode "init" obligatoire pour la bibliothèque DigiriskDolibarr.
 *
 * Fil d'Ariane vertical de la modale d'ajout d'un risque, chaque étape en face de sa section : le danger
 * s'y choisit, il coche les étapes remplies, en résume le contenu et ramène à chacune d'un clic.
 *
 * @since   23.6.0
 * @version 23.6.0
 */
window.digiriskdolibarr.riskAddStepper = {};

/**
 * La méthode appelée automatiquement par la bibliothèque DigiriskDolibarr.
 *
 * @since   23.6.0
 * @version 23.6.0
 *
 * @return {void}
 */
window.digiriskdolibarr.riskAddStepper.init = function() {
	window.digiriskdolibarr.riskAddStepper.event();
};

/**
 * La méthode contenant tous les événements du fil d'Ariane.
 *
 * La cotation passe par risk.haveDataInInput(), qui rafraîchit le fil : la cotation avancée est
 * calculée après un fetch, aucun clic ne la précède de peu.
 *
 * @since   23.6.0
 * @version 23.6.0
 *
 * @return {void}
 */
window.digiriskdolibarr.riskAddStepper.event = function() {
	$( document ).on( 'click', '.risk-add.modal-open', window.digiriskdolibarr.riskAddStepper.openOnStart );
	$( document ).on( 'click', '.risk-add-modal .risk-add-step-button', window.digiriskdolibarr.riskAddStepper.goToStep );
	$( document ).on( 'click', '.risk-add-danger-option', window.digiriskdolibarr.riskAddStepper.selectDanger );
	$( document ).on( 'input', '.risk-add-danger-search', window.digiriskdolibarr.riskAddStepper.filterDangers );
	$( document ).on( 'keydown', '.risk-add-danger-search', window.digiriskdolibarr.riskAddStepper.searchKeydown );
	$( document ).on( 'input change', '.risk-add-modal .modal-content :input', window.digiriskdolibarr.riskAddStepper.onChange );
	$( document ).on( 'click', '.risk-add-modal .select-evaluation-method', window.digiriskdolibarr.riskAddStepper.onChange );
	// mousedown précède le click : un choix qui termine l'étape la fait passer à la suivante après coup
	$( document ).on( 'focusin mousedown', '.risk-add-modal .modal-content', window.digiriskdolibarr.riskAddStepper.followSection );
};

/**
 * À l'ouverture de la modale, commence par le choix du danger tant qu'il n'est pas fait.
 *
 * @since   23.6.0
 * @version 23.6.0
 *
 * @return {void}
 */
window.digiriskdolibarr.riskAddStepper.openOnStart = function() {
	let modal = $( '.risk-add-modal[value="' + $( this ).attr( 'value' ) + '"]' );

	if ( ! modal.find( '.risk-add-step-danger' ).hasClass( 'done' ) ) {
		window.digiriskdolibarr.riskAddStepper.openPanel( modal );
	}
};

/**
 * Rafraîchit le fil après les autres gestionnaires du même événement, qui posent les valeurs lues.
 *
 * @since   23.6.0
 * @version 23.6.0
 *
 * @return {void}
 */
window.digiriskdolibarr.riskAddStepper.onChange = function() {
	let modal = $( this ).closest( '.risk-add-modal' );

	setTimeout( function() {
		window.digiriskdolibarr.riskAddStepper.refresh( modal );
	}, 0 );
};

/**
 * Désigne comme étape en cours celle de la section où l'utilisateur travaille.
 *
 * @since   23.6.0
 * @version 23.6.0
 *
 * @param  {Event} event Le focus ou l'appui dans la modale.
 * @return {void}
 */
window.digiriskdolibarr.riskAddStepper.followSection = function( event ) {
	let target = $( event.target );
	let modal  = target.closest( '.risk-add-modal' );

	modal.find( '.risk-add-step' ).each( function() {
		if ( target.closest( $( this ).data( 'section' ) ).length ) {
			window.digiriskdolibarr.riskAddStepper.setCurrent( modal, $( this ) );
			return false;
		}
	});
};

/**
 * Ouvre le choix du danger, ou amène la section de l'étape cliquée à l'écran et y place le curseur.
 *
 * @since   23.6.0
 * @version 23.6.0
 *
 * @return {void}
 */
window.digiriskdolibarr.riskAddStepper.goToStep = function() {
	let step    = $( this ).closest( '.risk-add-step' );
	let modal   = step.closest( '.risk-add-modal' );
	let content = modal.find( '.modal-content' );

	if ( step.data( 'panel' ) ) {
		if ( modal.find( '.risk-add-danger-panel' ).hasClass( 'active' ) ) {
			window.digiriskdolibarr.riskAddStepper.closePanel( modal );
		} else {
			window.digiriskdolibarr.riskAddStepper.openPanel( modal );
		}
		return;
	}

	window.digiriskdolibarr.riskAddStepper.closePanel( modal );
	window.digiriskdolibarr.riskAddStepper.setCurrent( modal, step );

	let section = content.find( step.data( 'section' ) ).first();
	if ( ! section.length ) {
		return;
	}

	content.stop().animate( { scrollTop: content.scrollTop() + section.offset().top - content.offset().top - 10 }, 200 );
	window.digiriskdolibarr.riskAddStepper.highlightSection( section );

	if ( step.data( 'focus' ) ) {
		section.find( step.data( 'focus' ) ).first().trigger( 'focus' );
	}
};

/**
 * Éclaire brièvement une section du formulaire.
 *
 * @since   23.6.0
 * @version 23.6.0
 *
 * @param  {jQuery} section La section.
 * @return {void}
 */
window.digiriskdolibarr.riskAddStepper.highlightSection = function( section ) {
	section.removeClass( 'risk-add-section-flash' );
	// Retiré puis remis au tour suivant : l'animation repart même si elle tournait encore
	setTimeout( function() {
		section.addClass( 'risk-add-section-flash' ).one( 'animationend', function() {
			section.removeClass( 'risk-add-section-flash' );
		});
	}, 0 );
};

/**
 * Ouvre le choix du danger par-dessus le formulaire. Il n'a pas de croix, qui doublerait celle de la
 * modale : il se referme sur un choix ou d'un clic sur une étape du fil.
 *
 * @since   23.6.0
 * @version 23.6.0
 *
 * @param  {jQuery} modal La modale d'ajout.
 * @return {void}
 */
window.digiriskdolibarr.riskAddStepper.openPanel = function( modal ) {
	let step = modal.find( '.risk-add-step-danger' );

	modal.find( '.risk-add-danger-panel' ).addClass( 'active' );
	step.addClass( 'open' );
	window.digiriskdolibarr.riskAddStepper.setCurrent( modal, step );
	// Après l'ouverture de la modale elle-même, sinon le champ caché ne prend pas le focus
	setTimeout( function() {
		modal.find( '.risk-add-danger-search' ).trigger( 'focus' );
	}, 0 );
};

/**
 * Referme le choix du danger.
 *
 * @since   23.6.0
 * @version 23.6.0
 *
 * @param  {jQuery} modal La modale d'ajout.
 * @return {void}
 */
window.digiriskdolibarr.riskAddStepper.closePanel = function( modal ) {
	modal.find( '.risk-add-danger-panel' ).removeClass( 'active' );
	modal.find( '.risk-add-step-danger' ).removeClass( 'open' );
};

/**
 * Retient le danger cliqué, l'affiche dans la première étape du fil et passe à l'évaluation.
 *
 * @since   23.6.0
 * @version 23.6.0
 *
 * @return {void}
 */
window.digiriskdolibarr.riskAddStepper.selectDanger = function() {
	let option       = $( this );
	let modal        = option.closest( '.risk-add-modal' );
	let step         = modal.find( '.risk-add-step-danger' );
	let previousName = modal.find( '.risk-add-danger-option.selected' ).attr( 'data-name' );

	modal.find( '.risk-add-danger-option.selected' ).removeClass( 'selected' );
	option.addClass( 'selected' );
	step.find( 'input[name="risk_category_id"]' ).val( option.data( 'id' ) );
	step.find( '.risk-add-step-danger-pic' ).attr( 'src', option.find( 'img' ).attr( 'src' ) );

	if ( step.find( '.input-risk-description-prefill' ).val() == 1 ) {
		let description = modal.find( '.risk-description textarea' );
		let newName     = option.attr( 'data-name' );
		// Le nom du danger précédent est remplacé sur place pour garder ce que l'utilisateur a écrit autour
		if ( previousName && description.val().indexOf( previousName ) !== -1 ) {
			description.val( description.val().split( previousName ).join( newName ) );
		} else {
			description.val( newName );
		}
	}

	window.digiriskdolibarr.riskAddStepper.closePanel( modal );
	window.digiriskdolibarr.risk.haveDataInInput( modal.find( '.modal-container' ) );

	let current = modal.find( '.risk-add-step.current' );
	if ( ! current.is( step ) ) {
		window.digiriskdolibarr.riskAddStepper.highlightSection( modal.find( '.modal-content' ).find( current.data( 'section' ) ).first() );
	}
};

/**
 * Ne garde dans le choix que les dangers dont le nom contient la recherche, accents et casse ignorés.
 *
 * @since   23.6.0
 * @version 23.6.0
 *
 * @return {void}
 */
window.digiriskdolibarr.riskAddStepper.filterDangers = function() {
	let modal   = $( this ).closest( '.risk-add-modal' );
	let search  = window.digiriskdolibarr.riskAddStepper.normalize( $( this ).val() );
	let visible = 0;

	modal.find( '.risk-add-danger-option' ).each( function() {
		let match = window.digiriskdolibarr.riskAddStepper.normalize( $( this ).attr( 'data-name' ) ).indexOf( search ) !== -1;
		$( this ).toggleClass( 'filtered-out', ! match );
		visible += match ? 1 : 0;
	});

	modal.find( '.risk-add-danger-empty' ).toggleClass( 'hidden', visible > 0 );
};

/**
 * Entrée dans la recherche choisit le danger s'il n'en reste qu'un.
 *
 * La modale est dans le formulaire de la liste des risques : laissée passer, Entrée l'enverrait.
 *
 * @since   23.6.0
 * @version 23.6.0
 *
 * @param  {KeyboardEvent} event La touche.
 * @return {void}
 */
window.digiriskdolibarr.riskAddStepper.searchKeydown = function( event ) {
	if ( event.key !== 'Enter' ) {
		return;
	}

	event.preventDefault();
	let options = $( this ).closest( '.risk-add-modal' ).find( '.risk-add-danger-option:not(.filtered-out)' );
	if ( options.length === 1 ) {
		options.trigger( 'click' );
	}
};

/**
 * Met un texte en minuscules sans accents, pour comparer une recherche à un nom.
 *
 * @since   23.6.0
 * @version 23.6.0
 *
 * @param  {string} text Le texte.
 * @return {string}
 */
window.digiriskdolibarr.riskAddStepper.normalize = function( text ) {
	return String( text || '' ).normalize( 'NFD' ).replace( /[̀-ͯ]/g, '' ).toLowerCase().trim();
};

/**
 * Marque une étape comme étape en cours.
 *
 * @since   23.6.0
 * @version 23.6.0
 *
 * @param  {jQuery} modal La modale d'ajout.
 * @param  {jQuery} step  L'étape du fil.
 * @return {void}
 */
window.digiriskdolibarr.riskAddStepper.setCurrent = function( modal, step ) {
	modal.find( '.risk-add-step.current' ).not( step ).removeClass( 'current' );
	step.addClass( 'current' );
};

/**
 * Relit le formulaire : coche les étapes remplies et écrit leur résumé.
 *
 * Un choix qui termine l'étape en cours (danger, cotation) passe la main à l'étape suivante encore à
 * faire, les obligatoires d'abord. Une saisie de texte ne le fait pas : l'utilisateur tape encore.
 *
 * @since   23.6.0
 * @version 23.6.0
 *
 * @param  {jQuery} modal La modale d'ajout.
 * @return {void}
 */
window.digiriskdolibarr.riskAddStepper.refresh = function( modal ) {
	// risk.haveDataInInput() passe aussi les modales d'évaluation, qui n'ont pas de fil
	let steps        = modal.filter( '.risk-add-modal' ).find( '.risk-add-step' );
	let advance      = null;
	let previousDone = false;

	steps.each( function() {
		let step           = $( this );
		let summaryElement = step.find( '.risk-add-step-summary' );
		let summary        = window.digiriskdolibarr.riskAddStepper.getSummary( modal, step.data( 'step' ), summaryElement );

		// Le trait qui arrive sur l'étape prend la couleur de l'étape d'avant
		step.toggleClass( 'previous-done', previousDone );
		previousDone = summary !== null;

		if ( summary === null ) {
			step.removeClass( 'done' );
			summaryElement.text( summaryElement.data( 'hint' ) );
			return;
		}

		if ( ! step.hasClass( 'done' ) && step.hasClass( 'current' ) && step.data( 'auto-advance' ) ) {
			advance = step;
		}
		step.addClass( 'done' );
		summaryElement.empty().append( summary );
	});

	if ( advance ) {
		let next = steps.filter( '[data-required]:not(.done)' ).first();
		if ( ! next.length ) {
			next = advance.nextAll( '.risk-add-step:not(.done)' ).first();
		}
		if ( next.length ) {
			window.digiriskdolibarr.riskAddStepper.setCurrent( modal, next );
		}
	}
};

/**
 * Résume ce qui a été rempli pour une étape.
 *
 * @since   23.6.0
 * @version 23.6.0
 *
 * @param  {jQuery} modal          La modale d'ajout.
 * @param  {string} stepName       Le nom de l'étape.
 * @param  {jQuery} summaryElement Le résumé de l'étape, qui porte ses libellés.
 * @return {string|jQuery|null}    Le résumé, null tant que l'étape n'est pas remplie.
 */
window.digiriskdolibarr.riskAddStepper.getSummary = function( modal, stepName, summaryElement ) {
	switch ( stepName ) {
		case 'risk': {
			// Même test que risk.haveDataInInput() : la valeur vaut « undefined » tant qu'aucun danger n'est choisi
			let categoryId = modal.find( 'input[name="risk_category_id"]' ).val();
			if ( ! ( categoryId >= 0 ) ) {
				return null;
			}
			return modal.find( '.risk-add-danger-option[data-id="' + categoryId + '"]' ).attr( 'data-name' ) || '';
		}

		case 'categories': {
			let categories = modal.find( '.risk-categories select' ).val() || [];
			return categories.length ? String( summaryElement.data( 'done' ) ).replace( '%s', categories.length ) : null;
		}

		case 'evaluation': {
			let seuil = modal.find( '.risk-evaluation-seuil' ).val();
			if ( ! ( seuil >= 0 ) ) {
				return null;
			}
			let cotation = modal.find( '.risk-evaluation-method' ).val() === 'advanced'
				? modal.find( '.risk-evaluation-calculated-cotation .risk-evaluation-cotation' )
				: modal.find( '.cotation-standard .selected-cotation' );
			return $( '<span>', {
				'class': 'risk-evaluation-cotation',
				'data-scale': cotation.attr( 'data-scale' ),
				text: cotation.text().trim() || seuil
			});
		}

		case 'task': {
			let label = ( modal.find( '.riskassessment-task-header input[name="label"]' ).val() || '' ).trim();
			return label.length ? label : null;
		}
	}

	return null;
};

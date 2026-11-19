/* Compass editor panel: live Google preview, social card and SEO checks. No dependencies. */
( function () {
	'use strict';

	var C = window.CompassEditor || {};
	var t = C.i18n || {};
	var k = t.checks || {};
	var box = document.getElementById( 'compass-mb' );
	if ( ! box ) {
		return;
	}
	var q = function ( sel ) {
		return box.querySelector( sel );
	};

	/* ---------------------------------------------------------------- tabs */
	var tabs = [].slice.call( box.querySelectorAll( '[role="tab"]' ) );
	function selectTab( tab, focus ) {
		tabs.forEach( function ( o ) {
			var on = o === tab;
			o.classList.toggle( 'is-active', on );
			o.setAttribute( 'aria-selected', on ? 'true' : 'false' );
			o.tabIndex = on ? 0 : -1;
			document.getElementById( o.getAttribute( 'aria-controls' ) ).hidden = ! on;
		} );
		if ( focus ) {
			tab.focus();
		}
	}
	tabs.forEach( function ( tab, i ) {
		tab.addEventListener( 'click', function () {
			selectTab( tab );
		} );
		// Arrow keys, Home and End move between tabs (WAI-ARIA tabs pattern).
		tab.addEventListener( 'keydown', function ( e ) {
			var to = { ArrowRight: i + 1, ArrowLeft: i - 1, Home: 0, End: tabs.length - 1 }[ e.key ];
			if ( undefined !== to ) {
				e.preventDefault();
				selectTab( tabs[ ( to + tabs.length ) % tabs.length ], true );
			}
		} );
	} );

	/* --------------------------------------------------- reading the post */
	function blockEditor() {
		return window.wp && wp.data && wp.data.select && wp.data.select( 'core/editor' );
	}
	function post() {
		var ed = blockEditor();
		if ( ed && ed.getEditedPostAttribute ) {
			var link = ed.getPermalink ? ed.getPermalink() : '';
			return {
				title: ed.getEditedPostAttribute( 'title' ) || '',
				slug: ed.getEditedPostAttribute( 'slug' ) || '',
				content: ed.getEditedPostContent ? ed.getEditedPostContent() : '',
				excerpt: ed.getEditedPostAttribute( 'excerpt' ) || '',
				link: link || '',
			};
		}
		var mce = window.tinymce && tinymce.get( 'content' );
		var titleEl = document.getElementById( 'title' );
		var slugEl = document.getElementById( 'editable-post-name-full' ) || document.getElementById( 'post_name' );
		var linkEl = document.getElementById( 'sample-permalink' );
		var contentEl = document.getElementById( 'content' );
		return {
			title: titleEl ? titleEl.value : '',
			slug: slugEl ? ( slugEl.value || slugEl.textContent ) : '',
			content: mce && ! mce.isHidden() ? mce.getContent() : ( contentEl ? contentEl.value : '' ),
			excerpt: ( document.getElementById( 'excerpt' ) || {} ).value || '',
			link: linkEl ? linkEl.textContent.trim() : '',
		};
	}

	/* ------------------------------------------------------------ helpers */
	function plain( html ) {
		var d = document.createElement( 'div' );
		d.innerHTML = String( html ).replace( /<!--[\s\S]*?-->/g, ' ' ).replace( /<(script|style)\b[\s\S]*?<\/\1>/gi, ' ' );
		return ( d.textContent || '' ).replace( /\s+/g, ' ' ).trim();
	}
	var KHMER = /[ក-៿]/g;
	function words( text ) {
		var khmer = ( text.match( KHMER ) || [] ).length;
		var rest = text.replace( /[ក-៿᧠-᧿]+/g, ' ' );
		var latin = ( rest.match( /[\p{L}\p{N}][\p{L}\p{N}'’\-]*/gu ) || [] ).length;
		return latin + Math.round( khmer / 5 );
	}
	function lc( s ) {
		return String( s || '' ).toLocaleLowerCase();
	}
	function count( hay, needle ) {
		if ( ! needle ) {
			return 0;
		}
		var n = 0;
		var i = hay.indexOf( needle );
		while ( i !== -1 ) {
			n++;
			i = hay.indexOf( needle, i + needle.length );
		}
		return n;
	}
	function slugify( s ) {
		return lc( s ).trim().replace( /[\s_]+/g, '-' ).replace( /[^\p{L}\p{N}\-]+/gu, '' );
	}
	function fill( tpl, p ) {
		var out = String( tpl )
			.split( '%title%' ).join( p.title || t.untitled )
			.split( '%sitename%' ).join( C.sitename )
			.split( '%tagline%' ).join( C.tagline )
			.split( '%sep%' ).join( C.sep )
			.split( '%year%' ).join( String( new Date().getFullYear() ) )
			.replace( /%[a-z_]+%/g, '' );
		var sep = C.sep.replace( /[.*+?^${}()|[\]\\]/g, '\\$&' );
		return out.replace( new RegExp( '(\\s*' + sep + '\\s*){2,}', 'g' ), ' ' + C.sep + ' ' ).replace( new RegExp( '^\\s*' + sep + '\\s*|\\s*' + sep + '\\s*$', 'g' ), '' ).replace( /\s+/g, ' ' ).trim();
	}
	function safeDecode( s, fn ) {
		try {
			return fn( s );
		} catch ( e ) {
			return s; // A malformed %-escape must not break the panel.
		}
	}
	function cut( s, n ) {
		return s.length > n ? s.slice( 0, n - 1 ).replace( /\s+\S*$/, '' ) + '…' : s;
	}
	function fmt( s ) {
		var args = [].slice.call( arguments, 1 );
		var i = 0;
		return s.replace( /%(\d\$)?[sd]/g, function ( m, pos ) {
			return String( pos ? args[ parseInt( pos, 10 ) - 1 ] : args[ i++ ] );
		} ).replace( '%%', '%' );
	}
	function setMeter( name, len, lo, hi, max ) {
		var bar = q( '[data-meter="' + name + '"]' );
		var pct = Math.min( 100, Math.round( 100 * len / max ) );
		bar.style.width = pct + '%';
		bar.className = len === 0 ? '' : ( len < lo ? 'is-ok' : ( len > hi ? 'is-poor' : 'is-good' ) );
		q( '[data-count="' + name + '"]' ).textContent = len ? fmt( t.chars, len, hi ) : '';
	}

	/* ------------------------------------------------------------- render */
	var last = '';
	function update() {
		var p = post();
		var titleIn = q( '#compass-title' ).value.trim();
		var descIn = q( '#compass-desc' ).value.trim();
		var focus = lc( q( '#compass-focus' ).value.trim() );
		var noindex = q( '[data-noindex]' ).checked;
		var key = JSON.stringify( [ p, titleIn, descIn, focus, noindex, q( '#compass-og-title' ).value, q( '#compass-og-desc' ).value, q( '#compass-og-image' ).value ] );
		if ( key === last ) {
			return;
		}
		last = key;

		var text = plain( p.content );
		var seoTitle = fill( titleIn || C.template, p );
		var autoDesc = cut( plain( p.excerpt ) || text, 156 );
		var desc = descIn ? fill( descIn, p ) : autoDesc;
		var url = p.link || C.home;

		// Google preview.
		q( '[data-preview="title"]' ).textContent = cut( seoTitle, 62 );
		q( '[data-preview="desc"]' ).textContent = cut( desc, 160 );
		q( '[data-preview="url"]' ).textContent = safeDecode( url, decodeURI ).replace( /^https?:\/\//, '' ).replace( /\/$/, '' ).split( '/' ).join( ' › ' );
		setMeter( 'title', seoTitle.length, 30, 60, 70 );
		setMeter( 'desc', descIn ? desc.length : 0, 120, 158, 170 );

		// Social card.
		q( '[data-social="title"]' ).textContent = q( '#compass-og-title' ).value || p.title || t.untitled;
		q( '[data-social="desc"]' ).textContent = cut( q( '#compass-og-desc' ).value || desc, 120 );
		var img = q( '#compass-og-image' ).value;
		var imgBox = q( '[data-social="img"]' );
		imgBox.style.backgroundImage = img ? 'url("' + img.replace( /"/g, '%22' ) + '")' : '';
		imgBox.classList.toggle( 'has-img', !! img );

		// Checks.
		var list = [];
		var add = function ( state, msg ) {
			list.push( [ state, msg ] );
		};
		if ( noindex ) {
			add( 'poor', k.noindex );
		}
		var tl = seoTitle.length;
		add( tl < 30 ? 'ok' : ( tl > 60 ? 'ok' : 'good' ), fmt( k.titleLen, tl < 30 ? k.tooShort : ( tl > 60 ? k.tooLong : k.fine ) ) );
		if ( ! descIn ) {
			add( 'ok', k.descMissing );
		} else {
			var dl = desc.length;
			add( dl < 120 ? 'ok' : ( dl > 160 ? 'ok' : 'good' ), fmt( k.descLen, dl < 120 ? k.tooShort : ( dl > 160 ? k.tooLong : k.fine ) ) );
		}
		var n = words( text );
		add( n < 150 ? 'poor' : ( n < 300 ? 'ok' : 'good' ), fmt( k.words, n ) );

		var doc = document.createElement( 'div' );
		doc.innerHTML = p.content;
		var imgs = [].slice.call( doc.querySelectorAll( 'img' ) );
		var noAlt = imgs.filter( function ( i ) {
			return ! ( i.getAttribute( 'alt' ) || '' ).trim();
		} ).length;
		if ( imgs.length ) {
			add( noAlt ? 'ok' : 'good', fmt( k.imgAlt, noAlt ) );
		}
		var links = [].slice.call( doc.querySelectorAll( 'a[href]' ) ).map( function ( a ) {
			return a.getAttribute( 'href' );
		} );
		var host = C.home.replace( /^https?:\/\//, '' ).split( '/' )[ 0 ];
		var internal = links.filter( function ( h ) {
			return /^\//.test( h ) || h.indexOf( host ) !== -1;
		} ).length;
		add( internal ? 'good' : 'ok', fmt( k.internal, internal ) );
		add( links.length - internal ? 'good' : 'ok', fmt( k.external, links.length - internal ) );

		if ( focus ) {
			var body = lc( text );
			var first = lc( plain( ( doc.querySelector( 'p' ) || {} ).innerHTML || text.slice( 0, 400 ) ) );
			var heads = lc( [].slice.call( doc.querySelectorAll( 'h2,h3' ) ).map( function ( h ) {
				return h.textContent;
			} ).join( ' ' ) );
			var alts = lc( imgs.map( function ( i ) {
				return i.getAttribute( 'alt' ) || '';
			} ).join( ' ' ) );
			var slug = lc( safeDecode( p.slug || '', decodeURIComponent ) );
			add( lc( seoTitle ).indexOf( focus ) !== -1 ? 'good' : 'poor', k.kpTitle );
			add( lc( desc ).indexOf( focus ) !== -1 ? 'good' : 'poor', k.kpDesc );
			add( slug && slug.indexOf( slugify( focus ) ) !== -1 ? 'good' : 'ok', k.kpSlug );
			add( first.indexOf( focus ) !== -1 ? 'good' : 'ok', k.kpIntro );
			add( heads.indexOf( focus ) !== -1 ? 'good' : 'ok', k.kpHeading );
			var times = count( body, focus );
			var density = n ? ( 100 * times * Math.max( 1, words( focus ) ) ) / n : 0;
			add( times === 0 ? 'poor' : ( density > 3 ? 'ok' : ( density < 0.5 ? 'ok' : 'good' ) ), fmt( k.kpDensity, times, density.toFixed( 1 ) ) );
			if ( imgs.length ) {
				add( alts.indexOf( focus ) !== -1 ? 'good' : 'ok', k.kpAlt );
			}
		}

		var pts = 0;
		list.forEach( function ( c ) {
			pts += 'good' === c[ 0 ] ? 2 : ( 'ok' === c[ 0 ] ? 1 : 0 );
		} );
		var ratio = list.length ? pts / ( 2 * list.length ) : 0;
		var anyPoor = list.some( function ( c ) {
			return 'poor' === c[ 0 ];
		} );
		// "Good" needs a keyphrase and no red checks; one red check caps the score at "Needs work".
		var score = noindex ? 'poor' : ( ratio >= 0.75 && focus && ! anyPoor ? 'good' : ( ratio >= 0.5 ? 'ok' : 'poor' ) );
		var ul = q( '[data-checks]' );
		ul.textContent = '';
		var order = { poor: 0, ok: 1, good: 2 };
		list.sort( function ( a, b ) {
			return order[ a[ 0 ] ] - order[ b[ 0 ] ];
		} ).forEach( function ( c ) {
			var li = document.createElement( 'li' );
			li.className = 'is-' + c[ 0 ];
			var sr = document.createElement( 'span' );
			sr.className = 'screen-reader-text';
			sr.textContent = ( 'good' === c[ 0 ] ? t.good : ( 'ok' === c[ 0 ] ? t.ok : t.poor ) ) + ': ';
			li.appendChild( sr );
			li.appendChild( document.createTextNode( c[ 1 ] ) );
			ul.appendChild( li );
		} );
		if ( ! focus ) {
			var hint = document.createElement( 'li' );
			hint.className = 'is-hint';
			hint.textContent = t.noFocus;
			ul.appendChild( hint );
		}
		var badge = q( '[data-score]' );
		badge.textContent = t[ score ];
		badge.className = 'compass-mb__score is-' + score;
		q( '[data-score-dot]' ).className = 'compass-mb__dot is-' + score;
		q( '[data-score-input]' ).value = score;
	}

	box.addEventListener( 'input', update );
	box.addEventListener( 'change', update );
	window.setInterval( update, 1500 ); // Picks up edits to the title and content.
	update();

	/* -------------------------------------------------------- image picker */
	var pick = q( '[data-pick-image]' );
	if ( pick && window.wp && wp.media ) {
		var frame;
		pick.addEventListener( 'click', function () {
			frame = frame || wp.media( { title: t.chooseImage, button: { text: t.useImage }, library: { type: 'image' }, multiple: false } );
			frame.off( 'select' ).on( 'select', function () {
				var a = frame.state().get( 'selection' ).first().toJSON();
				var size = a.sizes && ( a.sizes.large || a.sizes.full );
				q( '#compass-og-image' ).value = size ? size.url : a.url;
				update();
			} );
			frame.open();
		} );
	}
} )();

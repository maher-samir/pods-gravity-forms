<?php
/**
 * Add Pods GF integration for a specific form
 *
 * @param string|Pods Pod name (or Pods object)
 * @param int $form_id GF Form ID
 * @param array $options Form options for integration
 */
function pods_gf( $pod = null, $form_id = null, $options = array() ) {

	require_once( PODS_GF_DIR . 'includes/Pods_GF.php' );

	if ( null !== $pod || null !== $form_id || array() !== $options ) {
		return new Pods_GF( $pod, $form_id, $options );
	}

}

/**
 * Setup Pods_GF_UI object
 *
 * @param array $options Pods_GF_UI option overrides
 *
 * @return Pods_GF_UI
 */
function pods_gf_ui( $options = array() ) {

	require_once( PODS_GF_DIR . 'includes/Pods_GF_UI.php' );

	return new Pods_GF_UI( $options );

}

function pods_gf_ui_shortcode( $args, $content = '' ) {

	/**
	 * @var $pods_gf_ui Pods_GF_UI
	 */
	global $pods_gf_ui;

	if ( is_object( $pods_gf_ui ) ) {
		ob_start();

		$pods_gf_ui->ui();

		return ob_get_clean();
	}

	return '';

}

/**
 * Init Pods GF UI if there's a config to run
 */
function pods_gf_ui_init() {

	/**
	 * @var $pods_gf_ui Pods_GF_UI
	 */
	global $pods_gf_ui, $pods_gf_ui_loaded, $post;

	do_action( 'pods_gf_init' );

	$options = array();

	$path = explode( '?', $_SERVER[ 'REQUEST_URI' ] );
	$path = explode( '#', $path[ 0 ] );
	$path = trim( $path[ 0 ], '/' );

	$page = null;

	if ( is_singular() ) {
		if ( is_object( $post ) && ( is_single( $post ) || is_page( $post ) ) ) {
			$page = $post->post_name;
		}
		else {
			wp_reset_postdata();

			if ( is_object( $post ) ) {
				$page = $post->post_name;
			}
		}
	}

	// Root
	if ( strlen( $path ) < 1 ) {
		$uri = '/';

		$options = apply_filters( 'pods_gf_ui_init=' . $uri, $options, $uri, $page );
	}
	// Pages and wildcards
	else {
		$uri = '/' . $path . '/';

		$exploded_path = array_reverse( explode( '/', $path ) );
		$exploded_w = $exploded_path;
		$total = count( $exploded_path );

		foreach ( $exploded_path as $k => $exploded ) {
			if ( $k == ( $total - 1 ) ) {
				break;
			}

			$exploded_w[ $k ] = '*';

			$wildcard_uri = '/' . implode( '/', array_reverse( $exploded_w ) ) . '/';

			$options = apply_filters( 'pods_gf_ui_init=' . $wildcard_uri, $options, $uri, $page );

			if ( !is_array( $options ) ) {
				break;
			}
		}

		if ( is_array( $options ) ) {
			$options = apply_filters( 'pods_gf_ui_init=' . $uri, $options, $uri, $page );
		}
	}

	if ( is_array( $options ) ) {
		$options = apply_filters( 'pods_gf_ui_init', $options, $uri, $page );
	}

	// Bail on processing
	if ( empty( $options ) ) {
		return;
	}

	$pods_gf_ui = pods_gf_ui( $options );

	$pods_gf_ui_loaded = array(
		'options' => $options,
		'uri' => $uri,
		'page' => $page
	);

	add_action( 'wp', 'pods_gf_ui_loaded' );

	// Add content handler
	add_filter( 'the_content', 'pods_gf_ui_content' );

}

function pods_gf_ui_loaded() {

	global $pods_gf_ui, $pods_gf_ui_loaded;

	if ( empty( $pods_gf_ui ) || empty( $pods_gf_ui_loaded ) ) {
		return;
	}

	do_action( 'pods_gf_ui_loaded', $pods_gf_ui, $pods_gf_ui_loaded[ 'options' ], $pods_gf_ui_loaded[ 'uri' ], $pods_gf_ui_loaded[ 'page' ] );

	$pods_gf_ui_loaded = null;

}

/**
 * Ouput Pods GF UI if there's a config set for the page
 *
 * @param string $content
 * @param int $post_id
 *
 * @return string Content
 */
function pods_gf_ui_content( $content, $post_id = 0 ) {

	if ( !apply_filters( 'pods_gf_ui_content_filter', true, $post_id ) ) {
		return $content;
	}

	global $post;

	if ( empty( $post_id ) && is_object( $post ) ) {
		$post_id = $post->ID;
	}

	if ( false === strpos( $content, '[pods-gf-ui' ) && !empty( $post_id ) && ( is_single( $post_id ) || is_page( $post_id ) ) ) {
		$content .= "\n" . pods_gf_ui_shortcode( array(), '' );
	}

	return $content;

}

/**
 * Detect if there's a shortcode currently set in the content, if so, run it
 */
function pods_gf_ui_detect_shortcode() {

	/**
	 * @var $pods_gf_ui Pods_GF_UI
	 */
	global $pods_gf_ui;

	if ( !is_object( $pods_gf_ui ) && is_singular() ) {
		global $post;

        $form_id = (int) pods_v( 'gform_submit', 'post' );

		if ( 0 < $form_id && preg_match( '/\[pods\-gf\-ui/i', $post->post_content ) ) {
			$form_info = GFFormsModel::get_form( $form_id );

			if ( !empty( $form_info ) && $form_info->is_active ) {
				$GLOBALS[ 'pods-gf-ui-off' ] = true;

				do_shortcode( $post->post_content );

				unset( $GLOBALS[ 'pods-gf-ui-off' ] );
			}
		}
	}

}

/**
 * Run Admin AJAX for Save for Later
 */
function pods_gf_save_for_later_ajax() {

	require_once( PODS_GF_DIR . 'includes/Pods_GF.php' );

	Pods_GF::gf_save_for_later_ajax();

}
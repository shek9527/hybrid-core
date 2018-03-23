<?php

namespace Hybrid;

/**
 * Returns a view object.
 *
 * @since  1.0.0
 * @access public
 * @param  string        $name
 * @param  array|string  $slugs
 * @param  array         $data
 * @return object
 */
function view( $name, $slugs = [], $data = [] ) {

	return new View( $name, $slugs, new Collection( $data ) );
}

/**
 * Outputs a view template.
 *
 * @since  1.0.0
 * @access public
 * @param  string        $name
 * @param  array|string  $slugs
 * @param  array         $data
 * @return void
 */
function render_view( $name, $slugs = [], $data = [] ) {

	view( $name, $slugs, $data )->render();
}

/**
 * Returns a view template as a string.
 *
 * @since  1.0.0
 * @access public
 * @param  string        $name
 * @param  array|string  $slugs
 * @param  array         $data
 * @return string
 */
function fetch_view( $name, $slugs = [], $data = [] ) {

	return view( $name, $slugs, $data )->fetch();
}

/**
 * Wrapper for the core WP `locate_template()` function. Runs the templates
 * through `filter_templates()` to change the file paths.
 *
 * @since  1.0.0
 * @access public
 * @param  array|string  $templates
 * @param  bool          $load
 * @param  bool          $require_once
 * @return string
 */
function locate_template( $templates, $load = false, $require_once = true  ) {

	return \locate_template( filter_templates( (array) $templates ), $load, $require_once );
}

/**
 * Filters an array of templates and prefixes them with the
 * `/resources/views/` file path.
 *
 * @since  1.0.0
 * @access public
 * @param  array  $templates
 * @return array
 */
function filter_templates( $templates ) {

	array_walk( $templates, function( &$template, $key ) {

	//	$path = 'resources/views';
		$path = config( 'view' )->path;

		$template = ltrim( str_replace( $path, '', $template ), '/' );

		$template = "{$path}/{$template}";
	} );

	return $templates;
}

/**
 * Returns a configuration object.
 *
 * @since  1.0.0
 * @access public
 * @param  string  $name
 * @return object
 */
function config( $name ) {

	return app()->get( "config.{$name}" );
}

/**
 * Wrapper function for the `Collection` class.
 *
 * @since  1.0.0
 * @access public
 * @param  array   $items
 * @return object
 */
function collect( $items = [] ) {

	return new Collection( $items );
}

/**
 * Returns a new `Pagination` object.
 *
 * @since  1.0.0
 * @access public
 * @param  array  $args
 * @return object
 */
function pagination( $args = [] ) {

	return new Pagination( $args );
}

/**
 * Outputs the posts pagination.
 *
 * @since  1.0.0
 * @access public
 * @return void
 */
function posts_pagination( $args = [] ) {

	echo pagination( $args )->fetch();
}

/**
 * Single post pagination. This is a replacement for `wp_link_pages()`
 * using our `Pagination` class.
 *
 * @since  1.0.0
 * @access public
 * @param  array  $args
 * @global int    $page
 * @global int    $numpages
 * @global bool   $multipage
 * @global bool   $more
 * @global object $wp_rewrite
 * @return void
 */
function singular_pagination( $args = [] ) {
	global $page, $numpages, $multipage, $more, $wp_rewrite;

	if ( ! $multipage ) {
		return;
	}

	$url_parts = explode( '?', html_entity_decode( get_permalink() ) );
	$base      = trailingslashit( $url_parts[0] ) . '%_%';

	$format  = $wp_rewrite->using_index_permalinks() && ! strpos( $base, 'index.php' ) ? 'index.php/' : '';
	$format .= $wp_rewrite->using_permalinks() ? user_trailingslashit( '%#%' ) : '?page=%#%';

	$args = (array) $args + [
		'base'    => $base,
		'format'  => $format,
		'current' => ! $more && 1 === $page ? 0 : $page,
		'total'   => $numpages
	];

	echo pagination( $args )->fetch();
}

/**
 * Adds theme support for features that themes should be supporting.  Also, removes
 * theme supported features from themes in the case that a user has a plugin installed
 * that handles the functionality.
 *
 * @since  5.0.0
 * @access public
 * @return void
 */
add_action( 'after_setup_theme', function() {

	// Automatically add <title> to head.
	add_theme_support( 'title-tag' );

	// Adds core WordPress HTML5 support.
	add_theme_support( 'html5', array( 'caption', 'comment-form', 'comment-list', 'gallery', 'search-form' ) );

	// Remove support for the the Breadcrumb Trail extension if the plugin is installed.
	if ( function_exists( 'breadcrumb_trail' ) || class_exists( 'Breadcrumb_Trail' ) )
		remove_theme_support( 'breadcrumb-trail' );

	// Remove support for the the Cleaner Gallery extension if the plugin is installed.
	if ( function_exists( 'cleaner_gallery' ) || class_exists( 'Cleaner_Gallery' ) )
		remove_theme_support( 'cleaner-gallery' );

	// Remove support for the the Get the Image extension if the plugin is installed.
	if ( function_exists( 'get_the_image' ) || class_exists( 'Get_The_Image' ) )
		remove_theme_support( 'get-the-image' );

}, 15 );

/**
 * Loads the framework files supported by themes.  Functionality in these files should
 * not be expected within the theme setup function.
 *
 * @since  5.0.0
 * @access public
 * @return void
 */
add_action( 'after_setup_theme', function() {

	// Load the template hierarchy if supported.
	\require_if_theme_supports( 'hybrid-core-template-hierarchy', app()->dir . 'inc/class-template-hierarchy.php' );

	// Load the post format functionality if post formats are supported.
	\require_if_theme_supports( 'post-formats', app()->dir . 'inc/functions-formats.php' );
	\require_if_theme_supports( 'post-formats', app()->dir . 'inc/class-chat.php'        );

	// Load the Theme Layouts extension if supported.
	\require_if_theme_supports( 'theme-layouts', app()->dir . 'inc/class-layout.php'      );
	\require_if_theme_supports( 'theme-layouts', app()->dir . 'inc/functions-layouts.php' );

	// Load the deprecated functions if supported.
	\require_if_theme_supports( 'hybrid-core-deprecated', app()->dir . 'inc/functions-deprecated.php' );

	// Load admin files.
	if ( is_admin() && current_theme_supports( 'theme-layouts' ) ) {
		require_once( app()->dir . 'admin/class-post-layout.php' );
		require_once( app()->dir . 'admin/class-term-layout.php' );
	}

}, 20 );

/**
 * Load extensions (external projects).  Extensions are projects that are included
 * within the framework but are not a part of it.  They are external projects
 * developed outside of the framework.  Themes must use `add_theme_support( $extension )`
 * to use a specific extension within the theme.
 *
 * @since  5.0.0
 * @access public
 * @return void
 */
 add_action( 'after_setup_theme', function() {

	require_if_theme_supports( 'breadcrumb-trail', app()->dir . 'ext/breadcrumb-trail.php' );
	require_if_theme_supports( 'cleaner-gallery',  app()->dir . 'ext/cleaner-gallery.php'  );
	require_if_theme_supports( 'get-the-image',    app()->dir . 'ext/get-the-image.php'    );

}, 20 );

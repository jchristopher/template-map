<?php
/*
Plugin Name: Template Map
Plugin URI: http://wordpress.org/plugins/template-map/
Description: Automagic mapping of Page Templates to post IDs to facilitate better dynamic link generation
Version: 1.0
Author: Jonathan Christopher
Author URI: http://mondaybynoon.com/
Text Domain: templatemap

Copyright 2014 Jonathan Christopher

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, see <http://www.gnu.org/licenses/>.
*/

// exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class TemplateMap establishes a mapping of post IDs based on their Page Template
 */
class TemplateMap {

	/**
	 * @var TemplateMap Singleton
	 */
	private static $instance;

	/**
	 * @var array Internal cache where the key is the template, value is the determined post ID using that Page Template
	 */
	public $cache = array();

	/**
	 * Using a singleton so as to take advantage of post ID caches
	 *
	 * @return TemplateMap
	 */
	public static function instance() {
		if ( ! isset( self::$instance ) && ! ( self::$instance instanceof TemplateMap ) ) {
			self::$instance = new TemplateMap;
		}
		return self::$instance;
	}

	/**
	 * Constructor
	 */
	function __construct() {
		add_action( 'init', array( $this, 'set_sections_from_templates' ) );
	}

	/**
	 * When working with multiple environments you can't *really* rely on post IDs
	 * when implementing various logic (e.g. checking to see if you're in a site section
	 * so as to echo a 'current' class for styling) and in the same vein you can't
	 * completely rely on the slug either. You can however dynamically retrieve a post
	 * ID by querying for a post with a certain Page Template (which is more often than
	 * not the case when implementing)
	 *
	 * @param array $sections
	 */
	public function set_sections_from_templates() {
		$templates = wp_get_theme()->get_page_templates();
		foreach ( array_keys( $templates ) as $template ) {
			$args = array(
				'posts_per_page'    => 1,
				'post_type'         => 'page',
				'fields'            => 'ids',
				'meta_query'    => array(
					array(
						'key'       => '_wp_page_template',
						'value'     => sanitize_text_field( $template ),
					),
				),
			);
			$parents = new WP_Query( $args );
			$parent = empty( $parents->posts ) ? false : $parents->posts[0];

			if ( $parent ) {
				$this->cache[ $template ] = absint( $parent );
			} else {
				$this->cache[ $template ] = false;
			}
		}
	}

	/**
	 * There isn't always a Page Template to utilize so this method allows you
	 * to manually define a parent based on a hardcoded post ID, not ideal but
	 * in place as a fallback for edge cases (recommended to make a custom Page
	 * Template anyway)
	 *
	 * This method is designed to be used in your theme's functions.php (or otherwise) as
	 * you will need to hardcode your Page Template filename and post ID
	 *
	 * @param string $template
	 * @param int    $id The post ID of the parent
	 */
	public function set_section_from_id( $template = '', $id = 0 ) {
		if ( empty( $template ) || empty ( $id ) ) {
			return;
		}
		$this->cache[ $template ] = absint( $id );
	}

	/**
	 * Retrieve a post ID based on the page template it's using
	 *
	 * @param string $template The template for which to retrieve a post ID
	 *
	 * @return bool|int The post ID
	 */
	public function get_id_from_template( $template = '' ) {
		$id = array_key_exists( $template, $this->cache ) ? $this->cache[ $template ] : false;

		return $id;
	}

	/**
	 * Check to see if the current page is within the submitted 'section'. Section being
	 * defined at it's root by the Page with the submitted 'section' as it's Page Template.
	 * Child Pages of the parent are considered within the 'section', as are developer-filtered
	 * Custom Post Type singulars and registered taxonomy archives
	 *
	 * @param $section string The 'section' to check within
	 *
	 * @return bool Whether the current page is within the submitted 'section'
	 */
	function maybe_in_section( $section ) {
		global $post;

		// make sure the section has been registered
		if ( ! array_key_exists( $section, $this->cache ) ) {
			return false;
		}

		// an empty section means the front page
		if ( empty( $section ) && ! is_front_page() ) {
			return false;
		} elseif( empty( $section ) && is_front_page() ) {
			return true;
		}

		$in_section = false;
		$parent_id = $this->get_id_from_template( $section );

		// allow developers to specify which Custom Post Types are nested inside this section
		$cpts = apply_filters( 'template_map_post_types', array(), $section );

		// at the minimum we are considered 'in' the section if that's
		// the current post, or the parent is an ancestor of the current post
		$ancestors = get_post_ancestors( $post );
		if ( is_page( $parent_id ) ) {
			$in_section = true;
		} elseif ( in_array( $parent_id, $ancestors ) ) {
			$in_section = true;
		} else {
			// sometimes there are CPTs nested inside sections, check for that
			if ( is_array( $cpts ) && count( $cpts ) ) {
				if ( is_singular( $cpts ) || is_post_type_archive( $cpts ) ) {
					$in_section = true;
				}
				if ( ! $in_section ) { // check for CPT taxonomy archives
					// retrieve any custom taxonomies for the CPT
					foreach ( $cpts as $cpt ) {
						$maybe_archive = $this->is_taxonomy_archive_for_post_type( $cpt );
						if ( $maybe_archive ) {
							$in_section = true;
							break;
						}
					}
				}
			}
		}

		return $in_section;
	}

	/**
	 * Determines whether an archive was requested for any registered Taxonomy
	 * for the submitted post type
	 *
	 * @param string $post_type Post type to check
	 *
	 * @return bool Whether an applicable taxonomy archive is being shown
	 */
	function is_taxonomy_archive_for_post_type( $post_type = 'post' ) {
		$is_taxonomy_archive = false;
		$taxonomies = get_object_taxonomies( $post_type, 'objects' );
		if ( count( $taxonomies ) ) {
			foreach ( $taxonomies as $taxonomy ) {
				if ( is_tax( $taxonomy ) ) {
					$is_taxonomy_archive = true;
					break;
				}
			}
		}
		return $is_taxonomy_archive;
	}

}

/**
 * Singleton retrieval utility
 *
 * @return TemplateMap
 */
if ( ! function_exists( 'TemplateMap' ) ) {
	function TemplateMap() {
		return TemplateMap::instance();
	}
}

/**
 * Initializer
 *
 * @return TemplateMap
 */
if ( ! function_exists( 'template_map_init' ) ) {
	function template_map_init() {
		$template_map = TemplateMap::instance();
		return $template_map;
	}
}

// kickoff
template_map_init();

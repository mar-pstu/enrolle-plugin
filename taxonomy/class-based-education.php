<?php


if ( ! defined( 'ABSPATH' ) ) {	exit; };


if ( class_exists( 'pstuTaxonomyBasedEducation' ) ) return;


/**
 *	Таксономия ЗНО
 */
class pstuTaxonomyBasedEducation {


	protected $name = 'based_education';


	protected $object_types = array( 'competitive_offers' );


	public function __construct() {
		add_action( 'init', array( $this, 'create_taxonomy' ) );
		add_filter( 'the_title', array( $this, 'post_title' ), 10, 2 );
	}


	public function create_taxonomy() {
		register_taxonomy(
			$this->name,
			$this->object_types,
			array(
				'label'                 => '',
				'labels'                => array(
					'name'                => __( 'Предыдущее образование',      'pstu-enrollee' ),
					'singular_name'       => __( 'Предыдущее образование',      'pstu-enrollee' ),
					'search_items'        => __( 'Найти запись',                'pstu-enrollee' ),
					'all_items'           => __( 'Все записи',                  'pstu-enrollee' ),
					'view_item '          => __( 'Просмотр списка записей',     'pstu-enrollee' ),
					'parent_item'         => __( 'Родительская запись',         'pstu-enrollee' ),
					'parent_item_colon'   => __( 'Родительская запись:',        'pstu-enrollee' ),
					'edit_item'           => __( 'Редактировать запись',        'pstu-enrollee' ),
					'update_item'         => __( 'Обновить запись',             'pstu-enrollee' ),
					'add_new_item'        => __( 'Добавить новую запись',       'pstu-enrollee' ),
					'new_item_name'       => __( 'Добавить запись',             'pstu-enrollee' ),
					'menu_name'           => __( 'Предыдущее образование',      'pstu-enrollee' ),
				),
				'description'           => '',
				'public'                => true,
				'publicly_queryable'    => null,
				'show_in_nav_menus'     => false,
				'show_ui'               => true,
				'show_in_menu'          => true,
				'show_tagcloud'         => false,
				'show_in_rest'          => false,
				'rest_base'             => null,
				'hierarchical'          => false,
				'update_count_callback' => '',
				'rewrite'               => true,
				//'query_var'             => $taxonomy, // название параметра запроса
				'capabilities'          => array(),
				'meta_box_cb'           => array( $this, 'meta_box' ), // post_categories_meta_box / post_tags_meta_box / false
				'show_admin_column'     => true,
				'_builtin'              => false,
				'show_in_quick_edit'    => null, // по умолчанию значение show_ui
			)
		);
	}


	public function meta_box( $post, $box ) {
		$result = array();
		$terms = get_terms( array(
			'taxonomy'      => $this->name,
			'orderby'       => 'name', 
			'order'         => 'ASC',
			'get'           => 'all',
		) );
		if ( ( $terms ) && ( ! empty( $terms ) ) && ( ! is_wp_error( $terms ) ) ) {
			$result[] = sprintf( '<ul class="categorychecklist form-no-clear" id="%1$schecklist" data-wp-lists="list:%1$s"><input type="hidden" name="tax_input[%1$s][]" value="0" checked="checked">', $this->name );
			foreach ( $terms as $term ) $result[] = sprintf(
				'<li style="margin: 5px; display: inline-block;" id="%6$s-%1$s"><label class="selectit" for="in-%6$s-%1$s"><input type="checkbox" name="tax_input[%6$s][]" id="in-%6$s-%1$s" value="%2$s" %3$s> %4$s %5$s</label></li>',
				$term->term_id,
				$term->slug,
				checked( is_object_in_term( $post->ID, $this->name, $term->term_id ), true, false ),
				$term->name,
				( empty( trim( $term->description ) ) ) ? '' : '<i>(' . $term->description . ')</i>',
				$this->name
			);
			$result[] = '</ul>';
		} else {
			$result[] = __( 'Термины не найдены', 'pstu-enrollee' );
		}
		echo implode( "\r\n", $result );
	}



	public function post_title( $title, $post_id = false ) {
		if (
			$post_id && in_array( get_post_type( $post_id ), $this->object_types ) &&
			( ( is_admin() && wp_doing_ajax() ) || ( ! is_admin() && ! wp_doing_ajax() ) )
		) {
			$form_studies = $properties = get_terms( array(
				'taxonomy'      => $this->name,
				'object_ids'    => $post_id,
				'fields'        => 'names',
			) );
			if ( $form_studies && ! empty( $form_studies ) && ! is_wp_error( $form_studies ) ) {
				$title = wp_sprintf(
					__( '%1$s на базе %2$l', 'pstu-enrollee' ),
					$title,
					$form_studies
				);
			}
		}
		return $title;
	}


}
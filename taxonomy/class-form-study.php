<?php


if ( ! defined( 'ABSPATH' ) ) {	exit; };


if ( class_exists( 'pstuTaxonomyFormStudy' ) ) return;


/**
 *	Таксономия "Формы обучения"
 */
class pstuTaxonomyFormStudy {



	use pstuEnrolleeTrait;



	protected $name = 'form_study';



	protected $object_types = array( 'competitive_offers' );



	public function __construct() {
		add_action( 'init', array( $this, 'create_taxonomy' ) );
	}



	public function create_taxonomy() {
		register_taxonomy(
			$this->name,
			$this->object_types,
			array(
				'label'                 => '',
				'labels'                => array(
					'name'                => __( 'Форма обучения',                 'pstu-enrollee' ),
					'singular_name'       => __( 'Форма обучения',                 'pstu-enrollee' ),
					'search_items'        => __( 'Найти запись',                   'pstu-enrollee' ),
					'all_items'           => __( 'Все записи',                     'pstu-enrollee' ),
					'view_item '          => __( 'Просмотр списку записей',        'pstu-enrollee' ),
					'parent_item'         => __( 'Родительская запись',            'pstu-enrollee' ),
					'parent_item_colon'   => __( 'Родительская запись:',           'pstu-enrollee' ),
					'edit_item'           => __( 'Редактировать запись',           'pstu-enrollee' ),
					'update_item'         => __( 'Обновить запись',                'pstu-enrollee' ),
					'add_new_item'        => __( 'Добавить новую форму обучения',  'pstu-enrollee' ),
					'new_item_name'       => __( 'Добавить форму обучения',        'pstu-enrollee' ),
					'menu_name'           => __( 'Формы обучения',                 'pstu-enrollee' ),
				),
				'description'           => '',
				'public'                => true,
				'publicly_queryable'    => null,
				'show_in_nav_menus'     => false,
				'show_ui'               => true,
				'show_in_menu'          => true,
				'show_tagcloud'         => false,
				'show_in_rest'          => true,
				'rest_base'             => null,
				'hierarchical'          => false,
				'update_count_callback' => '',
				'rewrite'               => true,
				//'query_var'             => $taxonomy, // название параметра запроса
				'capabilities'          => array(),
				'meta_box_cb'           => array( $this, 'meta_box' ),
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



}
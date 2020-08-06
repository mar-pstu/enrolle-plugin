<?php


if ( ! defined( 'ABSPATH' ) ) {	exit; };


if ( class_exists( 'pstuTaxonomySpecializationTag' ) ) return;


/**
 *	Ключевые слова для образовательной программы
 */
class pstuTaxonomySpecializationTag {


	use pstuEnrolleeTrait;


	protected $name = 'specialization_tag';


	protected $object_types = array( 'educational_program' );


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
					'name'                =>	__( 'Ключевые слова', 'pstu-enrollee' ),
					'singular_name'       =>	__( 'Ключевые слова', 'pstu-enrollee' ),
					'search_items'        =>	__( 'Найти запись', 'pstu-enrollee' ),
					'all_items'           =>	__( 'Все записи', 'pstu-enrollee' ),
					'view_item '          =>	__( 'Просмотр списка записей', 'pstu-enrollee' ),
					'parent_item'         =>	__( 'Родительская запись', 'pstu-enrollee' ),
					'parent_item_colon'   =>	__( 'Родительская запись:', 'pstu-enrollee' ),
					'edit_item'           =>	__( 'Редактировать запись', 'pstu-enrollee' ),
					'update_item'         =>	__( 'Обновить запись', 'pstu-enrollee' ),
					'add_new_item'        =>	__( 'Добавить новую запись', 'pstu-enrollee' ),
					'new_item_name'       =>	__( 'Добавить запись', 'pstu-enrollee' ),
					'menu_name'           =>	__( 'Ключевые слова', 'pstu-enrollee' ),
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
				'meta_box_cb'           => 'post_tags_meta_box', // post_categories_meta_box / post_tags_meta_box / false
				'show_admin_column'     => true,
				'_builtin'              => false,
				'show_in_quick_edit'    => null, // по умолчанию значение show_ui
			)
		);
	}


}
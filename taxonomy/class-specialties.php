<?php


if ( ! defined( 'ABSPATH' ) ) {	exit; };


if ( class_exists( 'pstuTaxonomySpecialties' ) ) return;


/**
 *	Таксономия ЗНО
 */
class pstuTaxonomySpecialties extends pstuAbstractTaxonomy {


	protected $slug = 'pstu_specialties';


	protected $name = 'specialties';


	protected $object_types = array( 'educational_program' );


	function __construct() {
		parent::__construct();
		add_action( 'template_redirect', array( $this, 'single_redirect' ), 10, 0 );
		add_filter( 'single_term_title', array( $this, 'term_title' ), 10, 1 );
		add_filter( 'the_title', array( $this, 'post_title' ), 10, 2 );
		if ( is_admin() ) {
			add_action( 'add_meta_boxes', array( $this, 'register_meta_boxes' ), 10, 1 );
			add_action( 'save_post', array( $this, 'save_meta_boxes' ), 10, 1 );
			add_action( 'save_post', array( $this, 'save_post' ), 10, 1 );
		} else {
			add_filter( 'pre_get_posts', array( $this, 'query_filter' ), 10, 1 );
		}
		register_taxonomy(
			$this->name,
			$this->object_types,
			array(
				'label'                 => '',
				'labels'                => array(
					'name'                => __( 'Области знаний / специальности', 'pstu-enrollee' ),
					'singular_name'       => __( 'Область знаний / специальность', 'pstu-enrollee' ),
					'search_items'        => __( 'Найти запись', 'pstu-enrollee' ),
					'all_items'           => __( 'Все записи', 'pstu-enrollee' ),
					'view_item '          => __( 'Просмотр списка записей', 'pstu-enrollee' ),
					'parent_item'         => __( 'Родительская запись', 'pstu-enrollee' ),
					'parent_item_colon'   => __( 'Родительская запись:', 'pstu-enrollee' ),
					'edit_item'           => __( 'Редактировать запись', 'pstu-enrollee' ),
					'update_item'         => __( 'Обновить запись', 'pstu-enrollee' ),
					'add_new_item'        => __( 'Добавить новое конкурсное предложение', 'pstu-enrollee' ),
					'new_item_name'       => __( 'Добавить конкурсное предложение', 'pstu-enrollee' ),
					'menu_name'           => __( 'Область знаний / специальность', 'pstu-enrollee' ),
				),
				'description'           => __( 'Двухуровневая таксономия: область знаний -> специальности.', 'pstu-enrolee' ),
				'public'                => true,
				'publicly_queryable'    => null,
				'show_in_nav_menus'     => true,
				'show_ui'               => true,
				'show_in_menu'          => true,
				'show_tagcloud'         => true,
				'show_in_rest'          => null,
				'rest_base'             => null,
				'hierarchical'          => true,
				'update_count_callback' => '',
				'rewrite'               => true,
				//'query_var'             => $taxonomy, // название параметра запроса
				'capabilities'          => array(),
				'meta_box_cb'           => false,
				'show_admin_column'     => false,
				'_builtin'              => false,
				'show_in_quick_edit'    => null,
			) );
		if ( is_admin() ) {
			$this->fields[] = new pstuFieldTaxonomy( array(
				'type'					=> 'select',
				'name'					=> $this->slug . '_page_id',
				'label'					=> __( 'Страница с описанием', 'pstu-enrollee' ),
				'choises'				=> $this->get_pages(),
			) );
			$this->fields[] = new pstuFieldTaxonomy( array(
				'type'					=> 'text',
				'name'					=> $this->slug . '_code',
				'label'					=> __( 'Код', 'pstu-enrollee' ),
				'description'		=> __( 'Область знаний - код вида 0ХХ, специальность - XXX.', 'pstu-enrollee' ),
				'placeholder'		=> 'XXX',
				'required'			=> true,
				'column'				=> true,
				'sortable'			=> true,
				'mask'					=> '999?.999',
			) );
			$this->fields[] = new pstuFieldTaxonomy( array(
				'type'					=> 'image',
				'name'					=> $this->slug . '_logo',
				'label'					=> __( 'Логотип', 'pstu-enrollee' ),
			) );
		}
	}



	/**
	* Фильтр запроса для задания сортировки по метаполю
	*/
	public function query_filter( $query ) {
		if ( in_array( $query->get( 'post_type' ), $this->object_types ) ) {
			$query->set( 'orderby', 'meta_value' );
			$query->set( 'order', 'ASC' );
			$query->set( 'meta_key', "{$this->slug}_code" );
		}
	}



	/*
	* Добавление/обновление кода специальности в метаданных образовательной программы
	*/
	public function save_taxonomy( $term_id, $tt_id ) {
		parent::save_taxonomy( $term_id, $tt_id );
		if ( wp_get_term_taxonomy_parent_id( $term_id, $this->name ) ) {
			$code = get_term_meta( $term_id, "{$this->slug}_code", true );
			if ( ! empty( $code ) ) {
				$educational_programs = get_posts( array(
					'numberposts'   => -1,
					'post_type'     => 'educational_program',
					'tax_query'     => array(
						'relation'    => 'OR',
						array(
							'taxonomy' => $this->name,
							'field'    => 'id',
							'terms'    => $term_id,
							'operator' => 'IN'
						),
						'include_children' => false,
					),
				) );
				if ( is_array( $educational_programs ) && ! empty( $educational_programs ) ) {
					foreach ( $educational_programs as $educational_program ) {
						update_post_meta( $educational_program->ID, "{$this->slug}_code", $code );
					}
				}
			}
		}
	}


	/**
	* Добавление номера в метаданные поста
	*/
	public function save_post( $post_id ) {
		// исключаем автосохранение и ревизии
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
		if ( wp_is_post_revision( $post_id ) ) return;	
		if ( ! in_array( get_post_type( $post_id ), $this->object_types ) ) return;
		// проверяем права пользователя
		if ( 'page' == get_post_type( $post_id ) ) {
			if ( ! current_user_can( 'edit_page', $post_id ) ) return $post_id;
		} elseif ( ! current_user_can( 'edit_post', $post_id ) ) {
			wp_nonce_ays();
			return;
		}
		$specialties = get_the_terms( $post_id, $this->name );
		if ( is_array( $specialties ) && ! empty( $specialties ) ) {
			foreach ( $specialties as $specialty ) {
				$code = get_term_meta( $specialty->term_id, "{$this->slug}_code", true );
				if ( empty( $code ) ) {
					continue;
				} else {
					update_post_meta( $post_id, "{$this->slug}_code", $code );
					break;
				}
			}
		}
	}


	/**
	 *	Добавление метабокса
	 */
	public function register_meta_boxes( $post_type ) {
		if ( in_array( $post_type, $this->object_types ) ) add_meta_box(
			"{$this->slug}_meta_box",
			__( 'Области знаний / специальности', 'pstu-enrollee' ),
			array( $this, 'render_meta_box' ),
			$post_type,
			'side',
			'high',
			null
		);
	}


	/**
	 *	Сохранение метабокса
	 */
	public function save_meta_boxes( $post_id ) {
		if ( ! isset( $_POST[ "{$this->slug}_meta_box_nonce" ] ) ) { return; }
		if ( ! wp_verify_nonce( $_POST[ "{$this->slug}_meta_box_nonce" ], "{$this->slug}_meta_box" ) ) { wp_nonce_ays(); return; }
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
		if ( wp_is_post_revision( $post_id ) ) return;	
		if ( $this->name == $_POST[ 'post_type' ] ) {
			if ( ! current_user_can( 'edit_page', $post_id ) ) return $post_id;
		} elseif ( ! current_user_can( 'edit_post', $post_id ) ) { wp_nonce_ays(); return; }
		if ( isset( $_POST[ $this->slug ] ) ) {
			$result = array();
			foreach ( $_POST[ $this->slug ] as $key ) $result[] = (int)sanitize_key( $key );
			wp_set_post_terms( $post_id, $result, $this->name, false );
		}
	}



	/**
	 *	Метабокс в виде выпадающего списка с кодом и группировкой по областям знаний
	 */
	public function render_meta_box( $post, $meta ) {
		wp_nonce_field( "{$this->slug}_meta_box", "{$this->slug}_meta_box_nonce" );
		wp_enqueue_script( 'select2' );
		wp_add_inline_script( "select2", "jQuery( '#taxonomy-{$this->name} select' ).select2();", "after" );
		wp_enqueue_style( 'select2' );
		$terms = get_terms( array(
			'taxonomy'      => $this->name,
			'orderby'       => 'name', 
			'order'         => 'ASC',
			'get'           => 'all',
			'hierarchical'	=> true,
			'hide_empty'		=> false,
		) );
		if ( ( $terms ) && ( ! empty( $terms ) ) && ( ! is_wp_error( $terms ) ) ) {
			$optgroups = wp_list_filter( $terms, array( 'parent' => 0 ) );
			$result[] = "<div id=\"taxonomy-{$this->name}\">";
			$result[] = "  <input type=\"hidden\" value=\"0\" name=\"{$this->slug}[]\">";
			$result[] = "  <select name=\"{$this->slug}[]\"><option value=\"\">-</option>";
			foreach ( $optgroups as $optgroup ) {
				$options = wp_list_filter( $terms, array( 'parent' => $optgroup->term_id ) );
				if ( ( ! $options ) && ( empty( $options ) ) ) continue;
				$result[] = sprintf( '<optgroup label="%1$s">', apply_filters( 'single_term_title', $optgroup->name ) );
				foreach ( $options as $option ) {
					$option_code = get_term_meta( $option->term_id, "{$this->slug}_code", true );
					$result[] = sprintf(
						'<option id="taxonomy_term_%1$s" value="%1$s" %2$s>%3$s</option>',
						$option->term_id,
						selected( is_object_in_term( $post->ID, $this->name, $option->term_id ), true, false ),
						apply_filters( 'single_term_title', $option->name )
					);
				}
				$result[] = "</optgroup>";
			}
			$result[] = "  </select>";
			$result[] = "</div>";			
		} else {
			$result[] = __( 'Термины не найдены', 'pstu-enrollee' );
		}
		echo implode( "\r\n", $result );
	}



	/**
	 *	Переадресация на страницу с описанием термина, если конечно она есть
	 */
	public function single_redirect() {
		if ( is_tax( $this->name ) ) {
			global $wp_query;
			$term_id = get_queried_object_id();
			if ( ( ! $term_id ) || ( empty( $term_id ) ) ) return;
			$page_id = get_term_meta( $term_id, "{$this->slug}_page_id", true );
			if ( ( ! $page_id ) || ( empty( $page_id ) ) || ( is_wp_error( $page_id ) ) ) return;
			wp_safe_redirect( get_page_link( $page_id ), 302 );
			exit();
		}
	}


	/**
	 *
	 */
	public function term_title( $title ) {
		$term = get_term_by( 'name', $title, $this->name );
		if ( ( $term ) && ( ! is_wp_error( $term ) ) ) {
			$code = get_term_meta( $term->term_id, "{$this->slug}_code", true );
			if ( $code ) $title = sprintf(
				'%1$s %2$s',
				( '0' == $term->parent ) ? substr( $code, 0, -1 ) : $code,
				$title
			);
		}
		return $title;
	}


	/**
	* Фильтр заголовка
	*/
	public function post_title( $title, $post_id = '' ) {
		if ( empty( $post_id ) ) $post_id = get_the_ID();
		if ( in_array( get_post_type( $post_id ), $this->object_types ) ) {
			$title = sprintf(
				'%1$s %2$s',
				get_post_meta( $post_id, "{$this->slug}_code", true ),
				$title
			);
		}
		return $title;
	}


}
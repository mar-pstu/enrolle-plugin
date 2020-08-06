<?php


/**
*	Условия поступления (ЗНО/экзамены/собеседование)
*/


if ( ! defined( 'ABSPATH' ) ) {	exit; };


if ( class_exists( 'pstuTaxonomyComponentsOfCompetitivePoints' ) ) return;


/**
 *	Таксономия ЗНО
 */
class pstuTaxonomyComponentsOfCompetitivePoints {


	use pstuEnrolleeTrait;


	protected $slug = 'pstu_components_of_competitive_points';


	protected $name = 'components_of_competitive_points';


	protected $object_types = array( 'competitive_offers' );


	public function __construct() {
		add_action( 'init', array( $this, 'create_taxonomy' ) );
		if ( is_admin() ) {
			add_action( 'save_post', array( $this, 'save' ) );
			add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ) );
			add_action( "{$this->name}_add_form_fields", array( $this, 'add_fields' ), 10, 1 );
			add_action( "{$this->name}_edit_form_fields", array( $this, 'edit_fields' ), 10, 2 );
			add_action( "created_{$this->name}", array( $this, 'save_taxonomy' ), 10, 2 );
			add_action( "edited_{$this->name}", array( $this, 'save_taxonomy' ), 10, 2 );
		}
	}


	/**
	* Добавление пустого поля при создании таксономии
	*/
	public function add_fields() {
		?>
			<div class="form-field term-group-wrap">
				<label for="pstu_dont_use_in_filter">
					<input type="checkbox" id="pstu_dont_use_in_filter" value="1" name="pstu_dont_use_in_filter">
					<?php _e( 'Не использовать в выборке', 'pstu-enrollee' ); ?>
				</label>
			</div>
		<?php
	}


	/**
	* Добавление поля при редактировании
	*/
	public function edit_fields( $term, $taxonomy ) {
		?>
			<tr class="form-field term-group-wrap">
				<th scope="row">
					<label for="pstu_dont_use_in_filter"><?php _e( 'Не использовать в выборке', 'pstu-enrollee' ); ?></label>
				</th>
				<td>
					<input type="checkbox" id="pstu_dont_use_in_filter" value="1" name="pstu_dont_use_in_filter" <?php checked( '1', get_term_meta( $term->term_id, 'pstu_dont_use_in_filter', true ) ) ?>>
				</td>
			</tr>
		<?php
	}


	/**
	* Сохранение полей
	*/
	public function save_taxonomy( $term_id, $tt_id ) {
		if ( isset( $_POST[ 'pstu_dont_use_in_filter' ] ) ) {
			update_term_meta( $term_id, 'pstu_dont_use_in_filter', true );
		} else {
			delete_term_meta( $term_id, 'pstu_dont_use_in_filter' );
		}
	}


	/**
	* Регистрация метабокса
	*/
	public function add_meta_box( $post_type ) {
		if ( in_array( $post_type, $this->object_types ) ) add_meta_box(
			$this->slug,
			__( 'Компоненты и коэффициенты', 'pstu-enrollee' ),
			array( $this, 'render_content' ),
			$this->object_types,
			'advanced',
			'high',
			null
		);
	}



	/**
	*	Регистрация таксономии
	*/
	public function create_taxonomy() {
		register_taxonomy(
			$this->name,
			$this->object_types,
			array(
				'label'                 => '',
				'labels'                => array(
					'name'                => __( 'Компоненты',               'pstu-enrollee' ),
					'singular_name'       => __( 'Компоненты',               'pstu-enrollee' ),
					'search_items'        => __( 'Найти запись',             'pstu-enrollee' ),
					'all_items'           => __( 'Все записи',               'pstu-enrollee' ),
					'view_item '          => __( 'Просмотр списка записей',  'pstu-enrollee' ),
					'parent_item'         => __( 'Родительская запись',      'pstu-enrollee' ),
					'parent_item_colon'   => __( 'Родительская запись:',     'pstu-enrollee' ),
					'edit_item'           => __( 'Редактировать запись',     'pstu-enrollee' ),
					'update_item'         => __( 'Обновить запись',          'pstu-enrollee' ),
					'add_new_item'        => __( 'Добавить новую запись',    'pstu-enrollee' ),
					'new_item_name'       => __( 'Добавить запись',          'pstu-enrollee' ),
					'menu_name'           => __( 'Компоненты',               'pstu-enrollee' ),
				),
				'description'           => 'Составляющие конкурсного балла',
				'public'                => true,
				'publicly_queryable'    => false,
				'show_in_nav_menus'     => false,
				'show_ui'               => true,
				'show_in_menu'          => true,
				'show_tagcloud'         => false,
				'show_in_rest'          => true,
				'rest_base'             => null,
				'hierarchical'          => true,
				'update_count_callback' => '',
				'rewrite'               => true,
				//'query_var'             => $taxonomy, // название параметра запроса
				'capabilities'          => array(),
				'meta_box_cb'           => false,
				'show_admin_column'     => true,
				'_builtin'              => false,
				'show_in_quick_edit'    => false, // по умолчанию значение show_ui
			)
		);
	}


	/**
	*	Сохранеие метабокса
	*/
	public function save( $post_id ) {
		// проверяем существует ли nonce-поле, если нет - выходим
		if ( ! isset( $_POST[ "{$this->slug}_nonce" ] ) ) {
			// wp_nonce_ays();
			return;
		}

		// проверяем значение nonce-поля, если не совпадает - выходим
		if ( ! wp_verify_nonce( $_POST[ "{$this->slug}_nonce" ], $this->slug ) ) {
			wp_nonce_ays();
			return;
		}

		// исключаем автосохранение и ревизии
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
		if ( wp_is_post_revision( $post_id ) ) return;

		// проверяем права пользователя
		if ( in_array( $_POST[ 'post_type' ], $this->object_types ) ) {
			if ( ! current_user_can( 'edit_page', $post_id ) ) return $post_id;
		} elseif ( ! current_user_can( 'edit_post', $post_id ) ) {
			wp_nonce_ays();
			return;
		}

		$components = array();

		if ( isset( $_POST[ $this->slug ] ) ) {
			$data = $this->validate( $_POST[ $this->slug ] );
			if ( update_post_meta( $post_id, $this->slug, $data ) ) {
				foreach ( $data as $key => $value ) {
					$components = array_merge( $components, map_deep( $value[ 'components' ], 'intval' ) );
				}
				wp_set_object_terms( $post_id, $components, $this->name, false );
			}
		} else {
			$components = get_terms( array(
				'taxonomy'    => $this->name,
				'object_ids'  => $post_id,
				'fields'      => 'ids',
			) );
			if ( is_array( $components ) && ! empty( $components ) ) {
				wp_remove_object_terms( $post_id, $components, $this->name );
			}
			delete_post_meta( $post_id, $this->slug );
		}
		
	}



	/**
	*	Валидация полученных данных перед сохранением
	*/
	public function validate( $data ) {
		$result = array();
		$data = json_decode( wp_unslash( sanitize_text_field( $data ) ) );
		if ( is_array( $data ) ) {
			foreach ( $data as $key => $value ) {
				$value->coefficient = trim( $value->coefficient );
				$value->components = map_deep( $value->components, 'sanitize_key' );
				if (
					! empty( $value->coefficient ) &&
					preg_match( '/^[0-1][.][\d]{1,2}$/', $value->coefficient ) &&
					! empty( $value->components )
				) $result[] = array(
					'components' => $value->components,
					'coefficient' => $value->coefficient,
				);
			}
		}
		return $result;
	}


	/**
	 * Возвращает имя компонента
	 *
	 * @return String
	 */
	static function get_component_name( $term ) {
		return sprintf(
			'%1$s %2$s',
			$term->name,
			( empty( $term->description ) ) ? '' : '(' . esc_html( $term->description ) . ')'
		);;
	}



	/**
	*	Вывод метабокса
	*/
	public function render_content( $post ) {
		wp_nonce_field( $this->slug, "{$this->slug}_nonce" );
		$value = get_post_meta( $post->ID, $this->slug, true );

		if ( is_array( $value ) ) {
			if ( function_exists( 'pll_get_term' ) ) {
				foreach ( $value as $k => $v ) {
					$value[ $k ][ 'components' ] = map_deep( $v[ 'components' ], 'pll_get_term' );
				}
			}
		} else {
			$value = array();
		}
		
		$components = array();
		$terms = get_terms( array(
			'taxonomy' => $this->name,
			'hide_empty' => false,
			'fields' => 'all',
		) );
		if ( $terms && ! empty( $terms ) && ! is_wp_error( $terms ) ) {
			foreach ( wp_list_filter( $terms, array( 'parent' => 0 ) ) as $term ) {
				$children = wp_list_filter( $terms, array( 'parent' => $term->term_id ) );
				if ( count( $children ) > 0 ) {
					foreach ( $children as $child ) {
						$components[ $child->term_id ] = sprintf(
							'%1$s (%2$s%3$s)',
							$child->name,
							$term->name,
							( empty( $child->description ) ) ? '' : ' ' . esc_html( $child->description )
						);
					}
				} else {
					$components[ $term->term_id ] = sprintf(
						'%1$s %2$s',
						$term->name,
						( empty( $term->description ) ) ? '' : '(' . esc_html( $term->description ) . ')'
					);
				}
			}
		}
		?>
			<input type="hidden" id="<?php echo $this->slug; ?>" name="<?php echo $this->slug; ?>" value="<?php echo esc_attr( wp_json_encode( (object) $value ) ); ?>">
			<table class="components-of-competitive-points-table" id="components-of-competitive-points-table">
			  <thead>
			    <th><?php _e( '№', 'pstu-enrollee' ); ?></th>
			    <th><?php _e( 'Коэффициент', 'pstu-enrollee' ); ?></th>
			    <th><?php _e( 'Компоненты', 'pstu-enrollee' ); ?></th>
			  </thead>
			  <tbody></tbody>
			</table>
			<div id="edit-row-wrap" class="edit-row-wrap">
				<p><?php _e( 'Для завершения редактирования нажмите кнопку "Готово" или закройте окно', 'pstu-enrollee' ); ?></p>
				<label for="coefficient-field"><?php _e( 'Коэффициент', 'pstu-enrollee' ); ?></label>
				<input id="coefficient-field" type="text" class="coefficient">
				<h3><?php _e( 'Компоненты', 'pstu-enrollee' ); ?></h3>
				<fieldset id="components-list" class="components-list"></fieldset>
				<button class="button button-primary" type="button" id="complete-line-editg-button">
					<span class="dashicons dashicons-yes"></span>
					<?php _e( 'Готово', 'pstu-enrollee' ); ?>
				</button>
			</div>
			<button type="button" id="components-of-competitive-points-add-row" class="button button-primary">
				<span class="dashicons dashicons-plus"></span>
				<?php _e( 'Добавить строку', 'pstu-enrollee' ); ?>
			</button>
		<?php
		$this->render_tmpl( 'components-of-competitive-points-row', PSTU_ENROLLEE_DIR . '/assets/templates/components-of-competitive-points-row.html' );
		$this->render_tmpl( 'checkbox', PSTU_ENROLLEE_DIR . '/assets/templates/checkbox.html' );
		wp_enqueue_style(
			'components-of-competitive-points-metabox',
			PSTU_ENROLLEE_URL . '/assets/css/components-of-competitive-points-metabox.css',
			array(),
			false,
			'all'
		);
		wp_enqueue_style(
			'fancybox',
			PSTU_ENROLLEE_URL . '/assets/css/fancybox.css',
			array(),
			false,
			'all'
		);
		wp_enqueue_script(
			'fancybox',
			PSTU_ENROLLEE_URL . '/assets/scripts/fancybox.js',
			array( 'jquery' ),
			false,
			false
		);
		wp_enqueue_script(
			'maskedinput',
			PSTU_ENROLLEE_URL . '/assets/scripts/jquery.maskedinput.js',
			array( 'jquery' ),
			false,
			false
		);
		wp_add_inline_script( 'maskedinput', 'jQuery(function(){ jQuery("#coefficient-field").mask("9.9?9",{placeholder:" "});});', 'after' );
		wp_enqueue_script(
			'components-of-competitive-points-metabox',
			PSTU_ENROLLEE_URL . '/assets/scripts/components-of-competitive-points-metabox.js',
			array( 'jquery', 'fancybox' ),
			false,
			false
		);
		wp_localize_script( 'components-of-competitive-points-metabox', 'AdmissionConditionsComponents', $components );
		wp_localize_script( 'components-of-competitive-points-metabox', 'AdmissionConditionsValue', $value );
		wp_localize_script( 'components-of-competitive-points-metabox', 'AdmissionConditionsTranslates', array(
			'remove_row_button'        => __( 'Удалить строку', 'pstu-enrollee' ),
			'edit_row_button'          => __( 'Редактировать строку', 'pstu-enrollee' ),
			'error_empty_components'  => sprintf(
				'<b>%1$s:</b> %2$s',
				__( 'Ошибка', 'pstu-enrollee' ),
				__( 'компоненты не выбраны', 'pstu-enrollee' )
			),
			'components_not_filled' => __( 'Компоненты не заполнены', 'pstu-enrollee' ),
		) );

	}



	/**
	*	Возвращает форматированны список компонентов и их коэффициент
	*/
	static function get_components_list( $components ) {
		$result = array();
		if ( is_array( $components ) ) {
			foreach ( $components as $key => $value ) {
				$names = array();
				$terms = get_terms( array(
					'taxonomy'  => 'components_of_competitive_points',
					'include'   => $value[ 'components' ],
				) );
				if ( $terms && ! empty( $terms ) && ! is_wp_error( $terms ) ) {
					foreach ( $terms as $term ) $names[] = sprintf(
						'<b>%1$s</b> %2$s',
						apply_filters( 'single_term_title', $term->name, $term->term_id ),
						( empty( trim( $term->description ) ) ) ? '' : '<small>(' . strip_tags( $term->description ) . ')</small>'
					);
					$result[] = sprintf(
						'<li>%1$s - %2$s</li>',
						self::pstu_sprint_array( 'OR', $names ),
						$value[ 'coefficient' ]
					);
				}
			}
		}
		return ( empty( $result ) ) ? '' : '<ul>' . implode( "\r\n", $result ) . '</ul>';
	}



}
<?php

/*
Plugin Name: Абитуриент
Plugin URI: http://pstu.edu
Description: Плагин сайта Приазовского государственного технического университета
Version: 0.0.1
Author: chomovva
Author URI: http://chomovva.ru
License: GPL2
Text Domain: pstu-enrollee
Domain Path: /languages/
*/


if ( ! defined( 'ABSPATH' ) ) {	exit; };


define( 'PSTU_ENROLLEE_DIR', untrailingslashit( plugin_dir_path( __FILE__ ) ) . '/' );
define( 'PSTU_ENROLLEE_URL', untrailingslashit( plugin_dir_url(__FILE__) ) . '/' );


include_once PSTU_ENROLLEE_DIR . 'includes/trait-pstu-enrollee.php';
include_once PSTU_ENROLLEE_DIR . 'includes/abstract-taxonomy.php';
include_once PSTU_ENROLLEE_DIR . 'includes/class-field-taxonomy.php';


include_once PSTU_ENROLLEE_DIR . 'types/class-educational-program.php';
include_once PSTU_ENROLLEE_DIR . 'types/class-competitive-offers.php';


include_once PSTU_ENROLLEE_DIR . 'taxonomy/class-based-education.php';
include_once PSTU_ENROLLEE_DIR . 'taxonomy/class-type-of-offer.php';
include_once PSTU_ENROLLEE_DIR . 'taxonomy/class-form-study.php';
include_once PSTU_ENROLLEE_DIR . 'taxonomy/class-components-of-competitive-points.php';
include_once PSTU_ENROLLEE_DIR . 'taxonomy/class-okr.php';
include_once PSTU_ENROLLEE_DIR . 'taxonomy/class-specialization-tag.php';
include_once PSTU_ENROLLEE_DIR . 'taxonomy/class-specialties.php';


include_once PSTU_ENROLLEE_DIR . 'shortcodes/class-knowledge-areas.php';
include_once PSTU_ENROLLEE_DIR . 'shortcodes/class-specialties.php';
include_once PSTU_ENROLLEE_DIR . 'shortcodes/class-okr-list.php';
include_once PSTU_ENROLLEE_DIR . 'shortcodes/class-competitive-offers-list.php';
include_once PSTU_ENROLLEE_DIR . 'shortcodes/class-enrollee-filter.php';
include_once PSTU_ENROLLEE_DIR . 'shortcodes/class-knowledge-areas-educational-programs.php';
include_once PSTU_ENROLLEE_DIR . 'shortcodes/class-knowledge-areas-simple-list.php';


add_action( 'plugins_loaded', function () {
	load_plugin_textdomain( 'pstu-enrollee', false, PSTU_ENROLLEE_DIR . 'languages/' );
} );


add_action( 'wp_enqueue_scripts', function() {
	wp_register_style(
		'enrollee',
		PSTU_ENROLLEE_URL . 'assets/css/enrollee.css',
		array(),
		filemtime( PSTU_ENROLLEE_DIR . 'assets/css/enrollee.css' ),
		'all'
	);
}, 10 );


function pstu_enrollee_run() {
	new pstuTypeEducationalProgram();
	new pstuTypeCompetitiveOffers();
	new pstuTaxonomyComponentsOfCompetitivePoints();
	new pstuTaxonomyFormStudy();
	new pstuTaxonomyTypeOfOffer();
	new pstuTaxonomyBasedEducation();
	new pstuTaxonomyOKR();
	new pstuTaxonomySpecializationTag();
	add_action( 'init', function() { new pstuTaxonomySpecialties(); } );
	new pstuKnowledgeAreas();
	new pstuSpecialties();
	new pstuOKRList();
	new pstuCompetitiveOffersList();
	new pstuEnrolleeFilter();
	new pstuKnowledgeAreasEducationalPrograms();
	new pstuKnowledgeAreasSimpleList();
}
pstu_enrollee_run();
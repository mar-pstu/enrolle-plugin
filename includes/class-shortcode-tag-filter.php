<?php


if ( ! defined( 'ABSPATH' ) ) {	exit; };


if ( class_exists( 'pstuShortcodeCalculator' ) ) return;


/**
 *	Таксономия ЗНО
 */
class pstuShortcodeCalculator {


	use pstuEnrolleeTrait;


	public $name = 'ENROLLEE_TAG_FILTER';


	


}


?>
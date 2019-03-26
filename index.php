<?php
/**
  *  @PHP Simplex-Big-M
  *  @since 	Jan 2018
  *  @author    Idan Petel <ipetel@gmail.com>
*/

/* Show All Errors */
	ini_set('display_startup_errors',1); 
	ini_set('display_errors',1);
	error_reporting(-1);

/* Dependences */
	require_once('data.php');
	require_once('calculations_class.php');

/* Init Params */
	$max_iterations=50; // max num of iterations the model will run - so the code will not enter infinite loop

/* Run */
	$class_instance = new calculations_class($objective_function,$c,$st,$max_iterations);
	$class_instance->solve();

	#$class_instance->print_all_vars(); // debug
	$class_instance->print_all_iterations_matrixes($class_instance->iterations_array,$class_instance->columns);
	$class_instance->print_solve_answer();

?>
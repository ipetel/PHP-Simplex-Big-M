<?php
/**
	This page is to setup the data for the business story

The business story:
	"Wood & Wood" is a wood store that manufactures 4 products: chairs, tables, frames, and doors (there is only one type for each product).
	The store owner would like to decide how much to manufacture from each item to maximize his store profit for 1 week of work.
	You can assume that every product that will be manufactured, will be sold.

	Because the owner likes the chair and the door design so much at his store, he demands that the total number of chairs and doors that manufactured in a week of work will at least stand on 4.
	In addition, he wants that the total number of tables and doors that manufactured in a week of work will stand exactly on 7.


	Costs:
		1) The cost of raw material to create 1 chair is $5
		2) The cost of raw material to create 1 table is $10
		3) The cost of raw material to create 1 frame is $1.5
		4) The cost of raw material to create 1 door is $16

	Revenue:
		1) The revenue of selling 1 chair is $11
		2) The revenue of selling 1 table is $33
		3) The revenue of selling 1 frame is $8.5
		4) The revenue of selling 1 door is $40

	Production times:
		1) 5 hours is the time it takes to build 1 chair
		2) 15 hours is the time it takes to build 1 table
		3) 2 hours is the time it takes to build 1 frame
		4) 30 hours is the time it takes to build 1 door

	Resources:
		1) The store is open 5 days a week, 9 hours a day.   
		2) The store has 5 workers that work 9 hours every day, they don't miss any work day and all of them are healthy, strong and never get sick (good for them!).

	
Let the decision variables and constants of the problem be:
		ci = The profit for selling 1 unit of i product in USD ($)
		xi = Optimum number of product i to produce in a week (x1-chair, x2-table, x3-frame, x4-door)
		S1 = Total hours in a week of work
		S2 = At least the total numbers of chairs and doors in a week of work
		S3 = Total number of tables and doors in a week of work

	
	Formulation of the problem:
		Maximize Z = Total Profit = (11-5)x1 + (33-10)x2 + (8.5-1.5)x3 + (40-16)x4


	s.t. (=subject to):
		1) 5x1 + 15x2 + 2x3 + 30x4 <= 5 (workers) * 9 (hours a day) * 5 (days a week) 
		2) x1 + x4 >= 4
		3) x2 + x4 = 7
		4) xi >= 0 [for each i]
*/

/* Objective function - Max z or Min z*/ 
	
	// Max z= 6x1 + 23x2 + 7x3 + 24x4
	$objective_function='Max'; // or Min
	$c=array('x1'=>6,'x2'=>23,'x3'=>7,'x4'=>24); //coefficients of the objective function

/* Subject to */
	// 1) 5x1 + 15x2 + 2x3 + 30x4 <= 225
	// 2) x1 + x4 >= 4
	// 3) x1,x2,x3,x4>=0 => don't need to write. it will do it automatically ****

	$st=array(array('x1'=>5,'x2'=>15,'x3'=>2,'x4'=>30,'inequalitySign'=>'<=','RHS'=>225),
			  array('x1'=>1,'x4'=>1,'inequalitySign'=>'>=','RHS'=>4),
			  array('x2'=>1,'x4'=>1,'inequalitySign'=>'=','RHS'=>7),
			 );
?>
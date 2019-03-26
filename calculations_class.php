<?php
/** 
  *	SIMPLE SIMPLEX-BIG M CLASS (LINEAR PROGRAMMING) v1.0
  *  @since 	Jan 2018
  *  @author    Idan Petel <ipetel@gmail.com>
*/


class calculations_class{
	private $objective_function;
	private $original_c;
	private $original_st;
	private $isObjectiveMax; // true -> Max , false -> Min
	private $isBigM; // true -> Big M + simplex , false -> just Simplex
	private $M;
	public $iterations_array;
	public $iterations_array_baseChanged;
	public $columns;
	private $isRHS_OK;
	private $isObjectiveFunction_OK;
	private $isInequalitySign_OK;
	private $var_report; // for debug
	private $max_iterations;

	Public function calculations_class($objective_function,$c,$st,$max_iterations){
		$this->objective_function=$objective_function; 
		$this->original_c=$c;
		$this->original_st=$st;
		$this->max_iterations=$max_iterations;

		$this->isRHS_OK=false;
		$this->isObjectiveFunction_OK=false;
		$this->isInequalitySign_OK=false;
		$this->isObjectiveMax=true; // default val 
		$this->isBigM=false; // default val
		$this->M=10000000000; // Big M - very big number

		$this->prepare_iterations_array=array();
		$this->iterations_array=array();
		$this->var_report=array();
		$this->columns=array();
		$this->iterations_array_baseChanged=array();
	}// end builder

	Public function solve(){
		//check problem in standard form
			$this->isObjectiveFunction_OK=$this->check_objectiveFunction(); 
			$this->isRHS_OK=$this->check_RHS();//check RHS >= 0
			$this->isInequalitySign_OK=$this->check_inequalitySign();//check inequalitySign only contains '='/'>='/'<='

			if($this->isRHS_OK AND $this->isObjectiveFunction_OK AND $this->isInequalitySign_OK){
				$this->TransferDataToCanonicalForm();

				$this->columns=$this->prepare_iterations_array['variables'];

				if($this->isBigM){ // if it's Big M we need to turn the coefficients of the objective function to zero on Artificial variables columns
					$this->iterations_array=$this->prepare_matrix_before_simplex();
				}else{
					$this->iterations_array[]=$this->prepare_iterations_array['data Matrix'];
				}

				//start simplex
					$this->simplex_thisShit();
			}else{
				echo "one or more of the tests were failed";
			}
	}

	Private function check_RHS(){
		foreach ($this->original_st as $key1 => $value1) {
			foreach ($value1 as $key2 => $value2) {
				if($key2=='RHS' AND $value2<0){
					echo 'Error: one of your RHS values is not non-negative, please fix it and re-run the code';
					return false;
				}
			}
		}
		return true;
	}

	Private function check_objectiveFunction(){
		$temp_var=strtolower(trim($this->objective_function));
		if($temp_var<>'max' AND $temp_var<>'min'){
			echo 'Error: please init objective_function var to "Max" or "Min" in data.php file and re-run the code';
			return false;
		}elseif ($temp_var=='min') {
			$this->isObjectiveMax=false;
		}else{
			// default value is set to max so we don't need to update the var if it's "Max"
		}

		return true;
	}

	Private function check_inequalitySign(){
		foreach ($this->original_st as $key => $value) {
			if($value['inequalitySign']<>'=' AND $value['inequalitySign']<>'>=' AND $value['inequalitySign']<>'<='){
				echo "Error: one of the inequalitySign signs is wrong, the only valid signs are '='/'<='/'>=', please fix it and re-run the code";
				return false;
			}else{
				if($value['inequalitySign']=='=' OR $value['inequalitySign']=='>='){
					$this->isBigM=true;
				}
			}		
		}
		return true;
	}

	Private function TransferDataToCanonicalForm(){
		$this->prepare_iterations_array['variables']['z']=1;
		// first step is to add Artificial variables and lack variables for transform the s.t. to equality constraints and order in Matrix Lines
		// Matrix-L1 is the objective function and the rest are all the s.t.
			$i=2;
			$j=1;
			foreach ($this->original_st as $key1 => $value1) {
				foreach ($value1 as $key2 => $value2) {

					if($key2<>'inequalitySign' AND $key2<>'RHS'){
						$this->prepare_iterations_array['data Matrix']['L'.$i]['variables'][$key2]=$value2;
						$this->prepare_iterations_array['variables'][$key2]=1;

					}


					if($key2=='inequalitySign'){
						if($value2=='<='){
							$this->prepare_iterations_array['data Matrix']['L'.$i]['variables']['S'.$j]=1;
							$this->prepare_iterations_array['data Matrix']['L'.$i]['base']='S'.$j;
							$this->prepare_iterations_array['variables']['S'.$j]=1;
						}elseif ($value2=='=') {
							$this->prepare_iterations_array['data Matrix']['L'.$i]['variables']['A'.$j]=1;
							$this->prepare_iterations_array['variables']['A'.$j]=1;
							$this->prepare_iterations_array['data Matrix']['L'.$i]['base']='A'.$j;
							
							if($this->objective_function=='Max'){ // on Max problem we will add the artificial variables as negative and in Min as positive
								$this->prepare_iterations_array['data Matrix']['L1']['variables']['A'.$j]=$this->M;
							}else{
								$this->prepare_iterations_array['data Matrix']['L1']['variables']['A'.$j]=-$this->M;
							}

						}elseif ($value2=='>=') {
							$this->prepare_iterations_array['data Matrix']['L'.$i]['variables']['S'.$j]=-1;
							$this->prepare_iterations_array['data Matrix']['L'.$i]['variables']['A'.$j]=1;
							$this->prepare_iterations_array['data Matrix']['L'.$i]['base']='A'.$j;
							
							if($this->objective_function=='Max'){
								$this->prepare_iterations_array['data Matrix']['L1']['variables']['A'.$j]=$this->M;
							}else{
								$this->prepare_iterations_array['data Matrix']['L1']['variables']['A'.$j]=-$this->M;
							}

							$this->prepare_iterations_array['variables']['S'.$j]=1;
							$this->prepare_iterations_array['variables']['A'.$j]=1;
						}else{}
					}

					if($key2=='RHS'){
						$this->prepare_iterations_array['data Matrix']['L'.$i]['RHS']=$value2;
					}
				}

				$i++;
				$j++;
			}
		
		// second step is to add the coefficients of the objective function to Matrix-L1 + RHS + base
			foreach ($this->original_c as $key => $value) {
				$this->prepare_iterations_array['data Matrix']['L1']['variables'][$key]=-$value;
			}
			$this->prepare_iterations_array['data Matrix']['L1']['RHS']=0;
			$this->prepare_iterations_array['data Matrix']['L1']['base']='z';
			$this->prepare_iterations_array['data Matrix']['L1']['variables']['z']=1;
			

		// third step is to add to every line the rest of the params with value of zero
			foreach ($this->prepare_iterations_array['data Matrix'] as $key1 => $value1) {

				foreach ($value1['variables'] as $key2 => $value2) {
					$this->prepare_iterations_array['variables'][$key2]=0;
				}


				foreach ($this->prepare_iterations_array['variables'] as $key3 => $value3) {
					if($value3==1){
						$this->prepare_iterations_array['data Matrix'][$key1]['variables'][$key3]=0;
					}
				}

				foreach ($this->prepare_iterations_array['variables'] as $key4 => $value4) {
					$this->prepare_iterations_array['variables'][$key4]=1;
				}	
			}

		$this->prepare_iterations_array['variables']['RHS']=1;
	}		

	Private function prepare_matrix_before_simplex(){
		foreach ($this->prepare_iterations_array['data Matrix'] as $key1 => $value1) {
			if (strpos($value1['base'], 'A') !== false) {
				
				if(!array_key_exists('iterations',$this->prepare_iterations_array)){ // ['iterations'][0] will be the original Matrix
					$this->prepare_iterations_array['iterations'][]=$this->prepare_iterations_array['data Matrix'];
				}

			    $this->prepare_iterations_array['iterations'][]=$this->elementary_row_operation('L1',$key1,$value1['base'],end($this->prepare_iterations_array['iterations']));
			}
		}

		return $this->prepare_iterations_array['iterations'];
	}

	Private function elementary_row_operation($rowToOperateOn,$pivotRow,$variableToEquateToZero,$Matrix){ 	
		//example: elementary_row_operation(L1,L6,A5,matrix)
		// assumption: the $variableToEquateToZero will always we equal to 1 on the $pivotRow ***********
		
		if(isset($Matrix[$pivotRow]['variables'][$variableToEquateToZero]) AND is_numeric($Matrix[$pivotRow]['variables'][$variableToEquateToZero]) AND $Matrix[$pivotRow]['variables'][$variableToEquateToZero]<>0){
			$pivot=$Matrix[$rowToOperateOn]['variables'][$variableToEquateToZero];
			$Matrix[$rowToOperateOn]['action']="$rowToOperateOn-($pivot)*$pivotRow";	
			foreach ($this->columns as $key => $value){
				if($key=='RHS'){
					$Matrix[$rowToOperateOn][$key]=$Matrix[$rowToOperateOn][$key]-$pivot*$Matrix[$pivotRow][$key];
				}else{
					$Matrix[$rowToOperateOn]['variables'][$key]=$Matrix[$rowToOperateOn]['variables'][$key]-$pivot*$Matrix[$pivotRow]['variables'][$key];
				}
			}
		}else{
			echo "Error: there is a probelm with 'pivotRow'=$pivotRow, the value may not been set / is not numeric / is equal to Zero, please check it out.".$Matrix[$pivotRow]['variables'][$variableToEquateToZero]."<br><br>";
			die();
		}
		return $Matrix;
	}

	Private function simplex_thisShit(){
		$isSolutionOptimal=$this->check_SolutionIsOptimal();
		$iteration_count=1;
		$iteration_count=count($this->iterations_array);

		while(!$isSolutionOptimal AND $iteration_count<$this->max_iterations){
			$this->create_newIterationMatrix();
			$varToInsertToBase=$this->check_varToInsertToBase(); //get var to insert into the base
			$varToRemoveFromBase=$this->check_varToRemoveFromBase($varToInsertToBase); //get var to take out of the base
			$pivotLine=$this->findNewPivotLine($varToRemoveFromBase); // pivot line/row
			$j=count($this->iterations_array)-1;

			if(is_null($pivotLine)){
				echo "Error: Pivot Line was return NULL, something went wrong";
				die();
			}
			
			$isPivotLineNeedFix=$this->fixPivotLineIfNeeded($varToInsertToBase,$pivotLine);
			if(!$isPivotLineNeedFix){
				$this->iterations_array[$j][$pivotLine]['base']=$varToInsertToBase;
			}
			
			$this->updateAllOtherLinesByPivotLine($varToInsertToBase,$pivotLine); // all other lines have to be zero		

			$this->iterations_array_baseChanged[$j]=array('insert to base'=>$varToInsertToBase,'remove from base'=>$varToRemoveFromBase,'pivot line'=>$pivotLine,'isPivot Line Need Fix'=>$isPivotLineNeedFix);

			$iteration_count++;
			$isSolutionOptimal=$this->check_SolutionIsOptimal();
		}
	}

	Private function check_SolutionIsOptimal(){
		//Stop conditions (=SolutionIsOptimal): When all the coefficients in the Z line (Line 1) are not negative in a Max and not a positive in Min.
		$check_matrix=end($this->iterations_array);
		
		if($this->objective_function=='Max'){// Max probelm
			foreach ($check_matrix['L1']['variables'] as $key => $value) {
				if($value<0){
					return false;
				}
			}
		}else{// Min problem
			foreach ($check_matrix['L1']['variables'] as $key => $value) {
				if($value>0){
					return false;
				}
			}
		}
		return true;
	}

	Private function check_varToInsertToBase(){
		// Select the incoming variable: Maximum => the most negative variable | Minimum => the most positive in the Z line in the simplex table.
		// Note: If there are more than one arbitrary choice.
		$check_matrix=end($this->iterations_array);

		if($this->objective_function=='Max'){// Max probelm
			$varToInsert=array('name'=>'default','val'=>1);
			foreach ($check_matrix['L1']['variables'] as $key => $value) {
				if($value<$varToInsert['val']){
					$varToInsert['val']=$value;
					$varToInsert['name']=$key;
				}
			}
		}else{// Min problem
			$varToInsert=array('name'=>'default','val'=>-1);
			foreach ($check_matrix['L1']['variables'] as $key => $value) {
				if($value>$varToInsert['val']){
					$varToInsert['val']=$value;
					$varToInsert['name']=$key;
				}
			}
		}
		return $varToInsert['name'];
	}

	Private function check_varToRemoveFromBase($varToInsertToBase){
		// The variable that will be Remove from the base is the one that has the smallest ratio between its current value (in the RHS column) and its positive coefficient in the incoming variable column.
		// 	Note 1: Do not divide by negative numbers or zeros.
		// 	Note 2: If there are more than one candidate, choose randomly.
		$j=count($this->iterations_array);
		$check_matrix=$this->iterations_array[$j-1];
		$varToRemove=array('lowest_ratio'=>9999999,'var_name'=>'default'); //init

		// run ratio test -> RHS/Cj
		foreach ($check_matrix as $key => $value) {
			if($key=='L1'){continue;}

			if($check_matrix[$key]['variables'][$varToInsertToBase]>0){
				$check_matrix[$key]['ratio']=round($check_matrix[$key]['RHS']/$check_matrix[$key]['variables'][$varToInsertToBase],3);
				
				if($check_matrix[$key]['ratio']<$varToRemove['lowest_ratio']){
					$varToRemove['lowest_ratio']=$check_matrix[$key]['ratio'];
					$varToRemove['var_name']=$value['base'];
				}
			}else{
				$check_matrix[$key]['ratio']='-';
			}
		}
		return $varToRemove['var_name'];
	}

	Private function create_newIterationMatrix(){
		$array_size=count($this->iterations_array);
		$this->iterations_array[]=$this->iterations_array[$array_size-1];
		foreach ($this->iterations_array[$array_size] as $key => $value) {
			if(array_key_exists ('action',$value)){
				unset($this->iterations_array[$array_size][$key]['action']);
			}
		}
	}

	Private function findNewPivotLine($varToRemoveFromBase){
		foreach (end($this->iterations_array) as $key => $value) {
			if($value['base']==$varToRemoveFromBase){
				return $key;
			}
		}
		return null;
	}

	Private function fixPivotLineIfNeeded($varToInsertToBase,$pivotLine){
		$j=count($this->iterations_array)-1;

		if($this->iterations_array[$j][$pivotLine]['variables'][$varToInsertToBase]<>1){
			$divider=$this->iterations_array[$j][$pivotLine]['variables'][$varToInsertToBase];

			foreach ($this->iterations_array[$j][$pivotLine]['variables'] as $key => $value) {
				$this->iterations_array[$j][$pivotLine]['variables'][$key]=$this->iterations_array[$j][$pivotLine]['variables'][$key]/$divider;
			}

			$this->iterations_array[$j][$pivotLine]['RHS']=$this->iterations_array[$j][$pivotLine]['RHS']/$divider;
			$this->iterations_array[$j][$pivotLine]['base']=$varToInsertToBase;
			$this->iterations_array[$j][$pivotLine]['action']=$pivotLine.'/'.$divider;

			return true;
		}else{
			return false;
		}
	}

	Private function updateAllOtherLinesByPivotLine($varToInsertToBase,$pivotLine){
		$j=count($this->iterations_array)-1;

		foreach ($this->iterations_array[$j] as $key1 => $value1) {
			if($key1==$pivotLine){continue;}

			if($value1['variables'][$varToInsertToBase]<>0){
				$divider=$value1['variables'][$varToInsertToBase];

					foreach($this->columns as $key2 => $value2){
						if($key2=='RHS'){continue;}
						
						$this->iterations_array[$j][$key1]['variables'][$key2]=$this->iterations_array[$j][$key1]['variables'][$key2]-$divider*$this->iterations_array[$j][$pivotLine]['variables'][$key2];
					}

				$this->iterations_array[$j][$key1]['RHS']=$this->iterations_array[$j][$key1]['RHS']-$divider*$this->iterations_array[$j][$pivotLine]['RHS'];
				$this->iterations_array[$j][$key1]['action']=$key1.'-('.$divider.' x '.$pivotLine.')';
			}
		}
	}

/* Print Result To Screen */

	Public function print_all_vars(){
		/*$this->var_report['objective_function']=$this->objective_function;
		$this->var_report['original_c']=$this->original_c;
		$this->var_report['original_st']=$this->original_st;
		$this->var_report['isRHS_OK']=$this->isRHS_OK;
		$this->var_report['isObjectiveFunction_OK']=$this->isObjectiveFunction_OK;
		$this->var_report['isInequalitySign_OK']=$this->isInequalitySign_OK;
		$this->var_report['isObjectiveMax']=$this->isObjectiveMax;
		$this->var_report['isBigM']=$this->isBigM;
		$this->var_report['prepare_iterations_array']=$this->prepare_iterations_array;*/
		$this->var_report['iterations_array']=$this->iterations_array;
		$this->var_report['columns']=$this->columns;
		$this->var_report['iterations - base Changed']=$this->iterations_array_baseChanged;

		echo json_encode($this->var_report).PHP_EOL;
	}

	Public function print_matrix($matrix,$columns){
		echo "<style>
		table.minimalistBlack {
		  border: 3px solid #000000;
		  width: 100%;
		  text-align: left;
		  border-collapse: collapse;
		}
		table.minimalistBlack td, table.minimalistBlack th {
		  border: 1px solid #000000;
		  padding: 5px 4px;
		}
		table.minimalistBlack tbody td {
		  font-size: 13px;
		}
		table.minimalistBlack thead {
		  background: #CFCFCF;
		  background: -moz-linear-gradient(top, #dbdbdb 0%, #d3d3d3 66%, #CFCFCF 100%);
		  background: -webkit-linear-gradient(top, #dbdbdb 0%, #d3d3d3 66%, #CFCFCF 100%);
		  background: linear-gradient(to bottom, #dbdbdb 0%, #d3d3d3 66%, #CFCFCF 100%);
		  border-bottom: 3px solid #000000;
		}
		table.minimalistBlack thead th {
		  font-size: 15px;
		  font-weight: bold;
		  color: #000000;
		  text-align: left;
		}
		table.minimalistBlack tfoot td {
		  font-size: 14px;
		}
		</style>";


		echo "<table class=\"minimalistBlack\">";
		echo 	"<thead>";
		echo 		"<tr>";
		echo 			"<th>Line</th>";
		echo 			"<th>Action</th>";
		echo 			"<th>Base</th>";
		
		foreach ($columns as $key => $value) {
				echo	"<th>$key</th>";					
		}

		echo 		"</tr>";
		echo 	"</thead>";
		echo 	"<tbody>";

		
		for ($i=1; $i <= sizeof($matrix); $i++) { 
			echo 	"<tr>";
			echo 		"<td>$i</td>";
			if(array_key_exists('action',$matrix['L'.$i])){echo "<td>".$matrix['L'.$i]['action']."</td>";}else{echo "<td>---</td>";}
			echo 		"<td>".$matrix['L'.$i]['base']."</td>";
			
			foreach ($columns as $key => $value) {
				if($key<>'RHS'){echo 	"<td>".$matrix['L'.$i]['variables'][$key]."</td>";}
			}

			echo 		"<td>".$matrix['L'.$i]['RHS']."</td>";
			echo 	"</tr>";
			echo 	"</tbody";
			echo 	"</table>";
		
		}
	}

	Public function print_all_iterations_matrixes($matrix_arr,$columns){
		echo "<style>
				table.minimalistBlack {
				  border: 3px solid #000000;
				  width: 100%;
				  text-align: left;
				  border-collapse: collapse;
				}
				table.minimalistBlack td, table.minimalistBlack th {
				  border: 1px solid #000000;
				  padding: 5px 4px;
				}
				table.minimalistBlack tbody td {
				  font-size: 13px;
				}
				table.minimalistBlack thead {
				  background: #CFCFCF;
				  background: -moz-linear-gradient(top, #dbdbdb 0%, #d3d3d3 66%, #CFCFCF 100%);
				  background: -webkit-linear-gradient(top, #dbdbdb 0%, #d3d3d3 66%, #CFCFCF 100%);
				  background: linear-gradient(to bottom, #dbdbdb 0%, #d3d3d3 66%, #CFCFCF 100%);
				  border-bottom: 3px solid #000000;
				}
				table.minimalistBlack thead th {
				  font-size: 15px;
				  font-weight: bold;
				  color: #000000;
				  text-align: left;
				}
				table.minimalistBlack tfoot td {
				  font-size: 14px;
				}
			</style>";

		foreach ($matrix_arr as $key => $matrix) {
			echo "<h2>Iteration-$key</h2>";
			if(array_key_exists($key,$this->iterations_array_baseChanged)){echo "<p>insert to base: ".$this->iterations_array_baseChanged[$key]['insert to base']."<br>remove from base: ".$this->iterations_array_baseChanged[$key]['remove from base']."</p>";}

			echo "<table id=\"Iteration-$key\" class=\"minimalistBlack\">";
			echo 	"<thead>";
			echo 		"<tr>";
			echo 			"<th>Line</th>";
			echo 			"<th>Action</th>";
			echo 			"<th>Base</th>";
			
			foreach ($columns as $key => $value) {
				echo	"<th>$key</th>";					
			}

			echo 		"</tr>";
			echo 	"</thead>";
			echo 	"<tbody>";
			
			for ($i=1; $i <= sizeof($matrix); $i++) { 
				echo 	"<tr>";
				echo 		"<td>$i</td>";
				if(array_key_exists('action',$matrix['L'.$i])){echo "<td>".$matrix['L'.$i]['action']."</td>";}else{echo "<td>---</td>";}
				echo 		"<td>".$matrix['L'.$i]['base']."</td>";
				
				foreach ($columns as $key => $value) {
					if($key<>'RHS'){echo 	"<td>".$matrix['L'.$i]['variables'][$key]."</td>";}
				}

				echo 		"<td>".$matrix['L'.$i]['RHS']."</td>";
				echo 	"</tr>";
			
			}
				echo 	"</tbody>";
				echo 	"</table>";

		}//foreach
	}

	Public function print_solve_answer(){
		$last_matrix=end($this->iterations_array);
		if(isset($last_matrix) AND !is_null($last_matrix)){
			echo '<br>';
			foreach ($last_matrix as $key => $value) {
				echo $value['base'].' = '.round($value['RHS'],5).'<BR>';
			}
		}
	}

} //end class

#$this->print_all_vars();die();/*******************************************/
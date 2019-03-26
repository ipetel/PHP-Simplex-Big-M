# PHP-Simplex-Big-M
solve Linear Programming problem by using Simplex or Simplex + Big M algorithm

*** Little Intro ***

	Hey, You there!
	Let's start the fun. This code was developed to solve Linear Programming problems
	by using Simplex or Simplex + Big M algorithm => the code will automatically detect what method to use.


*** Preparation ***

	This code, as the simplex/Big M algorithms, operates on linear programs in standard form.

	objective function
		Max Z=c1x1+c2x2+c3x3+...
	Subject to (=s.t.)
		x1<=b1
		x1-x2=b2
		x1+2x3>=b3

	xi - Variables of the problem 
	ci - Coefficients of the objective function
	bi - Constants numbers, must be positive or zero (bi>=0) - also known as RHS = Right Hand Side of the Constraint


*** An Explanation Of The File Structure Of The Project ***

	1) index.php - Runs the project and solves the problem.
	2) data.php - Contains all our Story as data. Usually, this file will mostly to be changed.
	3) calculations_class - Contains all the "fun" math calculations that need to be done to solve the problem.

*** Ok, how do I use this code? ***

	Go to "data.php" file and read the Explanations

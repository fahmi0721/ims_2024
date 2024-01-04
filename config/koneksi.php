<?php
	$options = [
	    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
	    PDO::ATTR_CASE => PDO::CASE_NATURAL,
	    PDO::ATTR_ORACLE_NULLS => PDO::NULL_EMPTY_STRING
	];
	try{
		$db=new PDO('mysql:host=localhost;dbname=ims_2024','root','',$options);
	}catch(PDOException $e) {
	    die("Database connection failed: " . $e->getMessage());
	}

		// $options = [
		//     PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
		//     PDO::ATTR_CASE => PDO::CASE_NATURAL,
		//     PDO::ATTR_ORACLE_NULLS => PDO::NULL_EMPTY_STRING
		// ];
		// try{
		// 	$db=new PDO('mysql:host=localhost;dbname=n1732945_ims','n1732945_ims','nLcd7Tp1Y$Sw',$options);
		// }catch(PDOException $e) {
		//     die("Database connection failed: " . $e->getMessage());
		// }
?>



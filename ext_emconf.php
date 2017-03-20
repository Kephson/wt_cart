<?php
########################################################################
# Extension Manager/Repository config file for ext "wt_cart".
#
#
# Manual updates:
# Only the data in the array - everything else is removed by next
# writing. "version" and "dependencies" must not be touched!
########################################################################

$EM_CONF[$_EXTKEY] = array(
	'title' => 'Shopping Cart for TYPO3',
	'description' => 'Adds shopping cart(s) to your TYPO3 installation and utilizes powermail for checkout',
	'category' => 'plugin',
	'shy' => '',
	'version' => '4.0.0',
	'constraints' => array(
		'depends' => array(
			'typo3' => '7.6.0-7.99.99',
			'php' => '5.4.0-7.1.99',
		),
		'conflicts' => array(
		),
		'suggests' => array(
		),
	),
	'module' => '',
	'state' => 'stable',
	'uploadfolder' => '',
	'createDirs' => '',
	'modify_tables' => '',
	'clearcacheonload' => 1,
	'lockType' => '',
	'author' => 'wt_cart Development Team',
	'author_email' => 'info@wt-cart.com',
	'author_company' => '',
	'CGLcompliance' => '',
	'CGLcompliance_note' => '',
);

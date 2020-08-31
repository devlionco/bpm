<?php

require(__DIR__ . '/vendor/autoload.php');
use mikehaertl\wkhtmlto\Pdf;

$html_template = file_get_contents("assets/TestCourseCreate.html");

// Create a new Pdf object with some global PDF options
	$pdf = new Pdf(array(
	    'no-outline',         // Make Chrome not complain
	    'margin-top'    => 0,
	    'margin-right'  => 0,
	    'margin-bottom' => 0,
	    'margin-left'   => 0,

	    // Default page options
	    'disable-smart-shrinking',
	    'binary' => __DIR__ . '/wkhtmltopdf'
	));

//	header('Content-Type: application/pdf; charset=utf-8');
// 	header('Content-Disposition: attachment; filename="bpm_cert.pdf"');
	$html_template = mb_convert_encoding($html_template, "UTF-8");

	file_put_contents('tempTemplate.html', $html_template);

	// Add a page. To override above page defaults, you could add
	// another $options array as second argument.
	$pdf->addPage('tempTemplate.html');

	if (!$pdf->send('test.pdf')) {
	    throw new Exception('Could not create PDF: '.$pdf->getError());
	}

	unlink('tempTemplate.html');
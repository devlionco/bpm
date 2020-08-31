<?php

require_once(__DIR__ .'/../../config.php');
require_once(__DIR__ .'/../../lib/tcpdf/tcpdf.php');
global $USER, $DB;

$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('BPM');
$pdf->SetTitle('BPM_Validation');
$pdf->SetSubject('BPM_Validation');
$pdf->SetKeywords('TCPDF, PDF');
//file_put_content('font', $pdf->addTTFfont('narkissnew-medium_mfw.ttf', 'TrueTypeUnicode', '', 32));
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
$pdf->SetMargins(PDF_MARGIN_LEFT, 0, PDF_MARGIN_RIGHT);
$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
$lg = Array();
$lg['a_meta_dir'] = 'rtl';
$pdf->setLanguageArray($lg);
$pdf->setFontSubsetting(true);
$pdf->SetFont('freesans', '', 14, '', true);
$logo =  '../../blocks/study_validation/img/logo-head.png';
$footerImg = file_get_contents('../../local/BPM_pix/footer2018/footer_black.png');
// $pdf->setHeaderData($logo, '45');
// $pdf->setHeaderMargin(3);
$courses = $_POST['courses'];
$html = '';
$html_head = '<!DOCTYPE html>
<html>
<head>
<style>
	.title {
		text-align: center;
		text-decoration: underline;
		font-weight: bold;
	}
</style>
</head>
<body>
<div class="content">
	<table>
		<tr>
			<td></td>
			<td></td>
			<td></td>
			<td><img class="header" src="' . $logo . '"/></td>
		</tr>
		<tr>
			<td></td>
			<td></td>
			<td></td>
			<td class="header">' . date("d/m/y") . '</td>
		</tr>
	</table>
    <div>
        <h1 class="title">' . get_string('study_validation-bpm','block_study_validation') . '</h3>
        <h3 class="title">' . get_string('student','block_study_validation') . ' ' . $USER->firstname. ' ' . $USER->lastname;
	if(!empty($USER->idnumber)) {
		$html_head .= ' ' . get_string('id','block_study_validation') . $USER->idnumber .'</h3>';
	} else {
		$html_head .= '</h3>';
	}

	$html_head .= '</div>
    <div class="body">
       <p>1. ' . get_string('study_validation_content1', 'block_study_validation') . '<br>
			2. ' . get_string('study_validation_content2', 'block_study_validation') . '<br>
			3. ' . get_string('study_validation_content3', 'block_study_validation') . '<br>';
$html_foot = 
	'</div>
	<h5>' . get_string('study_validation_footer1','block_study_validation') . '</h5>
	<table>
		<tr>
			<td></td>
			<td></td>
			<td></td>
			<td><h5>' . get_string('study_validation_footer2','block_study_validation') . '</h5></td>
		</tr>
	</table>
</div>
</body>
</html>';

foreach($courses as $course)
{
	$i = 4;
	$pdf->AddPage();
	$html .= $html_head;
	$html_body = '';
	$details = $DB->get_record_sql('SELECT c.shortname, FROM_UNIXTIME(c.startdate) as startdate, FROM_UNIXTIME(c.enddate) as enddate, cd.several_days FROM {course} c LEFT JOIN {course_details} cd ON c.id = cd .courseid WHERE c.shortname = ?', array($course));
	$html_body .= $i . '. ' . get_string('study_validation_content4', 'block_study_validation') . ' ' . $details->shortname . '<br>';
	$i++;
	$html_body .= $i . '. ' . get_string('study_validation_content5', 'block_study_validation') . ' ' . date('d/m/y', strtotime($details->startdate)) . '<br>';
	$i++;
	$html_body .= $i . '. ' . get_string('study_validation_content6', 'block_study_validation') . ' ' . date('d/m/y', strtotime($details->enddate)) . '<br>';
	$i++;
	$html_body .= $i . '. ' . get_string('study_validation_content7', 'block_study_validation') . ': ' . $details->several_days . '<br>';
	$i++;
	$html .= $html_body . $html_foot;
	$pdf->writeHTML($html, true, false, true, false, '');
	$pdf->SetAutoPageBreak(false, 0);
	$pdf->Image('@' . $footerImg, 195, 280, 0, 0, 'PNG', '', '', false, 300, '', false, false, 0, false, false, true);
}

$pdf->Output('BPM_Validation.pdf', 'D');
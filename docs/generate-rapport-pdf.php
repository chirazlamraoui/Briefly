<?php

require __DIR__.'/../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

$html = file_get_contents(__DIR__.'/rapport-briefly.html');

$options = new Options;
$options->set('defaultFont', 'DejaVu Sans');
$options->set('isRemoteEnabled', false);

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

$output = __DIR__.'/rapport-briefly.pdf';
file_put_contents($output, $dompdf->output());

echo 'Generated: '.$output.PHP_EOL;

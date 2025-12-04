<?php
require __DIR__ . '/vendor/autoload.php';


use Dompdf\Dompdf;
use Dompdf\Options;

$name = "FUCK THIS SHIIIIIT";

$options = new Options();
$options->setChroot(__DIR__);
$dompdf = new Dompdf();
$dompdf->setBasePath(__DIR__ ."/Views/css/bootstrap.css");
$html = file_get_contents(__DIR__ ."/Views/template/template.phtml");
$html = str_replace("{{name}}", $name, $html);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
$dompdf->stream("invoice.pdf", ["Attachment" => false]);

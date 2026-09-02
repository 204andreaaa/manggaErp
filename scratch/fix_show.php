<?php
$f = 'C:/Users/itmdu/OneDrive/Documents/Mandau/project/project/kumpulan MP3/mp3/mp3/resources/views/erp/payment_advices/show.blade.php';
$c = file_get_contents($f);
$c = preg_replace('/\{\{-- Modal Approve PA --\}\}.*?(?=<script>)/s', '', $c);
file_put_contents($f, $c);
echo "Fixed show.blade.php";

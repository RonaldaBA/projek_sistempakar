<?php
function getRec($crop) {
    $recs = [
        'padi' => date('Y-m-d H:i:s', strtotime('+100 days')),
        'jagung' => 'Tanam: 1-20 Juli | Panen: 20-30 September…',
        'kedelai' => 'Tanam: 10-25 Agustus | Panen: 25 Oktober-10 Desember…',
        'cabe' => 'Tanam: 15-30 Juli | Panen: 15-30 Oktober…',
        'bawang' => 'Tanam: 5-20 Agustus | Panen: 5-20 November…',
    ];
    
}
echo getRec($_POST['crop'] ?? '');

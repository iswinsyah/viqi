<option value="">-- Pilih Hari --</option>
<?php foreach ($hari_opsi as $h): 
    $selected = '';
    if (isset($data_kesediaan) && isset($data_kesediaan['hari_'.($i ?? 1)]) && $data_kesediaan['hari_'.($i ?? 1)] == $h) $selected = 'selected';
?>
    <option value="<?= $h ?>" <?= $selected ?>><?= $h ?></option>
<?php endforeach; ?>
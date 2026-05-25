<option value="">-- Pilih Mapel --</option>
<?php foreach ($mapel_list as $kategori => $mapels): ?>
    <optgroup label="<?= htmlspecialchars($kategori) ?>">
        <?php foreach ($mapels as $mapel): 
            $selected = '';
            if (isset($data_kesediaan) && isset($data_kesediaan['mapel_'.($i ?? 1).'_id']) && $data_kesediaan['mapel_'.($i ?? 1).'_id'] == $mapel['id']) $selected = 'selected';
        ?>
            <option value="<?= $mapel['id'] ?>" <?= $selected ?>><?= htmlspecialchars($mapel['nama_mapel']) ?></option>
        <?php endforeach; ?>
    </optgroup>
<?php endforeach; ?>
<?php
// Modal Universal Pemilihan / Penambahan Ananda Bersaudara (Multi-Child)
$all_active_santri_modal = [];
if (isset($conn) && $conn instanceof mysqli) {
    $res_all_m = $conn->query("SELECT id, nama_lengkap, kelas_sekarang, kamar_asrama, foto_santri FROM buku_induk_santri WHERE status_santri = 'Aktif' ORDER BY nama_lengkap ASC");
    if ($res_all_m) {
        while ($r = $res_all_m->fetch_assoc()) $all_active_santri_modal[] = $r;
    }
}
$locked_ids_modal = function_exists('getLockedSantriIds') ? getLockedSantriIds($conn, $_SESSION['orangtua_id'] ?? 0) : [];
?>

<!-- MODAL OVERLAY & POPUP -->
<div id="modal-pilih-ananda" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs hidden flex items-center justify-center p-4 transition-opacity">
    <div class="bg-white rounded-3xl shadow-2xl border border-teal-100 w-full max-w-lg overflow-hidden flex flex-col max-h-[90vh] animate-in fade-in zoom-in duration-200">
        
        <!-- MODAL HEADER -->
        <div class="px-6 py-5 bg-gradient-to-r from-teal-50 to-emerald-50 border-b border-teal-100 flex items-center justify-between flex-shrink-0">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-2xl bg-[#0b8478] text-white flex items-center justify-center shadow-md">
                    <i class="fas fa-users-viewfinder text-base"></i>
                </div>
                <div>
                    <h3 class="font-black text-slate-900 text-base leading-tight">Pilih Ananda Tercinta</h3>
                    <p class="text-[11px] text-slate-500 mt-0.5">Bisa pilih 1 anak atau lebih jika bersaudara kandung</p>
                </div>
            </div>
            <button type="button" onclick="closePilihAnandaModal()" class="text-slate-400 hover:text-slate-600 p-2 rounded-xl hover:bg-white/80 transition">
                <i class="fas fa-times text-base"></i>
            </button>
        </div>

        <!-- FORM SUBMIT -->
        <form action="" method="POST" id="form-pilih-ananda-multi" class="flex flex-col flex-1 overflow-hidden">
            <input type="hidden" name="action" value="lock_multiple_ananda">

            <!-- SEARCH FILTER -->
            <div class="p-4 border-b border-slate-100 bg-slate-50/50 flex-shrink-0">
                <div class="relative">
                    <i class="fas fa-search absolute left-3.5 top-3 text-slate-400 text-xs"></i>
                    <input type="text" id="cari-santri-modal" oninput="filterSantriModal()" placeholder="Ketik nama ananda untuk mencari..." class="w-full pl-9 pr-4 py-2 bg-white border border-slate-200 rounded-xl text-xs font-semibold text-slate-800 focus:ring-2 focus:ring-[#0b8478] focus:outline-none transition">
                </div>
                <div class="flex items-center justify-between mt-2.5 px-1 text-[11px]">
                    <span class="text-slate-500">Centang kotak pada nama ananda Anda:</span>
                    <span id="counter-terpilih-badge" class="font-bold text-[#0b8478] bg-teal-50 px-2 py-0.5 rounded-full border border-teal-200">
                        0 Ananda Dipilih
                    </span>
                </div>
            </div>

            <!-- SCROLLABLE LIST OF SANTRI -->
            <div class="flex-1 overflow-y-auto p-4 space-y-2 no-scrollbar" id="list-santri-container">
                <?php if (empty($all_active_santri_modal)): ?>
                    <p class="text-center text-xs text-slate-400 py-8">Tidak ada data santri aktif.</p>
                <?php else: ?>
                    <?php foreach ($all_active_santri_modal as $santri_item): 
                        $is_checked = in_array((int)$santri_item['id'], $locked_ids_modal);
                    ?>
                        <label class="santri-item-row flex items-center justify-between p-3 rounded-2xl border <?= $is_checked ? 'border-teal-300 bg-teal-50/40' : 'border-slate-200/80 bg-white hover:bg-slate-50' ?> cursor-pointer transition select-none">
                            <div class="flex items-center gap-3 overflow-hidden pr-2">
                                <input type="checkbox" name="santri_ids[]" value="<?= $santri_item['id'] ?>" <?= $is_checked ? 'checked' : '' ?> onchange="updateCounterModal(this)" class="santri-modal-cb w-4 h-4 rounded text-[#0b8478] focus:ring-[#0b8478] border-slate-300 cursor-pointer">
                                <div class="overflow-hidden">
                                    <div class="font-bold text-xs text-slate-900 leading-tight truncate nama-santri-text"><?= htmlspecialchars($santri_item['nama_lengkap']) ?></div>
                                    <div class="text-[10px] text-slate-500 mt-0.5 flex items-center gap-1.5">
                                        <span class="font-semibold text-teal-800"><?= htmlspecialchars($santri_item['kelas_sekarang'] ?? 'Santri') ?></span>
                                        <?php if (!empty($santri_item['kamar_asrama'])): ?>
                                            <span>•</span>
                                            <span>Kamar <?= htmlspecialchars($santri_item['kamar_asrama']) ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <span class="status-terpilih-badge text-[10px] font-bold px-2 py-0.5 rounded-full <?= $is_checked ? 'bg-teal-100 text-teal-800' : 'hidden' ?>">
                                Terpilih
                            </span>
                        </label>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- MODAL FOOTER -->
            <div class="px-6 py-4 bg-slate-50 border-t border-slate-100 flex items-center justify-between flex-shrink-0">
                <button type="button" onclick="closePilihAnandaModal()" class="px-4 py-2 rounded-xl text-xs font-bold text-slate-600 hover:text-slate-800 transition">
                    Batal
                </button>
                <button type="submit" class="px-5 py-2.5 rounded-xl bg-[#0b8478] hover:bg-teal-700 text-white text-xs font-black shadow-md shadow-teal-900/10 flex items-center gap-2 transition">
                    <i class="fas fa-lock"></i>
                    <span>Kunci & Simpan Ananda</span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openPilihAnandaModal() {
    const modal = document.getElementById('modal-pilih-ananda');
    if (modal) {
        modal.classList.remove('hidden');
        recountCheckedModal();
        const searchInput = document.getElementById('cari-santri-modal');
        if (searchInput) searchInput.focus();
    }
}

function closePilihAnandaModal() {
    const modal = document.getElementById('modal-pilih-ananda');
    if (modal) modal.classList.add('hidden');
}

function filterSantriModal() {
    const query = (document.getElementById('cari-santri-modal').value || '').toLowerCase();
    const rows = document.querySelectorAll('.santri-item-row');
    rows.forEach(row => {
        const name = (row.querySelector('.nama-santri-text')?.innerText || '').toLowerCase();
        if (name.includes(query)) {
            row.classList.remove('hidden');
        } else {
            row.classList.add('hidden');
        }
    });
}

function updateCounterModal(checkbox) {
    const row = checkbox.closest('.santri-item-row');
    const badge = row.querySelector('.status-terpilih-badge');
    if (checkbox.checked) {
        row.classList.add('border-teal-300', 'bg-teal-50/40');
        row.classList.remove('border-slate-200/80', 'bg-white');
        if (badge) badge.classList.remove('hidden');
    } else {
        row.classList.remove('border-teal-300', 'bg-teal-50/40');
        row.classList.add('border-slate-200/80', 'bg-white');
        if (badge) badge.classList.add('hidden');
    }
    recountCheckedModal();
}

function recountCheckedModal() {
    const checked = document.querySelectorAll('.santri-modal-cb:checked').length;
    const badge = document.getElementById('counter-terpilih-badge');
    if (badge) {
        badge.innerText = checked + ' Ananda Dipilih';
    }
}

document.addEventListener('DOMContentLoaded', recountCheckedModal);
</script>

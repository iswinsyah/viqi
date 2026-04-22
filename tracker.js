document.addEventListener('DOMContentLoaded', function() {
    // Rekam jejak hanya 1x per sesi per halaman agar database tidak penuh (anti-spam)
    const page = window.location.pathname.split("/").pop() || 'index.html';
    const sessionKey = 'tracked_' + page;
    
    if (!sessionStorage.getItem(sessionKey)) {
        // Ambil parameter URL (Misal ada pengunjung masuk dari link agen: ?ref=UstadzBudi)
        const urlParams = new URLSearchParams(window.location.search);
        const refCode = urlParams.get('ref') || localStorage.getItem('agen_ref') || 'Organik';
        const utmSource = urlParams.get('utm_source') || (document.referrer ? new URL(document.referrer).hostname : 'Direct');

        // Susun Data Jejak Digital (Footprint)
        const data = {
            device: /Mobile|Android|iP(hone|od)|IEMobile|BlackBerry|Kindle/i.test(navigator.userAgent) ? 'Mobile' : 'Desktop',
            os_browser: navigator.userAgent,
            language: navigator.language || 'Unknown',
            timezone: Intl.DateTimeFormat().resolvedOptions().timeZone || 'Unknown',
            time: new Date().toISOString(),
            page_viewed: page,
            source: utmSource,
            campaign: refCode, // Menyimpan kode referal agen sebagai campaign
            traffic_type: document.referrer ? 'Referral/Search' : 'Direct',
            location: 'Memuat...',
            isp: 'Memuat...'
        };

        // Melacak IP, Lokasi Kota/Negara, dan ISP menggunakan API publik
        fetch('https://ipapi.co/json/')
            .then(res => res.json())
            .then(geo => {
                data.location = (geo.city ? geo.city + ', ' + geo.country_name : 'Unknown');
                data.isp = geo.org || 'Unknown';
                kirimDataTracker(data);
            })
            .catch(err => {
                data.location = 'Gagal dilacak';
                data.isp = 'Gagal dilacak';
                kirimDataTracker(data); // Tetap kirim meski gagal melacak lokasi
            });

        function kirimDataTracker(trackerData) {
            fetch('save_tracker.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(trackerData)
            })
            .then(res => res.json())
            .then(res => { if(res.status === 'success') sessionStorage.setItem(sessionKey, 'true'); })
            .catch(e => console.error('Mata AI Error:', e));
        }
    }
});
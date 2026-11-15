/**
 * cs-hasna-widget.js
 * Widget Chatbot Interaktif "Hasna" - Customer Service Virtual Villa Quran Baron Malang
 * Dapat dipasang di halaman manapun cukup dengan: <script src="cs-hasna-widget.js"></script>
 */

(function () {
    // Hindari duplikasi widget jika script dimuat lebih dari sekali
    if (window.HasnaChatWidgetLoaded) return;
    window.HasnaChatWidgetLoaded = true;

    const API_ENDPOINT = 'api-cs-hasna.php';
    let currentSession = localStorage.getItem('hasna_session_token') || null;
    let currentUserName = localStorage.getItem('hasna_user_name') || '';
    let isWidgetOpen = false;

    // 1. INJECT STYLE CSS ISOLATED
    const styleEl = document.createElement('style');
    styleEl.innerHTML = `
        #hasna-widget-container {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            position: fixed;
            bottom: 24px;
            right: 24px;
            z-index: 999999;
        }
        #hasna-bubble-btn {
            background: linear-gradient(135deg, #0b8478 0%, #065b53 100%);
            color: #ffffff;
            width: 62px;
            height: 62px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 10px 25px -5px rgba(11, 132, 120, 0.5), 0 8px 10px -6px rgba(11, 132, 120, 0.3);
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
            position: relative;
            border: 2px solid rgba(255, 255, 255, 0.3);
        }
        #hasna-bubble-btn:hover {
            transform: scale(1.08) translateY(-2px);
            box-shadow: 0 16px 30px -4px rgba(11, 132, 120, 0.6);
        }
        #hasna-bubble-badge {
            position: absolute;
            top: -2px;
            right: -2px;
            background: #f59e0b;
            color: #1e293b;
            font-size: 10px;
            font-weight: 800;
            padding: 2px 7px;
            border-radius: 999px;
            border: 2px solid #ffffff;
            box-shadow: 0 2px 5px rgba(0,0,0,0.15);
            display: flex;
            align-items: center;
            gap: 3px;
        }
        #hasna-bubble-badge span {
            width: 6px;
            height: 6px;
            background: #10b981;
            border-radius: 50%;
            display: inline-block;
            animation: hasna-pulse 1.8s infinite;
        }
        @keyframes hasna-pulse {
            0% { transform: scale(0.9); opacity: 0.8; }
            50% { transform: scale(1.4); opacity: 1; }
            100% { transform: scale(0.9); opacity: 0.8; }
        }
        #hasna-chat-window {
            position: fixed;
            bottom: 96px;
            right: 24px;
            width: 370px;
            max-width: calc(100vw - 32px);
            height: 540px;
            max-height: calc(100vh - 120px);
            background: #ffffff;
            border-radius: 20px;
            box-shadow: 0 20px 40px -10px rgba(0, 0, 0, 0.25), 0 1px 3px rgba(0, 0, 0, 0.05);
            display: none;
            flex-direction: column;
            overflow: hidden;
            border: 1px solid #e2e8f0;
            z-index: 999999;
            animation: hasna-slide-up 0.25s ease-out;
        }
        @keyframes hasna-slide-up {
            from { opacity: 0; transform: translateY(20px) scale(0.96); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }
        .hasna-header {
            background: linear-gradient(135deg, #0b8478 0%, #065b53 100%);
            color: white;
            padding: 16px 18px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }
        .hasna-header-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .hasna-avatar {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            background: white;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #0b8478;
            font-size: 20px;
            font-weight: bold;
            box-shadow: 0 2px 6px rgba(0,0,0,0.15);
            position: relative;
        }
        .hasna-avatar .hasna-online-dot {
            position: absolute;
            bottom: 1px;
            right: 1px;
            width: 10px;
            height: 10px;
            background: #10b981;
            border-radius: 50%;
            border: 2px solid white;
        }
        .hasna-title {
            font-weight: 800;
            font-size: 15px;
            line-height: 1.2;
            margin: 0;
        }
        .hasna-subtitle {
            font-size: 11px;
            color: #ccfbf1;
            margin: 2px 0 0 0;
            display: flex;
            align-items: center;
            gap: 4px;
        }
        .hasna-header-actions button {
            background: rgba(255,255,255,0.15);
            border: none;
            color: white;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background 0.2s;
            font-size: 14px;
        }
        .hasna-header-actions button:hover {
            background: rgba(255,255,255,0.3);
        }
        .hasna-body {
            flex: 1;
            overflow-y: auto;
            padding: 16px;
            background: #f8fafc;
            display: flex;
            flex-direction: column;
            gap: 12px;
            scroll-behavior: smooth;
        }
        .hasna-msg {
            max-width: 82%;
            padding: 10px 14px;
            border-radius: 14px;
            font-size: 13px;
            line-height: 1.5;
            position: relative;
            word-wrap: break-word;
        }
        .hasna-msg-bot {
            background: #ffffff;
            color: #1e293b;
            align-self: flex-start;
            border-bottom-left-radius: 3px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.06);
            border: 1px solid #f1f5f9;
        }
        .hasna-msg-user {
            background: #0b8478;
            color: #ffffff;
            align-self: flex-end;
            border-bottom-right-radius: 3px;
            box-shadow: 0 2px 6px rgba(11, 132, 120, 0.25);
        }
        .hasna-msg-time {
            font-size: 9px;
            margin-top: 4px;
            text-align: right;
            opacity: 0.7;
        }
        .hasna-suggestions {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            padding: 4px 0;
        }
        .hasna-pill {
            background: #e6fffa;
            color: #0b8478;
            border: 1px solid #b2f5ea;
            border-radius: 999px;
            padding: 5px 10px;
            font-size: 11px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }
        .hasna-pill:hover {
            background: #0b8478;
            color: #ffffff;
            transform: translateY(-1px);
        }
        .hasna-form-box {
            background: #ffffff;
            padding: 16px;
            border-radius: 14px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);
            margin: auto 0;
        }
        .hasna-form-box h4 {
            margin: 0 0 6px 0;
            font-size: 14px;
            color: #0f172a;
            font-weight: 800;
        }
        .hasna-form-box p {
            margin: 0 0 14px 0;
            font-size: 12px;
            color: #64748b;
            line-height: 1.4;
        }
        .hasna-input-group {
            margin-bottom: 10px;
        }
        .hasna-input-group label {
            display: block;
            font-size: 11px;
            font-weight: 700;
            color: #334155;
            margin-bottom: 4px;
        }
        .hasna-input-group input {
            width: 100%;
            padding: 9px 12px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            font-size: 12px;
            box-sizing: border-box;
            outline: none;
            transition: border-color 0.2s;
        }
        .hasna-input-group input:focus {
            border-color: #0b8478;
            box-shadow: 0 0 0 3px rgba(11, 132, 120, 0.15);
        }
        .hasna-btn-submit {
            width: 100%;
            background: #0b8478;
            color: white;
            border: none;
            padding: 10px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
            transition: background 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }
        .hasna-btn-submit:hover {
            background: #08665c;
        }
        .hasna-footer {
            padding: 10px 14px;
            background: #ffffff;
            border-top: 1px solid #f1f5f9;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .hasna-input-msg {
            flex: 1;
            padding: 10px 14px;
            border: 1px solid #cbd5e1;
            border-radius: 999px;
            font-size: 12.5px;
            outline: none;
            transition: border-color 0.2s;
            box-sizing: border-box;
        }
        .hasna-input-msg:focus {
            border-color: #0b8478;
            box-shadow: 0 0 0 2px rgba(11, 132, 120, 0.1);
        }
        .hasna-btn-send {
            background: #0b8478;
            color: white;
            border: none;
            width: 38px;
            height: 38px;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: transform 0.15s, background 0.2s;
            flex-shrink: 0;
        }
        .hasna-btn-send:hover {
            background: #08665c;
            transform: scale(1.05);
        }
        .hasna-typing {
            display: flex;
            gap: 4px;
            padding: 8px 12px;
            background: #ffffff;
            border-radius: 12px;
            width: fit-content;
            border: 1px solid #f1f5f9;
        }
        .hasna-dot {
            width: 6px;
            height: 6px;
            background: #0b8478;
            border-radius: 50%;
            animation: hasna-dot-blink 1.2s infinite both;
        }
        .hasna-dot:nth-child(2) { animation-delay: 0.2s; }
        .hasna-dot:nth-child(3) { animation-delay: 0.4s; }
        @keyframes hasna-dot-blink {
            0%, 80%, 100% { opacity: 0.2; transform: scale(0.8); }
            40% { opacity: 1; transform: scale(1.1); }
        }
        .hasna-wa-cta {
            background: #f0fdf4;
            border: 1px dashed #86efac;
            padding: 8px 12px;
            border-radius: 10px;
            font-size: 11px;
            color: #166534;
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-top: 6px;
        }
        .hasna-wa-cta a {
            background: #16a34a;
            color: white;
            text-decoration: none;
            padding: 4px 8px;
            border-radius: 6px;
            font-weight: bold;
            font-size: 10px;
        }
    `;
    document.head.appendChild(styleEl);

    // 2. CREATE WIDGET HTML STRUCTURE
    const widgetContainer = document.createElement('div');
    widgetContainer.id = 'hasna-widget-container';
    widgetContainer.innerHTML = `
        <!-- BUBBLE BUTTON -->
        <div id="hasna-bubble-btn" title="Tanya Hasna (CS Virtual Villa Quran)">
            <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
            </svg>
            <div id="hasna-bubble-badge">
                <span></span> Hasna
            </div>
        </div>

        <!-- CHAT WINDOW -->
        <div id="hasna-chat-window">
            <div class="hasna-header">
                <div class="hasna-header-info">
                    <div class="hasna-avatar">
                        🧕
                        <div class="hasna-online-dot"></div>
                    </div>
                    <div>
                        <h3 class="hasna-title">Hasna • CS AI</h3>
                        <p class="hasna-subtitle">
                            <span>Villa Quran Baron Malang</span>
                        </p>
                    </div>
                </div>
                <div class="hasna-header-actions">
                    <button id="hasna-btn-close" title="Tutup Chat">✕</button>
                </div>
            </div>

            <!-- CHAT BODY -->
            <div id="hasna-chat-body" class="hasna-body">
                <!-- Konten dinamis (Form atau Chat) -->
            </div>

            <!-- CHAT FOOTER -->
            <div id="hasna-chat-footer" class="hasna-footer" style="display: none;">
                <input type="text" id="hasna-msg-input" class="hasna-input-msg" placeholder="Ketik pertanyaan untuk Hasna..." autocomplete="off">
                <button id="hasna-btn-send" class="hasna-btn-send" title="Kirim">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="22" y1="2" x2="11" y2="13"></line>
                        <polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
                    </svg>
                </button>
            </div>
        </div>
    `;
    document.body.appendChild(widgetContainer);

    // 3. ELEMEN REFERENCES
    const bubbleBtn = document.getElementById('hasna-bubble-btn');
    const chatWindow = document.getElementById('hasna-chat-window');
    const btnClose = document.getElementById('hasna-btn-close');
    const chatBody = document.getElementById('hasna-chat-body');
    const chatFooter = document.getElementById('hasna-chat-footer');
    const msgInput = document.getElementById('hasna-msg-input');
    const btnSend = document.getElementById('hasna-btn-send');

    // 4. EVENT LISTENERS
    bubbleBtn.addEventListener('click', toggleWidget);
    btnClose.addEventListener('click', toggleWidget);

    function toggleWidget() {
        isWidgetOpen = !isWidgetOpen;
        if (isWidgetOpen) {
            chatWindow.style.display = 'flex';
            renderCurrentView();
        } else {
            chatWindow.style.display = 'none';
        }
    }

    // 5. RENDER LOGIC
    function renderCurrentView() {
        if (!currentSession) {
            renderPreChatForm();
        } else {
            renderChatRoom();
        }
    }

    // Form di awal chat (Pre-Chat Lead Form)
    function renderPreChatForm() {
        chatFooter.style.display = 'none';
        chatBody.innerHTML = `
            <div class="hasna-msg hasna-msg-bot">
                Assalamu'alaikum Ayah/Bunda! Selamat datang di Layanan Informasi <b>Villa Quran Baron Malang</b>. 
                <br><br>
                Saya <b>Hasna</b>, asisten virtual siap membantu menjawab pertanyaan seputar program tahfidz, fasilitas asrama, dan pendaftaran santri baru (SPMB).
            </div>

            <div class="hasna-form-box">
                <h4>📋 Mulai Konsultasi dengan Hasna</h4>
                <p>Silakan lengkapi kontak berikut agar Hasna dapat menyapa Ayah/Bunda secara langsung:</p>
                
                <form id="hasna-lead-form">
                    <div class="hasna-input-group">
                        <label>Nama Lengkap Anda</label>
                        <input type="text" id="hasna-input-nama" placeholder="Contoh: Ayah Budi / Bunda Aisyah" required>
                    </div>
                    <div class="hasna-input-group">
                        <label>Nomor WhatsApp Aktif</label>
                        <input type="tel" id="hasna-input-wa" placeholder="Contoh: 08123456789" required>
                    </div>
                    <button type="submit" class="hasna-btn-submit" id="hasna-submit-btn">
                        <span>Mulai Tanya Jawab</span> 💬
                    </button>
                </form>
            </div>
        `;

        const form = document.getElementById('hasna-lead-form');
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            const nama = document.getElementById('hasna-input-nama').value.trim();
            const wa = document.getElementById('hasna-input-wa').value.trim();

            if (!nama || !wa) return;

            const submitBtn = document.getElementById('hasna-submit-btn');
            submitBtn.disabled = true;
            submitBtn.innerHTML = 'Menghubungkan ke Hasna...';

            fetch(API_ENDPOINT, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({
                    action: 'start_session',
                    nama: nama,
                    whatsapp: wa
                })
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    currentSession = data.session_token;
                    currentUserName = data.nama;
                    localStorage.setItem('hasna_session_token', currentSession);
                    localStorage.setItem('hasna_user_name', currentUserName);
                    renderChatRoom();
                } else {
                    alert(data.message || 'Gagal memulai sesi.');
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = 'Mulai Tanya Jawab 💬';
                }
            })
            .catch(err => {
                console.error(err);
                alert('Terjadi kendala koneksi ke server.');
                submitBtn.disabled = false;
                submitBtn.innerHTML = 'Mulai Tanya Jawab 💬';
            });
        });
    }

    // Tampilan Ruang Chat Interaktif
    function renderChatRoom() {
        chatFooter.style.display = 'flex';
        chatBody.innerHTML = '<div style="text-align:center; padding: 20px; font-size:12px; color:#64748b;">Memuat percakapan...</div>';

        // Load riwayat chat
        fetch(`${API_ENDPOINT}?action=get_history&session_token=${encodeURIComponent(currentSession)}`)
            .then(res => res.json())
            .then(data => {
                chatBody.innerHTML = '';
                if (data.status === 'success' && data.messages && data.messages.length > 0) {
                    data.messages.forEach(m => {
                        appendMessage(m.sender, m.pesan, m.waktu);
                    });
                } else {
                    appendMessage('hasna', `Assalamu'alaikum Ayah/Bunda ${currentUserName}! Ada yang bisa Hasna bantu seputar info SPMB dan Pesantren Villa Quran?`, 'Baru saja');
                }

                // Render Quick Suggestion Pills
                renderSuggestionPills();
                scrollToBottom();
                msgInput.focus();
            })
            .catch(err => {
                chatBody.innerHTML = '<div style="text-align:center; padding: 20px; font-size:12px; color:#ef4444;">Gagal memuat riwayat. Silakan coba lagi.</div>';
            });
    }

    function renderSuggestionPills() {
        const pillsContainer = document.createElement('div');
        pillsContainer.className = 'hasna-suggestions';
        pillsContainer.id = 'hasna-pills-box';

        const questions = [
            'Berapa biaya masuk & SPP?',
            'Jenjang & kurikulum apa saja?',
            'Bagaimana cara daftar SPMB?',
            'Fasilitas asrama di Malang?'
        ];

        questions.forEach(q => {
            const pill = document.createElement('button');
            pill.className = 'hasna-pill';
            pill.textContent = q;
            pill.onclick = () => {
                sendUserMessage(q);
            };
            pillsContainer.appendChild(pill);
        });

        chatBody.appendChild(pillsContainer);
    }

    function appendMessage(sender, text, time) {
        const msgDiv = document.createElement('div');
        msgDiv.className = `hasna-msg hasna-msg-${sender === 'hasna' ? 'bot' : 'user'}`;
        
        // Format text sederhana (bold, list, newline)
        let formattedText = escapeHtml(text)
            .replace(/\*\*(.*?)\*\*/g, '<b>$1</b>')
            .replace(/\*(.*?)\*/g, '<i>$1</i>')
            .replace(/\n/g, '<br>');

        msgDiv.innerHTML = `
            ${formattedText}
            <div class="hasna-msg-time">${time || getCurrentTime()}</div>
        `;

        chatBody.appendChild(msgDiv);
        scrollToBottom();
    }

    function showTypingIndicator() {
        const typingDiv = document.createElement('div');
        typingDiv.id = 'hasna-typing-indicator';
        typingDiv.className = 'hasna-typing';
        typingDiv.innerHTML = `
            <div class="hasna-dot"></div>
            <div class="hasna-dot"></div>
            <div class="hasna-dot"></div>
        `;
        chatBody.appendChild(typingDiv);
        scrollToBottom();
    }

    function hideTypingIndicator() {
        const el = document.getElementById('hasna-typing-indicator');
        if (el) el.remove();
    }

    // Kirim pesan user
    function sendUserMessage(text) {
        const userText = text || msgInput.value.trim();
        if (!userText || !currentSession) return;

        // Kosongkan input
        msgInput.value = '';

        // Hilangkan pills lama jika ada
        const oldPills = document.getElementById('hasna-pills-box');
        if (oldPills) oldPills.remove();

        // Tampilkan pesan user
        appendMessage('user', userText, getCurrentTime());
        showTypingIndicator();

        // Kirim ke backend
        fetch(API_ENDPOINT, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({
                action: 'send_message',
                session_token: currentSession,
                pesan: userText
            })
        })
        .then(res => res.json())
        .then(data => {
            hideTypingIndicator();
            if (data.status === 'success') {
                appendMessage('hasna', data.jawaban, getCurrentTime());
                
                // Tambahkan CTA WhatsApp jika relevan
                if (data.jawaban.toLowerCase().includes('biaya') || data.jawaban.toLowerCase().includes('daftar') || data.jawaban.toLowerCase().includes('whatsapp')) {
                    appendWhatsAppCta();
                }
            } else {
                appendMessage('hasna', 'Afwan Ayah/Bunda, terjadi kendala teknis sementara. Silakan tanyakan kembali.', getCurrentTime());
            }
        })
        .catch(err => {
            hideTypingIndicator();
            appendMessage('hasna', 'Afwan, koneksi terputus. Silakan periksa jaringan internet Anda.', getCurrentTime());
        });
    }

    function appendWhatsAppCta() {
        const cta = document.createElement('div');
        cta.className = 'hasna-wa-cta';
        cta.innerHTML = `
            <span>💬 Butuh bantuan lebih detail?</span>
            <a href="https://wa.me/6285189918115?text=Assalamu%27alaikum%20Panitia%20SPMB%20Villa%20Quran,%20saya%20ingin%20konsultasi%20pendaftaran" target="_blank">Chat WA Panitia</a>
        `;
        chatBody.appendChild(cta);
        scrollToBottom();
    }

    // Event listener input enter & button send
    btnSend.addEventListener('click', () => sendUserMessage());
    msgInput.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') {
            e.preventDefault();
            sendUserMessage();
        }
    });

    function scrollToBottom() {
        setTimeout(() => {
            chatBody.scrollTop = chatBody.scrollHeight;
        }, 50);
    }

    function getCurrentTime() {
        const now = new Date();
        const h = String(now.getHours()).padStart(2, '0');
        const m = String(now.getMinutes()).padStart(2, '0');
        return `${h}:${m}`;
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
})();

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const baseHari = <?= json_encode($hari_str) ?>;
        const readable = <?= json_encode($hari_readable) ?>;
        const recommendationEl = document.getElementById('recommendation');

        if (recommendationEl) {
            recommendationEl.innerHTML = '<p>Pilih tanaman untuk melihat rekomendasi</p>';
        }

        function escapeHtml(str) {
            return String(str)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        document.addEventListener('click', function(e) {
            const btn = e.target.closest && e.target.closest('.crop-btn');
            if (!btn) return;
            if (e.cancelable) e.preventDefault();

            const crop = String(btn.getAttribute('data-crop') || '').trim();
            if (!crop) {
                console.error('data-crop kosong');
                return;
            }

            btn.disabled = true;
            btn.setAttribute('aria-busy', 'true');
            if (recommendationEl) {
                recommendationEl.innerHTML = '<p>Memuat rekomendasi untuk <strong>' + (btn.textContent || crop) + '</strong>…</p>';
            }

            const formData = new FormData();
            formData.append('crop', crop);
            formData.append('hari', baseHari);

            fetch(window.location.href, {
                    method: 'POST',
                    body: formData,
                    credentials: 'same-origin'
                })
                .then(res => {
                    if (!res.ok) throw new Error('Status: ' + res.status);
                    return res.text();
                })
                .then(html => {
                    if (!recommendationEl) {
                        console.warn('#recommendation tidak ditemukan');
                        return;
                    }
                    try {
                        const parser = new DOMParser();
                        const doc = parser.parseFromString(html, 'text/html');
                        doc.querySelectorAll('script').forEach(s => s.remove());

                        const allowed = doc.querySelectorAll('.rec-fragment');
                        if (allowed.length) {
                            const combined = Array.from(allowed).map(el => el.outerHTML).join('');
                            recommendationEl.innerHTML = combined;
                            return;
                        }

                        const fallback = doc.body && doc.body.innerHTML ? doc.body.innerHTML.trim() : '';
                        if (fallback) {
                            recommendationEl.innerHTML = fallback;
                        } else {
                            recommendationEl.innerHTML = '<pre>' + escapeHtml(html) + '</pre>';
                        }
                    } catch (err) {
                        console.error('Parsing error:', err);
                        recommendationEl.innerHTML = '<p class="text-danger">Gagal menampilkan rekomendasi.</p>';
                    }
                })
                .catch(err => {
                    console.error('Fetch error:', err);
                    if (recommendationEl) {
                        recommendationEl.innerHTML = '<p class="text-danger">Gagal mengambil rekomendasi.</p>';
                    }
                })
                .finally(() => {
                    btn.disabled = false;
                    btn.removeAttribute('aria-busy');
                });
        }, false);
    });
</script>
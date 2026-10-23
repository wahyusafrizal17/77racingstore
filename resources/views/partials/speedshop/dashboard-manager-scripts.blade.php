<script>
function omsetDetailModalState(baseUrl) {
    const esc = (value) => String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');

    return {
        openModal: false,
        loading: false,
        error: '',
        title: '',
        subtitle: '',
        tableHtml: '',
        total: 0,
        totalLabel: 'Total',
        compareEnabled: false,
        comparePeriodLabel: '',
        compareTotal: 0,
        compareDeltaPercent: 0,
        openOmsetDetail(type) {
            this.open(type);
        },
        async open(type) {
            this.openModal = true;
            this.loading = true;
            this.error = '';
            this.tableHtml = '';
            this.total = 0;
            this.compareEnabled = false;
            this.comparePeriodLabel = '';
            this.compareTotal = 0;
            this.compareDeltaPercent = 0;
            this.title = 'Memuat…';
            this.subtitle = '';
            document.body.classList.add('overflow-hidden');
            try {
                const url = new URL(baseUrl, window.location.origin);
                url.searchParams.set('type', type);
                const res = await fetch(url.toString(), {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin',
                });
                const data = await res.json().catch(() => ({}));
                if (!res.ok) {
                    throw new Error(data.message || 'Gagal memuat rincian.');
                }
                this.title = data.title || 'Rincian';
                this.subtitle = data.subtitle || '';
                const columns = Array.isArray(data.columns) ? data.columns : [];
                const rows = Array.isArray(data.rows) ? data.rows : [];
                this.tableHtml = this.buildTableHtml(columns, rows);
                this.total = Number(data.total || 0);
                this.totalLabel = data.total_label || 'Total';
                this.compareEnabled = Boolean(data.compare_enabled);
                this.comparePeriodLabel = data.compare_period_label || '';
                this.compareTotal = Number(data.compare_total || 0);
                this.compareDeltaPercent = Number(data.compare_delta_percent || 0);
            } catch (e) {
                this.error = e?.message || 'Gagal memuat rincian.';
                this.title = 'Rincian';
            } finally {
                this.loading = false;
            }
        },
        close() {
            this.openModal = false;
            document.body.classList.remove('overflow-hidden');
        },
        rupiah(value) {
            return 'Rp ' + Number(value || 0).toLocaleString('id-ID');
        },
        formatPercent(value) {
            const num = Number(value || 0);
            return (num >= 0 ? '+' : '') + num.toLocaleString('id-ID', { minimumFractionDigits: 1, maximumFractionDigits: 1 }) + '%';
        },
        formatCell(value, col) {
            if (col?.format === 'rupiah') {
                return this.rupiah(value);
            }
            if (col?.format === 'percent') {
                return this.formatPercent(value);
            }
            if (typeof value === 'number') {
                return Number(value).toLocaleString('id-ID');
            }
            return value ?? '-';
        },
        buildTableHtml(columns, rows) {
            if (!columns.length || !rows.length) {
                return '';
            }

            let html = '<table class="omset-detail-native-table w-full min-w-[560px] text-left text-sm"><thead><tr class="bg-[#f7f3f4]">';
            html += '<th class="whitespace-nowrap px-3 py-2.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">No.</th>';
            for (const col of columns) {
                const align = col.align === 'right' ? ' text-right' : '';
                html += `<th class="whitespace-nowrap px-3 py-2.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500${align}">${esc(col.label)}</th>`;
            }
            html += '</tr></thead><tbody class="divide-y divide-gray-100">';

            rows.forEach((row, idx) => {
                html += '<tr class="hover:bg-gray-50/60">';
                html += `<td class="px-3 py-2.5 text-gray-500">${idx + 1}</td>`;
                for (const col of columns) {
                    const align = col.align === 'right' ? ' text-right whitespace-nowrap' : '';
                    let extraClass = '';
                    if (col.format === 'percent') {
                        const pct = Number(row[col.key] || 0);
                        extraClass = pct >= 0 ? ' text-emerald-700 font-semibold' : ' text-red-600 font-semibold';
                    }
                    const text = esc(this.formatCell(row[col.key], col));
                    html += `<td class="px-3 py-2.5 text-gray-800${align}${extraClass}">${text}</td>`;
                }
                html += '</tr>';
            });

            html += '</tbody></table>';
            return html;
        },
    };
}

function resolveDashboardCaptureScale(node, maxWidth = 1600, maxHeight = 2260) {
    const width = Math.max(1, node.offsetWidth || node.scrollWidth);
    const height = Math.max(1, node.scrollHeight || node.offsetHeight);
    const fitScale = Math.min(1, maxWidth / width, maxHeight / height);

    return Math.max(0.5, Math.min(1.25, fitScale));
}

function suspendExternalStylesheetsForCapture() {
    const backup = [];

    document.querySelectorAll('link[rel="stylesheet"]').forEach((link) => {
        backup.push({ el: link, disabled: link.disabled });
        link.disabled = true;
    });

    return backup;
}

function restoreExternalStylesheetsForCapture(backup) {
    backup.forEach(({ el, disabled }) => {
        el.disabled = disabled;
    });
}

const DASHBOARD_CAPTURE_RESET_STYLE = `
.ss-dashboard-capture-exporting,
.ss-dashboard-capture-exporting * {
    animation: none !important;
    transition: none !important;
}
.ss-dashboard-capture-exporting .ss-dash-enter,
.ss-dashboard-capture-exporting .ss-dash-enter-child {
    opacity: 1 !important;
    transform: none !important;
    filter: none !important;
}
.ss-dashboard-capture-exporting .branch-bar {
    width: var(--bar-width, 0%) !important;
}
`;

function prepareDashboardCaptureVisibility(root) {
    const backups = [];

    root.querySelectorAll('.ss-dash-enter, .ss-dash-enter-child').forEach((el) => {
        backups.push({
            el,
            opacity: el.style.opacity,
            transform: el.style.transform,
            animation: el.style.animation,
            transition: el.style.transition,
        });
        el.style.opacity = '1';
        el.style.transform = 'none';
        el.style.animation = 'none';
        el.style.transition = 'none';
    });

    root.querySelectorAll('.branch-bar').forEach((el) => {
        backups.push({
            el,
            width: el.style.width,
            transition: el.style.transition,
        });
        const width = el.style.getPropertyValue('--bar-width') || window.getComputedStyle(el).getPropertyValue('--bar-width');
        if (width) {
            el.style.width = width;
        }
        el.style.transition = 'none';
    });

    return backups;
}

function restoreDashboardCaptureVisibility(backups) {
    backups.forEach((entry) => {
        if ('opacity' in entry) {
            entry.el.style.opacity = entry.opacity;
            entry.el.style.transform = entry.transform;
            entry.el.style.animation = entry.animation;
            entry.el.style.transition = entry.transition;
            return;
        }
        entry.el.style.width = entry.width;
        entry.el.style.transition = entry.transition;
    });
}

function prepareDashboardCloneForCapture(sourceRoot, clonedDoc) {
    const clonedRoot = clonedDoc.getElementById('ss-dashboard-capture');
    if (!clonedRoot) {
        return;
    }

    clonedDoc.querySelectorAll('link[rel="stylesheet"]').forEach((el) => el.remove());

    const resetStyle = clonedDoc.createElement('style');
    resetStyle.textContent = DASHBOARD_CAPTURE_RESET_STYLE;
    clonedDoc.head.appendChild(resetStyle);

    const sourceCanvases = sourceRoot.querySelectorAll('canvas');
    clonedRoot.querySelectorAll('canvas').forEach((clonedCanvas, index) => {
        const sourceCanvas = sourceCanvases[index];
        if (!sourceCanvas) {
            return;
        }
        clonedCanvas.width = sourceCanvas.width;
        clonedCanvas.height = sourceCanvas.height;
        const ctx = clonedCanvas.getContext('2d');
        if (ctx) {
            ctx.drawImage(sourceCanvas, 0, 0);
        }
    });

    clonedRoot.querySelectorAll('img').forEach((img) => {
        const src = img.getAttribute('src') || '';
        if (src.startsWith('http') && !src.startsWith(window.location.origin)) {
            img.remove();
        }
    });

    clonedRoot.classList.add('ss-dashboard-capture-exporting');
}

function speedshopManagerDashboard(baseUrl) {
    return {
        loading: false,
        exportingImage: false,
        ...omsetDetailModalState(baseUrl),
        async exportDashboardImage() {
            if (this.exportingImage || this.loading) {
                return;
            }
            if (typeof html2canvas === 'undefined') {
                window.alert('Fitur cetak gambar belum siap. Muat ulang halaman lalu coba lagi.');
                return;
            }

            const node = document.getElementById('ss-dashboard-capture');
            if (!node) {
                return;
            }

            node.classList.add('ss-dashboard-capture-exporting');
            this.exportingImage = true;

            let stylesheetBackup = null;
            let visibilityBackup = [];

            try {
                await new Promise((resolve) => requestAnimationFrame(() => requestAnimationFrame(resolve)));

                stylesheetBackup = suspendExternalStylesheetsForCapture();
                visibilityBackup = prepareDashboardCaptureVisibility(node);
                await new Promise((resolve) => requestAnimationFrame(resolve));

                const captureScale = resolveDashboardCaptureScale(node);

                const canvas = await html2canvas(node, {
                    backgroundColor: '#f4f5f7',
                    scale: captureScale,
                    useCORS: true,
                    allowTaint: true,
                    logging: false,
                    imageTimeout: 8000,
                    onclone: (doc) => {
                        prepareDashboardCloneForCapture(node, doc);
                    },
                });

                const fittedCanvas = this.fitDashboardCanvasToSinglePage(canvas, 1600, 2260);
                const dataUrl = fittedCanvas.toDataURL('image/png');
                const params = new URLSearchParams(window.location.search);
                const periodParts = [
                    params.get('mode') || 'bulanan',
                    params.get('bulan') || params.get('tanggal') || params.get('tahun') || params.get('dari'),
                ].filter(Boolean);
                const periodSlug = periodParts.join('-')
                    .replace(/[^\w\-]+/g, '-')
                    .replace(/-+/g, '-')
                    .replace(/^-|-$/g, '')
                    .slice(0, 48);
                const stamp = new Date().toISOString().slice(0, 10);

                const link = document.createElement('a');
                link.download = `dashboard-speedshop-${periodSlug || stamp}.png`;
                link.href = dataUrl;
                document.body.appendChild(link);
                link.click();
                link.remove();
            } catch (error) {
                console.error(error);
                window.alert('Gagal membuat gambar dashboard. Coba lagi.');
            } finally {
                if (stylesheetBackup) {
                    restoreExternalStylesheetsForCapture(stylesheetBackup);
                }
                if (visibilityBackup.length) {
                    restoreDashboardCaptureVisibility(visibilityBackup);
                }
                node.classList.remove('ss-dashboard-capture-exporting');
                this.exportingImage = false;
            }
        },
        fitDashboardCanvasToSinglePage(sourceCanvas, maxWidth, maxHeight) {
            const scale = Math.min(1, maxWidth / sourceCanvas.width, maxHeight / sourceCanvas.height);
            const width = Math.max(1, Math.round(sourceCanvas.width * scale));
            const height = Math.max(1, Math.round(sourceCanvas.height * scale));
            const canvas = document.createElement('canvas');
            canvas.width = width;
            canvas.height = height;
            const ctx = canvas.getContext('2d');
            if (!ctx) {
                return sourceCanvas;
            }
            ctx.fillStyle = '#f4f5f7';
            ctx.fillRect(0, 0, width, height);
            ctx.drawImage(sourceCanvas, 0, 0, width, height);
            return canvas;
        },
    };
}
</script>


<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
(function () {
    var QR_CDN = 'https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js';

    window.renderProductQr = function (containerId, text) {
        var el = document.getElementById(containerId);
        if (!el || typeof QRCode === 'undefined') return;
        el.innerHTML = '';
        new QRCode(el, {
            text: String(text || ''),
            width: 200,
            height: 200,
            colorDark: '#000000',
            colorLight: '#ffffff',
            correctLevel: QRCode.CorrectLevel.M
        });
    };

    function escapeHtml(raw) {
        return String(raw != null ? raw : '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;');
    }

    function normalizeQty(qty) {
        var n = parseInt(String(qty), 10);
        if (isNaN(n) || n < 1) n = 1;
        if (n > 500) n = 500;
        return n;
    }

    function printThermal33x25ThreeCol(code, qty) {
        var raw = String(code != null ? code : '');
        var esc = escapeHtml(raw);
        var titleSafe = raw.replace(/</g, '').replace(/>/g, '');
        var n = normalizeQty(qty);
        var codeJson = JSON.stringify(raw);

        var rowsHtml = '';
        for (var i = 0; i < n; i += 3) {
            rowsHtml += '<tr>';
            for (var col = 0; col < 3; col++) {
                var idx = i + col;
                if (idx < n) {
                    rowsHtml += '<td><div class="cell"><div class="qr-slot" id="qr-slot-' + idx + '"></div><div class="code">' + esc + '</div></div></td>';
                } else {
                    rowsHtml += '<td><div class="cell cell-empty"></div></td>';
                }
            }
            rowsHtml += '</tr>';
        }

        var w = window.open('', '_blank');
        if (!w) return;

        var styles = [
            '*{box-sizing:border-box;margin:0;padding:0}',
            'html,body{margin:0;padding:0;width:99mm}',
            'body{font-family:Arial,Helvetica,sans-serif}',
            '@page{size:99mm auto;margin:0}',
            'table.labels{width:99mm;border-collapse:collapse;table-layout:fixed;border-spacing:0}',
            'table.labels td{width:33mm;height:25mm;padding:0;vertical-align:middle}',
            '.cell{width:33mm;height:25mm;padding:1mm 0.5mm 0.6mm;display:flex;flex-direction:column;align-items:center;justify-content:center;overflow:hidden}',
            '.cell-empty{width:33mm;height:25mm}',
            '.qr-slot{width:17mm;height:17mm;line-height:0;overflow:hidden;display:flex;align-items:center;justify-content:center}',
            '.qr-slot img,.qr-slot canvas{width:17mm!important;height:17mm!important;max-width:17mm!important;max-height:17mm!important;display:block}',
            '.code{margin-top:0.3mm;width:31mm;font-family:Consolas,Monaco,monospace;font-size:5.5pt;font-weight:700;line-height:1.05;text-align:center;word-break:break-all;overflow:hidden}',
            '@media print{html,body{width:99mm;-webkit-print-color-adjust:exact;print-color-adjust:exact}}',
        ].join('');

        w.document.open();
        w.document.write('<!DOCTYPE html><html><head><meta charset="utf-8"><title>Label ' + titleSafe + '</title><style>' + styles + '</style></head><body>');
        w.document.write('<table class="labels" cellspacing="0" cellpadding="0">' + rowsHtml + '</table>');
        w.document.write('<script src="' + QR_CDN + '"><\/script>');
        w.document.write('<script>(function(){');
        w.document.write('var text=' + codeJson + ';');
        w.document.write('var total=' + n + ';');
        w.document.write('function renderAll(){');
        w.document.write('if(typeof QRCode==="undefined"){setTimeout(renderAll,50);return;}');
        w.document.write('for(var i=0;i<total;i++){');
        w.document.write('var slot=document.getElementById("qr-slot-"+i);');
        w.document.write('if(!slot)continue;');
        w.document.write('slot.innerHTML="";');
        w.document.write('new QRCode(slot,{text:text,width:64,height:64,colorDark:"#000000",colorLight:"#ffffff",correctLevel:QRCode.CorrectLevel.L});');
        w.document.write('}');
        w.document.write('setTimeout(function(){window.focus();window.print();},250);');
        w.document.write('}');
        w.document.write('renderAll();');
        w.document.write('})();<\/script>');
        w.document.write('</body></html>');
        w.document.close();
    }

    window.printProductQrLabels = function (containerId, code, qty, options) {
        options = options || {};
        if (options.layout === 'thermal-33x25-3col') {
            printThermal33x25ThreeCol(code, qty);
            return;
        }

        var el = document.getElementById(containerId);
        var inner = el ? el.innerHTML : '';
        var raw = String(code != null ? code : '');
        var esc = escapeHtml(raw);
        var titleSafe = raw.replace(/</g, '').replace(/>/g, '');
        var n = normalizeQty(qty);
        if (n > 100) n = 100;

        var w = window.open('', '_blank');
        if (!w) return;
        w.document.write('<!DOCTYPE html><html><head><meta charset="utf-8"><title>QR ' + titleSafe + '</title><style>body{margin:0;padding:20px;text-align:center;font-family:sans-serif}.label{display:inline-block;margin:12px;padding:12px;border:1px solid #eee;border-radius:8px;page-break-inside:avoid}.label .code{margin-top:10px;font-size:14px;font-weight:600}@media print{@page{margin:5mm}}</style></head><body>');
        for (var i = 0; i < n; i++) {
            w.document.write('<div class="label">' + inner + '<div class="code">' + esc + '</div></div>');
        }
        w.document.write('</body></html>');
        w.document.close();
        w.onload = function () { w.print(); };
    };
})();
</script>
<?php /**PATH /Users/wahyusafrizal/work/Github/77racingstore/resources/views/partials/product-qr-scripts.blade.php ENDPATH**/ ?>
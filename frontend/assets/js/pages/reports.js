initPageShell({
  title: 'Reports',
  activeNav: 'reports',
  roles: ['owner', 'branch_admin'],
  render: async (container, flash, user) => {
    const isOwner = user.role === 'owner';
    const today = new Date().toISOString().slice(0, 10);
    const monthStart = `${today.slice(0, 8)}01`;
    let currentReport = null;

    const csvEscape = (value) => `"${String(value ?? '').replace(/"/g, '""')}"`;
    const friendlyLabel = (key) => key.replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase());
    const safeFilename = (value) => String(value || 'report')
      .toLowerCase()
      .replace(/[^a-z0-9]+/g, '-')
      .replace(/^-+|-+$/g, '') || 'report';

    const downloadBlob = (blob, filename) => {
      const url = URL.createObjectURL(blob);
      const link = document.createElement('a');
      link.href = url;
      link.download = filename;
      link.style.display = 'none';
      document.body.appendChild(link);
      link.click();
      link.remove();
      setTimeout(() => URL.revokeObjectURL(url), 1000);
    };

    const ensureReport = () => {
      if (!currentReport?.rows?.length) {
        showFlash(flash, 'warning', 'Generate a report with data before downloading.');
        return false;
      }
      return true;
    };

    const downloadCsv = () => {
      if (!ensureReport()) return;
      const keys = Object.keys(currentReport.rows[0]);
      const csv = [
        keys.map((key) => csvEscape(friendlyLabel(key))).join(','),
        ...currentReport.rows.map((row) => keys.map((key) => csvEscape(row[key])).join(',')),
      ].join('\r\n');

      downloadBlob(
        new Blob(['\uFEFF' + csv], { type: 'text/csv;charset=utf-8;' }),
        `${safeFilename(currentReport.title)}-${today}.csv`
      );
      showFlash(flash, 'success', 'Report CSV downloaded from the system.');
    };

    const downloadPdf = () => {
      if (!ensureReport()) return;
      if (!window.jspdf?.jsPDF) {
        showFlash(flash, 'danger', 'PDF component could not load. Check the internet connection and refresh the page.');
        return;
      }

      const keys = Object.keys(currentReport.rows[0]);
      const { jsPDF } = window.jspdf;
      const pdf = new jsPDF({ orientation: 'landscape', unit: 'mm', format: 'a4' });
      const branchText = isOwner
        ? document.getElementById('branch-id')?.selectedOptions?.[0]?.textContent || 'All Branches'
        : (user.branch_name || 'Current Branch');
      const generatedAt = new Date().toLocaleString();

      pdf.setFillColor(8, 47, 91);
      pdf.rect(0, 0, 297, 30, 'F');
      pdf.setTextColor(255, 255, 255);
      pdf.setFontSize(19);
      pdf.setFont(undefined, 'bold');
      pdf.text('Smart Stock Finder', 14, 12);
      pdf.setFontSize(11);
      pdf.setFont(undefined, 'normal');
      pdf.text(currentReport.title, 14, 20);

      pdf.setTextColor(30, 41, 59);
      pdf.setFontSize(8.5);
      pdf.text(`Branch: ${branchText}`, 14, 37);
      pdf.text(`Period: ${document.getElementById('date-from').value} to ${document.getElementById('date-to').value}`, 92, 37);
      pdf.text(`Generated: ${generatedAt}`, 205, 37);

      pdf.autoTable({
        startY: 43,
        head: [keys.map(friendlyLabel)],
        body: currentReport.rows.map((row) => keys.map((key) => String(row[key] ?? ''))),
        theme: 'grid',
        styles: { fontSize: 6.7, cellPadding: 1.6, overflow: 'linebreak', textColor: [30, 41, 59] },
        headStyles: { fillColor: [15, 76, 129], textColor: 255, fontStyle: 'bold' },
        alternateRowStyles: { fillColor: [245, 249, 252] },
        margin: { left: 8, right: 8, bottom: 14 },
        didDrawPage: (data) => {
          const pageHeight = pdf.internal.pageSize.height;
          pdf.setDrawColor(203, 213, 225);
          pdf.line(8, pageHeight - 10, 289, pageHeight - 10);
          pdf.setFontSize(7.5);
          pdf.setTextColor(100, 116, 139);
          pdf.text('Smart Stock Finder • System Generated Report', 8, pageHeight - 5);
          pdf.text(`Page ${pdf.internal.getNumberOfPages()}`, 276, pageHeight - 5);
        },
      });

      pdf.save(`${safeFilename(currentReport.title)}-${today}.pdf`);
      showFlash(flash, 'success', 'Report PDF downloaded from the system.');
    };


    const printReport = () => {
      if (!ensureReport()) return;

      const keys = Object.keys(currentReport.rows[0]);
      const branchText = isOwner
        ? document.getElementById('branch-id')?.selectedOptions?.[0]?.textContent || 'All Branches'
        : (user.branch_name || 'Current Branch');
      const dateFrom = document.getElementById('date-from').value;
      const dateTo = document.getElementById('date-to').value;
      const generatedAt = new Date().toLocaleString();
      const logoUrl = new URL('../assets/images/logo.png', window.location.href).href;

      const printableRows = currentReport.rows.map((row) => `
        <tr>
          ${keys.map((key) => `<td>${escapeHtml(String(row[key] ?? ''))}</td>`).join('')}
        </tr>
      `).join('');

      const printWindow = window.open('', '_blank', 'width=1200,height=800');
      if (!printWindow) {
        showFlash(flash, 'warning', 'Please allow pop-ups for this site to print reports.');
        return;
      }

      const reportTitle = escapeHtml(currentReport.title || 'Report');
      const safeBranch = escapeHtml(branchText);
      const safePeriod = `${escapeHtml(dateFrom)} to ${escapeHtml(dateTo)}`;
      const safeGenerated = escapeHtml(generatedAt);

      printWindow.document.open();
      printWindow.document.write(`<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>${reportTitle} - Smart Stock Finder</title>
  <style>
    @page { size: A4 landscape; margin: 10mm; }
    * { box-sizing: border-box; }
    html, body { margin: 0; padding: 0; background: #ffffff; color: #172033; }
    body { font-family: Arial, Helvetica, sans-serif; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    .sheet { width: 100%; }
    .brand-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 24px;
      padding: 18px 20px;
      border-radius: 14px;
      background: linear-gradient(135deg, #082f5b 0%, #0f4c81 58%, #1268b3 100%);
      color: #fff;
      margin-bottom: 14px;
    }
    .brand-left { display: flex; align-items: center; gap: 14px; min-width: 0; }
    .brand-logo-wrap {
      width: 64px;
      height: 64px;
      display: grid;
      place-items: center;
      flex: 0 0 64px;
      background: #fff;
      border-radius: 14px;
      padding: 7px;
      box-shadow: 0 6px 18px rgba(0,0,0,.16);
    }
    .brand-logo { width: 100%; height: 100%; object-fit: contain; }
    .system-name { margin: 0; font-size: 24px; font-weight: 800; letter-spacing: -.02em; }
    .system-subtitle { margin-top: 4px; font-size: 10px; font-weight: 600; opacity: .84; text-transform: uppercase; letter-spacing: .08em; }
    .report-badge {
      flex: 0 0 auto;
      border: 1px solid rgba(255,255,255,.35);
      background: rgba(255,255,255,.12);
      border-radius: 999px;
      padding: 8px 13px;
      font-size: 9px;
      font-weight: 800;
      text-transform: uppercase;
      letter-spacing: .08em;
    }
    .title-row {
      display: flex;
      align-items: flex-end;
      justify-content: space-between;
      gap: 16px;
      margin: 5px 2px 12px;
    }
    .report-title { margin: 0; color: #082f5b; font-size: 20px; font-weight: 800; }
    .record-count { color: #64748b; font-size: 9px; font-weight: 700; }
    .meta-grid {
      display: grid;
      grid-template-columns: 1.05fr 1.1fr 1.15fr .7fr;
      gap: 8px;
      margin-bottom: 14px;
    }
    .meta-card {
      min-height: 52px;
      padding: 9px 11px;
      border: 1px solid #d7e4ef;
      border-radius: 9px;
      background: #f5f9fd;
    }
    .meta-label { color: #64748b; font-size: 7.5px; font-weight: 800; text-transform: uppercase; letter-spacing: .07em; }
    .meta-value { margin-top: 4px; color: #0f2742; font-size: 10px; font-weight: 750; word-break: break-word; }
    .table-wrap { border: 1px solid #cad8e5; border-radius: 10px; overflow: hidden; }
    table { width: 100%; border-collapse: collapse; table-layout: auto; }
    thead { display: table-header-group; }
    th {
      padding: 7px 6px;
      background: #0f4c81;
      color: #fff;
      border-right: 1px solid rgba(255,255,255,.14);
      font-size: 7px;
      line-height: 1.25;
      text-transform: uppercase;
      letter-spacing: .035em;
      text-align: left;
      vertical-align: middle;
    }
    td {
      padding: 6px;
      border-top: 1px solid #dde6ee;
      border-right: 1px solid #edf2f7;
      color: #203247;
      font-size: 7.3px;
      line-height: 1.3;
      vertical-align: top;
      overflow-wrap: anywhere;
    }
    th:last-child, td:last-child { border-right: none; }
    tbody tr:nth-child(even) td { background: #f7fafc; }
    tr { break-inside: avoid; page-break-inside: avoid; }
    .footer {
      display: flex;
      justify-content: space-between;
      align-items: center;
      gap: 16px;
      margin-top: 12px;
      padding-top: 8px;
      border-top: 2px solid #e0e8f0;
      color: #64748b;
      font-size: 7.5px;
    }
    .footer strong { color: #082f5b; }
    .screen-toolbar {
      display: flex;
      justify-content: flex-end;
      gap: 8px;
      margin-bottom: 12px;
    }
    .screen-toolbar button {
      border: 0;
      border-radius: 8px;
      padding: 9px 15px;
      background: #082f5b;
      color: #fff;
      font-size: 12px;
      font-weight: 700;
      cursor: pointer;
    }
    .screen-toolbar .secondary { background: #e8f3fb; color: #082f5b; }
    @media print {
      .screen-toolbar { display: none !important; }
      body { padding: 0; }
      .brand-header { box-shadow: none; }
      .table-wrap { overflow: visible; }
    }
  </style>
</head>
<body>
  <div class="sheet">
    <div class="screen-toolbar">
      <button class="secondary" type="button" onclick="window.close()">Close</button>
      <button type="button" onclick="window.print()">Print Report</button>
    </div>

    <header class="brand-header">
      <div class="brand-left">
        <div class="brand-logo-wrap"><img class="brand-logo" src="${logoUrl}" alt="Smart Stock Finder"></div>
        <div>
          <h1 class="system-name">Smart Stock Finder</h1>
          <div class="system-subtitle">Inventory &amp; Sales Management System</div>
        </div>
      </div>
      <div class="report-badge">Official System Report</div>
    </header>

    <div class="title-row">
      <h2 class="report-title">${reportTitle}</h2>
      <div class="record-count">${currentReport.rows.length} record${currentReport.rows.length === 1 ? '' : 's'}</div>
    </div>

    <section class="meta-grid">
      <div class="meta-card"><div class="meta-label">Branch</div><div class="meta-value">${safeBranch}</div></div>
      <div class="meta-card"><div class="meta-label">Report Period</div><div class="meta-value">${safePeriod}</div></div>
      <div class="meta-card"><div class="meta-label">Generated</div><div class="meta-value">${safeGenerated}</div></div>
      <div class="meta-card"><div class="meta-label">Rows</div><div class="meta-value">${currentReport.rows.length}</div></div>
    </section>

    <div class="table-wrap">
      <table>
        <thead><tr>${keys.map((key) => `<th>${escapeHtml(friendlyLabel(key))}</th>`).join('')}</tr></thead>
        <tbody>${printableRows}</tbody>
      </table>
    </div>

    <footer class="footer">
      <div><strong>Smart Stock Finder</strong> &bull; Confidential System Report</div>
      <div>Generated automatically from the owner reporting module</div>
    </footer>
  </div>
</body>
</html>`);
      printWindow.document.close();
      printWindow.focus();
    };

    container.innerHTML = `
      <div class="card mb-4 no-print"><div class="card-body">
        <form id="report-form" class="row g-3">
          <div class="col-md-3"><label class="form-label">Report</label><select class="form-select" id="report-type">
            <option value="fast_moving">Fast Moving Items</option>
            <option value="slow_moving">Slow Moving Items</option>
            <option value="stock_levels">Stock by Branch</option>
            <option value="low_stock">Low Stock</option>
            <option value="monthly_sales">Sales Summary</option>
            <option value="returns_summary">Returns & Exchanges</option>
            <option value="loyalty_stats">Loyalty Statistics</option>
            <option value="supplier_orders">Supplier Orders</option>
          </select></div>
          ${isOwner ? '<div class="col-md-2"><label class="form-label">Branch</label><select class="form-select" id="branch-id"><option value="0">All Branches</option></select></div>' : ''}
          <div class="col-md-2"><label class="form-label">From</label><input type="date" class="form-control" id="date-from" value="${monthStart}"></div>
          <div class="col-md-2"><label class="form-label">To</label><input type="date" class="form-control" id="date-to" value="${today}"></div>
          <div class="col-md-2 d-flex align-items-end"><button class="btn btn-primary w-100" type="submit"><i class="bi bi-bar-chart me-1"></i>Generate</button></div>
        </form>
      </div></div>
      <div class="card report-card">
        <div class="report-print-header">
          <div class="report-brand">
            <img src="../assets/images/logo.png" alt="Smart Stock Finder" class="report-brand-logo">
            <div>
              <div class="report-brand-name">Smart Stock Finder</div>
              <div class="report-brand-tagline">Inventory & Sales Management System</div>
            </div>
          </div>
          <div class="report-print-title" id="print-report-title">Report</div>
          <div class="report-print-meta">
            <div><span>Branch</span><strong id="print-report-branch">All Branches</strong></div>
            <div><span>Period</span><strong id="print-report-period">-</strong></div>
            <div><span>Generated</span><strong id="print-report-generated">-</strong></div>
          </div>
        </div>
        <div class="card-header report-screen-header d-flex flex-wrap justify-content-between align-items-center gap-2">
          <span id="report-title">Report</span>
          <div class="d-flex flex-wrap gap-2 no-print report-actions">
            <button type="button" class="btn btn-sm btn-outline-secondary" id="report-print"><i class="bi bi-printer me-1"></i>Print</button>
            <button type="button" class="btn btn-sm btn-outline-danger" id="report-pdf"><i class="bi bi-file-earmark-pdf me-1"></i>Download PDF</button>
            <button type="button" class="btn btn-sm btn-outline-success" id="report-csv"><i class="bi bi-file-earmark-spreadsheet me-1"></i>Download CSV</button>
          </div>
        </div>
        <div id="report-body" class="p-3 text-muted">Select filters and generate a report.</div>
        <div class="report-print-footer">Smart Stock Finder • Confidential System Report</div>
      </div>`;

    if (isOwner) {
      const { branches } = await apiGet('/api/branches');
      document.getElementById('branch-id').innerHTML += branches
        .map((branch) => `<option value="${branch.id}">${escapeHtml(branch.name)}</option>`)
        .join('');
    }

    const runReport = async () => {
      const params = new URLSearchParams({
        type: document.getElementById('report-type').value,
        date_from: document.getElementById('date-from').value,
        date_to: document.getElementById('date-to').value,
      });
      if (isOwner) params.set('branch_id', document.getElementById('branch-id').value);

      try {
        const data = await apiGet(`/api/reports?${params.toString()}`);
        currentReport = data;
        document.getElementById('report-title').textContent = data.title;
        document.getElementById('print-report-title').textContent = data.title;
        const selectedBranch = isOwner
          ? document.getElementById('branch-id')?.selectedOptions?.[0]?.textContent || 'All Branches'
          : (user.branch_name || 'Current Branch');
        document.getElementById('print-report-branch').textContent = selectedBranch;
        document.getElementById('print-report-period').textContent = `${document.getElementById('date-from').value} to ${document.getElementById('date-to').value}`;
        document.getElementById('print-report-generated').textContent = new Date().toLocaleString();

        if (!data.rows.length) {
          document.getElementById('report-body').innerHTML = '<p class="text-muted mb-0">No data for selected filters.</p>';
          return;
        }

        const keys = Object.keys(data.rows[0]);
        document.getElementById('report-body').innerHTML = renderTable(
          keys.map((key) => ({
            key,
            label: friendlyLabel(key),
            render: (row) => escapeHtml(String(row[key] ?? '')),
          })),
          data.rows
        );
      } catch (error) {
        currentReport = null;
        showFlash(flash, 'danger', error.message);
      }
    };

    document.getElementById('report-form').onsubmit = async (event) => {
      event.preventDefault();
      await runReport();
    };
    document.getElementById('report-print').onclick = printReport;
    document.getElementById('report-pdf').onclick = downloadPdf;
    document.getElementById('report-csv').onclick = downloadCsv;
  },
});

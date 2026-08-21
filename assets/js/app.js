function appUrl(path) {
  const base = window.APP_BASE || "";
  if (!path) return base || "/";
  if (path.startsWith("http")) return path;
  return base + (path.startsWith("/") ? path : "/" + path);
}

async function api(url, options = {}) {
  const response = await fetch(appUrl(url), {
    headers: {
      "Content-Type": "application/json",
      ...(options.headers || {}),
    },
    ...options,
  });

  const data = await response.json().catch(() => ({}));
  if (!response.ok) {
    throw new Error(data.error || "Request failed");
  }
  return data;
}

function formatMoney(value) {
  const amount = Number(value) || 0;
  return amount.toLocaleString("en-IN", {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  });
}

function emptyRow() {
  return { name: "", qty: "", unit: "Piece", price: "", product_id: null };
}

function rowTotal(row) {
  return (Number(row.qty) || 0) * (Number(row.price) || 0);
}

const UNITS = [
  "Piece", "Kg", "Gram", "Ltr", "ml", "Bag", "Box", "Packet", "Roll", "Meter", "Feet", "Dozen", "Unit",
];

function unitOptions(selected) {
  return UNITS.map((unit) => (
    `<option value="${unit}" ${unit === selected ? "selected" : ""}>${unit}</option>`
  )).join("");
}

function printInvoiceSheet(html) {
  const win = window.open("", "invoicePrint", "width=900,height=700");
  if (!win) {
    alert("Pop-up block ho gaya. Browser mein pop-up allow karo.");
    return;
  }
  win.document.open();
  win.document.write(
    "<!DOCTYPE html><html><head><meta charset=\"UTF-8\"><title>Invoice</title>" +
      "<style>" +
      "@page{size:A4 portrait;margin:10mm;}" +
      "html,body{margin:0;padding:0;background:#fff;color:#111;font-family:Arial,Helvetica,sans-serif;font-size:12px;}" +
      "table{width:100%;border-collapse:collapse;margin-top:10px;}" +
      "th,td{border:1px solid #111;padding:5px 6px;text-align:left;}" +
      "th{background:#e5e7eb;}" +
      "h1{margin:0 0 4px;font-size:18px;color:#15803d;}" +
      ".center{text-align:center;border-bottom:2px solid #15803d;padding-bottom:8px;}" +
      ".meta{display:flex;justify-content:space-between;margin-top:10px;}" +
      ".total{margin:10px 0 0 auto;width:220px;border:1px solid #111;padding:8px;font-weight:bold;display:flex;justify-content:space-between;}" +
      ".thanks{text-align:center;font-style:italic;margin-top:14px;}" +
      "</style></head><body>" +
      html +
      "</body></html>"
  );
  win.document.close();
  win.focus();
  setTimeout(() => {
    win.print();
  }, 250);
}

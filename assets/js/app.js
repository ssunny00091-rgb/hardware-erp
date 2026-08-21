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
    "<!DOCTYPE html><html><head><meta charset=\"UTF-8\"><title>Invoice</title></head>" +
      "<body style=\"margin:12px;font-family:Arial,Helvetica,sans-serif;color:#000;background:#fff;\">" +
      html +
      "</body></html>"
  );
  win.document.close();
  win.focus();
  setTimeout(() => {
    win.print();
  }, 250);
}

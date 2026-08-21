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
  return { name: "", color: "", color_hex: "#ffffff", qty: "", unit: "Piece", price: "", product_id: null };
}

const PAINT_SHADES = [
  ["White", "#FFFFFF"],
  ["Off White", "#F4F1E8"],
  ["Ivory", "#FFFFF0"],
  ["Cream", "#FFFDD0"],
  ["Snow White", "#FFFAFA"],
  ["Yellow", "#F5E642"],
  ["Golden Yellow", "#FFD700"],
  ["Orange", "#FF8C00"],
  ["Red", "#C41E3A"],
  ["Maroon", "#800000"],
  ["Pink", "#FFC0CB"],
  ["Blue", "#1E4D8C"],
  ["Sky Blue", "#87CEEB"],
  ["Green", "#2E8B57"],
  ["Mint", "#98FF98"],
  ["Grey", "#808080"],
  ["Brown", "#8B4513"],
  ["Beige", "#F5F5DC"],
  ["Black", "#111111"],
];

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
  const win = window.open("", "invoicePrint", "width=920,height=740");
  if (!win) {
    alert("Pop-up block ho gaya. Browser mein pop-up allow karo.");
    return;
  }
  const cssHref = window.location.origin + appUrl("assets/css/invoice-print.css");
  win.document.open();
  win.document.write(
    "<!DOCTYPE html><html><head><meta charset=\"UTF-8\"><title>Invoice</title>" +
      "<link rel=\"stylesheet\" href=\"" + cssHref + "\">" +
      "</head><body>" + html + "</body></html>"
  );
  win.document.close();
  win.focus();
  setTimeout(() => {
    win.print();
  }, 400);
}

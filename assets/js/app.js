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

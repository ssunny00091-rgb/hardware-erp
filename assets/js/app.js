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
  return { name: "", color: "", color_hex: "#ffffff", hsn: "", qty: "", unit: "Piece", price: "", product_id: null };
}

function twoDigitWords(n) {
  const ones = [
    "", "One", "Two", "Three", "Four", "Five", "Six", "Seven", "Eight", "Nine", "Ten",
    "Eleven", "Twelve", "Thirteen", "Fourteen", "Fifteen", "Sixteen", "Seventeen", "Eighteen", "Nineteen",
  ];
  const tens = ["", "", "Twenty", "Thirty", "Forty", "Fifty", "Sixty", "Seventy", "Eighty", "Ninety"];
  n = Number(n) || 0;
  if (n < 20) return ones[n];
  return (tens[Math.floor(n / 10)] + (n % 10 ? " " + ones[n % 10] : "")).trim();
}

function indianNumberToWords(number) {
  number = Math.floor(Number(number) || 0);
  if (number === 0) return "Zero";
  const parts = [];
  const crore = Math.floor(number / 10000000);
  number %= 10000000;
  const lakh = Math.floor(number / 100000);
  number %= 100000;
  const thousand = Math.floor(number / 1000);
  number %= 1000;
  const hundred = Math.floor(number / 100);
  const rest = number % 100;
  if (crore) parts.push(indianNumberToWords(crore) + " Crore");
  if (lakh) parts.push(twoDigitWords(lakh) + " Lakh");
  if (thousand) parts.push(twoDigitWords(thousand) + " Thousand");
  if (hundred) parts.push(twoDigitWords(hundred) + " Hundred");
  if (rest) parts.push(twoDigitWords(rest));
  return parts.join(" ");
}

function amountInWords(amount) {
  const value = Math.round((Number(amount) || 0) * 100) / 100;
  const rupees = Math.floor(value + 0.00001);
  const paise = Math.round((value - rupees) * 100);
  let text = indianNumberToWords(rupees) + " Rupees";
  if (paise > 0) text += " and " + twoDigitWords(paise) + " Paise";
  return text + " Only";
}

function gstStateLabel(gst) {
  const code = String(gst || "").slice(0, 2);
  const states = { "10": "Bihar" };
  if (!code) return "";
  return states[code] ? code + "-" + states[code] : code;
}

function escapeHtml(value) {
  return String(value ?? "")
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;");
}

function formatQty(value) {
  const amount = Number(value) || 0;
  return String(amount).replace(/\.0+$/, "").replace(/(\.\d*?)0+$/, "$1");
}

function todayIsoDate() {
  const d = new Date();
  return d.getFullYear() + "-" + String(d.getMonth() + 1).padStart(2, "0") + "-" + String(d.getDate()).padStart(2, "0");
}

function parseToIsoDate(value) {
  const s = String(value || "").trim();
  if (!s) return todayIsoDate();
  if (/^\d{4}-\d{2}-\d{2}/.test(s)) {
    return s.slice(0, 10);
  }
  const m = s.match(/^(\d{1,2})[/\-.](\d{1,2})[/\-.](\d{2,4})$/);
  if (m) {
    let day = Number(m[1]);
    let month = Number(m[2]);
    let year = Number(m[3]);
    if (year < 100) year += 2000;
    const dt = new Date(year, month - 1, day);
    if (dt.getFullYear() === year && dt.getMonth() === month - 1 && dt.getDate() === day) {
      return year + "-" + String(month).padStart(2, "0") + "-" + String(day).padStart(2, "0");
    }
  }
  const d = new Date(s);
  if (Number.isNaN(d.getTime())) return todayIsoDate();
  return d.getFullYear() + "-" + String(d.getMonth() + 1).padStart(2, "0") + "-" + String(d.getDate()).padStart(2, "0");
}

function formatDisplayDate(value) {
  const iso = parseToIsoDate(value);
  const parts = iso.split("-");
  return parts[2] + "/" + parts[1] + "/" + parts[0];
}

function invoiceDateLabel(date) {
  const raw = date || (document.getElementById("sale-date") && document.getElementById("sale-date").value) || todayIsoDate();
  return formatDisplayDate(raw);
}

function isoDateFromValue(value) {
  return parseToIsoDate(value);
}

function saleDateIso() {
  const el = document.getElementById("sale-date");
  return parseToIsoDate(el ? el.value : todayIsoDate());
}

function setDateField(textId, pickerId, value) {
  const text = document.getElementById(textId);
  const picker = document.getElementById(pickerId);
  const iso = parseToIsoDate(value);
  if (text) text.value = formatDisplayDate(iso);
  if (picker) picker.value = iso;
}

function bindDateField(textId, pickerId) {
  const text = document.getElementById(textId);
  const picker = document.getElementById(pickerId);
  if (!text) return;
  if (picker) {
    picker.addEventListener("change", () => {
      if (picker.value) text.value = formatDisplayDate(picker.value);
    });
  }
  text.addEventListener("blur", () => {
    const iso = parseToIsoDate(text.value);
    text.value = formatDisplayDate(iso);
    if (picker) picker.value = iso;
  });
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

function suggestOptions(box) {
  return box ? [...box.querySelectorAll("[data-pick], [data-save-new]")] : [];
}

function setSuggestActive(box, pos) {
  const items = suggestOptions(box);
  if (!box || !items.length) return 0;
  pos = Math.max(0, Math.min(pos, items.length - 1));
  items.forEach((el, i) => el.classList.toggle("suggest-active", i === pos));
  items[pos].scrollIntoView({ block: "nearest" });
  box.dataset.activeIndex = String(pos);
  return pos;
}

function suggestPageSize(box) {
  const items = suggestOptions(box);
  if (!items.length) return 5;
  const h = items[0].offsetHeight || 44;
  return Math.max(3, Math.floor((box.clientHeight || 180) / h));
}

function handleSuggestKey(event, box) {
  if (!box || box.classList.contains("hidden") || !suggestOptions(box).length) {
    return false;
  }
  const key = event.key;
  if (key === "ArrowDown") {
    event.preventDefault();
    setSuggestActive(box, Number(box.dataset.activeIndex || 0) + 1);
    return true;
  }
  if (key === "ArrowUp") {
    event.preventDefault();
    setSuggestActive(box, Number(box.dataset.activeIndex || 0) - 1);
    return true;
  }
  if (key === "PageDown") {
    event.preventDefault();
    setSuggestActive(box, Number(box.dataset.activeIndex || 0) + suggestPageSize(box));
    return true;
  }
  if (key === "PageUp") {
    event.preventDefault();
    setSuggestActive(box, Number(box.dataset.activeIndex || 0) - suggestPageSize(box));
    return true;
  }
  if (key === "Home") {
    event.preventDefault();
    setSuggestActive(box, 0);
    return true;
  }
  if (key === "End") {
    event.preventDefault();
    setSuggestActive(box, suggestOptions(box).length - 1);
    return true;
  }
  if (key === "Enter") {
    event.preventDefault();
    const items = suggestOptions(box);
    const el = items[Number(box.dataset.activeIndex || 0)] || items[0];
    if (el) el.click();
    return true;
  }
  if (key === "Escape") {
    event.preventDefault();
    box.classList.add("hidden");
    return true;
  }
  return false;
}

function focusRowField(rootId, index, field) {
  const el = document.querySelector("#" + rootId + " [data-index=\"" + index + "\"][data-field=\"" + field + "\"]");
  if (!el) return;
  el.focus();
  if (typeof el.select === "function" && el.type !== "color") {
    el.select();
  }
}

document.addEventListener("keydown", (event) => {
  const el = event.target;
  if (!(el instanceof HTMLElement) || el.dataset.field !== "name") return;
  const box = document.querySelector("[data-suggest=\"" + el.dataset.index + "\"]");
  handleSuggestKey(event, box);
}, true);

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
      "</head><body class=\"invoice-page\">" + html + "</body></html>"
  );
  win.document.close();
  win.focus();
  setTimeout(() => {
    win.print();
  }, 400);
}

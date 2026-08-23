<?php

declare(strict_types=1);

require_once __DIR__ . '/config/database.php';

$pageTitle = 'SATYANARAYAN HARDWARE STORES';
$activeNav = 'home';
require __DIR__ . '/includes/header.php';

$reminderBannerRows = reminder_banner_rows(db());
?>

<h1 class="mb-4 text-3xl font-extrabold leading-tight motion-in sm:mb-6 sm:text-5xl">
  <span aria-hidden="true">🏪</span>
  <span class="shop-title">SATYANARAYAN HARDWARE STORES</span>
</h1>
<?php require __DIR__ . '/includes/reminder-banner.php'; ?>

<div class="mb-6 flex flex-col gap-3 no-print sm:flex-row sm:items-stretch sm:flex-wrap">
  <button type="button" id="btn-new-sale" class="w-full rounded-lg bg-green-600 px-6 py-3 text-center font-semibold text-white shadow-lg hover:bg-green-700 motion-in motion-d1 sm:w-auto">➕ New Sale</button>
  <a href="<?= htmlspecialchars(app_url('assistant.php'), ENT_QUOTES, 'UTF-8') ?>" class="inline-flex w-full items-center justify-center rounded-lg bg-rose-600 px-6 py-3 text-center font-semibold text-white shadow-lg hover:bg-rose-500 motion-in motion-d2 sm:w-auto">🤖 Bolke / Photo se kaam</a>
  <button type="button" id="btn-sales-history" class="w-full rounded-lg bg-indigo-600 px-6 py-3 text-center font-semibold text-white shadow-lg hover:bg-indigo-700 motion-in motion-d3 sm:w-auto">🧾 Sales History</button>
</div>

<div id="dashboard-cards" class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
  <article class="relative overflow-hidden rounded-2xl p-6 shadow-xl motion-card" style="background: linear-gradient(135deg, #2563eb 0%, #7c3aed 100%); --d: 0">
    <h3 class="text-lg font-semibold">Today's Sales</h3>
    <p class="mt-3 text-4xl font-bold" data-stat="today_sales">₹0.00</p>
  </article>
  <article class="relative overflow-hidden rounded-2xl p-6 shadow-xl motion-card" style="background: linear-gradient(135deg, #f97316 0%, #ef4444 100%); --d: 1">
    <h3 class="text-lg font-semibold">Today's Purchase</h3>
    <p class="mt-3 text-4xl font-bold" data-stat="today_purchase">₹0.00</p>
  </article>
  <article class="relative overflow-hidden rounded-2xl p-6 shadow-xl motion-card" style="background: linear-gradient(135deg, #059669 0%, #14b8a6 100%); --d: 2">
    <h3 class="text-lg font-semibold">Cash in Hand</h3>
    <p class="mt-3 text-4xl font-bold" data-stat="cash_in_hand">₹0.00</p>
  </article>
  <article class="relative overflow-hidden rounded-2xl p-6 shadow-xl motion-card" style="background: linear-gradient(135deg, #9333ea 0%, #ec4899 100%); --d: 3">
    <h3 class="text-lg font-semibold">Pending Payment</h3>
    <p class="mt-3 text-4xl font-bold" data-stat="pending_payment">₹0.00</p>
  </article>
</div>

<section id="sale-form" class="mt-8 hidden rounded-2xl border border-white/20 bg-white/10 p-4 shadow-2xl backdrop-blur-xl sm:rounded-3xl sm:p-8">
  <div class="mb-6 flex flex-col gap-3 border-b border-white/10 pb-4 sm:mb-8 sm:flex-row sm:items-start sm:justify-between">
    <div>
      <h2 id="sale-form-title" class="text-2xl font-bold sm:text-3xl">📝 New Sale</h2>
      <p class="mt-2 text-sm text-gray-300">Pehle customer, phir saman, last mein payment. Bill pehle preview hoga, phir save.</p>
      <p class="mt-3 text-lg font-semibold text-emerald-300">Invoice No: <span id="next-invoice">—</span></p>
    </div>
    <button type="button" id="btn-close-sale" class="rounded-lg bg-white/10 px-4 py-2 text-sm hover:bg-white/20">✖ Close</button>
  </div>

  <input type="hidden" id="editing-sale-id" value="">

  <div class="mb-6 rounded-2xl border border-white/10 bg-black/20 p-4">
    <h3 class="mb-3 font-semibold">1. Customer</h3>
    <label class="mb-3 block text-sm text-gray-300">Customer name
      <span class="relative mt-1 block">
        <input type="text" id="customer-name" placeholder="Naam likho" autocomplete="off" class="w-full rounded-2xl border border-gray-300 bg-white px-4 py-3 text-gray-900 outline-none">
        <div id="customer-suggest" class="suggest hidden absolute left-0 right-0 z-50 mt-1 max-h-72 overflow-y-auto rounded-lg border bg-white text-gray-900 shadow-xl"></div>
      </span>
    </label>
    <label class="mb-3 block text-sm text-gray-300">Mobile
      <input type="text" id="customer-mobile" placeholder="10 digit number" class="mt-1 w-full rounded-2xl border border-gray-300 bg-white px-4 py-3 text-gray-900 outline-none">
    </label>
    <label class="mb-3 block text-sm text-gray-300">Address
      <input type="text" id="customer-address" placeholder="Optional" class="mt-1 w-full rounded-2xl border border-gray-300 bg-white px-4 py-3 text-gray-900 outline-none">
    </label>
    <label class="mb-3 block text-sm text-gray-300">GST number
      <input type="text" id="customer-gst" placeholder="Agar GST bill ho" class="mt-1 w-full rounded-2xl border border-gray-300 bg-white px-4 py-3 text-gray-900 outline-none">
    </label>
    <label class="block max-w-xs text-sm font-medium text-gray-200">
      Bill Date (dd/mm/yyyy)
      <span class="date-field mt-1">
        <input type="text" id="sale-date" inputmode="numeric" placeholder="dd/mm/yyyy" maxlength="10" autocomplete="off" class="rounded-xl border border-gray-300 bg-white px-3 py-2 text-base text-gray-900">
        <input type="date" id="sale-date-picker" title="Calendar" aria-label="Calendar">
      </span>
    </label>
    <p class="mt-4 mb-2 text-sm text-gray-400">Painter / plumber / electrician ke through sale ho to yahan likho.</p>
    <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
      <select id="ref-type" class="rounded-2xl border border-gray-300 bg-white px-4 py-3 text-gray-900">
        <option value="">Reference: None</option>
        <option value="painter">Painter</option>
        <option value="plumber">Plumber</option>
        <option value="electrician">Electrician</option>
      </select>
      <div class="relative md:col-span-1">
        <input type="text" id="ref-name" placeholder="Unka naam" autocomplete="off" class="w-full rounded-2xl border border-gray-300 bg-white px-4 py-3 text-gray-900 outline-none">
        <div id="ref-suggest" class="suggest hidden absolute left-0 right-0 z-50 mt-1 max-h-72 overflow-y-auto rounded-lg border bg-white text-gray-900 shadow-xl"></div>
      </div>
      <input type="text" id="ref-mobile" placeholder="Unka mobile (optional)" class="rounded-2xl border border-gray-300 bg-white px-4 py-3 text-gray-900 outline-none">
    </div>
  </div>
  <div class="mt-8">
    <h3 class="mb-3 font-semibold">2. Saman / Items</h3>
    <p class="mb-3 text-sm text-gray-400">Product naam type karo, list se choose karo. Last price par Enter dabane se next line khulti hai.</p>
    <div class="line-head sale-head mb-4 hidden rounded-2xl border border-white/10 bg-white/10 font-semibold md:grid">
      <div>📦 Product</div>
      <div>🎨 Colour / Shade</div>
      <div>Qty / Unit</div>
      <div>Price</div>
      <div>Total</div>
      <div>Action</div>
    </div>
    <datalist id="paint-shades">
      <option value="White"></option>
      <option value="Off White"></option>
      <option value="Ivory"></option>
      <option value="Cream"></option>
      <option value="Snow White"></option>
      <option value="Yellow"></option>
      <option value="Golden Yellow"></option>
      <option value="Orange"></option>
      <option value="Red"></option>
      <option value="Maroon"></option>
      <option value="Pink"></option>
      <option value="Blue"></option>
      <option value="Sky Blue"></option>
      <option value="Green"></option>
      <option value="Mint"></option>
      <option value="Grey"></option>
      <option value="Brown"></option>
      <option value="Beige"></option>
      <option value="Black"></option>
    </datalist>
    <div id="product-rows"></div>
    <button type="button" id="btn-add-sale-row" class="mt-2 rounded-xl bg-white/15 px-4 py-3 font-semibold hover:bg-white/25">➕ Aur item add karo</button>
  </div>

  <div class="mt-6 text-right text-2xl font-bold text-green-400">
    Grand Total: ₹<span id="grand-total">0.00</span>
  </div>
  <p class="mt-1 text-right text-lg text-amber-300">Due: ₹<span id="sale-due">0.00</span></p>
  <p id="sale-npr-hint" class="mt-1 hidden text-right text-base font-semibold text-sky-300"></p>

  <div class="mt-8 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
    <div class="rounded-2xl border border-white/20 bg-white/10 p-4">
      <div class="mb-2 font-semibold">3. Payment</div>
      <p class="mb-2 text-xs text-gray-400">Cash mil gaya to Full paid. Thoda mila to Partial. Udhaar to Credit / Due.</p>
      <div class="flex flex-wrap items-center gap-3 text-sm">
        <label class="flex items-center gap-2"><input type="radio" name="pay-mode" value="full" checked> Full paid</label>
        <label class="flex items-center gap-2"><input type="radio" name="pay-mode" value="partial"> Partial</label>
        <label class="flex items-center gap-2"><input type="radio" name="pay-mode" value="due"> Credit / Due</label>
      </div>
      <input type="number" id="sale-received" placeholder="Kitna cash mila" class="mt-3 hidden w-full max-w-xs rounded-xl border border-gray-300 bg-white px-4 py-2 text-gray-900">
      <label id="due-date-wrap" class="mt-3 hidden block max-w-xs text-sm text-gray-200">
        Due date (dd/mm/yyyy)
        <span class="date-field mt-1">
          <input type="text" id="sale-due-date" inputmode="numeric" placeholder="dd/mm/yyyy" maxlength="10" autocomplete="off" class="rounded-xl border border-gray-300 bg-white px-3 py-2 text-base text-gray-900">
          <input type="date" id="sale-due-date-picker" title="Calendar" aria-label="Due date">
        </span>
      </label>
      <div id="npr-wrap" class="mt-3 hidden max-w-xs rounded-xl border border-sky-400/30 bg-sky-500/10 p-3">
        <label class="block text-sm text-gray-200">
          🇳🇵 Nepal Rate (₹1 = रु)
          <input type="number" id="npr-rate" step="0.01" min="0.01" placeholder="1.6" class="mt-1 w-full rounded-xl border border-gray-300 bg-white px-4 py-2 text-gray-900">
        </label>
        <p class="mt-1 text-xs text-emerald-300">+977 customer — bill ke last me Nepali rupiya conversion dikhega.</p>
        <div id="npr-received-wrap" class="mt-3 hidden">
          <label class="block text-sm text-gray-200">
            Customer ne kitne <b>रु</b> (NPR) diye?
            <input type="number" id="npr-received" step="0.01" min="0" placeholder="Nepali rupiya" class="mt-1 w-full rounded-xl border border-gray-300 bg-white px-4 py-2 text-gray-900">
          </label>
          <p id="npr-received-inr" class="mt-1 text-xs font-bold text-sky-300"></p>
          <p class="mt-1 text-xs text-gray-400">Bharne par "Kitna cash mila" apne aap ₹ mein bhar jayega.</p>
        </div>
      </div>
    </div>
    <button type="button" id="btn-preview" class="rounded-xl bg-green-600 px-6 py-3 font-semibold text-white hover:bg-green-500">👀 Preview bill</button>
  </div>
</section>

<div id="history-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/60 p-0 sm:p-4">
  <div class="modal-sheet flex h-full w-full max-w-6xl flex-col overflow-hidden rounded-none bg-white p-4 text-black shadow-2xl sm:h-auto sm:max-h-[90vh] sm:rounded-xl sm:p-6">
    <div class="mb-4 flex items-center justify-between gap-2">
      <h2 class="text-xl font-bold sm:text-3xl">🧾 Sales History</h2>
      <button type="button" id="btn-close-history" class="rounded-lg bg-red-600 px-5 py-2 text-white">✖ Close</button>
    </div>
    <div class="table-scroll max-h-[70vh] overflow-auto">
      <table class="w-full border-collapse border">
        <thead>
          <tr class="bg-blue-600 text-white">
            <th class="border p-3">Invoice</th>
            <th class="border p-3">Customer</th>
            <th class="border p-3">Reference</th>
            <th class="border p-3">Total</th>
            <th class="border p-3">Date</th>
            <th class="border p-3">Action</th>
          </tr>
        </thead>
        <tbody id="sales-history-body"></tbody>
      </table>
    </div>
  </div>
</div>

<div id="preview-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/60 p-0 sm:p-4">
  <div class="modal-sheet flex h-full max-h-none w-full max-w-6xl flex-col overflow-hidden rounded-none bg-white shadow-2xl sm:h-auto sm:max-h-[95vh] sm:rounded-xl">
        <div class="flex items-center justify-between border-b bg-white px-3 py-3 text-black print-hide sm:px-6 sm:py-4">
      <h2 class="text-lg font-bold text-blue-700 sm:text-2xl">Invoice Preview</h2>
      <button type="button" id="btn-close-preview" class="rounded-lg bg-red-500 px-4 py-2 text-white">✕ Close</button>
    </div>
    <div class="flex-1 overflow-y-auto bg-white p-6 text-black" id="invoice-preview-body"></div>
    <div class="sticky bottom-0 flex flex-wrap justify-center gap-2 border-t bg-white p-3 print-hide sm:gap-4 sm:p-4">
      <button type="button" id="btn-edit-sale" class="w-full rounded-lg bg-gray-600 px-5 py-2 text-white sm:w-auto">✏️ Edit</button>
      <button type="button" id="btn-print-invoice" class="w-full rounded-lg bg-blue-600 px-5 py-2 text-white sm:w-auto">🖨️ Print</button>
      <button type="button" id="btn-wa-preview" class="w-full rounded-lg bg-green-600 px-5 py-2 text-white sm:w-auto">📲 WhatsApp PDF</button>
      <button type="button" id="btn-confirm-save" class="w-full rounded-lg bg-purple-600 px-5 py-2 text-white sm:w-auto">💾 Save Sale</button>
    </div>
  </div>
</div>

<script>
  let saleRows = [emptyRow()];
  let catalog = [];

  function renderRows() {
    const wrap = document.getElementById("product-rows");
    wrap.innerHTML = saleRows.map((row, index) => `
      <div class="sale-line mb-3">
        <div class="relative">
          <span class="line-label">Product</span>
          <input data-index="${index}" data-field="name" value="${row.name ?? ""}" placeholder="Search Product..." autocomplete="off" class="w-full rounded-lg border border-gray-300 bg-white p-3 text-gray-900">
          <div class="suggest hidden absolute left-0 z-50 mt-1 max-h-72 w-full overflow-y-auto rounded-lg border bg-white text-gray-900 shadow-xl" data-suggest="${index}"></div>
        </div>
        <div>
          <span class="line-label">Colour / Shade</span>
          <div class="flex items-center gap-2">
            <input data-index="${index}" data-field="color_hex" type="color" value="${row.color_hex || "#ffffff"}" title="Colour" class="h-11 w-11 cursor-pointer rounded border border-gray-300 bg-white p-0">
            <input data-index="${index}" data-field="color" list="paint-shades" value="${row.color ?? ""}" placeholder="Shade / code" class="min-w-0 flex-1 rounded-lg border border-gray-300 bg-white p-3 text-gray-900">
          </div>
        </div>
        <div>
          <span class="line-label">Qty / Unit</span>
          <div class="qty-unit flex gap-2">
            <input data-index="${index}" data-field="qty" type="number" value="${row.qty ?? ""}" placeholder="Qty" class="w-20 rounded-lg border border-gray-300 bg-white p-3 text-gray-900">
            <select data-index="${index}" data-field="unit" class="min-w-0 flex-1 rounded-lg border border-gray-300 bg-white p-3 text-gray-900">${unitOptions(row.unit || "Piece")}</select>
          </div>
        </div>
        <div>
          <span class="line-label">Price</span>
          <input data-index="${index}" data-field="price" type="number" value="${row.price ?? ""}" placeholder="Price" class="w-full rounded-lg border border-gray-300 bg-white p-3 text-gray-900">
        </div>
        <div>
          <span class="line-label">Total</span>
          <div class="flex items-center font-semibold text-green-400" data-row-total="${index}">₹${formatMoney(rowTotal(row))}</div>
        </div>
        <button type="button" data-delete="${index}" class="rounded-lg bg-red-500 px-3 py-2 text-white hover:bg-red-600">🗑️ Remove</button>
      </div>
    `).join("");
    document.getElementById("grand-total").textContent = formatMoney(
      saleRows.reduce((sum, row) => sum + rowTotal(row), 0)
    );
    syncPaymentUi();
  }

  function updateSaleTotals() {
    document.querySelectorAll("#product-rows [data-row-total]").forEach((el) => {
      const index = Number(el.dataset.rowTotal);
      el.textContent = "₹" + formatMoney(rowTotal(saleRows[index] || emptyRow()));
    });
    document.getElementById("grand-total").textContent = formatMoney(
      saleRows.reduce((sum, row) => sum + rowTotal(row), 0)
    );
    syncPaymentUi();
  }

  function saleGrandTotal() {
    return validProducts().reduce((sum, row) => sum + rowTotal(row), 0)
      || saleRows.reduce((sum, row) => sum + rowTotal(row), 0);
  }

  function payMode() {
    return document.querySelector("input[name=\"pay-mode\"]:checked")?.value || "full";
  }

  const DEFAULT_NPR_RATE = 1.6;

  function isNepalCustomer(mobile) {
    const raw = String(mobile || "").trim();
    if (!raw) return false;
    const hasIntl = raw.startsWith("+") || raw.startsWith("00");
    let digits = raw.replace(/\D+/g, "");
    if (hasIntl && digits.startsWith("00")) digits = digits.slice(2);
    if (!digits.startsWith("977")) return false;
    return hasIntl ? digits.length >= 11 : digits.length > 10;
  }

  function nprRate() {
    const el = document.getElementById("npr-rate");
    const v = Number(el && el.value);
    return v > 0 ? v : DEFAULT_NPR_RATE;
  }

  function saleReceivedAmount(total) {
    const grand = total == null ? saleGrandTotal() : Number(total);
    const mode = payMode();
    if (mode === "due") return 0;
    if (mode === "partial") return Number(document.getElementById("sale-received").value) || 0;
    return grand;
  }

  function syncPaymentUi() {
    const mode = payMode();
    const receivedBox = document.getElementById("sale-received");
    receivedBox.classList.toggle("hidden", mode !== "partial");
    const dueWrap = document.getElementById("due-date-wrap");
    const showDue = mode === "partial" || mode === "due";
    if (dueWrap) dueWrap.classList.toggle("hidden", !showDue);
    if (showDue && dueWrap && !document.getElementById("sale-due-date").value) {
      const base = parseToIsoDate(document.getElementById("sale-date").value || todayIsoDate());
      const d = new Date(base + "T00:00:00");
      d.setDate(d.getDate() + 7);
      const iso = d.getFullYear() + "-" + String(d.getMonth() + 1).padStart(2, "0") + "-" + String(d.getDate()).padStart(2, "0");
      setDateField("sale-due-date", "sale-due-date-picker", iso);
    }
    const total = saleGrandTotal();
    const received = saleReceivedAmount(total);
    const dueEl = document.getElementById("sale-due");
    if (dueEl) dueEl.textContent = formatMoney(Math.max(0, total - received));
    const nepal = isNepalCustomer(document.getElementById("customer-mobile").value);
    const nepalBox = document.getElementById("npr-wrap");
    if (nepalBox) {
      nepalBox.classList.toggle("hidden", !nepal);
      const rateEl = document.getElementById("npr-rate");
      if (nepal && rateEl && !rateEl.value) {
        rateEl.value = localStorage.getItem("nprRate") || String(DEFAULT_NPR_RATE);
      }
      const recvWrap = document.getElementById("npr-received-wrap");
      if (recvWrap) recvWrap.classList.toggle("hidden", mode !== "partial");
    }
    const nprHint = document.getElementById("sale-npr-hint");
    if (nprHint) {
      if (nepal) {
        nprHint.textContent = "🇳🇵 रु " + formatMoney(total * nprRate()) + " (approx)";
        nprHint.classList.remove("hidden");
      } else {
        nprHint.textContent = "";
        nprHint.classList.add("hidden");
      }
    }
  }

  function setPayMode(mode) {
    const radio = document.querySelector("input[name=\"pay-mode\"][value=\"" + mode + "\"]");
    if (radio) radio.checked = true;
    syncPaymentUi();
  }

  function setSaleRowField(index, field, value) {
    const el = document.querySelector(`#product-rows [data-index="${index}"][data-field="${field}"]`);
    if (el) el.value = value;
  }

  function validProducts() {
    const byIndex = {};
    document.querySelectorAll("#product-rows [data-index][data-field]").forEach((el) => {
      const index = Number(el.dataset.index);
      if (Number.isNaN(index)) return;
      if (!byIndex[index]) {
        byIndex[index] = { name: "", color: "", color_hex: "#ffffff", hsn: "", qty: "", unit: "Piece", price: "", product_id: null };
      }
      byIndex[index][el.dataset.field] = el.value;
      if (saleRows[index] && saleRows[index].product_id) {
        byIndex[index].product_id = saleRows[index].product_id;
      }
      if (saleRows[index] && saleRows[index].hsn) {
        byIndex[index].hsn = saleRows[index].hsn;
      }
    });
    return Object.values(byIndex).filter((row) => String(row.name || "").trim() !== "").map((row) => {
      const qty = Number(row.qty) > 0 ? Number(row.qty) : 1;
      const price = Number(row.price) || 0;
      return {
        ...row,
        name: String(row.name).trim(),
        qty,
        price,
      };
    });
  }

  async function loadDashboard() {
    const stats = await api("/api/dashboard.php");
    document.querySelectorAll("[data-stat]").forEach((el) => {
      el.textContent = "₹" + formatMoney(stats[el.dataset.stat] || 0);
    });
  }

  async function loadCatalog() {
    const data = await api("/api/products.php");
    catalog = data.products || [];
  }

  function showSuggestions(index, value) {
    const box = document.querySelector(`[data-suggest="${index}"]`);
    if (!box) return;
    const search = (value || "").trim();
    if (!search) {
      box.classList.add("hidden");
      box.innerHTML = "";
      return;
    }
    const matches = catalog.filter((p) => (p.product_name || "").toLowerCase().includes(search.toLowerCase())).slice(0, 80);
    const exact = catalog.some((p) => (p.product_name || "").toLowerCase() === search.toLowerCase());
    let html = matches.map((p) => `
      <div class="cursor-pointer border-b p-3 hover:bg-blue-100" data-pick="${p.id}" data-index="${index}">
        <div class="font-medium">${escapeHtml(p.product_name)}</div>
        <div class="text-sm text-gray-500">₹ ${formatMoney(p.selling_price)}</div>
      </div>
    `).join("");
    if (!exact) {
      html += `
        <div class="cursor-pointer bg-green-50 p-3 font-semibold text-green-800 hover:bg-green-100" data-save-new="${index}">
          ➕ Save "${escapeHtml(search)}" as new product
        </div>`;
    }
    if (!html) {
      box.classList.add("hidden");
      return;
    }
    box.classList.remove("hidden");
    box.innerHTML = html;
    setSuggestActive(box, 0);
  }

  async function saveRowAsProduct(index) {
    const row = saleRows[index];
    const name = (row.name || "").trim();
    if (!name) {
      alert("Pehle product name likho");
      return;
    }
    const price = Number(row.price) || 0;
    const data = await api("/api/product_save.php", {
      method: "POST",
      body: JSON.stringify({
        product_name: name,
        unit: row.unit || "Piece",
        selling_price: price,
        purchase_price: 0,
        stock: 0,
        gst_percent: 18,
      }),
    });
    const product = {
      id: data.id,
      product_name: name,
      unit: row.unit || "Piece",
      selling_price: price,
    };
    catalog.push(product);
    saleRows[index].product_id = data.id;
    alert("✅ Product saved: " + name);
    const box = document.querySelector(`[data-suggest="${index}"]`);
    if (box) box.classList.add("hidden");
  }

  let nextInvoiceNo = "";

  async function loadNextInvoice() {
    try {
      const data = await api("/api/sales.php?next_invoice=1");
      nextInvoiceNo = String(data.invoice_no || "");
      const el = document.getElementById("next-invoice");
      if (el) el.textContent = nextInvoiceNo || "—";
    } catch (err) {
      nextInvoiceNo = "";
    }
  }

  function invoiceSheetHtml(customer, products, grandTotal, invoiceNo) {
    const company = window.COMPANY || {};
    const name = company.name || "SATYANARAYAN HARDWARE STORES";
    const email = company.email || "";
    const state = gstStateLabel(company.gst || "10");
    const no = invoiceNo || nextInvoiceNo || "—";
    const received = saleReceivedAmount(grandTotal);
    const balance = grandTotal - received;
    const nepal = isNepalCustomer(customer.mobile);
    const npr = nepal ? nprRate() : 0;
    const rows = products.length
      ? products.map((p, i) => {
          const colorText = String(p.color || "").trim();
          const hex = String(p.color_hex || "").trim();
          const hasColor = colorText || (hex && hex.toLowerCase() !== "#ffffff");
          const colorCell = hasColor
            ? (hex ? "<span class=\"swatch\" style=\"background:" + hex + "\"></span>" : "") +
              escapeHtml(colorText || hex)
            : "—";
          return (
            "<tr>" +
            "<td class=\"center\">" + (i + 1) + "</td>" +
            "<td class=\"item-name\">" + escapeHtml(p.name) + "</td>" +
            "<td>" + colorCell + "</td>" +
            "<td class=\"center\">" + escapeHtml(p.hsn || "—") + "</td>" +
            "<td class=\"center\">" + formatQty(p.qty) + "</td>" +
            "<td class=\"center\">" + escapeHtml(p.unit || "Piece") + "</td>" +
            "<td class=\"num\">" + formatMoney(p.price) + "</td>" +
            "<td class=\"num\">" + formatMoney(rowTotal(p)) + "</td>" +
            "</tr>"
          );
        }).join("")
      : "<tr><td colspan=\"8\">No products</td></tr>";

    return `
      <article class="invoice">
        <table class="tax-sheet">
          <colgroup>
            <col class="c-no"><col class="c-item"><col class="c-color"><col class="c-hsn">
            <col class="c-qty"><col class="c-unit"><col class="c-rate"><col class="c-amt">
          </colgroup>
          <thead>
            <tr><th colspan="8" class="title-cell">Tax Invoice</th></tr>
            <tr>
              <td colspan="8" class="company-cell">
                <div class="inv-name">${escapeHtml(name)}</div>
                <p>${escapeHtml(company.address_line1 || "")}</p>
                <p>${escapeHtml(company.address_line2 || "")}</p>
                <p>Phone: ${escapeHtml(company.mobile || "")}${email ? " &nbsp;|&nbsp; Email: " + escapeHtml(email) : ""}</p>
                <p>GSTIN: ${escapeHtml(company.gst || "")}${state ? " &nbsp;|&nbsp; State: " + escapeHtml(state) : ""}</p>
              </td>
            </tr>
            <tr>
              <td colspan="4" class="party-cell">
                <div class="section-lbl">Bill To</div>
                <p class="party">${escapeHtml(customer.name || "Walk-in Customer")}</p>
                <p>Phone: ${escapeHtml(customer.mobile || "")}</p>
                ${customer.address ? "<p>" + escapeHtml(customer.address) + "</p>" : ""}
                ${customer.gst ? "<p>GSTIN: " + escapeHtml(customer.gst) + "</p>" : ""}
              </td>
              <td colspan="4" class="meta-cell">
                <div class="section-lbl">Invoice Details</div>
                <div class="meta-line"><span>Invoice No.</span><span>${escapeHtml(no)}</span></div>
                <div class="meta-line"><span>Date</span><span>${invoiceDateLabel()}</span></div>
                ${balance > 0.009 && document.getElementById("sale-due-date") && document.getElementById("sale-due-date").value
                  ? '<div class="meta-line"><span>Due Date</span><span>' + escapeHtml(document.getElementById("sale-due-date").value) + "</span></div>"
                  : ""}
                ${customer.ref_type && customer.ref_name ? '<div class="meta-line"><span>' + escapeHtml(customer.ref_type) + '</span><span>' + escapeHtml(customer.ref_name) + '</span></div>' : ""}
              </td>
            </tr>
            <tr>
              <th class="center">#</th>
              <th>Item Name</th>
              <th>Colour / Shade</th>
              <th class="center">HSN/SAC</th>
              <th class="center">Qty</th>
              <th class="center">Unit</th>
              <th class="num">Price/Unit (₹)</th>
              <th class="num">Amount (₹)</th>
            </tr>
          </thead>
          <tbody>
            ${rows}
            <tr>
              <td colspan="5" class="words-cell">
                <div class="words-label">Invoice Amount In Words</div>
                <div>${escapeHtml(amountInWords(grandTotal))}</div>
              </td>
              <td colspan="3" style="padding:0">
                <table class="inner-tot">
                  <tr class="grand"><td>Total</td><td class="num">₹ ${formatMoney(grandTotal)}</td></tr>
                  <tr><td>Received</td><td class="num">₹ ${formatMoney(received)}</td></tr>
                  <tr><td>Balance</td><td class="num">₹ ${formatMoney(balance)}</td></tr>
                  ${nepal ? '<tr class="npr-row"><td>🇳🇵 Total in Nepali Rupiya (₹1 = रु ' + npr + ')</td><td class="num">रु ' + formatMoney(grandTotal * npr) + "</td></tr>" : ""}
                  ${nepal && balance > 0.009 ? '<tr class="npr-row"><td>🇳🇵 Balance in Nepali Rupiya</td><td class="num">रु ' + formatMoney(balance * npr) + "</td></tr>" : ""}
                </table>
              </td>
            </tr>
            <tr>
              <td colspan="5" class="terms-cell">
                <div class="terms-label">Terms and Conditions</div>
                Thank you for doing business with us.
              </td>
              <td colspan="3" class="sign-cell">
                <div class="sign-who">For ${escapeHtml(name)}</div>
                <div>Authorized Signatory</div>
              </td>
            </tr>
          </tbody>
        </table>
      </article>
    `;
  }

  document.getElementById("btn-new-sale").addEventListener("click", () => {
    document.getElementById("editing-sale-id").value = "";
    document.getElementById("sale-form-title").textContent = "📝 New Sale";
    saleRows = [emptyRow()];
    document.getElementById("customer-name").value = "";
    document.getElementById("customer-mobile").value = "";
    document.getElementById("customer-address").value = "";
    document.getElementById("customer-gst").value = "";
    document.getElementById("sale-received").value = "";
    document.getElementById("sale-due-date").value = "";
    setPayMode("full");
    document.getElementById("ref-type").value = "";
    document.getElementById("ref-name").value = "";
    document.getElementById("ref-mobile").value = "";
    setDateField("sale-date", "sale-date-picker", todayIsoDate());
    document.getElementById("sale-form").classList.remove("hidden");
    document.getElementById("sale-form").scrollIntoView({ behavior: "smooth", block: "start" });
    renderRows();
    loadNextInvoice();
  });

  document.getElementById("btn-close-sale").addEventListener("click", () => {
    document.getElementById("sale-form").classList.add("hidden");
  });

  document.getElementById("btn-add-sale-row").addEventListener("click", () => {
    saleRows.push(emptyRow());
    renderRows();
    focusRowField("product-rows", saleRows.length - 1, "name");
  });

  let customerSuggestNav = -1;

  function closeCustomerSuggest() {
    const box = document.getElementById("customer-suggest");
    box.classList.add("hidden");
    box.innerHTML = "";
    customerSuggestNav = -1;
  }

  function customerSuggestItems() {
    return [...document.querySelectorAll("#customer-suggest [data-cust-new], #customer-suggest [data-cust-pick]")];
  }

  function highlightCustomerSuggest(index) {
    const items = customerSuggestItems();
    if (!items.length) {
      customerSuggestNav = -1;
      return;
    }
    customerSuggestNav = Math.max(0, Math.min(index, items.length - 1));
    items.forEach((el, i) => el.classList.toggle("suggest-active", i === customerSuggestNav));
    items[customerSuggestNav].scrollIntoView({ block: "nearest" });
  }

  function goCustomerNextField() {
    closeCustomerSuggest();
    document.getElementById("customer-mobile").focus();
  }

  function pickCustomerRow(el) {
    if (!el) return goCustomerNextField();
    if (el.dataset.custNew) {
      goCustomerNextField();
      return;
    }
    document.getElementById("customer-name").value = el.dataset.name || "";
    if (el.dataset.mobile) document.getElementById("customer-mobile").value = el.dataset.mobile;
    if (el.dataset.address) document.getElementById("customer-address").value = el.dataset.address;
    goCustomerNextField();
  }

  async function showCustomerSuggestions() {
    const box = document.getElementById("customer-suggest");
    const search = document.getElementById("customer-name").value.trim();
    customerSuggestNav = -1;
    if (search.length < 2) {
      closeCustomerSuggest();
      return;
    }
    const data = await api("/api/parties.php?type=customer&q=" + encodeURIComponent(search));
    const q = search.toLowerCase();
    const parties = (data.parties || []).filter((p) => {
      const name = String(p.name || "").toLowerCase();
      const score = Number(p.match_score || 0);
      return name.includes(q) || score >= 700;
    }).slice(0, 8);
    const newRow = `
      <div class="cursor-pointer border-b bg-green-50 p-3 font-semibold text-green-800 hover:bg-green-100" data-cust-new="1">
        ➕ Naya customer: "${escapeHtml(search)}"
      </div>`;
    const oldRows = parties.map((p) => `
      <div class="cursor-pointer border-b p-3 hover:bg-blue-100" data-cust-pick="${p.id}" data-name="${escapeHtml(p.name)}" data-mobile="${escapeHtml(p.mobile || "")}" data-address="${escapeHtml(p.address || "")}">
        <div class="font-medium">${escapeHtml(p.name)}</div>
        <div class="text-sm text-gray-500">${escapeHtml(p.mobile || "")}</div>
      </div>
    `).join("");
    box.innerHTML = newRow + oldRows;
    box.classList.remove("hidden");
  }

  document.getElementById("customer-name").addEventListener("input", () => {
    showCustomerSuggestions().catch(() => {});
  });
  document.getElementById("customer-name").addEventListener("keydown", (e) => {
    const box = document.getElementById("customer-suggest");
    const open = box && !box.classList.contains("hidden") && customerSuggestItems().length;
    if (e.key === "ArrowDown" && open) {
      e.preventDefault();
      highlightCustomerSuggest(customerSuggestNav < 0 ? 0 : customerSuggestNav + 1);
      return;
    }
    if (e.key === "ArrowUp" && open) {
      e.preventDefault();
      highlightCustomerSuggest(customerSuggestNav < 0 ? 0 : customerSuggestNav - 1);
      return;
    }
    if (e.key === "Escape") {
      e.preventDefault();
      closeCustomerSuggest();
      return;
    }
    if (e.key === "Enter") {
      e.preventDefault();
      if (open && customerSuggestNav >= 0) {
        pickCustomerRow(customerSuggestItems()[customerSuggestNav]);
      } else {
        goCustomerNextField();
      }
    }
  });
  document.getElementById("customer-name").addEventListener("blur", () => {
    setTimeout(closeCustomerSuggest, 180);
  });
  document.getElementById("customer-suggest").addEventListener("mousedown", (e) => {
    const row = e.target.closest("[data-cust-new], [data-cust-pick]");
    if (!row) return;
    e.preventDefault();
    pickCustomerRow(row);
  });

  document.getElementById("customer-mobile").addEventListener("keydown", (e) => {
    if (e.key !== "Enter") return;
    e.preventDefault();
    document.getElementById("customer-address").focus();
  });
  document.getElementById("customer-address").addEventListener("keydown", (e) => {
    if (e.key !== "Enter") return;
    e.preventDefault();
    document.getElementById("customer-gst").focus();
  });
  document.getElementById("customer-gst").addEventListener("keydown", (e) => {
    if (e.key !== "Enter") return;
    e.preventDefault();
    focusRowField("product-rows", 0, "name");
  });

  document.getElementById("customer-mobile").addEventListener("blur", async (e) => {
    const mobile = e.target.value.trim();
    if (!mobile) return;
    const data = await api("/api/customer_lookup.php?mobile=" + encodeURIComponent(mobile));
    if (data.customer) {
      if (!document.getElementById("customer-name").value.trim()) {
        document.getElementById("customer-name").value = data.customer.name || "";
      }
      if (!document.getElementById("customer-address").value.trim()) {
        document.getElementById("customer-address").value = data.customer.address || "";
      }
      if (!document.getElementById("customer-gst").value.trim()) {
        document.getElementById("customer-gst").value = data.customer.gst || "";
      }
    }
  });

  document.getElementById("product-rows").addEventListener("input", (e) => {
    const field = e.target.dataset.field;
    const index = Number(e.target.dataset.index);
    if (!field || Number.isNaN(index)) return;
    saleRows[index][field] = e.target.value;
    if (field === "name") {
      const match = catalog.find((p) => (p.product_name || "").toLowerCase() === e.target.value.trim().toLowerCase());
      if (match) {
        saleRows[index].price = String(match.selling_price);
        saleRows[index].unit = match.unit;
        saleRows[index].product_id = match.id;
        saleRows[index].hsn = match.hsn_code || "";
        setSaleRowField(index, "price", String(match.selling_price));
        setSaleRowField(index, "unit", match.unit);
      } else {
        saleRows[index].product_id = null;
      }
      showSuggestions(index, e.target.value);
    }
    if (field === "color") {
      const shade = PAINT_SHADES.find((item) => item[0].toLowerCase() === e.target.value.trim().toLowerCase());
      if (shade) {
        saleRows[index].color_hex = shade[1];
        setSaleRowField(index, "color_hex", shade[1]);
      }
    }
    updateSaleTotals();
  });

  document.getElementById("product-rows").addEventListener("keydown", (e) => {
    const field = e.target.dataset.field;
    const index = Number(e.target.dataset.index);
    if (!field || Number.isNaN(index)) return;

    if ((e.key === "Enter" || (e.key === "Tab" && !e.shiftKey)) && field === "price") {
      e.preventDefault();
      if (index >= saleRows.length - 1) {
        saleRows.push(emptyRow());
        renderRows();
      }
      focusRowField("product-rows", index + 1, "name");
    }
  });

  document.getElementById("product-rows").addEventListener("click", (e) => {
    const del = e.target.closest("[data-delete]");
    if (del) {
      saleRows.splice(Number(del.dataset.delete), 1);
      if (!saleRows.length) saleRows = [emptyRow()];
      renderRows();
      return;
    }
    const pick = e.target.closest("[data-pick]");
    if (pick) {
      const index = Number(pick.dataset.index);
      const product = catalog.find((p) => String(p.id) === String(pick.dataset.pick));
      if (product) {
        saleRows[index].name = product.product_name;
        saleRows[index].price = String(product.selling_price);
        saleRows[index].unit = product.unit;
        saleRows[index].product_id = product.id;
        saleRows[index].hsn = product.hsn_code || "";
        renderRows();
        focusRowField("product-rows", index, "qty");
      }
      return;
    }
    const saveNew = e.target.closest("[data-save-new]");
    if (saveNew) {
      saveRowAsProduct(Number(saveNew.dataset.saveNew)).catch((err) => alert(err.message));
    }
  });

  function currentCustomer() {
    return {
      name: document.getElementById("customer-name").value,
      mobile: document.getElementById("customer-mobile").value,
      address: document.getElementById("customer-address").value,
      gst: document.getElementById("customer-gst").value,
      ref_type: document.getElementById("ref-type").value,
      ref_name: document.getElementById("ref-name").value,
      ref_mobile: document.getElementById("ref-mobile").value,
    };
  }

  async function showRefSuggestions() {
    const type = document.getElementById("ref-type").value;
    const box = document.getElementById("ref-suggest");
    const search = document.getElementById("ref-name").value.trim();
    if (!type || !search) {
      box.classList.add("hidden");
      return;
    }
    const data = await api("/api/parties.php?type=" + encodeURIComponent(type) + "&q=" + encodeURIComponent(search));
    const parties = data.parties || [];
    if (!parties.length) {
      box.classList.add("hidden");
      return;
    }
    box.innerHTML = parties.map((p) => `
      <div class="cursor-pointer border-b p-3 hover:bg-blue-100" data-ref-pick="${p.id}" data-name="${escapeHtml(p.name)}" data-mobile="${escapeHtml(p.mobile || "")}">
        <div class="font-medium">${escapeHtml(p.name)}</div>
        <div class="text-sm text-gray-500">${escapeHtml(p.mobile || "")}</div>
      </div>
    `).join("");
    box.classList.remove("hidden");
    setSuggestActive(box, 0);
  }

  document.getElementById("ref-name").addEventListener("input", () => {
    showRefSuggestions().catch(() => {});
  });
  document.getElementById("ref-suggest").addEventListener("click", (e) => {
    const pick = e.target.closest("[data-ref-pick]");
    if (!pick) return;
    document.getElementById("ref-name").value = pick.dataset.name || "";
    document.getElementById("ref-mobile").value = pick.dataset.mobile || "";
    document.getElementById("ref-suggest").classList.add("hidden");
  });

  document.querySelectorAll("input[name=\"pay-mode\"]").forEach((el) => {
    el.addEventListener("change", syncPaymentUi);
  });
  document.getElementById("sale-received").addEventListener("input", syncPaymentUi);
  document.getElementById("customer-mobile").addEventListener("input", syncPaymentUi);

  const nprRateInput = document.getElementById("npr-rate");
  if (nprRateInput) {
    nprRateInput.addEventListener("input", () => {
      if (Number(nprRateInput.value) > 0) localStorage.setItem("nprRate", nprRateInput.value);
      syncPaymentUi();
    });
    const storedNpr = localStorage.getItem("nprRate");
    if (storedNpr && Number(storedNpr) > 0) nprRateInput.value = storedNpr;
  }

  const nprReceivedInput = document.getElementById("npr-received");
  if (nprReceivedInput) {
    nprReceivedInput.addEventListener("input", () => {
      const amt = Number(nprReceivedInput.value);
      const rate = nprRate();
      const recvEl = document.getElementById("sale-received");
      const out = document.getElementById("npr-received-inr");
      if (amt > 0 && rate > 0) {
        const inr = Math.round((amt / rate) * 100) / 100;
        if (recvEl) recvEl.value = String(inr);
        if (out) out.textContent = "रु " + formatMoney(amt) + " = ₹ " + formatMoney(inr);
      } else {
        if (out) out.textContent = "";
        if (recvEl && document.activeElement === nprReceivedInput) recvEl.value = "";
      }
      syncPaymentUi();
    });
  }

  document.getElementById("btn-preview").addEventListener("click", () => {
    const products = validProducts();
    const total = products.reduce((sum, row) => sum + rowTotal(row), 0);
    document.getElementById("invoice-preview-body").innerHTML = invoiceSheetHtml(currentCustomer(), products, total);
    document.getElementById("preview-modal").classList.remove("hidden");
    document.getElementById("preview-modal").classList.add("flex");
  });

  document.getElementById("btn-close-preview").addEventListener("click", () => {
    document.getElementById("preview-modal").classList.add("hidden");
    document.getElementById("preview-modal").classList.remove("flex");
  });
  document.getElementById("btn-edit-sale").addEventListener("click", () => {
    document.getElementById("preview-modal").classList.add("hidden");
    document.getElementById("preview-modal").classList.remove("flex");
  });
  document.getElementById("btn-print-invoice").addEventListener("click", () => {
    const products = validProducts();
    const total = products.reduce((sum, row) => sum + rowTotal(row), 0);
    printInvoiceSheet(invoiceSheetHtml(currentCustomer(), products, total));
  });

  async function persistCurrentSale() {
    const customer = currentCustomer();
    const products = validProducts();
    const editId = document.getElementById("editing-sale-id").value;
    const payload = {
      customer_name: customer.name,
      mobile: customer.mobile,
      address: customer.address,
      gst: customer.gst,
      ref_type: customer.ref_type,
      ref_name: customer.ref_name,
      ref_mobile: customer.ref_mobile,
      products,
      received: saleReceivedAmount(),
      sale_date: saleDateIso(),
      due_date: payMode() === "full" ? "" : parseToIsoDate(document.getElementById("sale-due-date").value),
    };
    if (editId) payload.id = Number(editId);
    return api("/api/sales.php" + (editId ? "?id=" + editId : ""), {
      method: editId ? "PUT" : "POST",
      body: JSON.stringify(payload),
    });
  }

  document.getElementById("btn-confirm-save").addEventListener("click", async () => {
    try {
      const editId = document.getElementById("editing-sale-id").value;
      const result = await persistCurrentSale();
      alert((editId ? "✅ Sale Updated\\nInvoice: " : "✅ Sale Saved Successfully\\nInvoice: ") + result.invoice_no);
      document.getElementById("editing-sale-id").value = "";
      document.getElementById("preview-modal").classList.add("hidden");
      document.getElementById("preview-modal").classList.remove("flex");
      window.open(appUrl("invoice.php?id=" + result.id), "_blank");
      loadDashboard();
      loadCatalog();
      loadNextInvoice();
    } catch (err) {
      alert(err.message);
    }
  });

  document.getElementById("btn-wa-preview").addEventListener("click", async () => {
    const btn = document.getElementById("btn-wa-preview");
    const customer = currentCustomer();
    if (!String(customer.mobile || "").replace(/\D+/g, "")) {
      alert("Customer ka mobile number likho, tab WhatsApp PDF jayega.");
      return;
    }
    const original = btn.textContent;
    btn.disabled = true;
    btn.textContent = "PDF bana rahe hain…";
    try {
      const result = await persistCurrentSale();
      const products = validProducts();
      const total = products.reduce((sum, row) => sum + rowTotal(row), 0);
      document.getElementById("invoice-preview-body").innerHTML = invoiceSheetHtml(
        customer,
        products,
        total,
        result.invoice_no
      );
      const sheet = document.querySelector("#invoice-preview-body .invoice");
      const sent = await shareInvoicePdfToWhatsApp({
        element: sheet,
        filename: "Invoice-" + String(result.invoice_no).replace(/[^A-Za-z0-9._-]+/g, "-") + ".pdf",
        phone: customer.mobile,
      });
      if (sent === "download") {
        alert("PDF save ho gaya. WhatsApp mein 📎 se wahi Invoice PDF attach karke customer ko bhejo.");
      }
      document.getElementById("editing-sale-id").value = "";
      loadDashboard();
      loadCatalog();
      loadNextInvoice();
    } catch (err) {
      alert(err.message);
    } finally {
      btn.disabled = false;
      btn.textContent = original;
    }
  });

  document.getElementById("btn-sales-history").addEventListener("click", async () => {
    const data = await api("/api/sales.php");
    document.getElementById("sales-history-body").innerHTML = (data.sales || []).map((sale) => {
      const ref = sale.ref_name ? (sale.ref_type || "ref") + ": " + sale.ref_name : "—";
      return `
      <tr>
        <td class="border p-3">${sale.invoice_no}</td>
        <td class="border p-3">${sale.customer_name || ""}</td>
        <td class="border p-3">${ref}</td>
        <td class="border p-3">₹${formatMoney(sale.total)}</td>
        <td class="border p-3">${invoiceDateLabel(sale.sale_date || sale.created_at)}</td>
        <td class="border p-3">
          <div class="flex justify-center gap-2">
            <a href="${appUrl("invoice.php?id=" + sale.id)}" target="_blank" class="rounded bg-blue-600 px-3 py-1 text-white">👁</a>
            ${sale.mobile ? `<a href="${appUrl("invoice.php?id=" + sale.id + "&whatsapp=1")}" target="_blank" class="rounded bg-green-600 px-3 py-1 text-white">WA</a>` : ""}
            <button type="button" data-sale-edit="${sale.id}" class="rounded bg-amber-500 px-3 py-1 text-white">✏️</button>
            <button type="button" data-sale-delete="${sale.id}" class="rounded bg-red-600 px-3 py-1 text-white">🗑</button>
          </div>
        </td>
      </tr>
    `;
    }).join("");
    document.getElementById("history-modal").classList.remove("hidden");
    document.getElementById("history-modal").classList.add("flex");
  });

  document.getElementById("btn-close-history").addEventListener("click", () => {
    document.getElementById("history-modal").classList.add("hidden");
    document.getElementById("history-modal").classList.remove("flex");
  });

  function applySaleToForm(sale) {
    document.getElementById("editing-sale-id").value = sale.id;
    document.getElementById("sale-form-title").textContent = "✏️ Edit Sale";
    document.getElementById("next-invoice").textContent = sale.invoice_no;
    document.getElementById("customer-name").value = sale.customer_name || "";
    document.getElementById("customer-mobile").value = sale.mobile || "";
    document.getElementById("customer-address").value = sale.address || "";
    document.getElementById("customer-gst").value = sale.gst || "";
    document.getElementById("ref-type").value = sale.ref_type || "";
    document.getElementById("ref-name").value = sale.ref_name || "";
    document.getElementById("ref-mobile").value = "";
    setDateField("sale-date", "sale-date-picker", sale.sale_date || sale.created_at);
    const rec = sale.received == null || sale.received === "" ? Number(sale.total) : Number(sale.received);
    const tot = Number(sale.total) || 0;
    if (rec <= 0) {
      setPayMode("due");
      document.getElementById("sale-received").value = "";
    } else if (Math.abs(rec - tot) < 0.009) {
      setPayMode("full");
      document.getElementById("sale-received").value = "";
    } else {
      setPayMode("partial");
      document.getElementById("sale-received").value = String(rec);
    }
    if (sale.due_date) {
      setDateField("sale-due-date", "sale-due-date-picker", sale.due_date);
    }
    saleRows = (sale.products || []).length
      ? sale.products.map((row) => ({
          name: row.name || "",
          color: row.color || "",
          color_hex: row.color_hex || "#ffffff",
          hsn: row.hsn || "",
          qty: row.qty,
          unit: row.unit || "Piece",
          price: row.price,
          product_id: row.product_id || null,
        }))
      : [emptyRow()];
    renderRows();
    document.getElementById("sale-form").classList.remove("hidden");
    document.getElementById("sale-form").scrollIntoView({ behavior: "smooth", block: "start" });
  }

  document.getElementById("sales-history-body").addEventListener("click", async (e) => {
    const editBtn = e.target.closest("[data-sale-edit]");
    if (editBtn) {
      const data = await api("/api/sales.php?id=" + editBtn.dataset.saleEdit);
      applySaleToForm(data.sale);
      document.getElementById("history-modal").classList.add("hidden");
      document.getElementById("history-modal").classList.remove("flex");
      return;
    }
    const btn = e.target.closest("[data-sale-delete]");
    if (!btn) return;
    if (!confirm("Delete this sale?")) return;
    await api("/api/sales.php?id=" + btn.dataset.saleDelete, { method: "DELETE" });
    btn.closest("tr").remove();
    loadDashboard();
  });

  bindDateField("sale-date", "sale-date-picker");
  bindDateField("sale-due-date", "sale-due-date-picker");
  setDateField("sale-date", "sale-date-picker", todayIsoDate());

  const editFromUrl = Number(new URLSearchParams(window.location.search).get("edit") || 0);
  if (editFromUrl > 0) {
    api("/api/sales.php?id=" + editFromUrl)
      .then((data) => applySaleToForm(data.sale))
      .catch((err) => alert(err.message));
  }

  loadDashboard().catch((err) => {
    const go = confirm("MySQL connect nahi hua: " + err.message + "\n\nSetup wizard (install.php) kholun?");
    if (go) window.location.href = appUrl("install.php");
  });
  loadCatalog().catch(() => {});
  loadNextInvoice().catch(() => {});
  renderRows();
</script>
<script src="<?= htmlspecialchars(app_url('assets/vendor/html2canvas.min.js'), ENT_QUOTES, 'UTF-8') ?>"></script>
<script src="<?= htmlspecialchars(app_url('assets/vendor/jspdf.umd.min.js'), ENT_QUOTES, 'UTF-8') ?>"></script>
<script src="<?= htmlspecialchars(app_url('assets/js/whatsapp-pdf.js'), ENT_QUOTES, 'UTF-8') ?>"></script>

<?php require __DIR__ . '/includes/footer.php'; ?>

<?php

declare(strict_types=1);

$pageTitle = 'Profit / Reports';
$activeNav = 'reports';
require __DIR__ . '/includes/header.php';
?>

<h1 class="mb-2 text-2xl font-bold sm:text-4xl">📊 Profit &amp; Product Report</h1>
<p class="mb-6 text-sm text-gray-300">Kitna profit, kaun sa saman sabse zyada / kam bika — din, mahina, saal.</p>

<div class="mb-6 flex flex-wrap gap-2">
  <button type="button" data-period="day" class="period-tab rounded-lg bg-green-600 px-4 py-2">Aaj / Din</button>
  <button type="button" data-period="month" class="period-tab rounded-lg bg-white/10 px-4 py-2">Mahina</button>
  <button type="button" data-period="year" class="period-tab rounded-lg bg-white/10 px-4 py-2">Saal</button>
  <button type="button" data-period="range" class="period-tab rounded-lg bg-white/10 px-4 py-2">Custom date</button>
</div>

<div class="mb-6 flex flex-wrap items-end gap-3 rounded-2xl border border-white/15 bg-white/10 p-4">
  <label id="wrap-day" class="text-sm text-gray-300">Din
    <span class="date-field mt-1">
      <input type="text" id="rep-date" inputmode="numeric" placeholder="dd/mm/yyyy" maxlength="10" class="rounded-xl border border-gray-300 bg-white px-3 py-2 text-gray-900">
      <input type="date" id="rep-date-picker" title="Calendar" aria-label="Calendar">
    </span>
  </label>
  <label id="wrap-month" class="hidden text-sm text-gray-300">Mahina
    <input type="month" id="rep-month" class="mt-1 block rounded-xl border border-gray-300 bg-white px-3 py-2 text-gray-900">
  </label>
  <label id="wrap-year" class="hidden text-sm text-gray-300">Saal
    <input type="number" id="rep-year" min="2020" max="2100" class="mt-1 block w-28 rounded-xl border border-gray-300 bg-white px-3 py-2 text-gray-900">
  </label>
  <label id="wrap-from" class="hidden text-sm text-gray-300">Se
    <span class="date-field mt-1">
      <input type="text" id="rep-from" inputmode="numeric" placeholder="dd/mm/yyyy" maxlength="10" class="rounded-xl border border-gray-300 bg-white px-3 py-2 text-gray-900">
      <input type="date" id="rep-from-picker" title="Calendar">
    </span>
  </label>
  <label id="wrap-to" class="hidden text-sm text-gray-300">Tak
    <span class="date-field mt-1">
      <input type="text" id="rep-to" inputmode="numeric" placeholder="dd/mm/yyyy" maxlength="10" class="rounded-xl border border-gray-300 bg-white px-3 py-2 text-gray-900">
      <input type="date" id="rep-to-picker" title="Calendar">
    </span>
  </label>
  <button type="button" id="btn-load-report" class="rounded-xl bg-emerald-600 px-5 py-2 font-semibold">Dekho</button>
</div>

<p id="report-label" class="mb-4 text-lg font-semibold text-amber-200"></p>

<div id="summary-cards" class="mb-8 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4"></div>
<p id="report-note" class="mb-6 hidden text-xs text-gray-400"></p>

<section id="breakup-wrap" class="mb-8 hidden">
  <h2 id="breakup-title" class="mb-3 text-xl font-bold">Breakup</h2>
  <div class="table-scroll overflow-auto rounded-2xl border border-white/15 bg-white/10">
    <table class="w-full text-left">
      <thead class="bg-white/10">
        <tr>
          <th class="p-3">Period</th>
          <th class="p-3 text-right">Bills</th>
          <th class="p-3 text-right">Sale</th>
          <th class="p-3 text-right">Cost</th>
          <th class="p-3 text-right">Profit</th>
        </tr>
      </thead>
      <tbody id="breakup-body"></tbody>
    </table>
  </div>
</section>

<div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
  <section>
    <h2 class="mb-3 text-xl font-bold">🔥 Sabse zyada sale</h2>
    <div class="table-scroll overflow-auto rounded-2xl border border-white/15 bg-white/10">
      <table class="w-full text-left">
        <thead class="bg-white/10">
          <tr>
            <th class="p-3">Product</th>
            <th class="p-3 text-right">Qty</th>
            <th class="p-3 text-right">Sale ₹</th>
            <th class="p-3 text-right">Profit ₹</th>
          </tr>
        </thead>
        <tbody id="top-body"></tbody>
      </table>
    </div>
  </section>
  <section>
    <h2 class="mb-3 text-xl font-bold">🐢 Kam sale</h2>
    <div class="table-scroll overflow-auto rounded-2xl border border-white/15 bg-white/10">
      <table class="w-full text-left">
        <thead class="bg-white/10">
          <tr>
            <th class="p-3">Product</th>
            <th class="p-3 text-right">Qty</th>
            <th class="p-3 text-right">Sale ₹</th>
            <th class="p-3 text-right">Profit ₹</th>
          </tr>
        </thead>
        <tbody id="slow-body"></tbody>
      </table>
    </div>
  </section>
</div>

<section class="mt-8">
  <h2 class="mb-3 text-xl font-bold">Is period mein 0 sale</h2>
  <p class="mb-2 text-sm text-gray-400">Catalogue ke woh items jo is time mein ek bhi nahi bike.</p>
  <div class="table-scroll overflow-auto rounded-2xl border border-white/15 bg-white/10">
    <table class="w-full text-left">
      <thead class="bg-white/10">
        <tr>
          <th class="p-3">Product</th>
          <th class="p-3 text-right">Stock</th>
          <th class="p-3 text-right">Price</th>
        </tr>
      </thead>
      <tbody id="unsold-body"></tbody>
    </table>
  </div>
</section>

<script>
  let period = "day";

  function setPeriod(next) {
    period = next;
    document.querySelectorAll(".period-tab").forEach((btn) => {
      const on = btn.dataset.period === period;
      btn.classList.toggle("bg-green-600", on);
      btn.classList.toggle("bg-white/10", !on);
    });
    document.getElementById("wrap-day").classList.toggle("hidden", period !== "day");
    document.getElementById("wrap-month").classList.toggle("hidden", period !== "month");
    document.getElementById("wrap-year").classList.toggle("hidden", period !== "year");
    document.getElementById("wrap-from").classList.toggle("hidden", period !== "range");
    document.getElementById("wrap-to").classList.toggle("hidden", period !== "range");
  }

  function reportQuery() {
    const q = new URLSearchParams({ period });
    if (period === "day") q.set("date", parseToIsoDate(document.getElementById("rep-date").value));
    if (period === "month") q.set("month", document.getElementById("rep-month").value);
    if (period === "year") q.set("year", document.getElementById("rep-year").value);
    if (period === "range") {
      q.set("from", parseToIsoDate(document.getElementById("rep-from").value));
      q.set("to", parseToIsoDate(document.getElementById("rep-to").value));
    }
    return q.toString();
  }

  function emptyRow(colspan, text) {
    return `<tr><td class="p-4 text-gray-400" colspan="${colspan}">${text}</td></tr>`;
  }

  function productRows(list) {
    if (!list || !list.length) return emptyRow(4, "Is period mein koi sale nahi.");
    return list.map((p) => `
      <tr class="border-t border-white/10">
        <td class="p-3">${escapeHtml(p.name || "")}</td>
        <td class="p-3 text-right">${formatQty(p.qty)}</td>
        <td class="p-3 text-right">₹${formatMoney(p.amount)}</td>
        <td class="p-3 text-right">₹${formatMoney(p.profit)}</td>
      </tr>
    `).join("");
  }

  async function loadReport() {
    const data = await api("/api/reports.php?" + reportQuery());
    const s = data.summary || {};
    document.getElementById("report-label").textContent = data.label || "";
    document.getElementById("summary-cards").innerHTML = [
      ["Sale", s.sales, "linear-gradient(135deg,#2563eb,#7c3aed)"],
      ["Cost (purchase rate)", s.cogs, "linear-gradient(135deg,#ea580c,#b45309)"],
      ["Profit", s.profit, "linear-gradient(135deg,#059669,#14b8a6)"],
      ["Supplier kharid", s.purchase_spend, "linear-gradient(135deg,#9333ea,#ec4899)"],
    ].map(([title, val, bg]) => `
      <article class="rounded-2xl p-5 shadow-xl" style="background:${bg}">
        <h3 class="text-sm opacity-90">${title}</h3>
        <p class="mt-2 text-3xl font-bold">₹${formatMoney(val)}</p>
        ${title === "Sale" ? `<small class="opacity-80">${s.bills || 0} bills</small>` : ""}
      </article>
    `).join("");
    const note = document.getElementById("report-note");
    note.textContent = data.note || "";
    note.classList.toggle("hidden", !data.note);

    const breakup = data.breakup || [];
    const wrap = document.getElementById("breakup-wrap");
    wrap.classList.toggle("hidden", breakup.length === 0);
    document.getElementById("breakup-title").textContent =
      data.period === "year" ? "Mahine ke hisaab se" : "Din ke hisaab se";
    document.getElementById("breakup-body").innerHTML = breakup.map((row) => `
      <tr class="border-t border-white/10">
        <td class="p-3">${escapeHtml(row.label)}</td>
        <td class="p-3 text-right">${row.bills}</td>
        <td class="p-3 text-right">₹${formatMoney(row.sales)}</td>
        <td class="p-3 text-right">₹${formatMoney(row.cogs)}</td>
        <td class="p-3 text-right font-semibold">₹${formatMoney(row.profit)}</td>
      </tr>
    `).join("");

    document.getElementById("top-body").innerHTML = productRows(data.top_products);
    document.getElementById("slow-body").innerHTML = productRows(data.slow_products);
    const unsold = data.unsold || [];
    document.getElementById("unsold-body").innerHTML = unsold.length
      ? unsold.map((p) => `
          <tr class="border-t border-white/10">
            <td class="p-3">${escapeHtml(p.name || "")}</td>
            <td class="p-3 text-right">${formatQty(p.stock)}</td>
            <td class="p-3 text-right">₹${formatMoney(p.price)}</td>
          </tr>
        `).join("")
      : emptyRow(3, "Catalogue ke saare items is period mein bik chuke hain, ya product list khali hai.");
  }

  document.querySelectorAll(".period-tab").forEach((btn) => {
    btn.addEventListener("click", () => {
      setPeriod(btn.dataset.period);
      loadReport().catch((err) => alert(err.message));
    });
  });
  document.getElementById("btn-load-report").addEventListener("click", () => {
    loadReport().catch((err) => alert(err.message));
  });

  const now = new Date();
  document.getElementById("rep-month").value =
    now.getFullYear() + "-" + String(now.getMonth() + 1).padStart(2, "0");
  document.getElementById("rep-year").value = String(now.getFullYear());
  bindDateField("rep-date", "rep-date-picker");
  bindDateField("rep-from", "rep-from-picker");
  bindDateField("rep-to", "rep-to-picker");
  setDateField("rep-date", "rep-date-picker", todayIsoDate());
  setDateField("rep-from", "rep-from-picker", todayIsoDate());
  setDateField("rep-to", "rep-to-picker", todayIsoDate());
  setPeriod("day");
  loadReport().catch((err) => alert(err.message));
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>

<?php

declare(strict_types=1);

$pageTitle = 'Ledger';
$activeNav = 'ledger';
require __DIR__ . '/includes/header.php';

$types = [
    'customer' => 'Customer',
    'supplier' => 'Supplier',
    'painter' => 'Painter',
    'plumber' => 'Plumber',
    'electrician' => 'Electrician',
];
?>

<h1 class="mb-6 text-4xl font-bold">📒 Ledger</h1>
<p class="mb-6 text-gray-300">Customer, supplier aur painter/plumber/electrician ka hisaab. Painter par click karke dekho usne kitna saman liya.</p>

<div class="mb-6 flex flex-wrap items-center gap-2">
  <?php foreach ($types as $key => $label): ?>
    <button type="button" data-type="<?= htmlspecialchars($key, ENT_QUOTES, 'UTF-8') ?>" class="ledger-tab rounded-lg bg-white/10 px-4 py-2 hover:bg-white/20"><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></button>
  <?php endforeach; ?>
  <button type="button" id="btn-add-party" class="rounded-lg bg-green-600 px-4 py-2 font-semibold hover:bg-green-500">➕ Naya Party</button>
  <a href="<?= htmlspecialchars(app_url('painter-list.php'), ENT_QUOTES) ?>" target="_blank" class="rounded-lg bg-purple-600 px-4 py-2 font-semibold hover:bg-purple-500">📋 Painter List (PDF)</a>
</div>

<div id="add-party-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/60">
  <div class="w-full max-w-lg rounded-2xl bg-slate-900 p-6 shadow-2xl">
    <div class="mb-4 flex items-center justify-between">
      <h3 class="text-xl font-bold">➕ Naya Party Add Karo</h3>
      <button type="button" id="close-add-party" class="rounded-lg bg-red-500 px-3 py-1">✕</button>
    </div>
    <form id="add-party-form" class="grid grid-cols-1 gap-3 sm:grid-cols-2">
      <div class="sm:col-span-2">
        <label class="mb-1 block text-sm text-neutral-400">Naam *</label>
        <input type="text" id="ap-name" placeholder="Jaise: Ram Painter, Sharma Ji" class="w-full rounded-lg border border-gray-300 bg-white p-3 text-gray-900" required>
      </div>
      <div>
        <label class="mb-1 block text-sm text-neutral-400">Mobile</label>
        <input type="text" id="ap-mobile" placeholder="9831046765" class="w-full rounded-lg border border-gray-300 bg-white p-3 text-gray-900">
      </div>
      <div>
        <label class="mb-1 block text-sm text-neutral-400">Type *</label>
        <select id="ap-type" class="w-full rounded-lg border border-gray-300 bg-white p-3 text-gray-900">
          <option value="painter">🎨 Painter</option>
          <option value="plumber">🔧 Plumber</option>
          <option value="electrician">⚡ Electrician</option>
          <option value="customer">👤 Customer</option>
          <option value="supplier">📦 Supplier</option>
        </select>
      </div>
      <div class="sm:col-span-2">
        <label class="mb-1 block text-sm text-neutral-400">Address (optional)</label>
        <input type="text" id="ap-address" placeholder="Area / Mohalla / City" class="w-full rounded-lg border border-gray-300 bg-white p-3 text-gray-900">
      </div>
      <div class="sm:col-span-2 flex gap-3 pt-2">
        <button type="button" id="cancel-add-party" class="flex-1 rounded-xl bg-gray-600 py-3 font-semibold">Cancel</button>
        <button type="submit" class="flex-1 rounded-xl bg-green-600 py-3 font-semibold">💾 Save Party</button>
      </div>
    </form>
  </div>
</div>
<div class="mb-6">
  <div class="relative max-w-xl">
    <input
      type="search"
      id="ledger-search"
      placeholder="🔎 Search by name or mobile number..."
      autocomplete="off"
      class="w-full rounded-xl border border-white/20 bg-white/10 px-4 py-3 pr-12 text-white placeholder:text-gray-400 outline-none focus:border-green-500"
    >

    <button
      type="button"
      id="clear-ledger-search"
      class="absolute right-3 top-1/2 hidden -translate-y-1/2 rounded-lg px-2 py-1 text-gray-300 hover:bg-white/10"
      aria-label="Clear search"
    >
      ✕
    </button>
  </div>
</div>
<div class="table-scroll overflow-auto rounded-2xl border border-white/20 bg-white/10">
  <table class="w-full border-collapse text-left">
    <thead>
      <tr class="bg-white/10">
        <th class="p-3">Name</th>
        <th class="p-3">Mobile</th>
        <th class="p-3 text-right">Debit</th>
        <th class="p-3 text-right">Credit</th>
        <th class="p-3 text-right">Balance</th>
        <th class="p-3">Action</th>
      </tr>
    </thead>
    <tbody id="ledger-list"></tbody>
  </table>
</div>

<div id="ledger-detail" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/60 p-4">
  <div class="modal-sheet max-h-[100vh] w-full max-w-5xl overflow-auto rounded-none bg-white p-4 text-black sm:max-h-[90vh] sm:rounded-xl sm:p-6">
    <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
      <h2 id="ledger-party-name" class="text-xl font-bold sm:text-2xl">Ledger</h2>
      <div class="flex flex-wrap gap-2">
        <a id="btn-download-ledger" href="#" target="_blank" class="flex-1 rounded-lg bg-blue-600 px-4 py-2 text-center text-white sm:flex-none">⬇ Download</a>
        <a id="btn-wa-ledger" href="#" target="_blank" class="flex-1 rounded-lg bg-green-600 px-4 py-2 text-center text-white sm:flex-none">WhatsApp</a>
        <button type="button" id="btn-delete-party" class="flex-1 rounded-lg bg-red-700 px-4 py-2 text-white sm:flex-none">Delete</button>
        <button type="button" id="btn-close-ledger" class="flex-1 rounded-lg bg-slate-600 px-4 py-2 text-white sm:flex-none">Close</button>
      </div>
    </div>
    <p id="ledger-party-meta" class="mb-4 text-gray-600"></p>
    <div class="mb-4 flex flex-wrap items-end gap-2">
      <label class="text-sm font-semibold text-gray-700">Particular date
        <span class="date-field mt-1 block">
          <input type="text" id="ledger-entry-filter" inputmode="numeric" placeholder="dd/mm/yyyy" maxlength="10" autocomplete="off" class="w-36 rounded-lg border border-gray-300 bg-white px-3 py-2 text-gray-900">
          <input type="date" id="ledger-entry-filter-picker" title="Calendar" aria-label="Calendar">
        </span>
      </label>
      <span class="pb-2 font-bold text-gray-500">ya</span>
      <label class="text-sm font-semibold text-gray-700">Range: Se
        <span class="date-field mt-1 block">
          <input type="text" id="ledger-entry-filter-from" inputmode="numeric" placeholder="dd/mm/yyyy" maxlength="10" autocomplete="off" class="w-36 rounded-lg border border-gray-300 bg-white px-3 py-2 text-gray-900">
          <input type="date" id="ledger-entry-filter-from-picker" title="Calendar" aria-label="Calendar">
        </span>
      </label>
      <label class="text-sm font-semibold text-gray-700">Tak
        <span class="date-field mt-1 block">
          <input type="text" id="ledger-entry-filter-to" inputmode="numeric" placeholder="dd/mm/yyyy" maxlength="10" autocomplete="off" class="w-36 rounded-lg border border-gray-300 bg-white px-3 py-2 text-gray-900">
          <input type="date" id="ledger-entry-filter-to-picker" title="Calendar" aria-label="Calendar">
        </span>
      </label>
      <button type="button" id="btn-clear-ledger-filter" class="rounded-lg bg-slate-600 px-4 py-2 text-white">✕ Clear</button>
    </div>
    <p id="ledger-filter-info" class="mb-2 hidden text-sm font-bold text-blue-700"></p>
    <div class="mb-4 grid grid-cols-1 gap-2 rounded-xl border border-slate-200 bg-slate-50 p-3 md:grid-cols-4">
      <input type="text" id="edit-party-name" placeholder="Name" class="rounded-lg border p-2">
      <input type="text" id="edit-party-mobile" placeholder="Mobile" class="rounded-lg border p-2">
      <input type="text" id="edit-party-address" placeholder="Address" class="rounded-lg border p-2 md:col-span-1">
      <button type="button" id="btn-save-party" class="rounded-lg bg-amber-500 px-4 py-2 font-semibold text-white">✏️ Save details</button>
    </div>
    <div class="mb-4 flex flex-wrap gap-2">
      <span class="date-field">
        <input type="text" id="pay-date" inputmode="numeric" placeholder="dd/mm/yyyy" maxlength="10" autocomplete="off" class="rounded-lg border p-2">
        <input type="date" id="pay-date-picker" title="Calendar" aria-label="Calendar">
      </span>
      <select id="pay-currency" class="rounded-lg border bg-white p-2 text-gray-900">
        <option value="inr">₹ Indian</option>
        <option value="npr">🇳🇵 रु Nepali</option>
      </select>
      <input type="number" id="pay-amount" placeholder="Amount" class="rounded-lg border bg-white p-2 text-gray-900">
      <input type="text" id="pay-notes" placeholder="Receipt / Payment note" class="min-w-[200px] flex-1 rounded-lg border p-2">
      <button type="button" id="btn-add-payment" class="rounded-lg bg-green-600 px-4 py-2 text-white">Add Receipt / Payment</button>
      <div id="pay-npr-extra" class="flex-wrap items-center gap-3" style="display:none">
        <label class="flex items-center gap-1 text-sm text-gray-300">₹1 =
          <input type="number" step="0.01" min="0.01" id="pay-npr-rate" placeholder="1.6" class="w-20 rounded border bg-white p-1 text-gray-900">
          रु
        </label>
        <span id="pay-npr-inr" class="text-sm font-bold text-emerald-300"></span>
      </div>
    </div>
    <table class="w-full border-collapse border">
      <thead>
        <tr class="bg-slate-100">
          <th class="border p-2">Date</th>
          <th class="border p-2">Particulars</th>
          <th class="border p-2">Ref</th>
          <th class="border p-2 text-right">Debit</th>
          <th class="border p-2 text-right">Credit</th>
          <th class="border p-2 text-right">Balance</th>
          <th class="border p-2">Action</th>
        </tr>
      </thead>
      <tbody id="ledger-entries"></tbody>
    </table>
  </div>
</div>

<script>
  let currentType = "customer";
  let currentPartyId = 0;
  let ledgerAllEntries = [];

  function renderLedgerEntries() {
    const rawDate = document.getElementById("ledger-entry-filter").value.trim();
    const rawFrom = document.getElementById("ledger-entry-filter-from").value.trim();
    const rawTo = document.getElementById("ledger-entry-filter-to").value.trim();
    const iso = rawDate !== "" ? parseToIsoDate(rawDate) : "";
    const fromIso = rawFrom !== "" ? parseToIsoDate(rawFrom) : "";
    const toIso = rawTo !== "" ? parseToIsoDate(rawTo) : "";
    const dayOf = (row) => (row.entry_date || "").slice(0, 10);
    let list;
    if (iso) {
      list = ledgerAllEntries.filter((row) => dayOf(row) === iso);
    } else if (fromIso || toIso) {
      list = ledgerAllEntries.filter((row) => {
        const d = dayOf(row);
        return (!fromIso || d >= fromIso) && (!toIso || d <= toIso);
      });
    } else {
      list = ledgerAllEntries;
    }
    const info = document.getElementById("ledger-filter-info");
    if ((iso || fromIso || toIso) && list.length) {
      const dSum = list.reduce((s, r) => s + (Number(r.debit) || 0), 0);
      const cSum = list.reduce((s, r) => s + (Number(r.credit) || 0), 0);
      const label = iso
        ? "Is date ki entries"
        : "Is range ki entries (" + (fromIso ? formatDisplayDate(fromIso) : "shuruaat") + " se " + (toIso ? formatDisplayDate(toIso) : "aaj tak") + ")";
      info.textContent = label + ": " + list.length + " | Debit ₹" + formatMoney(dSum) + " | Credit ₹" + formatMoney(cSum);
      info.classList.remove("hidden");
    } else if (iso || fromIso || toIso) {
      info.textContent = "Is date/range ki koi entry nahi mili.";
      info.classList.remove("hidden");
    } else {
      info.classList.add("hidden");
    }
    document.getElementById("ledger-entries").innerHTML = list.map((row) => `
      <tr data-entry-id="${row.id}">
        <td class="border p-2"><input type="text" data-f="entry_date" value="${escapeHtml(invoiceDateLabel(row.entry_date))}" class="w-28 rounded border p-1"></td>
        <td class="border p-2"><input type="text" data-f="particulars" value="${escapeHtml(row.particulars || "")}" class="w-full min-w-[140px] rounded border p-1"></td>
        <td class="border p-2">
          <input type="text" data-f="ref_no" value="${escapeHtml(row.ref_no || "")}" class="w-24 rounded border p-1">
          ${row.sale_id ? ' <a class="text-blue-600" href="' + appUrl("invoice.php?id=" + row.sale_id) + '" target="_blank">sale</a>' : ""}
          ${row.purchase_id ? ' <a class="text-blue-600" href="' + appUrl("purchase-bill.php?id=" + row.purchase_id) + '" target="_blank">bill</a>' : ""}
        </td>
        <td class="border p-2 text-right"><input type="number" step="0.01" data-f="debit" value="${Number(row.debit) || ""}" class="w-24 rounded border p-1 text-right"></td>
        <td class="border p-2 text-right"><input type="number" step="0.01" data-f="credit" value="${Number(row.credit) || ""}" class="w-24 rounded border p-1 text-right"></td>
        <td class="border p-2 text-right">${formatMoney(row.balance)}</td>
        <td class="border p-2">
          <div class="flex gap-1">
            <button type="button" data-save-entry="${row.id}" class="rounded bg-amber-500 px-2 py-1 text-white">✏️</button>
            <button type="button" data-del-entry="${row.id}" class="rounded bg-red-600 px-2 py-1 text-white">🗑</button>
          </div>
        </td>
      </tr>
    `).join("") || `<tr><td colspan="7" class="border p-3 text-center text-gray-500">${iso ? "Is date ki koi entry nahi mili." : "Koi entry nahi."}</td></tr>`;
  }

  function tabButtons() {
    document.querySelectorAll(".ledger-tab").forEach((btn) => {
      btn.classList.toggle("bg-green-600", btn.dataset.type === currentType);
      btn.classList.toggle("bg-white/10", btn.dataset.type !== currentType);
    });
  }

  async function loadList() {
    tabButtons();
    const search = document.getElementById("ledger-search")?.value.trim() || "";

const params = new URLSearchParams({
  type: currentType,
});

if (search !== "") {
  params.set("search", search);
}

const data = await api("/api/ledger.php?" + params.toString());
    document.getElementById("ledger-list").innerHTML = (data.parties || []).map((row) => `
      <tr class="border-t border-white/10 hover:bg-white/10" data-party="${row.id}">
        <td class="cursor-pointer p-3 font-semibold">${escapeHtml(row.name)}</td>
        <td class="cursor-pointer p-3">${escapeHtml(row.mobile || "")}</td>
        <td class="cursor-pointer p-3 text-right">₹${formatMoney(row.debit)}</td>
        <td class="cursor-pointer p-3 text-right">₹${formatMoney(row.credit)}</td>
        <td class="cursor-pointer p-3 text-right font-bold">₹${formatMoney(row.balance)}</td>
        <td class="p-3">
          <div class="flex flex-wrap gap-2">
            <button type="button" data-party-edit="${row.id}" class="rounded bg-amber-500 px-3 py-1 text-white">✏️</button>
            <a href="${appUrl("ledger-print.php?id=" + row.id)}" target="_blank" class="rounded bg-blue-600 px-3 py-1 text-white">⬇</a>
            ${row.mobile ? `<a href="${appUrl("ledger-print.php?id=" + row.id + "&whatsapp=1")}" target="_blank" class="rounded bg-green-600 px-3 py-1 text-white">WA</a>` : ""}
            <button type="button" data-del-party="${row.id}" class="rounded bg-red-600 px-3 py-1 text-white">🗑</button>
          </div>
        </td>
      </tr>
    `).join("") || `<tr><td class="p-4" colspan="6">Is type ka koi ledger nahi mila.</td></tr>`;
  }

  async function openParty(id) {
    currentPartyId = id;
    const data = await api("/api/ledger.php?party_id=" + id);
    const party = data.party || {};
    document.getElementById("ledger-party-name").textContent = (party.name || "") + " (" + (party.type || "") + ")";
    document.getElementById("ledger-party-meta").textContent =
      (party.mobile ? "Mobile: " + party.mobile + "  |  " : "") +
      "Debit ₹" + formatMoney(data.debit) + "  Credit ₹" + formatMoney(data.credit) +
      "  |  Balance ₹" + formatMoney(data.balance);
    document.getElementById("edit-party-name").value = party.name || "";
    document.getElementById("edit-party-mobile").value = party.mobile || "";
    document.getElementById("edit-party-address").value = party.address || "";
    const dl = document.getElementById("btn-download-ledger");
    dl.href = appUrl("ledger-print.php?id=" + id);
    const wa = document.getElementById("btn-wa-ledger");
    if (party.mobile) {
      wa.href = appUrl("ledger-print.php?id=" + id + "&whatsapp=1");
      wa.classList.remove("hidden");
    } else {
      wa.href = "#";
      wa.classList.add("hidden");
    }
    ledgerAllEntries = data.entries || [];
    renderLedgerEntries();
    document.getElementById("ledger-detail").classList.remove("hidden");
    document.getElementById("ledger-detail").classList.add("flex");
  }

  document.querySelectorAll(".ledger-tab").forEach((btn) => {
    btn.addEventListener("click", () => {
      currentType = btn.dataset.type;
      loadList().catch((err) => alert(err.message));
    });
  });

  document.getElementById("ledger-list").addEventListener("click", (e) => {
    const del = e.target.closest("[data-del-party]");
    if (del) {
      e.stopPropagation();
      if (!confirm("Is person ka poora ledger delete ho jayega. Bills delete nahi honge. Continue?")) return;
      api("/api/ledger.php?party_id=" + del.dataset.delParty, {
        method: "DELETE",
        body: JSON.stringify({ party_id: Number(del.dataset.delParty) }),
      })
        .then(() => loadList())
        .catch((err) => alert(err.message));
      return;
    }
    const edit = e.target.closest("[data-party-edit]");
    if (edit) {
      e.stopPropagation();
      openParty(Number(edit.dataset.partyEdit)).catch((err) => alert(err.message));
      return;
    }
    if (e.target.closest("a")) return;
    const row = e.target.closest("[data-party]");
    if (row) openParty(Number(row.dataset.party)).catch((err) => alert(err.message));
  });

  document.getElementById("ledger-entries").addEventListener("click", async (e) => {
    const saveBtn = e.target.closest("[data-save-entry]");
    if (saveBtn) {
      const tr = saveBtn.closest("tr");
      const val = (name) => (tr.querySelector('[data-f="' + name + '"]') || {}).value || "";
      try {
        await api("/api/ledger.php", {
          method: "PUT",
          body: JSON.stringify({
            id: Number(saveBtn.dataset.saveEntry),
            entry_date: parseToIsoDate(val("entry_date")),
            particulars: val("particulars"),
            ref_no: val("ref_no"),
            debit: val("debit"),
            credit: val("credit"),
          }),
        });
        await openParty(currentPartyId);
        await loadList();
      } catch (err) {
        alert(err.message);
      }
      return;
    }
    const btn = e.target.closest("[data-del-entry]");
    if (!btn) return;
    if (!confirm("Is ledger entry ko delete karein?")) return;
    try {
      await api("/api/ledger.php?id=" + btn.dataset.delEntry, { method: "DELETE" });
      await openParty(currentPartyId);
      await loadList();
    } catch (err) {
      alert(err.message);
    }
  });

  document.getElementById("btn-save-party").addEventListener("click", async () => {
    if (!currentPartyId) return;
    try {
      await api("/api/ledger.php", {
        method: "PUT",
        body: JSON.stringify({
          party_id: currentPartyId,
          name: document.getElementById("edit-party-name").value,
          mobile: document.getElementById("edit-party-mobile").value,
          address: document.getElementById("edit-party-address").value,
        }),
      });
      await openParty(currentPartyId);
      await loadList();
    } catch (err) {
      alert(err.message);
    }
  });

  document.getElementById("btn-delete-party").addEventListener("click", async () => {
    if (!currentPartyId) return;
    if (!confirm("Is person ka poora ledger delete ho jayega. Continue?")) return;
    try {
      await api("/api/ledger.php?party_id=" + currentPartyId, {
        method: "DELETE",
        body: JSON.stringify({ party_id: currentPartyId }),
      });
      document.getElementById("ledger-detail").classList.add("hidden");
      document.getElementById("ledger-detail").classList.remove("flex");
      await loadList();
    } catch (err) {
      alert(err.message);
    }
  });

  document.getElementById("btn-close-ledger").addEventListener("click", () => {
    document.getElementById("ledger-detail").classList.add("hidden");
    document.getElementById("ledger-detail").classList.remove("flex");
  });

  const payCurrency = document.getElementById("pay-currency");
  const payAmountEl = document.getElementById("pay-amount");
  const payNprExtra = document.getElementById("pay-npr-extra");
  const payNprRate = document.getElementById("pay-npr-rate");
  const payNprInr = document.getElementById("pay-npr-inr");

  function ledgerPayIsNpr() {
    return payCurrency && payCurrency.value === "npr";
  }

  function ledgerPayRate() {
    const v = Number(payNprRate && payNprRate.value);
    return v > 0 ? v : (Number(localStorage.getItem("nprRate")) || 1.6);
  }

  function syncLedgerPayUi() {
    const npr = ledgerPayIsNpr();
    if (payNprExtra) payNprExtra.style.display = npr ? "flex" : "none";
    if (!npr || !payNprInr) return;
    if (payNprRate && !payNprRate.value) {
      payNprRate.value = localStorage.getItem("nprRate") || "1.6";
    }
    const amt = Number(payAmountEl ? payAmountEl.value : 0);
    const rate = ledgerPayRate();
    payNprInr.textContent = amt > 0 && rate > 0
      ? "= ₹ " + formatMoney(Math.round((amt / rate) * 100) / 100)
      : "";
  }

  if (payCurrency) payCurrency.addEventListener("change", syncLedgerPayUi);
  if (payNprRate) {
    payNprRate.addEventListener("input", () => {
      const v = Number(payNprRate.value);
      if (v > 0) localStorage.setItem("nprRate", String(v));
      syncLedgerPayUi();
    });
  }
  if (payAmountEl) payAmountEl.addEventListener("input", syncLedgerPayUi);
  syncLedgerPayUi();

  document.getElementById("btn-add-payment").addEventListener("click", async () => {
    let amountVal = document.getElementById("pay-amount").value;
    let notesVal = document.getElementById("pay-notes").value;
    if (ledgerPayIsNpr()) {
      const nprAmt = Number(amountVal);
      const rate = ledgerPayRate();
      if (!(nprAmt > 0)) { alert("Nepali rupiya mein amount likho"); return; }
      if (!(rate > 0)) { alert("Rate likho: ₹1 = kitne रु"); return; }
      amountVal = String(Math.round((nprAmt / rate) * 100) / 100);
      notesVal = (notesVal ? notesVal + " " : "") + "[रु" + formatMoney(nprAmt) + " @ ₹1=रु" + rate + "]";
    }
    try {
      await api("/api/ledger.php", {
        method: "POST",
        body: JSON.stringify({
          party_id: currentPartyId,
          amount: amountVal,
          notes: notesVal,
          entry_date: parseToIsoDate(document.getElementById("pay-date").value),
        }),
      });
      document.getElementById("pay-amount").value = "";
      document.getElementById("pay-notes").value = "";
      if (payNprInr) payNprInr.textContent = "";
      await openParty(currentPartyId);
      await loadList();
    } catch (err) {
      alert(err.message);
    }
  });

  bindDateField("pay-date", "pay-date-picker");
  setDateField("pay-date", "pay-date-picker", todayIsoDate());

  bindDateField("ledger-entry-filter", "ledger-entry-filter-picker");
  bindDateField("ledger-entry-filter-from", "ledger-entry-filter-from-picker");
  bindDateField("ledger-entry-filter-to", "ledger-entry-filter-to-picker");
  ["ledger-entry-filter", "ledger-entry-filter-from", "ledger-entry-filter-to"].forEach((id) => {
    document.getElementById(id).addEventListener("change", renderLedgerEntries);
  });
  ["ledger-entry-filter-picker", "ledger-entry-filter-from-picker", "ledger-entry-filter-to-picker"].forEach((id) => {
    document.getElementById(id).addEventListener("change", renderLedgerEntries);
  });
  document.getElementById("btn-clear-ledger-filter").addEventListener("click", () => {
    ["ledger-entry-filter", "ledger-entry-filter-picker", "ledger-entry-filter-from", "ledger-entry-filter-from-picker", "ledger-entry-filter-to", "ledger-entry-filter-to-picker"].forEach((id) => {
      document.getElementById(id).value = "";
    });
    renderLedgerEntries();
  });

  loadList().catch((err) => alert(err.message));
  let ledgerSearchTimer = null;

document.getElementById("ledger-search").addEventListener("input", () => {
  clearTimeout(ledgerSearchTimer);

  const search = document.getElementById("ledger-search").value.trim();

  document
    .getElementById("clear-ledger-search")
    .classList.toggle("hidden", search === "");

  ledgerSearchTimer = setTimeout(() => {
    loadList();
  }, 300);
});

document.getElementById("clear-ledger-search").addEventListener("click", () => {
  document.getElementById("ledger-search").value = "";
  document.getElementById("clear-ledger-search").classList.add("hidden");
  loadList();
});

  document.getElementById("btn-add-party").addEventListener("click", () => {
    document.getElementById("add-party-modal").classList.remove("hidden");
    document.getElementById("add-party-modal").classList.add("flex");
    document.getElementById("ap-name").focus();
  });
  document.getElementById("close-add-party").addEventListener("click", () => {
    document.getElementById("add-party-modal").classList.add("hidden");
    document.getElementById("add-party-modal").classList.remove("flex");
  });
  document.getElementById("cancel-add-party").addEventListener("click", () => {
    document.getElementById("add-party-modal").classList.add("hidden");
    document.getElementById("add-party-modal").classList.remove("flex");
  });
  document.getElementById("add-party-modal").addEventListener("click", (e) => {
    if (e.target === e.currentTarget) {
      document.getElementById("add-party-modal").classList.add("hidden");
      document.getElementById("add-party-modal").classList.remove("flex");
    }
  });

  document.getElementById("add-party-form").addEventListener("submit", async (e) => {
    e.preventDefault();
    const name = document.getElementById("ap-name").value.trim();
    const mobile = document.getElementById("ap-mobile").value.trim();
    const type = document.getElementById("ap-type").value;
    const address = document.getElementById("ap-address").value.trim();
    if (!name) { alert("Naam toh likho!"); return; }
    try {
      await api("/api/parties.php", {
        method: "POST",
        body: JSON.stringify({ name, mobile, type, address }),
      });
      alert("✅ Party saved: " + name + " (" + type + ")");
      document.getElementById("add-party-modal").classList.add("hidden");
      document.getElementById("add-party-modal").classList.remove("flex");
      document.getElementById("add-party-form").reset();
      document.querySelector('.ledger-tab[data-type="' + type + '"]')?.click();
      loadList().catch(() => {});
    } catch (err) {
      alert("Error: " + err.message);
    }
  });
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>

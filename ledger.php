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

<div class="mb-6 flex flex-wrap gap-2">
  <?php foreach ($types as $key => $label): ?>
    <button type="button" data-type="<?= htmlspecialchars($key, ENT_QUOTES, 'UTF-8') ?>" class="ledger-tab rounded-lg bg-white/10 px-4 py-2 hover:bg-white/20"><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></button>
  <?php endforeach; ?>
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
        <button type="button" id="btn-delete-party" class="flex-1 rounded-lg bg-red-700 px-4 py-2 text-white sm:flex-none">Delete</button>
        <button type="button" id="btn-close-ledger" class="flex-1 rounded-lg bg-slate-600 px-4 py-2 text-white sm:flex-none">Close</button>
      </div>
    </div>
    <p id="ledger-party-meta" class="mb-4 text-gray-600"></p>
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
      <input type="number" id="pay-amount" placeholder="Amount" class="rounded-lg border p-2">
      <input type="text" id="pay-notes" placeholder="Receipt / Payment note" class="min-w-[200px] flex-1 rounded-lg border p-2">
      <button type="button" id="btn-add-payment" class="rounded-lg bg-green-600 px-4 py-2 text-white">Add Receipt / Payment</button>
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

  function tabButtons() {
    document.querySelectorAll(".ledger-tab").forEach((btn) => {
      btn.classList.toggle("bg-green-600", btn.dataset.type === currentType);
      btn.classList.toggle("bg-white/10", btn.dataset.type !== currentType);
    });
  }

  async function loadList() {
    tabButtons();
    const data = await api("/api/ledger.php?type=" + encodeURIComponent(currentType));
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
    document.getElementById("ledger-entries").innerHTML = (data.entries || []).map((row) => `
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
    `).join("");
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

  document.getElementById("btn-add-payment").addEventListener("click", async () => {
    try {
      await api("/api/ledger.php", {
        method: "POST",
        body: JSON.stringify({
          party_id: currentPartyId,
          amount: document.getElementById("pay-amount").value,
          notes: document.getElementById("pay-notes").value,
          entry_date: parseToIsoDate(document.getElementById("pay-date").value),
        }),
      });
      document.getElementById("pay-amount").value = "";
      document.getElementById("pay-notes").value = "";
      await openParty(currentPartyId);
      await loadList();
    } catch (err) {
      alert(err.message);
    }
  });

  bindDateField("pay-date", "pay-date-picker");
  setDateField("pay-date", "pay-date-picker", todayIsoDate());
  loadList().catch((err) => alert(err.message));
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>

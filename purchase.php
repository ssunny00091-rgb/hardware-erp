<?php

declare(strict_types=1);

$pageTitle = 'Purchase Entry';
$activeNav = 'purchase';
require __DIR__ . '/includes/header.php';
?>

<h1 id="purchase-form-title" class="mb-8 text-4xl font-bold">🛒 Purchase Entry</h1>
<input type="hidden" id="editing-purchase-id" value="">

<div class="mb-8 rounded-2xl border border-white/20 bg-white/10 p-6 backdrop-blur-xl">
  <h2 class="mb-6 text-2xl font-bold">🛒 Purchase Details</h2>
  <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
    <input type="text" id="supplier-name" placeholder="Supplier Name" class="rounded-xl border border-gray-300 bg-white p-3 text-gray-900">
    <input type="text" id="invoice-no" placeholder="Invoice Number" class="rounded-xl border border-gray-300 bg-white p-3 text-gray-900">
    <span class="date-field">
      <input type="text" id="purchase-date" inputmode="numeric" placeholder="dd/mm/yyyy" maxlength="10" autocomplete="off" class="rounded-xl border border-gray-300 bg-white p-3 text-gray-900">
      <input type="date" id="purchase-date-picker" title="Calendar" aria-label="Calendar">
    </span>
    <input type="number" id="purchase-paid" placeholder="Paid now (optional)" class="rounded-xl border border-gray-300 bg-white p-3 text-gray-900">
  </div>
</div>

<div class="mt-8">
  <div class="line-head mb-4 hidden grid-cols-5 gap-3 rounded-xl bg-white/10 p-4 md:grid">
    <div>Product</div>
    <div>Qty</div>
    <div>Purchase Price</div>
    <div>Total</div>
    <div>Action</div>
  </div>
  <div id="purchase-rows"></div>
</div>

<div class="mt-8 rounded-2xl border border-white/20 bg-white/10 p-6 backdrop-blur-xl">
  <div class="mb-6 flex items-center justify-between">
    <h2 class="text-2xl font-bold">💰 Purchase Summary</h2>
    <div class="text-3xl font-bold text-green-400">₹ <span id="purchase-total">0</span></div>
  </div>
  <div class="flex flex-col gap-3 sm:flex-row sm:gap-4">
    <button type="button" id="btn-add-row" class="flex-1 rounded-xl bg-blue-600 py-3 text-lg font-semibold hover:bg-blue-500">➕ Add Product</button>
    <button type="button" id="btn-cancel-edit" class="hidden flex-1 rounded-xl bg-slate-600 py-3 text-lg font-semibold">Cancel edit</button>
    <button type="button" id="btn-save-purchase" class="flex-1 rounded-xl bg-green-600 py-3 text-lg font-semibold hover:bg-green-500">💾 Save Purchase</button>
  </div>
</div>

<div class="mt-10">
  <h2 class="mb-4 text-2xl font-bold">📄 Supplier Bills</h2>
  <div class="table-scroll overflow-auto rounded-2xl border border-white/20 bg-white/10">
    <table class="w-full border-collapse">
      <thead>
        <tr class="bg-white/10">
          <th class="p-3 text-left">Date</th>
          <th class="p-3 text-left">Supplier</th>
          <th class="p-3 text-left">Bill No</th>
          <th class="p-3 text-right">Total</th>
          <th class="p-3 text-right">Paid</th>
          <th class="p-3 text-right">Due</th>
          <th class="p-3">Action</th>
        </tr>
      </thead>
      <tbody id="purchase-history"></tbody>
    </table>
  </div>
</div>

<script>
  let purchaseRows = [emptyRow()];
  let catalog = [];
  let editingPurchaseId = 0;

  function setPurchaseEditMode(on) {
    document.getElementById("purchase-form-title").textContent = on ? "✏️ Edit Supplier Bill" : "🛒 Purchase Entry";
    document.getElementById("btn-save-purchase").textContent = on ? "💾 Update Bill" : "💾 Save Purchase";
    document.getElementById("btn-cancel-edit").classList.toggle("hidden", !on);
  }

  function resetPurchaseForm() {
    editingPurchaseId = 0;
    document.getElementById("editing-purchase-id").value = "";
    document.getElementById("supplier-name").value = "";
    document.getElementById("invoice-no").value = "";
    document.getElementById("purchase-paid").value = "";
    setDateField("purchase-date", "purchase-date-picker", todayIsoDate());
    purchaseRows = [emptyRow()];
    setPurchaseEditMode(false);
    renderPurchase();
  }

  async function loadPurchaseForEdit(id) {
    const data = await api("/api/purchases.php?id=" + id);
    const bill = data.purchase;
    if (!bill) throw new Error("Bill not found");
    editingPurchaseId = Number(bill.id);
    document.getElementById("editing-purchase-id").value = String(bill.id);
    document.getElementById("supplier-name").value = bill.supplier_name || "";
    document.getElementById("invoice-no").value = bill.invoice_no || "";
    setDateField("purchase-date", "purchase-date-picker", bill.purchase_date || todayIsoDate());
    const paid = bill.paid == null || bill.paid === "" ? "" : String(bill.paid);
    document.getElementById("purchase-paid").value = paid;
    purchaseRows = (bill.products || []).length
      ? bill.products.map((row) => ({
          name: row.name || "",
          qty: row.qty,
          unit: row.unit || "Piece",
          price: row.price,
          product_id: row.product_id || null,
          color: "",
          color_hex: "#ffffff",
          hsn: "",
        }))
      : [emptyRow()];
    setPurchaseEditMode(true);
    renderPurchase();
    window.scrollTo({ top: 0, behavior: "smooth" });
  }

  function renderPurchase() {
    document.getElementById("purchase-rows").innerHTML = purchaseRows.map((row, index) => `
      <div class="purchase-line mb-3">
        <div class="relative">
          <span class="line-label">Product</span>
          <input data-index="${index}" data-field="name" value="${row.name ?? ""}" placeholder="Product Name" autocomplete="off" class="w-full rounded-xl border border-gray-300 bg-white p-3 text-gray-900">
          <div class="absolute left-0 right-0 z-50 mt-1 hidden max-h-72 overflow-y-auto rounded-lg border bg-white text-gray-900" data-suggest="${index}"></div>
        </div>
        <div>
          <span class="line-label">Qty</span>
          <input data-index="${index}" data-field="qty" type="number" value="${row.qty ?? ""}" placeholder="Qty" class="w-full rounded-xl border border-gray-300 bg-white p-3 text-gray-900">
        </div>
        <div>
          <span class="line-label">Purchase Price</span>
          <input data-index="${index}" data-field="price" type="number" value="${row.price ?? ""}" placeholder="Purchase Price" class="w-full rounded-xl border border-gray-300 bg-white p-3 text-gray-900">
        </div>
        <div>
          <span class="line-label">Total</span>
          <div class="flex items-center rounded-xl border border-white/20 bg-white/10 p-3 font-bold text-green-400" data-row-total="${index}">₹${formatMoney(rowTotal(row))}</div>
        </div>
        <button type="button" data-delete="${index}" class="rounded-xl bg-red-500 p-3 text-white hover:bg-red-600">🗑 Remove</button>
      </div>
    `).join("");
    document.getElementById("purchase-total").textContent = formatMoney(
      purchaseRows.reduce((sum, row) => sum + rowTotal(row), 0)
    );
  }

  function updatePurchaseTotals() {
    document.querySelectorAll("#purchase-rows [data-row-total]").forEach((el) => {
      const index = Number(el.dataset.rowTotal);
      el.textContent = "₹" + formatMoney(rowTotal(purchaseRows[index] || emptyRow()));
    });
    document.getElementById("purchase-total").textContent = formatMoney(
      purchaseRows.reduce((sum, row) => sum + rowTotal(row), 0)
    );
  }

  document.getElementById("btn-add-row").addEventListener("click", () => {
    purchaseRows.push(emptyRow());
    renderPurchase();
  });

  document.getElementById("purchase-rows").addEventListener("input", (e) => {
    const field = e.target.dataset.field;
    const index = Number(e.target.dataset.index);
    if (!field || Number.isNaN(index)) return;
    purchaseRows[index][field] = e.target.value;
    if (field === "name") {
      const box = document.querySelector(`[data-suggest="${index}"]`);
      const search = e.target.value.trim().toLowerCase();
      const matches = catalog.filter((p) => (p.product_name || "").toLowerCase().includes(search)).slice(0, 80);
      if (!search || !matches.length) {
        box.classList.add("hidden");
      } else {
        box.classList.remove("hidden");
        box.innerHTML = matches.map((p) => `
          <div class="cursor-pointer border-b p-3 hover:bg-blue-100" data-pick="${p.id}" data-index="${index}">
            ${escapeHtml(p.product_name)} — ₹${formatMoney(p.purchase_price)}
          </div>
        `).join("");
        setSuggestActive(box, 0);
      }
    }
    updatePurchaseTotals();
  });

  document.getElementById("purchase-rows").addEventListener("keydown", (e) => {
    const field = e.target.dataset.field;
    const index = Number(e.target.dataset.index);
    if (!field || Number.isNaN(index)) return;

    if ((e.key === "Enter" || (e.key === "Tab" && !e.shiftKey)) && field === "price") {
      e.preventDefault();
      if (index >= purchaseRows.length - 1) {
        purchaseRows.push(emptyRow());
        renderPurchase();
      }
      focusRowField("purchase-rows", index + 1, "name");
    }
  });

  document.getElementById("purchase-rows").addEventListener("click", (e) => {
    const del = e.target.closest("[data-delete]");
    if (del) {
      purchaseRows.splice(Number(del.dataset.delete), 1);
      if (!purchaseRows.length) purchaseRows = [emptyRow()];
      renderPurchase();
      return;
    }
    const pick = e.target.closest("[data-pick]");
    if (pick) {
      const product = catalog.find((p) => String(p.id) === String(pick.dataset.pick));
      const index = Number(pick.dataset.index);
      if (product) {
        purchaseRows[index].name = product.product_name;
        purchaseRows[index].price = String(product.purchase_price);
        purchaseRows[index].unit = product.unit;
        purchaseRows[index].product_id = product.id;
        renderPurchase();
        focusRowField("purchase-rows", index, "qty");
      }
    }
  });

  document.getElementById("btn-save-purchase").addEventListener("click", async () => {
    try {
      const payload = {
        supplier_name: document.getElementById("supplier-name").value,
        invoice_no: document.getElementById("invoice-no").value,
        purchase_date: parseToIsoDate(document.getElementById("purchase-date").value),
        paid: document.getElementById("purchase-paid").value,
        products: purchaseRows,
      };
      if (editingPurchaseId) payload.id = editingPurchaseId;
      const result = await api("/api/purchases.php" + (editingPurchaseId ? "?id=" + editingPurchaseId : ""), {
        method: editingPurchaseId ? "PUT" : "POST",
        body: JSON.stringify(payload),
      });
      alert(editingPurchaseId ? "✅ Bill update ho gaya. Stock adjust ho gaya." : "✅ Purchase Saved. Stock updated.");
      resetPurchaseForm();
      loadPurchaseHistory();
      if (result.id) window.open(appUrl("purchase-bill.php?id=" + result.id), "_blank");
    } catch (err) {
      alert(err.message);
    }
  });

  document.getElementById("btn-cancel-edit").addEventListener("click", () => {
    resetPurchaseForm();
  });

  api("/api/products.php").then((data) => { catalog = data.products || []; }).catch(() => {});
  renderPurchase();

  async function loadPurchaseHistory() {
    const data = await api("/api/purchases.php");
    document.getElementById("purchase-history").innerHTML = (data.purchases || []).map((row) => {
      const paid = Number(row.paid || 0);
      const total = Number(row.total || 0);
      const due = Math.max(0, total - paid);
      return `
        <tr class="border-t border-white/10">
          <td class="p-3">${row.purchase_date ? invoiceDateLabel(row.purchase_date) : ""}</td>
          <td class="p-3">${escapeHtml(row.supplier_name || "")}</td>
          <td class="p-3">${escapeHtml(row.invoice_no || ("#" + row.id))}</td>
          <td class="p-3 text-right">₹${formatMoney(total)}</td>
          <td class="p-3 text-right">₹${formatMoney(paid)}</td>
          <td class="p-3 text-right">₹${formatMoney(due)}</td>
          <td class="p-3">
            <div class="flex flex-wrap gap-2">
              <a class="rounded bg-blue-600 px-3 py-1 text-white" href="${appUrl("purchase-bill.php?id=" + row.id)}" target="_blank">👁 Bill</a>
              <button type="button" data-edit-purchase="${row.id}" class="rounded bg-amber-500 px-3 py-1 text-white">✏️ Edit</button>
            </div>
          </td>
        </tr>
      `;
    }).join("") || `<tr><td class="p-4" colspan="7">Abhi koi supplier bill nahi.</td></tr>`;
  }
  bindDateField("purchase-date", "purchase-date-picker");
  setDateField("purchase-date", "purchase-date-picker", todayIsoDate());
  loadPurchaseHistory().catch(() => {});

  document.getElementById("purchase-history").addEventListener("click", (e) => {
    const btn = e.target.closest("[data-edit-purchase]");
    if (!btn) return;
    loadPurchaseForEdit(btn.dataset.editPurchase).catch((err) => alert(err.message));
  });

  const editParam = new URLSearchParams(window.location.search).get("edit");
  if (editParam) {
    loadPurchaseForEdit(editParam).catch((err) => alert(err.message));
  }
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>

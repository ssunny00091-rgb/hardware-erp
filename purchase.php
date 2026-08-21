<?php

declare(strict_types=1);

$pageTitle = 'Purchase Entry';
$activeNav = 'purchase';
require __DIR__ . '/includes/header.php';
?>

<h1 class="mb-8 text-4xl font-bold">🛒 Purchase Entry</h1>

<div class="mb-8 rounded-2xl border border-white/20 bg-white/10 p-6 backdrop-blur-xl">
  <h2 class="mb-6 text-2xl font-bold">🛒 Purchase Details</h2>
  <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
    <input type="text" id="supplier-name" placeholder="Supplier Name" class="rounded-xl border border-white/20 bg-white/10 p-3 text-white placeholder-gray-300">
    <input type="text" id="invoice-no" placeholder="Invoice Number" class="rounded-xl border border-white/20 bg-white/10 p-3 text-white placeholder-gray-300">
    <input type="date" id="purchase-date" class="rounded-xl border border-white/20 bg-white/10 p-3 text-white" value="<?= htmlspecialchars(date('Y-m-d'), ENT_QUOTES, 'UTF-8') ?>">
  </div>
</div>

<div class="mt-8">
  <div class="mb-4 grid grid-cols-5 gap-3 rounded-xl bg-white/10 p-4">
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
  <div class="flex gap-4">
    <button type="button" id="btn-add-row" class="flex-1 rounded-xl bg-blue-600 py-3 text-lg font-semibold hover:bg-blue-500">➕ Add Product</button>
    <button type="button" id="btn-save-purchase" class="flex-1 rounded-xl bg-green-600 py-3 text-lg font-semibold hover:bg-green-500">💾 Save Purchase</button>
  </div>
</div>

<script>
  let purchaseRows = [emptyRow()];
  let catalog = [];

  function renderPurchase() {
    document.getElementById("purchase-rows").innerHTML = purchaseRows.map((row, index) => `
      <div class="mb-3 grid grid-cols-5 gap-3">
        <div class="relative">
          <input data-index="${index}" data-field="name" value="${row.name ?? ""}" placeholder="Product Name" class="rounded-xl border border-white/20 bg-white/10 p-3 text-white placeholder-gray-300 w-full">
          <div class="absolute left-0 right-0 z-50 mt-1 hidden max-h-60 overflow-y-auto rounded-lg border bg-black" data-suggest="${index}"></div>
        </div>
        <input data-index="${index}" data-field="qty" type="number" value="${row.qty ?? ""}" placeholder="Qty" class="rounded-xl border border-white/20 bg-white/10 p-3 text-white">
        <input data-index="${index}" data-field="price" type="number" value="${row.price ?? ""}" placeholder="Purchase Price" class="rounded-xl border border-white/20 bg-white/10 p-3 text-white">
        <div class="flex items-center justify-center rounded-xl border border-white/20 bg-white/10 font-bold text-green-400">₹${formatMoney(rowTotal(row))}</div>
        <button type="button" data-delete="${index}" class="rounded-xl bg-red-500 p-3 text-white hover:bg-red-600">🗑</button>
      </div>
    `).join("");
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
      const matches = catalog.filter((p) => (p.product_name || "").toLowerCase().includes(search)).slice(0, 8);
      if (!search || !matches.length) {
        box.classList.add("hidden");
      } else {
        box.classList.remove("hidden");
        box.innerHTML = matches.map((p) => `
          <div class="cursor-pointer border-b p-3 hover:bg-blue-600" data-pick="${p.id}" data-index="${index}">
            ${p.product_name} — ₹${formatMoney(p.purchase_price)}
          </div>
        `).join("");
      }
    } else {
      renderPurchase();
    }
  });

  document.getElementById("purchase-rows").addEventListener("keydown", (e) => {
    if (e.key === "Enter" && e.target.dataset.field === "price") {
      e.preventDefault();
      purchaseRows.push(emptyRow());
      renderPurchase();
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
      }
    }
  });

  document.getElementById("btn-save-purchase").addEventListener("click", async () => {
    try {
      await api("/api/purchases.php", {
        method: "POST",
        body: JSON.stringify({
          supplier_name: document.getElementById("supplier-name").value,
          invoice_no: document.getElementById("invoice-no").value,
          purchase_date: document.getElementById("purchase-date").value,
          products: purchaseRows,
        }),
      });
      alert("✅ Purchase Saved. Stock updated.");
      purchaseRows = [emptyRow()];
      renderPurchase();
    } catch (err) {
      alert(err.message);
    }
  });

  api("/api/products.php").then((data) => { catalog = data.products || []; }).catch(() => {});
  renderPurchase();
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>

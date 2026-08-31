<?php

declare(strict_types=1);

$pageTitle = 'Product Master';
$activeNav = 'products';
require __DIR__ . '/includes/header.php';
?>

<div class="mb-6 flex flex-col gap-3 sm:mb-8 sm:flex-row sm:items-center sm:justify-between">
  <div>
    <h1 class="text-2xl font-bold sm:text-4xl">📦 Product Master</h1>
    <p class="mt-1 text-sm text-neutral-400"><span id="product-count">0</span> products</p>
  </div>
  <button type="button" id="btn-add-product" class="w-full rounded-xl bg-green-600 px-5 py-3 font-semibold hover:bg-green-500 sm:w-auto">➕ Add Product</button>
</div>

<div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center">
  <div class="relative flex-1">
    <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">🔍</span>
    <input type="text" id="product-search" placeholder="Search by name or brand..." class="w-full rounded-lg border border-gray-300 bg-white py-3 pl-10 pr-4 text-gray-900">
  </div>
  <select id="brand-filter" class="rounded-lg border border-gray-300 bg-white p-3 text-gray-900 sm:w-48">
    <option value="">All Brands</option>
  </select>
  <select id="stock-filter" class="rounded-lg border border-gray-300 bg-white p-3 text-gray-900 sm:w-44">
    <option value="">All Stock</option>
    <option value="in">In Stock (>5)</option>
    <option value="low">Low (1-5)</option>
    <option value="out">Out (0)</option>
  </select>
</div>

<div class="table-scroll overflow-hidden rounded-2xl border border-white/20 bg-white/10 backdrop-blur-xl">
  <table class="w-full">
    <thead class="bg-white/10">
      <tr>
        <th class="p-4 text-left">Product</th>
        <th class="p-4 text-left">Brand</th>
        <th class="p-4 text-left">Category</th>
        <th class="p-4 text-center">Stock</th>
        <th class="p-4 text-right">Purchase ₹</th>
        <th class="p-4 text-right">Selling ₹</th>
        <th class="p-4 text-center">Unit</th>
        <th class="p-4 text-center">Action</th>
      </tr>
    </thead>
    <tbody id="product-table-body">
      <tr><td colspan="8" class="p-8 text-center">Loading...</td></tr>
    </tbody>
  </table>
</div>

<div id="product-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/60">
  <div class="modal-sheet w-full max-w-2xl overflow-y-auto rounded-none bg-slate-900 p-4 shadow-2xl sm:rounded-2xl sm:p-8">
    <div class="mb-6 flex items-center justify-between">
      <h2 id="product-modal-title" class="text-2xl font-bold sm:text-3xl">📦 Add Product</h2>
      <button type="button" class="close-modal rounded-lg bg-red-500 px-4 py-2">✕</button>
    </div>
    <form id="product-form" class="grid grid-cols-1 gap-4 sm:grid-cols-2">
      <input type="hidden" name="id" id="product-id">
      <div class="sm:col-span-2">
        <label class="mb-1 block text-sm font-medium text-neutral-300">Product Name *</label>
        <input name="product_name" id="product_name" placeholder="e.g. Asian Paint Ace" class="w-full rounded-lg border border-gray-300 bg-white p-3 text-gray-900" required>
      </div>
      <div>
        <label class="mb-1 block text-sm font-medium text-neutral-300">Brand</label>
        <input name="brand" id="brand" list="brand-list" placeholder="e.g. Asian Paints" class="w-full rounded-lg border border-gray-300 bg-white p-3 text-gray-900">
        <datalist id="brand-list"></datalist>
      </div>
      <div>
        <label class="mb-1 block text-sm font-medium text-neutral-300">Category</label>
        <input name="category" id="category" list="category-list" placeholder="e.g. Emulsion, Primer" class="w-full rounded-lg border border-gray-300 bg-white p-3 text-gray-900">
        <datalist id="category-list"></datalist>
      </div>
      <div>
        <label class="mb-1 block text-sm font-medium text-neutral-300">Unit *</label>
        <select name="unit" id="unit" class="w-full rounded-lg border border-gray-300 bg-white p-3 text-gray-900"></select>
      </div>
      <div>
        <label class="mb-1 block text-sm font-medium text-neutral-300">HSN Code</label>
        <input name="hsn_code" id="hsn_code" placeholder="e.g. 3209" class="w-full rounded-lg border border-gray-300 bg-white p-3 text-gray-900">
      </div>
      <div>
        <label class="mb-1 block text-sm font-medium text-neutral-300">Purchase Price ₹</label>
        <input type="number" step="0.01" name="purchase_price" id="purchase_price" placeholder="0.00" class="w-full rounded-lg border border-gray-300 bg-white p-3 text-gray-900">
      </div>
      <div>
        <label class="mb-1 block text-sm font-medium text-neutral-300">Selling Price ₹ *</label>
        <input type="number" step="0.01" name="selling_price" id="selling_price" placeholder="0.00" class="w-full rounded-lg border border-gray-300 bg-white p-3 text-gray-900" required>
      </div>
      <div>
        <label class="mb-1 block text-sm font-medium text-neutral-300">Opening Stock *</label>
        <input type="number" step="0.01" name="stock" id="stock" placeholder="0" class="w-full rounded-lg border border-gray-300 bg-white p-3 text-gray-900" required>
      </div>
      <div>
        <label class="mb-1 block text-sm font-medium text-neutral-300">GST %</label>
        <input type="number" step="0.01" name="gst_percent" id="gst_percent" placeholder="18" value="18" class="w-full rounded-lg border border-gray-300 bg-white p-3 text-gray-900">
      </div>
      <div class="mt-2 flex gap-3 sm:col-span-2">
        <button type="button" class="close-modal flex-1 rounded-xl bg-gray-600 py-3 font-semibold">Cancel</button>
        <button type="submit" class="flex-1 rounded-xl bg-green-600 py-3 font-semibold">💾 Save Product</button>
      </div>
    </form>
  </div>
</div>

<script>
  document.getElementById("unit").innerHTML = unitOptions("Piece");

  function stockBadge(stock) {
    const value = Number(stock);
    if (value > 20) return '<span class="inline-flex items-center gap-1 rounded-full bg-green-600 px-2 py-0.5 text-xs font-semibold">🟢 ' + value + '</span>';
    if (value > 5) return '<span class="inline-flex items-center gap-1 rounded-full bg-yellow-500 px-2 py-0.5 text-xs font-semibold">🟡 ' + value + '</span>';
    if (value > 0) return '<span class="inline-flex items-center gap-1 rounded-full bg-orange-500 px-2 py-0.5 text-xs font-semibold">🟠 ' + value + '</span>';
    return '<span class="inline-flex items-center gap-1 rounded-full bg-red-600 px-2 py-0.5 text-xs font-semibold">🔴 0</span>';
  }

  let productCache = [];
  let brandSet = [];
  let categorySet = [];

  function populateFilters() {
    brandSet = [...new Set(productCache.map(p => (p.brand || "").trim()).filter(Boolean))].sort();
    categorySet = [...new Set(productCache.map(p => (p.category || "").trim()).filter(Boolean))].sort();

    const brandDl = document.getElementById("brand-list");
    if (brandDl) brandDl.innerHTML = brandSet.map(b => '<option value="' + escapeHtml(b) + '">').join("");

    const catDl = document.getElementById("category-list");
    if (catDl) catDl.innerHTML = categorySet.map(c => '<option value="' + escapeHtml(c) + '">').join("");

    const brandFilter = document.getElementById("brand-filter");
    const curBrand = brandFilter.value;
    brandFilter.innerHTML = '<option value="">All Brands</option>' + brandSet.map(b => '<option value="' + escapeHtml(b) + '">' + escapeHtml(b) + '</option>').join("");
    brandFilter.value = curBrand;
  }

  function filteredProducts() {
    const q = (document.getElementById("product-search").value || "").trim().toLowerCase();
    const brand = document.getElementById("brand-filter").value;
    const stockMode = document.getElementById("stock-filter").value;
    return productCache.filter(p => {
      if (q && !(p.product_name || "").toLowerCase().includes(q) && !(p.brand || "").toLowerCase().includes(q)) return false;
      if (brand && (p.brand || "") !== brand) return false;
      const s = Number(p.stock || 0);
      if (stockMode === "in" && s <= 5) return false;
      if (stockMode === "low" && (s <= 0 || s > 5)) return false;
      if (stockMode === "out" && s > 0) return false;
      return true;
    });
  }

  async function loadProducts() {
    const data = await api("/api/products.php");
    productCache = data.products || [];
    populateFilters();
    renderTable();
  }

  function renderTable() {
    const rows = filteredProducts();
    const body = document.getElementById("product-table-body");
    document.getElementById("product-count").textContent = rows.length;
    if (!rows.length) {
      body.innerHTML = '<tr><td colspan="8" class="p-8 text-center text-neutral-300">No products found.</td></tr>';
      return;
    }
    body.innerHTML = rows.map((p) => `
      <tr class="border-t border-white/10 hover:bg-white/5">
        <td class="p-3 font-medium">${escapeHtml(p.product_name)}</td>
        <td class="p-3">${escapeHtml(p.brand || "—")}</td>
        <td class="p-3 text-sm text-neutral-400">${escapeHtml(p.category || "—")}</td>
        <td class="p-3 text-center">${stockBadge(p.stock)}</td>
        <td class="p-3 text-right text-sm">${p.purchase_price ? "₹" + formatMoney(p.purchase_price) : "—"}</td>
        <td class="p-3 text-right font-semibold">₹${formatMoney(p.selling_price)}</td>
        <td class="p-3 text-center text-sm">${escapeHtml(p.unit || "Piece")}</td>
        <td class="p-3 text-center">
          <button type="button" class="mr-1 rounded-lg bg-yellow-500 px-3 py-1.5 text-sm" data-edit="${p.id}">✏️</button>
          <button type="button" class="rounded-lg bg-red-500 px-3 py-1.5 text-sm" data-delete="${p.id}">🗑</button>
        </td>
      </tr>
    `).join("");
  }

  function openModal(product) {
    document.getElementById("product-modal-title").textContent = product ? "✏️ Edit Product" : "📦 Add Product";
    document.getElementById("product-id").value = product?.id || "";
    document.getElementById("product_name").value = product?.product_name || "";
    document.getElementById("brand").value = product?.brand || "";
    document.getElementById("category").value = product?.category || "";
    document.getElementById("unit").innerHTML = unitOptions(product?.unit || "Piece");
    document.getElementById("purchase_price").value = product?.purchase_price ?? "";
    document.getElementById("selling_price").value = product?.selling_price ?? "";
    document.getElementById("stock").value = product?.stock ?? "";
    document.getElementById("gst_percent").value = product?.gst_percent ?? 18;
    document.getElementById("hsn_code").value = product?.hsn_code || "";
    document.getElementById("product-modal").classList.remove("hidden");
    document.getElementById("product-modal").classList.add("flex");
  }

  function closeModal() {
    document.getElementById("product-modal").classList.add("hidden");
    document.getElementById("product-modal").classList.remove("flex");
  }

  document.getElementById("btn-add-product").addEventListener("click", () => openModal(null));
  document.querySelectorAll(".close-modal").forEach((btn) => btn.addEventListener("click", closeModal));

  document.getElementById("product-table-body").addEventListener("click", async (e) => {
    const edit = e.target.closest("[data-edit]");
    if (edit) {
      const product = productCache.find((p) => String(p.id) === String(edit.dataset.edit));
      openModal(product);
      return;
    }
    const del = e.target.closest("[data-delete]");
    if (del) {
      const product = productCache.find((p) => String(p.id) === String(del.dataset.delete));
      if (!confirm("Delete \"" + (product?.product_name || "") + "\"? This cannot be undone.")) return;
      await api("/api/product_save.php?id=" + del.dataset.delete, { method: "DELETE" });
      alert("✅ Product Deleted");
      loadProducts();
    }
  });

  document.getElementById("product-form").addEventListener("submit", async (e) => {
    e.preventDefault();
    const payload = Object.fromEntries(new FormData(e.target).entries());
    payload.purchase_price = Number(payload.purchase_price || 0);
    payload.selling_price = Number(payload.selling_price || 0);
    payload.stock = Number(payload.stock || 0);
    payload.gst_percent = Number(payload.gst_percent || 0);
    const isEdit = Boolean(payload.id);

    if (!payload.product_name.trim()) { alert("Product name zaroori hai!"); return; }
    if (payload.selling_price <= 0) { alert("Selling price 0 se zyada hona chahiye!"); return; }

    await api("/api/product_save.php", {
      method: isEdit ? "PUT" : "POST",
      body: JSON.stringify(payload),
    });
    alert(isEdit ? "✅ Product Updated" : "✅ Product Added Successfully");
    closeModal();
    loadProducts();
  });

  document.getElementById("product-search").addEventListener("input", renderTable);
  document.getElementById("brand-filter").addEventListener("change", renderTable);
  document.getElementById("stock-filter").addEventListener("change", renderTable);

  loadProducts().catch((err) => {
    document.getElementById("product-table-body").innerHTML =
      '<tr><td colspan="8" class="p-8 text-center text-red-300">' + err.message + '</td></tr>';
  });
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>

<?php

declare(strict_types=1);

$pageTitle = 'Product Master';
$activeNav = 'products';
require __DIR__ . '/includes/header.php';
?>

<div class="mb-8 flex items-center justify-between">
  <h1 class="text-4xl font-bold">📦 Product Master</h1>
  <button type="button" id="btn-add-product" class="rounded-xl bg-green-600 px-5 py-3 font-semibold hover:bg-green-500">➕ Add Product</button>
</div>

<div class="overflow-hidden rounded-2xl border border-white/20 bg-white/10 backdrop-blur-xl">
  <table class="w-full">
    <thead class="bg-white/10">
      <tr>
        <th class="p-4 text-left">Product</th>
        <th class="p-4 text-left">Brand</th>
        <th class="p-4 text-center">Stock</th>
        <th class="p-4 text-right">Price</th>
        <th class="p-4 text-center">Action</th>
      </tr>
    </thead>
    <tbody id="product-table-body">
      <tr><td colspan="5" class="p-8 text-center">Loading...</td></tr>
    </tbody>
  </table>
</div>

<div id="product-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/60">
  <div class="w-full max-w-2xl rounded-2xl bg-slate-900 p-8 shadow-2xl">
    <div class="mb-6 flex items-center justify-between">
      <h2 id="product-modal-title" class="text-3xl font-bold">📦 Add Product</h2>
      <button type="button" class="close-modal rounded-lg bg-red-500 px-4 py-2">✕</button>
    </div>
    <form id="product-form" class="grid grid-cols-2 gap-4">
      <input type="hidden" name="id" id="product-id">
      <input name="product_name" id="product_name" placeholder="Product Name" class="rounded-lg border p-3" required>
      <input name="brand" id="brand" placeholder="Brand" class="rounded-lg border p-3">
      <input name="category" id="category" placeholder="Category" class="rounded-lg border p-3">
      <select name="unit" id="unit" class="rounded-lg border p-3"></select>
      <input type="number" step="0.01" name="purchase_price" id="purchase_price" placeholder="Purchase Price" class="rounded-lg border p-3">
      <input type="number" step="0.01" name="selling_price" id="selling_price" placeholder="Selling Price" class="rounded-lg border p-3">
      <input type="number" step="0.01" name="stock" id="stock" placeholder="Opening Stock" class="rounded-lg border p-3">
      <input type="number" step="0.01" name="gst_percent" id="gst_percent" placeholder="GST %" value="18" class="rounded-lg border p-3">
      <input name="hsn_code" id="hsn_code" placeholder="HSN Code" class="col-span-2 rounded-lg border p-3">
      <div class="col-span-2 mt-2 flex gap-3">
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
    if (value > 20) return `<span class="rounded-full bg-green-600 px-3 py-1 text-sm font-semibold">🟢 ${value}</span>`;
    if (value > 5) return `<span class="rounded-full bg-yellow-500 px-3 py-1 text-sm font-semibold">🟡 ${value}</span>`;
    return `<span class="rounded-full bg-red-600 px-3 py-1 text-sm font-semibold">🔴 LOW (${value})</span>`;
  }

  let productCache = [];

  async function loadProducts() {
    const data = await api("/api/products.php");
    productCache = data.products || [];
    const rows = productCache;
    const body = document.getElementById("product-table-body");
    if (!rows.length) {
      body.innerHTML = `<tr><td colspan="5" class="p-8 text-center text-neutral-300">No products yet. Click "Add Product" to create one.</td></tr>`;
      return;
    }
    body.innerHTML = rows.map((p) => `
      <tr class="border-t border-white/10 hover:bg-white/5">
        <td class="p-4">${p.product_name}</td>
        <td class="p-4">${p.brand || ""}</td>
        <td class="p-4 text-center">${stockBadge(p.stock)}</td>
        <td class="p-4 text-right">₹${formatMoney(p.selling_price)}</td>
        <td class="p-4 text-center">
          <button type="button" class="mr-2 rounded-lg bg-yellow-500 px-3 py-2" data-edit="${p.id}">✏️</button>
          <button type="button" class="rounded-lg bg-red-500 px-3 py-2" data-delete="${p.id}">🗑</button>
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
      if (!confirm("Are you sure you want to delete this product?")) return;
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
    await api("/api/product_save.php", {
      method: isEdit ? "PUT" : "POST",
      body: JSON.stringify(payload),
    });
    alert(isEdit ? "✅ Product Updated" : "✅ Product Added Successfully");
    closeModal();
    loadProducts();
  });

  loadProducts().catch((err) => {
    document.getElementById("product-table-body").innerHTML =
      `<tr><td colspan="5" class="p-8 text-center text-red-300">${err.message}</td></tr>`;
  });
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>

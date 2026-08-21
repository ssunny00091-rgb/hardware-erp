<?php

declare(strict_types=1);

$pageTitle = 'SATYANARAYAN HARDWARE STORES';
$activeNav = 'home';
require __DIR__ . '/includes/header.php';
?>

<h1 class="mb-6 text-4xl font-bold">🏪 SATYANARAYAN HARDWARE STORES</h1>

<div class="mb-6 flex flex-wrap gap-4 no-print">
  <button type="button" id="btn-new-sale" class="rounded-lg bg-green-600 px-5 py-3 text-white hover:bg-green-700">➕ New Sale</button>
  <button type="button" id="btn-sales-history" class="rounded-lg bg-indigo-600 px-5 py-3 text-white hover:bg-indigo-700">🧾 Sales History</button>
</div>

<div id="dashboard-cards" class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
  <article class="relative overflow-hidden rounded-2xl p-6 shadow-xl" style="background: linear-gradient(135deg, #2563eb 0%, #7c3aed 100%)">
    <h3 class="text-lg font-semibold">Today's Sales</h3>
    <p class="mt-3 text-4xl font-bold" data-stat="today_sales">₹0.00</p>
  </article>
  <article class="relative overflow-hidden rounded-2xl p-6 shadow-xl" style="background: linear-gradient(135deg, #f97316 0%, #ef4444 100%)">
    <h3 class="text-lg font-semibold">Today's Purchase</h3>
    <p class="mt-3 text-4xl font-bold" data-stat="today_purchase">₹0.00</p>
  </article>
  <article class="relative overflow-hidden rounded-2xl p-6 shadow-xl" style="background: linear-gradient(135deg, #059669 0%, #14b8a6 100%)">
    <h3 class="text-lg font-semibold">Cash in Hand</h3>
    <p class="mt-3 text-4xl font-bold" data-stat="cash_in_hand">₹0.00</p>
  </article>
  <article class="relative overflow-hidden rounded-2xl p-6 shadow-xl" style="background: linear-gradient(135deg, #9333ea 0%, #ec4899 100%)">
    <h3 class="text-lg font-semibold">Pending Payment</h3>
    <p class="mt-3 text-4xl font-bold" data-stat="pending_payment">₹0.00</p>
  </article>
</div>

<section id="sale-form" class="mt-8 hidden rounded-3xl border border-white/20 bg-white/10 p-8 shadow-2xl backdrop-blur-xl">
  <div class="mb-8 border-b border-white/10 pb-4">
    <h2 class="text-3xl font-bold">📝 New Sale</h2>
    <p class="mt-2 text-sm text-gray-300">Create a new invoice, add customer details and products.</p>
  </div>

  <input type="text" id="customer-name" placeholder="Customer Name" class="mb-4 w-full rounded-2xl border border-white/20 bg-white/10 px-4 py-3 text-white placeholder:text-gray-300 outline-none">
  <input type="text" id="customer-mobile" placeholder="Mobile Number" class="mb-4 w-full rounded-2xl border border-white/20 bg-white/10 px-4 py-3 text-white placeholder:text-gray-300 outline-none">
  <input type="text" id="customer-address" placeholder="Address" class="mb-4 w-full rounded-2xl border border-white/20 bg-white/10 px-4 py-3 text-white placeholder:text-gray-300 outline-none">
  <input type="text" id="customer-gst" placeholder="GST Number" class="mb-4 w-full rounded-2xl border border-white/20 bg-white/10 px-4 py-3 text-white placeholder:text-gray-300 outline-none">

  <div class="mt-8">
    <div class="mb-4 grid grid-cols-5 gap-3 rounded-2xl border border-white/10 bg-white/10 p-4 font-semibold">
      <div>📦 Product</div>
      <div>Qty</div>
      <div>Price</div>
      <div>Total</div>
      <div>Action</div>
    </div>
    <div id="product-rows"></div>
  </div>

  <div class="mt-6 text-right text-2xl font-bold text-green-400">
    Grand Total: ₹<span id="grand-total">0.00</span>
  </div>

  <div class="mt-8 flex justify-end gap-4">
    <button type="button" id="btn-preview" class="rounded-xl bg-green-600 px-6 py-3 font-semibold text-white hover:bg-green-500">💾 Save Sale</button>
  </div>
</section>

<div id="history-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/60">
  <div class="w-[95%] max-w-6xl rounded-xl bg-white p-6 text-black shadow-2xl">
    <div class="mb-5 flex items-center justify-between">
      <h2 class="text-3xl font-bold">🧾 Sales History</h2>
      <button type="button" id="btn-close-history" class="rounded-lg bg-red-600 px-5 py-2 text-white">✖ Close</button>
    </div>
    <div class="max-h-[70vh] overflow-auto">
      <table class="w-full border-collapse border">
        <thead>
          <tr class="bg-blue-600 text-white">
            <th class="border p-3">Invoice</th>
            <th class="border p-3">Customer</th>
            <th class="border p-3">Mobile</th>
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

<div id="preview-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/60 p-4">
  <div class="flex max-h-[95vh] w-full max-w-6xl flex-col overflow-hidden rounded-xl bg-white shadow-2xl">
    <div class="flex items-center justify-between border-b bg-white px-6 py-4 text-black">
      <h2 class="text-2xl font-bold text-blue-700">Invoice Preview</h2>
      <button type="button" id="btn-close-preview" class="rounded-lg bg-red-500 px-4 py-2 text-white">✕ Close</button>
    </div>
    <div class="flex-1 overflow-y-auto bg-gray-100 p-6" id="invoice-preview-body"></div>
    <div class="sticky bottom-0 flex flex-wrap justify-center gap-4 border-t bg-white p-4">
      <button type="button" id="btn-edit-sale" class="rounded-lg bg-gray-600 px-5 py-2 text-white">✏️ Edit</button>
      <button type="button" id="btn-print-invoice" class="rounded-lg bg-blue-600 px-5 py-2 text-white">🖨️ Print</button>
      <button type="button" id="btn-confirm-save" class="rounded-lg bg-purple-600 px-5 py-2 text-white">💾 Save Sale</button>
    </div>
  </div>
</div>

<script>
  let saleRows = [emptyRow()];
  let catalog = [];

  function renderRows() {
    const wrap = document.getElementById("product-rows");
    wrap.innerHTML = saleRows.map((row, index) => `
      <div class="mb-3 grid grid-cols-5 gap-3">
        <div class="relative">
          <input data-index="${index}" data-field="name" value="${row.name ?? ""}" placeholder="Search Product..." class="w-full rounded-lg border p-3">
          <div class="suggest hidden absolute left-0 right-0 z-50 mt-1 max-h-60 overflow-y-auto rounded-lg border bg-black shadow-xl" data-suggest="${index}"></div>
        </div>
        <div class="flex gap-2">
          <input data-index="${index}" data-field="qty" type="number" value="${row.qty ?? ""}" placeholder="Qty" class="w-20 rounded-lg border p-3">
          <select data-index="${index}" data-field="unit" class="rounded-lg border p-3">${unitOptions(row.unit || "Piece")}</select>
        </div>
        <input data-index="${index}" data-field="price" type="number" value="${row.price ?? ""}" placeholder="Price" class="rounded-lg border p-3">
        <div class="flex items-center font-semibold text-green-400">₹${formatMoney(rowTotal(row))}</div>
        <button type="button" data-delete="${index}" class="rounded-lg bg-red-500 px-3 py-2 text-white hover:bg-red-600">🗑️</button>
      </div>
    `).join("");
    document.getElementById("grand-total").textContent = formatMoney(
      saleRows.reduce((sum, row) => sum + rowTotal(row), 0)
    );
  }

  function validProducts() {
    return saleRows.filter((row) => row.name.trim() !== "" && Number(row.qty) > 0 && Number(row.price) > 0);
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
    const search = (value || "").trim().toLowerCase();
    if (!search) {
      box.classList.add("hidden");
      box.innerHTML = "";
      return;
    }
    const matches = catalog.filter((p) => (p.product_name || "").toLowerCase().includes(search)).slice(0, 8);
    if (!matches.length || matches.some((p) => p.product_name === value)) {
      box.classList.add("hidden");
      return;
    }
    box.classList.remove("hidden");
    box.innerHTML = matches.map((p) => `
      <div class="cursor-pointer border-b p-3 hover:bg-blue-600" data-pick="${p.id}" data-index="${index}">
        <div class="font-medium">${p.product_name}</div>
        <div class="text-sm text-gray-400">₹ ${formatMoney(p.selling_price)}</div>
      </div>
    `).join("");
  }

  function invoiceHtml(customer, products, grandTotal) {
    const rows = products.map((p, i) => `
      <tr>
        <td class="border p-2">${i + 1}</td>
        <td class="border p-2">${p.name}</td>
        <td class="border p-2">${p.qty}</td>
        <td class="border p-2 text-center">${p.unit}</td>
        <td class="border p-2 text-right">₹${p.price}</td>
        <td class="border p-2 text-right">₹${rowTotal(p)}</td>
      </tr>
    `).join("");
    return `
      <div class="print-area mx-auto max-w-4xl rounded-lg bg-white p-8 shadow-lg text-black">
        <div class="border-b-2 border-green-700 pb-4 text-center">
          <h1 class="text-3xl font-bold text-green-700">SATYANARAYAN HARDWARE STORES</h1>
          <p class="text-gray-700">Main Road, Jayanagar, PIN - 847226</p>
          <p class="text-gray-700">Second Branch - Near Anumandal Hospital, Jayanagar</p>
          <p class="text-gray-700">📞 9431875263 | 9831046765</p>
          <p class="text-gray-700">✉️ sunnynayak01@gmail.com</p>
          <p class="text-gray-700">GSTIN : 10ADTPN8807A1ZP</p>
        </div>
        <div class="mt-6 flex justify-between">
          <div>
            <p><strong>Date :</strong> ${new Date().toLocaleDateString("en-IN")}</p>
          </div>
          <div class="text-right">
            <p><strong>Customer :</strong> ${customer.name}</p>
            <p><strong>Mobile :</strong> ${customer.mobile}</p>
          </div>
        </div>
        <table class="mt-8 w-full border-collapse border">
          <thead>
            <tr class="bg-gray-200">
              <th class="border p-2">#</th>
              <th class="border p-2">Product</th>
              <th class="border p-2">Qty</th>
              <th class="border p-2">Unit</th>
              <th class="border p-2">Rate</th>
              <th class="border p-2">Amount</th>
            </tr>
          </thead>
          <tbody>${rows}</tbody>
        </table>
        <div class="mt-8 flex justify-end">
          <div class="w-72 border p-4">
            <div class="flex justify-between text-xl font-bold">
              <span>Grand Total</span>
              <span>₹${formatMoney(grandTotal)}</span>
            </div>
          </div>
        </div>
      </div>
    `;
  }

  document.getElementById("btn-new-sale").addEventListener("click", () => {
    saleRows = [emptyRow()];
    document.getElementById("customer-name").value = "";
    document.getElementById("customer-mobile").value = "";
    document.getElementById("customer-address").value = "";
    document.getElementById("customer-gst").value = "";
    document.getElementById("sale-form").classList.remove("hidden");
    renderRows();
  });

  document.getElementById("customer-mobile").addEventListener("blur", async (e) => {
    const mobile = e.target.value.trim();
    if (!mobile) return;
    const data = await api("/api/customer_lookup.php?mobile=" + encodeURIComponent(mobile));
    if (data.customer) {
      document.getElementById("customer-name").value = data.customer.name || "";
      document.getElementById("customer-address").value = data.customer.address || "";
      document.getElementById("customer-gst").value = data.customer.gst || "";
    }
  });

  document.getElementById("product-rows").addEventListener("input", (e) => {
    const field = e.target.dataset.field;
    const index = Number(e.target.dataset.index);
    if (!field || Number.isNaN(index)) return;
    saleRows[index][field] = e.target.value;
    if (field === "name") {
      const match = catalog.find((p) => p.product_name === e.target.value);
      if (match) {
        saleRows[index].price = String(match.selling_price);
        saleRows[index].unit = match.unit;
        saleRows[index].product_id = match.id;
      }
      showSuggestions(index, e.target.value);
    }
    if (field === "qty" || field === "price" || field === "unit") {
      renderRows();
    }
  });

  document.getElementById("product-rows").addEventListener("keydown", (e) => {
    if (e.key !== "Enter") return;
    const field = e.target.dataset.field;
    const index = Number(e.target.dataset.index);
    if (field === "price") {
      e.preventDefault();
      saleRows.push(emptyRow());
      renderRows();
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
        renderRows();
      }
    }
  });

  function currentCustomer() {
    return {
      name: document.getElementById("customer-name").value,
      mobile: document.getElementById("customer-mobile").value,
      address: document.getElementById("customer-address").value,
      gst: document.getElementById("customer-gst").value,
    };
  }

  document.getElementById("btn-preview").addEventListener("click", () => {
    const products = validProducts();
    const total = products.reduce((sum, row) => sum + rowTotal(row), 0);
    document.getElementById("invoice-preview-body").innerHTML = invoiceHtml(currentCustomer(), products, total);
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
  document.getElementById("btn-print-invoice").addEventListener("click", () => window.print());

  document.getElementById("btn-confirm-save").addEventListener("click", async () => {
    try {
      const customer = currentCustomer();
      const products = validProducts();
      const result = await api("/api/sales.php", {
        method: "POST",
        body: JSON.stringify({
          customer_name: customer.name,
          mobile: customer.mobile,
          address: customer.address,
          gst: customer.gst,
          products,
        }),
      });
      alert("✅ Sale Saved Successfully\\nInvoice: " + result.invoice_no);
      document.getElementById("preview-modal").classList.add("hidden");
      document.getElementById("preview-modal").classList.remove("flex");
      window.open("/invoice.php?id=" + result.id, "_blank");
      loadDashboard();
    } catch (err) {
      alert(err.message);
    }
  });

  document.getElementById("btn-sales-history").addEventListener("click", async () => {
    const data = await api("/api/sales.php");
    document.getElementById("sales-history-body").innerHTML = (data.sales || []).map((sale) => `
      <tr>
        <td class="border p-3">${sale.invoice_no}</td>
        <td class="border p-3">${sale.customer_name || ""}</td>
        <td class="border p-3">${sale.mobile || ""}</td>
        <td class="border p-3">₹${formatMoney(sale.total)}</td>
        <td class="border p-3">${new Date(sale.created_at).toLocaleDateString("en-IN")}</td>
        <td class="border p-3">
          <div class="flex justify-center gap-2">
            <a href="/invoice.php?id=${sale.id}" target="_blank" class="rounded bg-blue-600 px-3 py-1 text-white">👁</a>
            <button type="button" data-sale-delete="${sale.id}" class="rounded bg-red-600 px-3 py-1 text-white">🗑</button>
          </div>
        </td>
      </tr>
    `).join("");
    document.getElementById("history-modal").classList.remove("hidden");
    document.getElementById("history-modal").classList.add("flex");
  });

  document.getElementById("btn-close-history").addEventListener("click", () => {
    document.getElementById("history-modal").classList.add("hidden");
    document.getElementById("history-modal").classList.remove("flex");
  });

  document.getElementById("sales-history-body").addEventListener("click", async (e) => {
    const btn = e.target.closest("[data-sale-delete]");
    if (!btn) return;
    if (!confirm("Delete this sale?")) return;
    await api("/api/sales.php?id=" + btn.dataset.saleDelete, { method: "DELETE" });
    btn.closest("tr").remove();
    loadDashboard();
  });

  loadDashboard().catch((err) => alert("MySQL connect failed: " + err.message));
  loadCatalog().catch(() => {});
  renderRows();
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>

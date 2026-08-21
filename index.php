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

  <input type="text" id="customer-name" placeholder="Customer Name" class="mb-4 w-full rounded-2xl border border-gray-300 bg-white px-4 py-3 text-gray-900 outline-none">
  <input type="text" id="customer-mobile" placeholder="Mobile Number" class="mb-4 w-full rounded-2xl border border-gray-300 bg-white px-4 py-3 text-gray-900 outline-none">
  <input type="text" id="customer-address" placeholder="Address" class="mb-4 w-full rounded-2xl border border-gray-300 bg-white px-4 py-3 text-gray-900 outline-none">
  <input type="text" id="customer-gst" placeholder="GST Number" class="mb-4 w-full rounded-2xl border border-gray-300 bg-white px-4 py-3 text-gray-900 outline-none">

  <div class="mt-8">
    <div class="mb-4 grid grid-cols-6 gap-3 rounded-2xl border border-white/10 bg-white/10 p-4 font-semibold">
      <div>📦 Product</div>
      <div>🎨 Colour / Shade</div>
      <div>Qty</div>
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
        <div class="flex items-center justify-between border-b bg-white px-6 py-4 text-black print-hide">
      <h2 class="text-2xl font-bold text-blue-700">Invoice Preview</h2>
      <button type="button" id="btn-close-preview" class="rounded-lg bg-red-500 px-4 py-2 text-white">✕ Close</button>
    </div>
    <div class="flex-1 overflow-y-auto bg-white p-6 text-black" id="invoice-preview-body"></div>
    <div class="sticky bottom-0 flex flex-wrap justify-center gap-4 border-t bg-white p-4 print-hide">
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
      <div class="mb-3 grid grid-cols-6 gap-3">
        <div class="relative">
          <input data-index="${index}" data-field="name" value="${row.name ?? ""}" placeholder="Search Product..." class="w-full rounded-lg border border-gray-300 bg-white p-3 text-gray-900">
          <div class="suggest hidden absolute left-0 right-0 z-50 mt-1 max-h-60 overflow-y-auto rounded-lg border bg-white text-gray-900 shadow-xl" data-suggest="${index}"></div>
        </div>
        <div class="flex items-center gap-2">
          <input data-index="${index}" data-field="color_hex" type="color" value="${row.color_hex || "#ffffff"}" title="Colour" class="h-11 w-11 cursor-pointer rounded border border-gray-300 bg-white p-0">
          <input data-index="${index}" data-field="color" list="paint-shades" value="${row.color ?? ""}" placeholder="Shade / code" class="min-w-0 flex-1 rounded-lg border border-gray-300 bg-white p-3 text-gray-900">
        </div>
        <div class="flex gap-2">
          <input data-index="${index}" data-field="qty" type="number" value="${row.qty ?? ""}" placeholder="Qty" class="w-20 rounded-lg border border-gray-300 bg-white p-3 text-gray-900">
          <select data-index="${index}" data-field="unit" class="rounded-lg border border-gray-300 bg-white p-3 text-gray-900">${unitOptions(row.unit || "Piece")}</select>
        </div>
        <input data-index="${index}" data-field="price" type="number" value="${row.price ?? ""}" placeholder="Price" class="rounded-lg border border-gray-300 bg-white p-3 text-gray-900">
        <div class="flex items-center font-semibold text-green-400" data-row-total="${index}">₹${formatMoney(rowTotal(row))}</div>
        <button type="button" data-delete="${index}" class="rounded-lg bg-red-500 px-3 py-2 text-white hover:bg-red-600">🗑️</button>
      </div>
    `).join("");
    document.getElementById("grand-total").textContent = formatMoney(
      saleRows.reduce((sum, row) => sum + rowTotal(row), 0)
    );
  }

  function updateSaleTotals() {
    document.querySelectorAll("#product-rows [data-row-total]").forEach((el) => {
      const index = Number(el.dataset.rowTotal);
      el.textContent = "₹" + formatMoney(rowTotal(saleRows[index] || emptyRow()));
    });
    document.getElementById("grand-total").textContent = formatMoney(
      saleRows.reduce((sum, row) => sum + rowTotal(row), 0)
    );
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
        byIndex[index] = { name: "", color: "", color_hex: "#ffffff", qty: "", unit: "Piece", price: "", product_id: null };
      }
      byIndex[index][el.dataset.field] = el.value;
      if (saleRows[index] && saleRows[index].product_id) {
        byIndex[index].product_id = saleRows[index].product_id;
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
    const matches = catalog.filter((p) => (p.product_name || "").toLowerCase().includes(search.toLowerCase())).slice(0, 8);
    const exact = catalog.some((p) => (p.product_name || "").toLowerCase() === search.toLowerCase());
    let html = matches.map((p) => `
      <div class="cursor-pointer border-b p-3 hover:bg-blue-100" data-pick="${p.id}" data-index="${index}">
        <div class="font-medium">${p.product_name}</div>
        <div class="text-sm text-gray-500">₹ ${formatMoney(p.selling_price)}</div>
      </div>
    `).join("");
    if (!exact) {
      html += `
        <div class="cursor-pointer bg-green-50 p-3 font-semibold text-green-800 hover:bg-green-100" data-save-new="${index}">
          ➕ Save "${search}" as new product
        </div>`;
    }
    if (!html) {
      box.classList.add("hidden");
      return;
    }
    box.classList.remove("hidden");
    box.innerHTML = html;
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

  function invoiceSheetHtml(customer, products, grandTotal) {
    const rows = products.length
      ? products.map((p, i) => {
          const colorText = String(p.color || "").trim();
          const hex = String(p.color_hex || "").trim();
          const hasColor = colorText || (hex && hex.toLowerCase() !== "#ffffff");
          const colorCell = hasColor
            ? (hex ? "<span class=\"swatch\" style=\"background:" + hex + "\"></span>" : "") +
              (colorText || hex).replace(/</g, "&lt;")
            : "—";
          return (
            "<tr>" +
            "<td class=\"center\">" + (i + 1) + "</td>" +
            "<td>" + String(p.name || "").replace(/</g, "&lt;") + "</td>" +
            "<td>" + colorCell + "</td>" +
            "<td class=\"center\">" + p.qty + "</td>" +
            "<td class=\"center\">" + (p.unit || "Piece") + "</td>" +
            "<td class=\"num\">Rs. " + formatMoney(p.price) + "</td>" +
            "<td class=\"num\">Rs. " + formatMoney(rowTotal(p)) + "</td>" +
            "</tr>"
          );
        }).join("")
      : "<tr><td colspan=\"7\">No products</td></tr>";

    return `
      <article class="invoice">
        <header class="invoice-hero">
          <div class="badge">Tax Invoice</div>
          <h1>SATYANARAYAN HARDWARE STORES</h1>
          <p>Main Road, Jayanagar, PIN - 847226</p>
          <p>Second Branch - Near Anumandal Hospital, Jayanagar</p>
          <p>Phone: 9431875263, 9831046765 &nbsp;|&nbsp; GSTIN: 10ADTPN8807A1ZP</p>
        </header>
        <div class="invoice-body">
          <div class="meta-grid">
            <div class="meta-card">
              <h3>Bill To</h3>
              <p><strong>${(customer.name || "Walk-in Customer").replace(/</g, "&lt;")}</strong></p>
              <p>Mobile: ${(customer.mobile || "").replace(/</g, "&lt;")}</p>
            </div>
            <div class="meta-card">
              <h3>Invoice</h3>
              <p>Date: ${new Date().toLocaleDateString("en-IN")}</p>
            </div>
          </div>
          <table class="items">
            <thead>
              <tr>
                <th class="center">#</th>
                <th>Product</th>
                <th>Colour / Shade</th>
                <th class="center">Qty</th>
                <th class="center">Unit</th>
                <th class="num">Rate</th>
                <th class="num">Amount</th>
              </tr>
            </thead>
            <tbody>${rows}</tbody>
          </table>
          <div class="totals">
            <div class="totals-box">
              <span>Grand Total</span>
              <span>Rs. ${formatMoney(grandTotal)}</span>
            </div>
          </div>
          <p class="thanks">Thank you for your business. Visit again.</p>
        </div>
      </article>
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
      const match = catalog.find((p) => (p.product_name || "").toLowerCase() === e.target.value.trim().toLowerCase());
      if (match) {
        saleRows[index].price = String(match.selling_price);
        saleRows[index].unit = match.unit;
        saleRows[index].product_id = match.id;
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
    };
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
      window.open(appUrl("invoice.php?id=" + result.id), "_blank");
      loadDashboard();
      loadCatalog();
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
            <a href="${appUrl("invoice.php?id=" + sale.id)}" target="_blank" class="rounded bg-blue-600 px-3 py-1 text-white">👁</a>
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

  loadDashboard().catch((err) => {
    const go = confirm("MySQL connect nahi hua: " + err.message + "\n\nSetup wizard (install.php) kholun?");
    if (go) window.location.href = appUrl("install.php");
  });
  loadCatalog().catch(() => {});
  renderRows();
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>

<?php

declare(strict_types=1);

$pageTitle = 'General Expenses';
$activeNav = 'expenses';
require __DIR__ . '/includes/header.php';
?>

<div class="mb-6 flex flex-col gap-3 sm:mb-8 sm:flex-row sm:items-center sm:justify-between">
  <div>
    <h1 class="text-2xl font-bold sm:text-4xl">💸 General Expenses</h1>
    <p class="mt-1 text-sm text-neutral-400">Roj ke kharche yahan daalo aur track karo</p>
  </div>
  <div class="flex gap-3">
    <div class="rounded-xl bg-white/10 px-4 py-2 text-center">
      <div class="text-xs text-neutral-400">Aaj</div>
      <div class="text-xl font-bold text-red-400" id="stat-today">₹0</div>
    </div>
    <div class="rounded-xl bg-white/10 px-4 py-2 text-center">
      <div class="text-xs text-neutral-400">Is Mahine</div>
      <div class="text-xl font-bold text-orange-400" id="stat-month">₹0</div>
    </div>
  </div>
</div>

<section class="mb-8 rounded-2xl border border-white/20 bg-white/10 p-4 shadow-2xl backdrop-blur-xl sm:rounded-3xl sm:p-6">
  <h2 class="mb-4 text-lg font-bold">➕ Naya Kharcha Daalo</h2>
  <form id="expense-form" class="grid grid-cols-1 gap-3 sm:grid-cols-5">
    <input type="date" id="exp-date" class="rounded-lg border border-gray-300 bg-white p-2.5 text-gray-900">
    <select id="exp-category" class="rounded-lg border border-gray-300 bg-white p-2.5 text-gray-900">
      <option value="General">General</option>
      <option value="Rent">🏠 Rent</option>
      <option value="Salary">👤 Salary</option>
      <option value="Transport">🚗 Transport</option>
      <option value="Electricity">⚡ Electricity</option>
      <option value="Phone/Internet">📱 Phone/Internet</option>
      <option value="Office Supplies">📎 Office Supplies</option>
      <option value="Maintenance">🔧 Maintenance</option>
      <option value="Food/Tea">☕ Food/Tea</option>
      <option value="Labour">👷 Labour</option>
      <option value="Other">📋 Other</option>
    </select>
    <input type="text" id="exp-desc" placeholder="Kis liye kharcha?" class="rounded-lg border border-gray-300 bg-white p-2.5 text-gray-900">
    <input type="number" step="0.01" id="exp-amount" placeholder="Amount ₹" class="rounded-lg border border-gray-300 bg-white p-2.5 text-gray-900" required>
    <button type="submit" class="rounded-lg bg-green-600 px-4 py-2.5 font-semibold hover:bg-green-500">➕ Add</button>
  </form>
</section>

<section class="mb-8 rounded-2xl border border-white/20 bg-white/10 p-4 shadow-2xl backdrop-blur-xl sm:rounded-3xl sm:p-6">
  <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <h2 class="text-lg font-bold">📋 Kharche Ki List</h2>
    <div class="flex flex-wrap gap-2">
      <button type="button" data-exp-filter="today" class="expense-tab rounded-lg bg-white/20 px-3 py-1.5 text-sm font-semibold">Aaj</button>
      <button type="button" data-exp-filter="week" class="expense-tab rounded-lg bg-white/10 px-3 py-1.5 text-sm">Is Hafte</button>
      <button type="button" data-exp-filter="month" class="expense-tab rounded-lg bg-white/10 px-3 py-1.5 text-sm">Is Mahine</button>
      <button type="button" data-exp-filter="all" class="expense-tab rounded-lg bg-white/10 px-3 py-1.5 text-sm">Sab</button>
    </div>
  </div>

  <div class="mb-3 text-sm text-neutral-300">
    <span id="exp-summary">Loading...</span>
  </div>

  <div class="table-scroll overflow-hidden rounded-xl border border-white/10">
    <table class="w-full text-sm">
      <thead class="bg-white/10">
        <tr>
          <th class="p-3 text-left">Date</th>
          <th class="p-3 text-left">Category</th>
          <th class="p-3 text-left">Description</th>
          <th class="p-3 text-right">Amount</th>
          <th class="p-3 text-center">Mode</th>
          <th class="p-3 text-center">Action</th>
        </tr>
      </thead>
      <tbody id="expense-table-body">
        <tr><td colspan="6" class="p-6 text-center text-neutral-400">Loading...</td></tr>
      </tbody>
    </table>
  </div>
</section>

<script>
  let expFilter = "today";
  let expCache = [];

  const CAT_COLORS = {
    Rent: "bg-blue-600",
    Salary: "bg-purple-600",
    Transport: "bg-yellow-600",
    Electricity: "bg-orange-600",
    "Phone/Internet": "bg-cyan-600",
    "Office Supplies": "bg-pink-600",
    Maintenance: "bg-gray-600",
    "Food/Tea": "bg-green-600",
    Labour: "bg-indigo-600",
    General: "bg-white/20",
    Other: "bg-white/10",
  };

  function expDateRange(mode) {
    const now = new Date();
    const y = now.getFullYear();
    const m = String(now.getMonth() + 1).padStart(2, "0");
    const d = String(now.getDate()).padStart(2, "0");
    const iso = y + "-" + m + "-" + d;
    if (mode === "today") return { from: iso, to: iso };
    if (mode === "week") {
      const dow = now.getDay();
      const mon = new Date(now);
      mon.setDate(now.getDate() - ((dow + 6) % 7));
      const sun = new Date(mon);
      sun.setDate(mon.getDate() + 6);
      return {
        from: mon.getFullYear() + "-" + String(mon.getMonth() + 1).padStart(2, "0") + "-" + String(mon.getDate()).padStart(2, "0"),
        to: sun.getFullYear() + "-" + String(sun.getMonth() + 1).padStart(2, "0") + "-" + String(sun.getDate()).padStart(2, "0"),
      };
    }
    if (mode === "month") return { from: y + "-" + m + "-01", to: iso };
    return { from: "", to: "" };
  }

  function renderExpenses() {
    const body = document.getElementById("expense-table-body");
    const summary = document.getElementById("exp-summary");
    let total = 0;
    expCache.forEach((e) => (total += Number(e.amount || 0)));
    summary.textContent = expCache.length + " kharche | Total: ₹" + formatMoney(total);

    if (!expCache.length) {
      body.innerHTML = '<tr><td colspan="6" class="p-6 text-center text-neutral-400">Koi kharcha nahi hai is period me.</td></tr>';
      return;
    }
    body.innerHTML = expCache
      .map((e) => {
        const badge = CAT_COLORS[e.category] || "bg-white/10";
        return (
          '<tr class="border-t border-white/10 hover:bg-white/5">' +
          '<td class="p-2.5 whitespace-nowrap">' + escapeHtml(e.expense_date) + "</td>" +
          '<td class="p-2.5"><span class="inline-block rounded-full ' + badge + ' px-2.5 py-0.5 text-xs font-semibold">' + escapeHtml(e.category) + "</span></td>" +
          '<td class="p-2.5 text-neutral-300">' + escapeHtml(e.description || "—") + "</td>" +
          '<td class="p-2.5 text-right font-semibold text-red-400">₹' + formatMoney(e.amount) + "</td>" +
          '<td class="p-2.5 text-center text-xs">' + escapeHtml(e.payment_mode || "Cash") + "</td>" +
          '<td class="p-2.5 text-center"><button type="button" class="rounded bg-red-500/80 px-2 py-1 text-xs hover:bg-red-500" data-del-exp="' + e.id + '">🗑</button></td>' +
          "</tr>"
        );
      })
      .join("");
  }

  async function loadExpenses() {
    const range = expDateRange(expFilter);
    let url = "/api/expenses.php";
    if (expFilter === "all") {
      url += "?month=all";
    } else {
      url += "?from=" + range.from + "&to=" + range.to;
    }
    const data = await api(url);
    expCache = data.expenses || [];
    renderExpenses();

    const todayData = await api("/api/expenses.php?from=" + expDateRange("today").from + "&to=" + expDateRange("today").to);
    const monthData = await api("/api/expenses.php?from=" + expDateRange("month").from + "&to=" + expDateRange("month").to);
    document.getElementById("stat-today").textContent = "₹" + formatMoney(todayData.total || 0);
    document.getElementById("stat-month").textContent = "₹" + formatMoney(monthData.total || 0);
  }

  document.getElementById("exp-date").value = new Date().toISOString().slice(0, 10);

  document.querySelectorAll(".expense-tab").forEach((btn) => {
    btn.addEventListener("click", () => {
      document.querySelectorAll(".expense-tab").forEach((b) => {
        b.classList.remove("bg-white/20");
        b.classList.add("bg-white/10");
      });
      btn.classList.remove("bg-white/10");
      btn.classList.add("bg-white/20");
      expFilter = btn.dataset.expFilter;
      loadExpenses().catch(() => {});
    });
  });

  document.getElementById("expense-form").addEventListener("submit", async (e) => {
    e.preventDefault();
    const date = document.getElementById("exp-date").value;
    const category = document.getElementById("exp-category").value;
    const desc = document.getElementById("exp-desc").value.trim();
    const amount = Number(document.getElementById("exp-amount").value);
    if (!amount || amount <= 0) {
      alert("Amount daalo!");
      return;
    }
    await api("/api/expenses.php", {
      method: "POST",
      body: JSON.stringify({ expense_date: date, category: category, description: desc, amount: amount, payment_mode: "Cash" }),
    });
    document.getElementById("exp-desc").value = "";
    document.getElementById("exp-amount").value = "";
    loadExpenses().catch(() => {});
  });

  document.getElementById("expense-table-body").addEventListener("click", async (e) => {
    const btn = e.target.closest("[data-del-exp]");
    if (!btn) return;
    if (!confirm("Is kharche ko delete karna hai?")) return;
    await api("/api/expenses.php?id=" + btn.dataset.delExp, { method: "DELETE" });
    loadExpenses().catch(() => {});
  });

  loadExpenses().catch((err) => {
    document.getElementById("expense-table-body").innerHTML =
      '<tr><td colspan="6" class="p-6 text-center text-red-400">' + err.message + "</td></tr>";
  });
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>

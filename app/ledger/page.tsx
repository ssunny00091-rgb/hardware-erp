"use client";

import { useEffect, useState } from "react";

import PageHeader from "../components/shared/PageHeader";
import { useLedger, LedgerType } from "../hooks/useLedger";

type Party = {
  id: number;
  name: string;
};

export default function LedgerPage() {
  const [ledgerType, setLedgerType] =
    useState<LedgerType>("customer");

  const [parties, setParties] = useState<Party[]>([]);
  const [selectedName, setSelectedName] = useState("");

  const {
    entries,
    summary,
    loading,
    error,
    loadLedger,
  } = useLedger();

  useEffect(() => {
    async function loadParties() {
      try {
        let data: Party[] = [];

        if (ledgerType === "customer") {
          const response = await fetch("/api/ledger/customers");
          data = await response.json();
        }

        if (ledgerType === "supplier") {
          const response = await fetch("/api/ledger/suppliers");
          data = await response.json();
        }

        if (ledgerType === "painter") {
          const response = await fetch("/api/ledger/painters");
          data = await response.json();
        }

        setParties(data);
        setSelectedName("");
      } catch (error) {
        console.error("Party loading error:", error);
        setParties([]);
      }
    }

    loadParties();
  }, [ledgerType]);

  useEffect(() => {
    if (!selectedName) return;

    loadLedger(ledgerType, selectedName);
  }, [ledgerType, selectedName, loadLedger]);

  return (
    <main className="min-h-screen bg-slate-900 p-8 text-white">
      <div className="mx-auto max-w-7xl">

        <PageHeader
          title="Ledger"
          description="View customer, supplier and painter transactions"
        />

        {/* Filters */}
        <div className="mt-8 grid gap-4 md:grid-cols-2">

          <div>
            <label className="mb-2 block text-sm font-medium">
              Ledger Type
            </label>

            <select
              value={ledgerType}
              onChange={(e) =>
                setLedgerType(
                  e.target.value as LedgerType
                )
              }
              className="w-full rounded-xl border border-white/20 bg-white/10 p-3 text-white"
            >
              <option
                value="customer"
                className="bg-slate-900"
              >
                Customer
              </option>

              <option
                value="supplier"
                className="bg-slate-900"
              >
                Supplier
              </option>

              <option
                value="painter"
                className="bg-slate-900"
              >
                Painter
              </option>
            </select>
          </div>

          <div>
            <label className="mb-2 block text-sm font-medium">
              Select Name
            </label>

            <select
              value={selectedName}
              onChange={(e) =>
                setSelectedName(e.target.value)
              }
              className="w-full rounded-xl border border-white/20 bg-white/10 p-3 text-white"
            >
              <option
                value=""
                className="bg-slate-900"
              >
                Select {ledgerType}
              </option>

              {parties.map((party) => (
                <option
                  key={party.id}
                  value={party.name}
                  className="bg-slate-900"
                >
                  {party.name}
                </option>
              ))}
            </select>
          </div>

        </div>

        {/* Summary */}
        {selectedName && (
          <div className="mt-8 grid gap-4 md:grid-cols-4">

            <SummaryCard
              title="Opening Balance"
              value={summary.openingBalance}
            />

            <SummaryCard
              title="Total Debit"
              value={summary.totalDebit}
            />

            <SummaryCard
              title="Total Credit"
              value={summary.totalCredit}
            />

            <SummaryCard
              title="Balance"
              value={summary.balance}
            />

          </div>
        )}

        {/* Error */}
        {error && (
          <div className="mt-6 rounded-xl border border-red-500/30 bg-red-500/10 p-4 text-red-300">
            {error}
          </div>
        )}

        {/* Loading */}
        {loading && (
          <div className="mt-8 text-center text-slate-300">
            Loading Ledger...
          </div>
        )}

        {/* Ledger Table */}
        {!loading && selectedName && (
          <div className="mt-8 overflow-hidden rounded-2xl border border-white/10 bg-white/5">

            <div className="overflow-x-auto">

              <table className="w-full">

                <thead className="bg-white/10">
                  <tr>
                    <th className="p-4 text-left">
                      Date
                    </th>

                    <th className="p-4 text-left">
                      Particular
                    </th>

                    <th className="p-4 text-left">
                      Reference
                    </th>

                    <th className="p-4 text-right">
                      Debit
                    </th>

                    <th className="p-4 text-right">
                      Credit
                    </th>

                    <th className="p-4 text-right">
                      Balance
                    </th>
                  </tr>
                </thead>

                <tbody>

                  {entries.length === 0 ? (
                    <tr>
                      <td
                        colSpan={6}
                        className="p-8 text-center text-slate-400"
                      >
                        No transactions found.
                      </td>
                    </tr>
                  ) : (
                    entries.map((entry) => (
                      <tr
                        key={entry.id}
                        className="border-t border-white/10 hover:bg-white/5"
                      >
                        <td className="p-4">
                          {entry.date}
                        </td>

                        <td className="p-4">
                          {entry.particular}
                        </td>

                        <td className="p-4 text-slate-300">
                          {entry.reference || "-"}
                        </td>

                        <td className="p-4 text-right">
                          {formatCurrency(entry.debit)}
                        </td>

                        <td className="p-4 text-right">
                          {formatCurrency(entry.credit)}
                        </td>

                        <td className="p-4 text-right font-semibold">
                          {formatCurrency(entry.balance)}
                        </td>
                      </tr>
                    ))
                  )}

                </tbody>

              </table>

            </div>
          </div>
        )}

      </div>
    </main>
  );
}

function SummaryCard({
  title,
  value,
}: {
  title: string;
  value: number;
}) {
  return (
    <div className="rounded-2xl border border-white/10 bg-white/5 p-5">
      <p className="text-sm text-slate-400">
        {title}
      </p>

      <p className="mt-2 text-2xl font-bold">
        {formatCurrency(value)}
      </p>
    </div>
  );
}

function formatCurrency(value: number) {
  return new Intl.NumberFormat("en-IN", {
    style: "currency",
    currency: "INR",
    maximumFractionDigits: 2,
  }).format(value);
}
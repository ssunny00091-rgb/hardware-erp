"use client";

import { useCallback, useState } from "react";
import {
  LedgerEntry,
  LedgerService,
  LedgerSummary,
} from "../services/ledger.service";

export type LedgerType = "customer" | "supplier" | "painter";

export function useLedger() {
  const [entries, setEntries] = useState<LedgerEntry[]>([]);
  const [summary, setSummary] = useState<LedgerSummary>({
    openingBalance: 0,
    totalDebit: 0,
    totalCredit: 0,
    balance: 0,
  });

  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const loadLedger = useCallback(
    async (type: LedgerType, name: string) => {
      if (!name) {
        setEntries([]);
        setSummary({
          openingBalance: 0,
          totalDebit: 0,
          totalCredit: 0,
          balance: 0,
        });
        return;
      }

      setLoading(true);
      setError(null);

      try {
        let result;

        if (type === "customer") {
          result = await LedgerService.getCustomerLedger(name);
        } else if (type === "supplier") {
          result = await LedgerService.getSupplierLedger(name);
        } else {
  throw new Error(
    "Painter ledger is not available yet."
  );

        }

        setEntries(result.entries);
        setSummary(result.summary);
      } catch (err: any) {
        console.error("Ledger error:", err);

        setEntries([]);

        setSummary({
          openingBalance: 0,
          totalDebit: 0,
          totalCredit: 0,
          balance: 0,
        });

        setError(
          err?.message || "Failed to load ledger"
        );
      } finally {
        setLoading(false);
      }
    },
    []
  );

  return {
    entries,
    summary,
    loading,
    error,
    loadLedger,
  };
}
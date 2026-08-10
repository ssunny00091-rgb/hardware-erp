import { supabase } from "../lib/supabase";

export type LedgerType = "customer" | "supplier" | "painter";

export interface LedgerEntry {
  id: string;
  date: string;
  particular: string;
  reference: string | null;
  debit: number;
  credit: number;
  balance: number;
}

export interface LedgerSummary {
  openingBalance: number;
  totalDebit: number;
  totalCredit: number;
  balance: number;
}

export class LedgerService {
  /**
   * Customer Ledger
   *
   * Debit  = Opening Balance + Sales
   * Credit = Payments Received
   */
  static async getCustomerLedger(
    customerName: string
  ): Promise<{
    entries: LedgerEntry[];
    summary: LedgerSummary;
  }> {
    const [{ data: customer, error: customerError }, { data: sales, error: salesError }, { data: payments, error: paymentsError }] =
      await Promise.all([
        supabase
          .from("customers")
          .select("id, customer_name, opening_balance")
          .eq("customer_name", customerName)
          .maybeSingle(),

        supabase
          .from("sales")
          .select("id, customer_name, total, invoice_no, bill_date, created_at")
          .eq("customer_name", customerName)
          .order("bill_date", { ascending: true }),

        supabase
          .from("payments")
          .select(
            "id, party_name, payment_type, amount, payment_date, reference_no, notes"
          )
          .eq("party_type", "customer")
          .eq("party_name", customerName)
          .order("payment_date", { ascending: true }),
      ]);

    if (customerError) throw customerError;
    if (salesError) throw salesError;
    if (paymentsError) throw paymentsError;

    const entries: LedgerEntry[] = [];

    const openingBalance = Number(customer?.opening_balance ?? 0);

    if (openingBalance !== 0) {
      entries.push({
        id: `opening-${customer?.id ?? customerName}`,
        date: customer?.id
          ? new Date().toISOString().split("T")[0]
          : new Date().toISOString().split("T")[0],
        particular: "Opening Balance",
        reference: null,
        debit: openingBalance > 0 ? openingBalance : 0,
        credit: openingBalance < 0 ? Math.abs(openingBalance) : 0,
        balance: openingBalance,
      });
    }

    const transactions = [
      ...(sales ?? []).map((sale) => ({
        date: sale.bill_date ?? sale.created_at,
        type: "sale" as const,
        id: String(sale.id),
        particular: "Sale",
        reference: sale.invoice_no,
        amount: Number(sale.total ?? 0),
      })),

      ...(payments ?? []).map((payment) => ({
        date: payment.payment_date,
        type: "payment" as const,
        id: String(payment.id),
        particular: "Payment Received",
        reference: payment.reference_no,
        amount: Number(payment.amount ?? 0),
        notes: payment.notes,
      })),
    ].sort((a, b) => {
      return (
        new Date(a.date).getTime() -
        new Date(b.date).getTime()
      );
    });

    let balance = openingBalance;

    for (const transaction of transactions) {
      if (transaction.type === "sale") {
        balance += transaction.amount;

        entries.push({
          id: `sale-${transaction.id}`,
          date: transaction.date,
          particular: transaction.particular,
          reference: transaction.reference,
          debit: transaction.amount,
          credit: 0,
          balance,
        });
      } else {
        balance -= transaction.amount;

        entries.push({
          id: `payment-${transaction.id}`,
          date: transaction.date,
          particular: transaction.particular,
          reference: transaction.reference,
          debit: 0,
          credit: transaction.amount,
          balance,
        });
      }
    }

    const totalDebit = entries.reduce(
      (sum, entry) => sum + entry.debit,
      0
    );

    const totalCredit = entries.reduce(
      (sum, entry) => sum + entry.credit,
      0
    );

    return {
      entries,
      summary: {
        openingBalance,
        totalDebit,
        totalCredit,
        balance,
      },
    };
  }

  /**
   * Supplier Ledger
   *
   * Debit  = Purchase
   * Credit = Payment Made
   */
 static async getSupplierLedger(
  supplierName: string
): Promise<{
  entries: LedgerEntry[];
  summary: LedgerSummary;
}> {
  const [
    { data: supplier, error: supplierError },
    { data: purchases, error: purchasesError },
    { data: payments, error: paymentsError },
  ] = await Promise.all([
    supabase
      .from("suppliers")
      .select("id, supplier_name, opening_balance")
      .eq("supplier_name", supplierName)
      .maybeSingle(),

    supabase
      .from("purchases")
      .select(
        "id, supplier_name, invoice_no, purchase_date, total, created_at"
      )
      .eq("supplier_name", supplierName)
      .order("purchase_date", { ascending: true }),

    supabase
      .from("payments")
      .select(
        "id, party_name, payment_type, amount, payment_date, reference_no, notes"
      )
      .eq("party_type", "supplier")
      .eq("party_name", supplierName)
      .order("payment_date", { ascending: true }),
  ]);

  if (supplierError) throw supplierError;
  if (purchasesError) throw purchasesError;
  if (paymentsError) throw paymentsError;

  const openingBalance = Number(
    supplier?.opening_balance ?? 0
  );

  const entries: LedgerEntry[] = [];

  // Opening Balance
  if (openingBalance !== 0) {
    entries.push({
      id: `opening-${supplier?.id ?? supplierName}`,
      date: new Date().toISOString().split("T")[0],
      particular: "Opening Balance",
      reference: null,
      debit: openingBalance > 0 ? openingBalance : 0,
      credit: openingBalance < 0 ? Math.abs(openingBalance) : 0,
      balance: openingBalance,
    });
  }

  const transactions = [
    ...(purchases ?? []).map((purchase) => ({
      date: purchase.purchase_date ?? purchase.created_at,
      type: "purchase" as const,
      id: String(purchase.id),
      particular: "Purchase",
      reference: purchase.invoice_no,
      amount: Number(purchase.total ?? 0),
    })),

    ...(payments ?? []).map((payment) => ({
      date: payment.payment_date,
      type: "payment" as const,
      id: String(payment.id),
      particular: "Payment Made",
      reference: payment.reference_no,
      amount: Number(payment.amount ?? 0),
    })),
  ].sort((a, b) => {
    return (
      new Date(a.date).getTime() -
      new Date(b.date).getTime()
    );
  });

  let balance = openingBalance;

  for (const transaction of transactions) {
    if (transaction.type === "purchase") {
      balance += transaction.amount;

      entries.push({
        id: `purchase-${transaction.id}`,
        date: transaction.date,
        particular: transaction.particular,
        reference: transaction.reference,
        debit: transaction.amount,
        credit: 0,
        balance,
      });
    } else {
      balance -= transaction.amount;

      entries.push({
        id: `payment-${transaction.id}`,
        date: transaction.date,
        particular: transaction.particular,
        reference: transaction.reference,
        debit: 0,
        credit: transaction.amount,
        balance,
      });
    }
  }

  const totalDebit = entries.reduce(
    (sum, entry) => sum + entry.debit,
    0
  );

  const totalCredit = entries.reduce(
    (sum, entry) => sum + entry.credit,
    0
  );

  return {
    entries,
    summary: {
      openingBalance,
      totalDebit,
      totalCredit,
      balance,
    },
  };
}
}
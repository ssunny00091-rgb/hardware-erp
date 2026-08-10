"use client";

import { Supplier } from "@/app/types/supplier";

interface SupplierRowProps {
  supplier: Supplier;
}

export default function SupplierRow({
  supplier,
}: SupplierRowProps) {
  return (
    <tr className="border-t border-white/10 hover:bg-white/5">
      <td className="p-3">
        {supplier.supplier_name}
      </td>

      <td className="p-3">
        {supplier.mobile || "-"}
      </td>

      <td className="p-3">
        {supplier.gst_no || "-"}
      </td>

      <td className="p-3 text-right">
        ₹
        {Number(supplier.opening_balance || 0).toLocaleString(
          "en-IN"
        )}
      </td>

      <td className="p-3 text-center">
        <button
          className="rounded-lg bg-blue-600 px-3 py-2 text-sm hover:bg-blue-500"
          type="button"
        >
          View Ledger
        </button>
      </td>
    </tr>
  );
}
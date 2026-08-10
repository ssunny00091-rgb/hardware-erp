"use client";

import { Supplier } from "@/app/types/supplier";
import SupplierRow from "./SupplierRow";

interface SupplierListProps {
  suppliers: Supplier[];
  loading: boolean;
}

export default function SupplierList({
  suppliers,
  loading,
}: SupplierListProps) {
  if (loading) {
    return (
      <p className="mt-10 text-center text-white">
        Loading Suppliers...
      </p>
    );
  }

  if (suppliers.length === 0) {
    return (
      <p className="mt-10 text-center text-gray-300">
        No Suppliers Found
      </p>
    );
  }

  return (
    <div className="mt-6 overflow-hidden rounded-xl border border-slate-700">
      <table className="w-full bg-slate-900 text-white">
        <thead className="bg-blue-600">
          <tr>
            <th className="p-3 text-left">Supplier</th>
            <th className="p-3 text-left">Mobile</th>
            <th className="p-3 text-left">GST</th>
            <th className="p-3 text-right">Opening Balance</th>
            <th className="p-3 text-center">Action</th>
          </tr>
        </thead>

        <tbody>
          {suppliers.map((supplier) => (
            <SupplierRow
              key={supplier.id}
              supplier={supplier}
            />
          ))}
        </tbody>
      </table>
    </div>
  );
}
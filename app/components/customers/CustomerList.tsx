"use client";

import { Customer } from "@/app/types/customer";
import CustomerRow from "./CustomerRow";

interface CustomerListProps {
  customers: Customer[];
  loading: boolean;
}

export default function CustomerList({
  customers,
  loading,
}: CustomerListProps) {
  if (loading) {
    return (
      <p className="mt-10 text-center text-white">
        Loading Customers...
      </p>
    );
  }

  if (customers.length === 0) {
    return (
      <p className="mt-10 text-center text-gray-300">
        No Customers Found
      </p>
    );
  }

  return (
    <table className="mt-6 w-full overflow-hidden rounded-xl border border-slate-700 bg-slate-900 text-white">
      <thead className="bg-blue-600">
        <tr>
          <th className="p-3 text-left">Customer</th>
          <th className="p-3 text-left">Mobile</th>
          <th className="p-3 text-left">Credit Limit</th>
          <th className="p-3 text-left">Action</th>
        </tr>
      </thead>

      <tbody>
        {customers.map((customer) => (
          <CustomerRow
            key={customer.id}
            customer={customer}
          />
        ))}
      </tbody>
    </table>
  );
}
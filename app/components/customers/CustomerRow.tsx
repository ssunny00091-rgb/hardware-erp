import { Customer } from "@/app/types/customer";
import { Pencil, Trash2 } from "lucide-react";

interface CustomerRowProps {
  customer: Customer;
}

export default function CustomerRow({
  customer,
}: CustomerRowProps) {
  return (
    <tr className="border-b border-slate-700 text-white hover:bg-slate-800">
      <td className="p-3">{customer.customer_name}</td>

      <td className="p-3">
        {customer.mobile || "-"}
      </td>

      <td className="p-3">
        ₹{customer.credit_limit.toLocaleString()}
      </td>

      <td className="p-3">
        <div className="flex gap-3">
          <button>
            <Pencil
              size={18}
              className="text-blue-400 hover:text-blue-300"
            />
          </button>

          <button>
            <Trash2
              size={18}
              className="text-red-400 hover:text-red-300"
            />
          </button>
        </div>
      </td>
    </tr>
  );
}
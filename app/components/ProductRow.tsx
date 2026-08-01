import { useRef } from "react";
import ProductSearch from "./ProductSearch";
type ProductRowProps = {
  
  index: number;
  product: {
    name: string;
    qty: string;
    unit: string;
    price: string;
  };
  onChange: (
    index: number,
    field: "name" | "qty" | "unit" | "price",
    value: string
  ) => void;
  total: number;
  onDelete: (index: number) => void;
  onAddNewRow: () => void;
};
export default function ProductRow({
  index,
  product,
  onChange,
  total,
  onDelete,
   onAddNewRow,
}: ProductRowProps) {
  const qtyRef = useRef<HTMLInputElement>(null);
  const priceRef = useRef<HTMLInputElement>(null);
    
  return (
  <div className="mb-3 grid grid-cols-5 gap-3">
   <ProductSearch
  value={product.name}
  onChange={(value) => {
    onChange(index, "name", value);
  }}
 onSelect={(name, price, unit) => {
  onChange(index, "name", name);
  onChange(index, "price", price.toString());
  onChange(index, "unit", unit);

  setTimeout(() => {
    qtyRef.current?.focus();
  }, 0);
}}
/>

    <div className="flex gap-2">
  <input
  ref={qtyRef}
  type="number"
  value={product.qty}
  placeholder="Qty"
  className="w-20 rounded-lg border p-3"
  onChange={(e) => onChange(index, "qty", e.target.value)}
  onKeyDown={(e) => {
    if (e.key === "Enter") {
      e.preventDefault();
      priceRef.current?.focus();
    }
  }}
/>

  <select
  value={product.unit}
  className="rounded-lg border p-3"
  onChange={(e) => onChange(index, "unit", e.target.value)}
>
  <option value="Piece">Piece</option>
  <option value="Kg">Kg</option>
  <option value="Gram">Gram</option>
  <option value="Ltr">Ltr</option>
  <option value="ml">ml</option>
  <option value="Bag">Bag</option>
  <option value="Box">Box</option>
  <option value="Packet">Packet</option>
  <option value="Roll">Roll</option>
  <option value="Meter">Meter</option>
  <option value="Feet">Feet</option>
  <option value="Dozen">Dozen</option>
  <option value="Unit">Unit</option>
</select>
</div>

    <input
  ref={priceRef}
  type="number"
  value={product.price}
  placeholder="Price"
  className="rounded-lg border p-3"
  onChange={(e) => onChange(index, "price", e.target.value)}
  onKeyDown={(e) => {
    if (e.key === "Enter") {
      e.preventDefault();
      onAddNewRow();
    }
  }}
/>

    {/* Total Column */}
    <div className="flex items-center font-semibold text-green-600">
      ₹{total}
    </div>

    {/* Action Column */}
   <button
  onClick={() => onDelete(index)}
  className="rounded-lg bg-red-500 px-3 py-2 text-white hover:bg-red-600"
>
  🗑️
</button>
  </div>
);
}
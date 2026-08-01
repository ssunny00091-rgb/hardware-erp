"use client";

import { useMemo, useState } from "react";
import { products } from "../data/products";

type ProductSearchProps = {
  value: string;
  onChange: (value: string) => void;
  onSelect: (
    name: string,
    price: number,
    unit: string
  ) => void;
};

export default function ProductSearch({
  value,
  onChange,
  onSelect,
}: ProductSearchProps) {
  const [showDropdown, setShowDropdown] = useState(false);

  const filteredProducts = useMemo(() => {
    const search = (value ?? "").trim().toLowerCase();

    if (!search) return [];

    return products.filter((product) =>
      product.name.toLowerCase().includes(search)
    );
  }, [value]);

  return (
    <div className="relative w-full">
      <input
        type="text"
        value={value ?? ""}
        placeholder="Search Product..."
        className="w-full rounded-lg border p-3 focus:outline-none focus:ring-2 focus:ring-blue-500"
        onFocus={() => setShowDropdown(true)}
        onChange={(e) => {
          onChange(e.target.value);
          setShowDropdown(true);
        }}
      />

      {showDropdown &&
        (value ?? "").trim() !== "" &&
        filteredProducts.length > 0 &&
        !filteredProducts.some((p) => p.name === value) && (
          <div className="absolute left-0 right-0 z-50 mt-1 max-h-60 overflow-y-auto rounded-lg border border-gray-300 bg-black shadow-xl">
            {filteredProducts.map((product) => (
              <div
                key={product.id}
                className="cursor-pointer border-b p-3 hover:bg-blue-500"
                onMouseDown={(e) => {
                  e.preventDefault();

                  onSelect(
  product.name,
  product.price,
  product.unit
);

                  setShowDropdown(false);
                }}
              >
                <div className="font-medium">{product.name}</div>

                <div className="text-sm text-gray-500">
                  ₹ {product.price}
                </div>
              </div>
            ))}
          </div>
        )}
    </div>
  );
}
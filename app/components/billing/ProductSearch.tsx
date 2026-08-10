"use client";

import { forwardRef, useMemo, useState } from "react";

export type InventoryProduct = {
  id: number;
  product_name: string;
  selling_price: number;
  purchase_price: number;
  unit: string;
  stock: number;
};

type ProductSearchProps = {
  value: string;
  products?: InventoryProduct[];
  priceType?: "selling" | "purchase";

  onChange: (value: string) => void;

  onSelect: (
    name: string,
    price: number,
    unit: string
  ) => void;

  onAddNewProduct?: () => void;
};

const ProductSearch = forwardRef<
  HTMLInputElement,
  ProductSearchProps
>(
  (
    {
      value,
      products = [],
      priceType = "selling",
      onChange,
      onSelect,
      onAddNewProduct,
    },
    ref
  ) => {
    const [open, setOpen] = useState(false);
    const [selectedIndex, setSelectedIndex] = useState(0);

    const filteredProducts = useMemo(() => {
      const query = value.trim().toLowerCase();

      if (!query) {
        return products.slice(0, 8);
      }

      return products
        .filter((product) =>
          product.product_name
            .toLowerCase()
            .includes(query)
        )
        .slice(0, 8);
    }, [products, value]);

    const selectProduct = (
      product: InventoryProduct
    ) => {
      onSelect(
        product.product_name,
        priceType === "purchase"
          ? product.purchase_price
          : product.selling_price,
        product.unit
      );

      setOpen(false);
      setSelectedIndex(0);
    };

    const hasSearch =
      value.trim().length > 0;

    const productNotFound =
      hasSearch &&
      filteredProducts.length === 0;

    return (
      <div className="relative w-full">
        <input
          ref={ref}
          type="text"
          value={value}
          placeholder="Search product"
          className="w-full rounded-xl border border-white/20 bg-white/10 p-3 text-white placeholder:text-slate-300 focus:border-sky-400 focus:outline-none"
          onFocus={() => setOpen(true)}
          onBlur={() =>
            window.setTimeout(
              () => setOpen(false),
              150
            )
          }
          onChange={(event) => {
            onChange(event.target.value);
            setOpen(true);
            setSelectedIndex(0);
          }}
          onKeyDown={(event) => {
            if (
              !open ||
              filteredProducts.length === 0
            ) {
              return;
            }

            if (event.key === "ArrowDown") {
              event.preventDefault();

              setSelectedIndex((index) =>
                Math.min(
                  index + 1,
                  filteredProducts.length - 1
                )
              );
            } else if (
              event.key === "ArrowUp"
            ) {
              event.preventDefault();

              setSelectedIndex((index) =>
                Math.max(index - 1, 0)
              );
            } else if (
              event.key === "Enter"
            ) {
              event.preventDefault();

              const product =
                filteredProducts[selectedIndex];

              if (product) {
                selectProduct(product);
              }
            } else if (
              event.key === "Escape"
            ) {
              setOpen(false);
            }
          }}
        />

        {open && (
          <div className="absolute left-0 right-0 z-50 mt-1 max-h-72 overflow-y-auto rounded-xl border border-white/20 bg-slate-900 shadow-xl">

            {/* Existing products */}
            {filteredProducts.length > 0 && (
              <>
                {filteredProducts.map(
                  (product, index) => (
                    <button
                      key={product.id}
                      type="button"
                      className={`flex w-full items-center justify-between p-3 text-left text-white ${
                        index === selectedIndex
                          ? "bg-sky-600"
                          : "hover:bg-slate-800"
                      }`}
                      onMouseDown={(event) =>
                        event.preventDefault()
                      }
                      onClick={() =>
                        selectProduct(product)
                      }
                    >
                      <span>
                        {product.product_name}
                      </span>

                      <span className="text-sm text-slate-200">
                        ₹
                        {priceType ===
                        "purchase"
                          ? product.purchase_price
                          : product.selling_price}
                        {" · "}
                        Stock {product.stock}
                      </span>
                    </button>
                  )
                )}
              </>
            )}

            {/* Product not found */}
            {productNotFound && (
              <div className="p-4">
                <p className="mb-3 text-sm text-slate-300">
                  Product not found
                </p>

                {onAddNewProduct && (
                  <button
                    type="button"
                    onMouseDown={(event) =>
                      event.preventDefault()
                    }
                    onClick={() => {
                      setOpen(false);
                      onAddNewProduct();
                    }}
                    className="w-full rounded-xl bg-green-600 px-4 py-3 font-semibold text-white hover:bg-green-500"
                  >
                    ➕ Add New Product
                  </button>
                )}
              </div>
            )}
          </div>
        )}
      </div>
    );
  }
);

ProductSearch.displayName =
  "ProductSearch";

export default ProductSearch;
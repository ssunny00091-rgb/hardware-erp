"use client";

import { useCallback, useEffect, useState } from "react";

import { Product } from "../types/product";
import { ProductService } from "../services/product.service";

export function useProducts() {
  const [products, setProducts] = useState<Product[]>([]);
  const [loading, setLoading] = useState(true);

  const refresh = useCallback(async () => {
    setLoading(true);

    try {
      const data = await ProductService.getAll();
      setProducts(data);
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    refresh();
  }, [refresh]);

  return {
    products,
    loading,
    refresh,
  };
}

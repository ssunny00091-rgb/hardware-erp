"use client";

import { useCallback, useEffect, useState } from "react";
import { Supplier } from "../types/supplier";
import { SupplierService } from "../services/supplier.service";

export function useSuppliers() {
  const [suppliers, setSuppliers] = useState<Supplier[]>([]);
  const [loading, setLoading] = useState(true);

  const refresh = useCallback(async () => {
    setLoading(true);

    try {
      const data = await SupplierService.getAll();
      setSuppliers(data);
    } catch (error) {
      console.error("Load Suppliers:", error);
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    refresh();
  }, [refresh]);

  return {
    suppliers,
    loading,
    refresh,
  };
}
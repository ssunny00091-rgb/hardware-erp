"use client";

import { useCallback, useEffect, useState } from "react";
import { Customer } from "@/app/types/customer";
import { CustomerService } from "@/app/services/customer.service";

export function useCustomers() {
  const [customers, setCustomers] = useState<Customer[]>([]);
  const [loading, setLoading] = useState(true);

  const loadCustomers = useCallback(async () => {
    setLoading(true);

    try {
      const data = await CustomerService.getAll();
      setCustomers(data);
    } catch (error) {
      console.error("Load Customers:", error);
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    loadCustomers();
  }, [loadCustomers]);

  return {
    customers,
    loading,
    refresh: loadCustomers,
  };
}
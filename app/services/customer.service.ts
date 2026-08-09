import { supabase } from "../lib/supabase";
import { Customer } from "../types/customer";
import { CustomerFormData } from "../lib/validations/customer";

export class CustomerService {
  static async getAll(): Promise<Customer[]> {
    const { data, error } = await supabase
      .from("customers")
      .select("*")
      .order("customer_name");

    if (error) throw error;

    return data ?? [];
  }

static async create(customer: CustomerFormData) {
  const { data, error } = await supabase
    .from("customers")
    .insert([customer])
    .select();

  console.log("Supabase Data:", data);
  console.log("Supabase Error:", error);

  if (error) {
    throw error;
  }

  return data;
} }
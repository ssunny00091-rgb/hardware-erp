import { z } from "zod";

export const supplierSchema = z.object({
  supplier_name: z
    .string()
    .min(2, "Supplier name is required"),

  mobile: z.string().optional(),

  address: z.string().optional(),

  gst_no: z.string().optional(),

  opening_balance: z.number().min(
    0,
    "Opening balance cannot be negative"
  ),
});

export type SupplierFormData =
  z.infer<typeof supplierSchema>;
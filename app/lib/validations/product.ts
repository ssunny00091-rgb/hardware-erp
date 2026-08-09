import { z } from "zod";

export const productSchema = z.object({
  product_name: z
    .string()
    .min(2, "Product Name is required"),

  category: z.string().optional(),

  brand: z.string().optional(),

  unit: z
    .string()
    .min(1, "Unit is required"),

  purchase_price: z.coerce.number().default(0),

  selling_price: z.coerce.number().default(0),

  stock: z.coerce.number().default(0),

  minimum_stock: z.coerce.number().default(0),

  hsn_code: z.string().optional(),

  gst_percent: z.coerce.number().default(18),
});

export type ProductFormData = z.infer<
  typeof productSchema
>;
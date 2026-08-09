import { z } from "zod";

export const customerSchema = z.object({
  customer_name: z
    .string()
    .min(3, "Customer name is required"),

  mobile: z.string().optional(),

  email: z
    .string()
    .email("Invalid email")
    .or(z.literal(""))
    .optional(),

  gst_no: z.string().optional(),

  address: z.string().optional(),

  city: z.string().optional(),

  state: z.string().optional(),

  pincode: z.string().optional(),

  credit_limit: z.coerce.number().default(0),

  payment_term: z.coerce.number().default(0),

  opening_balance: z.coerce.number().default(0),
});

export type CustomerFormData = z.infer<typeof customerSchema>;
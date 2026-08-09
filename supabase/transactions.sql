-- Run this file once in Supabase Dashboard > SQL Editor.
-- It makes stock changes and sale/purchase records atomic: if any validation
-- fails, nothing is saved.

create or replace function public.create_sale_and_reduce_stock(
  p_customer_name text,
  p_mobile text,
  p_invoice_no text,
  p_bill_date date,
  p_products jsonb,
  p_total numeric
)
returns uuid
language plpgsql
security definer
set search_path = public
as $$
declare
  line_item record;
  product_record record;
  sale_id uuid;
begin
  if p_customer_name is null or btrim(p_customer_name) = '' then
    raise exception 'Customer name is required';
  end if;

  if p_invoice_no is null or btrim(p_invoice_no) = '' then
    raise exception 'Invoice number is required';
  end if;

  if jsonb_typeof(p_products) <> 'array' or jsonb_array_length(p_products) = 0 then
    raise exception 'At least one product is required';
  end if;

  for line_item in
    select name, sum(qty)::integer as qty
    from jsonb_to_recordset(p_products) as item(name text, qty numeric)
    where name is not null and btrim(name) <> '' and qty > 0
    group by name
  loop
    select id, stock
    into product_record
    from products
    where product_name = line_item.name
    for update;

    if not found then
      raise exception 'Product "%" does not exist', line_item.name;
    end if;

    if product_record.stock < line_item.qty then
      raise exception 'Insufficient stock for "%". Available: %, requested: %',
        line_item.name, product_record.stock, line_item.qty;
    end if;

    update products
    set stock = stock - line_item.qty
    where id = product_record.id;
  end loop;

  insert into sales (customer_name, mobile, invoice_no, bill_date, products, total)
  values (btrim(p_customer_name), nullif(btrim(p_mobile), ''), p_invoice_no, p_bill_date, p_products, p_total)
  returning id into sale_id;

  return sale_id;
end;
$$;

create or replace function public.create_purchase_and_increase_stock(
  p_supplier_name text,
  p_invoice_no text,
  p_purchase_date date,
  p_products jsonb
)
returns bigint
language plpgsql
security definer
set search_path = public
as $$
declare
  line_item record;
  product_record record;
  purchase_id bigint;
begin
  if p_supplier_name is null or btrim(p_supplier_name) = '' then
    raise exception 'Supplier name is required';
  end if;

  if jsonb_typeof(p_products) <> 'array' or jsonb_array_length(p_products) = 0 then
    raise exception 'At least one product is required';
  end if;

  for line_item in
    select name, sum(qty)::integer as qty
    from jsonb_to_recordset(p_products) as item(name text, qty numeric)
    where name is not null and btrim(name) <> '' and qty > 0
    group by name
  loop
    select id
    into product_record
    from products
    where product_name = line_item.name
    for update;

    if not found then
      raise exception 'Product "%" does not exist', line_item.name;
    end if;

    update products
    set stock = stock + line_item.qty
    where id = product_record.id;
  end loop;

  insert into purchases (supplier_name, invoice_no, purchase_date, products)
  values (btrim(p_supplier_name), nullif(btrim(p_invoice_no), ''), p_purchase_date, p_products)
  returning id into purchase_id;

  return purchase_id;
end;
$$;

grant execute on function public.create_sale_and_reduce_stock(text, text, text, date, jsonb, numeric) to anon, authenticated;
grant execute on function public.create_purchase_and_increase_stock(text, text, date, jsonb) to anon, authenticated;

-- Edit an existing sale and reconcile its stock in the same transaction.
create or replace function public.update_sale_and_adjust_stock(
  p_sale_id uuid, p_customer_name text, p_mobile text, p_invoice_no text,
  p_bill_date date, p_products jsonb, p_total numeric
)
returns void language plpgsql security definer set search_path = public as $$
declare line_item record; existing_sale record; product_record record;
begin
  select products into existing_sale from sales where id = p_sale_id for update;
  if not found then raise exception 'Sale record not found'; end if;
  for line_item in select name, sum(qty)::integer as qty from jsonb_to_recordset(existing_sale.products) as item(name text, qty numeric) group by name loop
    update products set stock = stock + line_item.qty where product_name = line_item.name;
  end loop;
  for line_item in select name, sum(qty)::integer as qty from jsonb_to_recordset(p_products) as item(name text, qty numeric) where name is not null and btrim(name) <> '' and qty > 0 group by name loop
    select id, stock into product_record from products where product_name = line_item.name for update;
    if not found then raise exception 'Product "%" does not exist', line_item.name; end if;
    if product_record.stock < line_item.qty then raise exception 'Insufficient stock for "%"', line_item.name; end if;
    update products set stock = stock - line_item.qty where id = product_record.id;
  end loop;
  update sales set customer_name = btrim(p_customer_name), mobile = nullif(btrim(p_mobile), ''), invoice_no = p_invoice_no, bill_date = p_bill_date, products = p_products, total = p_total where id = p_sale_id;
end;
$$;

create or replace function public.delete_sale_and_restore_stock(p_sale_id uuid)
returns void language plpgsql security definer set search_path = public as $$
declare line_item record; existing_sale record;
begin
  select products into existing_sale from sales where id = p_sale_id for update;
  if not found then raise exception 'Sale record not found'; end if;
  for line_item in select name, sum(qty)::integer as qty from jsonb_to_recordset(existing_sale.products) as item(name text, qty numeric) group by name loop
    update products set stock = stock + line_item.qty where product_name = line_item.name;
  end loop;
  delete from sales where id = p_sale_id;
end;
$$;

grant execute on function public.update_sale_and_adjust_stock(uuid, text, text, text, date, jsonb, numeric) to anon, authenticated;
grant execute on function public.delete_sale_and_restore_stock(uuid) to anon, authenticated;

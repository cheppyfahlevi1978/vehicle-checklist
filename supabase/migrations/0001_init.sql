-- Hotel Fleet Check — initial schema
-- Tables: users (profile), vehicles, checklists, checklist_items

create extension if not exists "pgcrypto";

-- ---------------------------------------------------------------------------
-- users: profile row mirroring auth.users, carrying the app-level role
-- ---------------------------------------------------------------------------
create table if not exists public.users (
  id uuid primary key references auth.users (id) on delete cascade,
  email text not null,
  role text not null default 'driver' check (role in ('driver', 'supervisor', 'engineering')),
  created_at timestamptz not null default now()
);

-- Auto-create a profile row whenever a new auth user signs up.
create or replace function public.handle_new_user()
returns trigger
language plpgsql
security definer set search_path = public
as $$
begin
  insert into public.users (id, email)
  values (new.id, new.email);
  return new;
end;
$$;

drop trigger if exists on_auth_user_created on auth.users;
create trigger on_auth_user_created
  after insert on auth.users
  for each row execute procedure public.handle_new_user();

-- ---------------------------------------------------------------------------
-- vehicles
-- ---------------------------------------------------------------------------
create table if not exists public.vehicles (
  id uuid primary key default gen_random_uuid(),
  plate_number text not null unique,
  name text not null,
  type text not null check (type in ('SUV', 'Van', 'Buggy')),
  status text not null default 'available'
    check (status in ('available', 'in_use', 'maintenance', 'dirty')),
  created_at timestamptz not null default now()
);

-- ---------------------------------------------------------------------------
-- checklists
-- ---------------------------------------------------------------------------
create table if not exists public.checklists (
  id uuid primary key default gen_random_uuid(),
  vehicle_id uuid not null references public.vehicles (id) on delete cascade,
  user_id uuid not null references public.users (id) on delete cascade,
  fuel_level text not null check (fuel_level in ('full', '3/4', '1/2', '1/4', 'empty')),
  status text not null default 'passed' check (status in ('passed', 'issues_found')),
  created_at timestamptz not null default now()
);

create index if not exists checklists_vehicle_id_idx on public.checklists (vehicle_id);
create index if not exists checklists_user_id_idx on public.checklists (user_id);

-- ---------------------------------------------------------------------------
-- checklist_items
-- ---------------------------------------------------------------------------
create table if not exists public.checklist_items (
  id uuid primary key default gen_random_uuid(),
  checklist_id uuid not null references public.checklists (id) on delete cascade,
  category text not null check (category in ('exterior', 'interior', 'amenities', 'engine')),
  item_name text not null,
  condition text not null check (condition in ('good', 'issue')),
  notes text,
  photo_url text
);

create index if not exists checklist_items_checklist_id_idx on public.checklist_items (checklist_id);

-- ---------------------------------------------------------------------------
-- Row Level Security
-- ---------------------------------------------------------------------------
alter table public.users enable row level security;
alter table public.vehicles enable row level security;
alter table public.checklists enable row level security;
alter table public.checklist_items enable row level security;

-- users: everyone authenticated can read profiles (needed to show driver names);
-- a user can only update their own row.
create policy "users can view all profiles" on public.users
  for select to authenticated using (true);

create policy "users can update own profile" on public.users
  for update to authenticated using (auth.uid() = id);

-- vehicles: readable by any authenticated staff member; only supervisors and
-- engineering can change status (e.g. send to maintenance).
create policy "authenticated can view vehicles" on public.vehicles
  for select to authenticated using (true);

create policy "supervisors and engineering can update vehicles" on public.vehicles
  for update to authenticated using (
    exists (
      select 1 from public.users
      where users.id = auth.uid() and users.role in ('supervisor', 'engineering')
    )
  );

-- checklists: any authenticated staff member can view and submit; a driver
-- may only submit checklists under their own user_id.
create policy "authenticated can view checklists" on public.checklists
  for select to authenticated using (true);

create policy "authenticated can insert own checklists" on public.checklists
  for insert to authenticated with check (auth.uid() = user_id);

-- checklist_items: readable/writable through the parent checklist ownership.
create policy "authenticated can view checklist items" on public.checklist_items
  for select to authenticated using (true);

create policy "authenticated can insert checklist items for own checklist" on public.checklist_items
  for insert to authenticated with check (
    exists (
      select 1 from public.checklists
      where checklists.id = checklist_items.checklist_id
        and checklists.user_id = auth.uid()
    )
  );

-- ---------------------------------------------------------------------------
-- Storage: bucket for checklist issue photos
-- ---------------------------------------------------------------------------
insert into storage.buckets (id, name, public)
values ('checklist-photos', 'checklist-photos', true)
on conflict (id) do nothing;

create policy "authenticated can upload checklist photos" on storage.objects
  for insert to authenticated with check (bucket_id = 'checklist-photos');

create policy "anyone can view checklist photos" on storage.objects
  for select using (bucket_id = 'checklist-photos');

-- ---------------------------------------------------------------------------
-- Seed data (sample fleet)
-- ---------------------------------------------------------------------------
insert into public.vehicles (plate_number, name, type, status) values
  ('B 1234 XYZ', 'Toyota Fortuner', 'SUV', 'available'),
  ('B 5678 ABC', 'Toyota Hiace', 'Van', 'available'),
  ('B 9012 QRS', 'Garia Golf Buggy', 'Buggy', 'in_use'),
  ('B 3456 DEF', 'Alphard', 'Van', 'maintenance'),
  ('B 7890 GHI', 'Fortuner VRZ', 'SUV', 'dirty')
on conflict (plate_number) do nothing;

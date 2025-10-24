-- Migration: add unique constraint on LIGNES_REGULIERES(icao_dep, icao_arr)
ALTER TABLE LIGNES_REGULIERES
  ADD UNIQUE KEY uniq_icao_pair (icao_dep, icao_arr);

-- Note: run this migration carefully on large tables. If there are existing duplicates, the ALTER will fail.
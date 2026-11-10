-- Migration 010b : FK TRACE_GPS.id -> CARNET_DE_VOL_GENERAL.id (partie separee de 010).
-- Sur la prod, 010 a echoue sur cette FK (#1452) : des traces GPS orphelines existent
-- (vols supprimes du carnet, trace conservee). Les index de 010, eux, sont bien poses.
--
-- Etape 1 — recenser (a executer d'abord, noter le resultat) :
--   SELECT COUNT(*) AS orphelines FROM TRACE_GPS t
--     LEFT JOIN CARNET_DE_VOL_GENERAL c ON c.id = t.id WHERE c.id IS NULL;
--   SELECT t.id FROM TRACE_GPS t
--     LEFT JOIN CARNET_DE_VOL_GENERAL c ON c.id = t.id WHERE c.id IS NULL LIMIT 50;
--
-- Etape 2 — nettoyer : supprime les traces des vols qui n'existent plus.
--   Ce sont des donnees sans referentiel ; si tu preferes les conserver, NE PAS
--   executer le DELETE ni la FK (TRACE_GPS.id est deja PK, les jointures sont
--   deja indexees ; la FK n'apporte que l'integrite + le ON DELETE CASCADE).
DELETE t FROM `TRACE_GPS` t
  LEFT JOIN `CARNET_DE_VOL_GENERAL` c ON c.id = t.id
  WHERE c.id IS NULL;

-- Etape 3 — poser la FK (reussit forcement apres le DELETE)
ALTER TABLE `TRACE_GPS`
  ADD CONSTRAINT `fk_trace_vol` FOREIGN KEY (`id`) REFERENCES `CARNET_DE_VOL_GENERAL` (`id`) ON DELETE CASCADE;

-- Verification :
--   SELECT COUNT(*) FROM TRACE_GPS t LEFT JOIN CARNET_DE_VOL_GENERAL c ON c.id=t.id WHERE c.id IS NULL;  -- 0
--   SHOW CREATE TABLE TRACE_GPS;
-- Rollback :
--   ALTER TABLE `TRACE_GPS` DROP FOREIGN KEY `fk_trace_vol`;

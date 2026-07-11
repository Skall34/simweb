# SimWeb — Feuille de route SaaS

> Document de synthèse — Juillet 2026  
> Mis à jour après Phase 1 complétée.

---

## Contexte

SimWeb est un système de gestion de Virtual Airline (VA) pour Microsoft Flight Simulator.  
Actuellement : **application single-tenant** — une installation = une VA.  
Objectif : transformer en **SaaS multi-tenant** avec abonnement mensuel.

---

## Décisions prises

| Sujet | Décision |
|---|---|
| **Modèle commercial** | Abonnement mensuel, prix unique, tout inclus |
| **Multi-tenancy** | 1 base de données par client (isolation totale) |
| **Infrastructure** | Docker (VPS auto-géré, Nginx, MariaDB) |
| **Approche** | Itérative — Phase 1 (fondations) puis Phase 2 (backend SaaS complet) |
| **SimAddon C#** | Distribué avec chaque VA — URL configurable par tenant |

---

## Architecture cible

```
simweb_master            → tenants, subscriptions, routing
simweb_tenant_001        → schéma complet VA (Brassins Aérien, etc.)
simweb_tenant_002        → schéma complet VA (...)
...

Base partagée (lecture seule) :
simweb_airports          → table AEROPORTS (80 000 lignes) — commune à tous les tenants
```

### Routing tenant
- Sous-domaine : `ma-va.simweb.io` → PHP lit le sous-domaine → se connecte à `simweb_tenant_XXX`
- La DB master contient la table de routage `tenants (subdomain, db_name, status, ...)`

---

## SimAddon C# — Adaptation SaaS

Le client ACARS est indispensable au fonctionnement du site. En SaaS :
- **Option retenue (à confirmer)** : champ URL configurable dans l'UI du client
- L'admin VA configure l'URL `https://ma-va.simweb.io` une fois, elle est distribuée aux pilotes
- Alternative future : "activation code" qui configure l'URL automatiquement

---

## Phase 1 — Fondations ✅ COMPLÉTÉE (11 juillet 2026)

> Branche `saas` créée et poussée sur GitHub. Aucun fichier PHP existant modifié. Production inchangée.

- [x] Branche Git `saas` — créée depuis `main`, poussée sur `origin/saas`
- [x] Docker Compose (PHP-FPM + MariaDB + Nginx + phpMyAdmin)
- [x] `docker/nginx.conf` — sécurisé (bloque `/includes/`, `/scripts/`, `/lang/`, `.ini`, `.sql`, `.log`)
- [x] `Dockerfile` — PHP 8.4-FPM + pdo_mysql + mbstring + curl
- [x] `config.ini.docker` — config dev versionnable, montée comme `config.ini` dans le conteneur
- [x] `.dockerignore` — exclut secrets, logs, airports SQL (80K lignes)
- [x] Landing page (`saas/landing.html`) — thème aviation, dark mode, 9 features, section ACARS animée
- [x] Pricing page (`saas/pricing.html`) — plan unique 9,90 €/mois, FAQ accordion
- [x] Wizard d'onboarding (`saas/wizard.html`) — 4 étapes, validation JS, résumé SimAddon
- [x] `saas/SAAS-ROADMAP.md` — ce fichier

**Lancer en local :**
```bash
docker compose up --build
# Site  : http://localhost:8080
# PMA   : http://localhost:8081
# DB    : localhost:3307
```

---

## Phase 2 — Backend SaaS (≈ 4-6 semaines avec aide AI)

| Composant | Effort estimé | Notes |
|---|---|---|
| DB master (`tenants`, `subscriptions`) | 2-3h | Nouveau schéma SQL |
| Provisioning tenant (script création DB) | 1-2 jours | Crée DB + importe schéma + AEROPORTS |
| Router tenant (sous-domaine → DB) | 1 jour | Modifier `includes/db_connect.php` |
| Refactor config (constantes → par tenant) | 2-3 jours | Remplace les `define()` globaux |
| Wizard SaaS (signup → provisioning) | 2-3 jours | Remplace l'installer actuel |
| CRON scripts multi-tenant | 2-3 jours | Itérer sur tous les tenants actifs |
| SimAddon C# — URL dynamique | 1-2 jours | Config URL par tenant |
| Stripe (souscription + webhooks) | 3-5 jours | Billing complet |
| CGV / Mentions légales / RGPD | 2-3 jours | Rédaction + intégration |
| Tests sécurité isolation inter-tenant | 3-5 jours | **Critique** — risque de fuite de données |

---

## Points de vigilance

### Table AEROPORTS (80 000 lignes)
Inclure dans chaque tenant DB est lourd. **Recommandé : base partagée en lecture seule.**  
À décider définitivement avant de commencer Phase 2.

### Isolation inter-tenant
Le risque principal d'un SaaS : une requête SQL sans filtre tenant expose les données d'un autre client.  
Stratégie : la DB par tenant **élimine ce risque structurellement** — c'est le principal avantage du modèle choisi.

### Production active
Le site est **actuellement en production**. Toutes les modifications SaaS se font sur la branche `saas`.  
La branche `main` reste intacte jusqu'à une décision explicite de merge.

---

## Infrastructure cible

| Composant | Solution | Coût estimé |
|---|---|---|
| VPS | OVH, Hetzner, ou DigitalOcean (4 Go RAM) | ~10-20 €/mois |
| Base de données | MariaDB auto-hébergé sur VPS (Docker) | Inclus |
| Reverse proxy | Nginx + Let's Encrypt (auto-SSL) | Gratuit |
| Email transactionnel | Brevo ou Resend | 5-15 €/mois |
| CDN / DNS | Cloudflare | Gratuit |
| Billing | Stripe | % par transaction |
| **Total estimé** | | **~20-40 €/mois** |

---

## Références

- FollowBrew SaaS Roadmap : `f:\GitHub\FollowBrew\saas\SAAS-ROADMAP.md`
- Architecture SimWeb actuelle : `.github/copilot-instructions.md`

#!/bin/bash
# Script de vérification des fichiers avant installation
# Usage : bash check_files.sh

echo "🔍 Vérification de la structure des fichiers..."
echo ""

ERRORS=0
WARNINGS=0

# Couleurs
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Fonction de vérification
check_dir() {
    if [ -d "$1" ]; then
        echo -e "${GREEN}✓${NC} $1"
    else
        echo -e "${RED}✗${NC} $1 ${RED}MANQUANT !${NC}"
        ((ERRORS++))
    fi
}

check_file() {
    if [ -f "$1" ]; then
        echo -e "${GREEN}✓${NC} $1"
    else
        echo -e "${RED}✗${NC} $1 ${RED}MANQUANT !${NC}"
        ((ERRORS++))
    fi
}

check_optional() {
    if [ -d "$1" ] || [ -f "$1" ]; then
        echo -e "${GREEN}✓${NC} $1"
    else
        echo -e "${YELLOW}⚠${NC} $1 (optionnel)"
        ((WARNINGS++))
    fi
}

echo "📁 Dossiers obligatoires :"
check_dir "admin"
check_dir "api"
check_dir "assets"
check_dir "css"
check_dir "includes"
check_dir "install"
check_dir "lang"
check_dir "pages"
check_dir "scripts"
check_dir "sql_database"

echo ""
echo "📁 Dossiers optionnels :"
check_optional "Documentation"
check_optional "tools"
check_optional "data"

echo ""
echo "📄 Fichiers obligatoires :"
check_file ".htaccess"
check_file "index.php"
check_file "lang.php"
check_file "live_flights.php"
check_file "login.php"
check_file "logout.php"

echo ""
echo "📄 Fichiers SQL critiques :"
check_file "sql_database/01_Main_Database.sql"
check_file "sql_database/02_Airports_data.sql"

echo ""
echo "📄 Fichiers de configuration (seront générés) :"
if [ -f "includes/db_connect.php" ]; then
    echo -e "${YELLOW}⚠${NC} includes/db_connect.php existe déjà (sera écrasé)"
fi
if [ -f "includes/config.php" ]; then
    echo -e "${YELLOW}⚠${NC} includes/config.php existe déjà (sera écrasé)"
fi

echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
if [ $ERRORS -eq 0 ]; then
    echo -e "${GREEN}✓ VALIDATION RÉUSSIE !${NC}"
    echo "Tous les fichiers obligatoires sont présents."
    echo "Vous pouvez lancer l'installation : http://votre-site/install/"
else
    echo -e "${RED}✗ ERREURS DÉTECTÉES : $ERRORS fichier(s) manquant(s)${NC}"
    echo "Uploadez les fichiers manquants avant de continuer."
    exit 1
fi

if [ $WARNINGS -gt 0 ]; then
    echo -e "${YELLOW}⚠ $WARNINGS fichier(s) optionnel(s) manquant(s)${NC}"
fi

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

# WordPress.org Distribution Build

## Quick Start

Nabudúce pre vytvorenie WordPress.org distribúcie spusti iba:

```bash
./build-release.sh
```

**To je všetko!** Script automaticky:
- ✅ Nainštaluje production dependencies (composer)
- ✅ Skompiluje frontend assets (npm run build) 
- ✅ Vytvorí WordPress.org compliant distribúciu
- ✅ Overí že všetky kľúčové súbory sú zahrnuté

## Výsledok

Dostaneš kompletný `mcp-for-woocommerce-1.1.5.zip` pripravený na WordPress.org submission.

## Čo je zahrnuté

### ✅ Štruktúra
```
mcp-for-woocommerce/
├── includes/Core/WpMcp.php      # Core files v správnej štruktúre
├── vendor/autoload.php          # Composer dependencies  
├── build/index.js               # Compiled frontend assets
├── client-setup.md              # Dokumentácia
├── woo-mcp.php                  # Main plugin file
└── ...
```

### ✅ WordPress.org Compliance
- Správna directory štruktúra
- Všetky dependencies zahrnuté
- Debug kód odstránený
- Text domain konzistentný
- .DS_Store súbory vylúčené

## Troubleshooting

Ak script zlyháva, skontroluj:
- `composer.json` a `package.json` existujú
- Node.js a npm sú nainštalované
- Composer je nainštalovaný

## Manual Steps (ak potrebuješ)

```bash
# 1. Dependencies
composer install --no-dev --optimize-autoloader

# 2. Build assets  
npm run build

# 3. Create distribution
./create-wordpress-org-compliant.sh
```
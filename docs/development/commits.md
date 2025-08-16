# Zmeny medzi commitmi

## Od: fix(config): remove invalid LOG_FILE path causing connection failures (e71236a)
## Po: fix(schema): fix inputSchema properties format - change empty arrays (694e224)

### Čas: 4 hodiny rozdiel

---

## Hlavné zmeny vykonané:

### 1. **Oprava inputSchema formátu** (694e224d1)
- **Problém**: Prázdne pole `'properties' => []` spôsobovalo chybu `'list' object has no attribute 'items'` v MCP klientoch
- **Riešenie**: Zmenené na prázdny objekt `'properties' => (object)[]` vo všetkých nástrojoch
- **Ovplyvnené súbory**:
  - `includes/Tools/McpWooAttributes.php`
  - `includes/Tools/McpWooPaymentGateways.php`
  - `includes/Tools/McpWooShipping.php`
  - `includes/Tools/McpWooSystemStatus.php`
  - `includes/Tools/McpWooTaxes.php`

### 2. **Oprava REST API callback systému** (81092832e)
- **Problém**: Nástroje používali `rest_alias` namiesto priamych callback funkcií
- **Riešenie**: Nahradené všetky chýbajúce nástroje s priamymi callback funkciami
- **Výsledok**: Lepšia kompatibilita a spoľahlivosť REST API

### 3. **Podpora fallback URL pre JWT** (9f673134b)
- **Problém**: JWT endpoints nefungovali keď WordPress nemal pretty permalinks
- **Riešenie**: Pridaná fallback URL podpora pre JWT autentifikáciu
- **Výhoda**: Plugin funguje aj bez pretty permalinks

### 4. **Zlepšenie JWT error handling** (96f0f59b6)
- **Vylepšenie**: Lepšie spracovanie chýb JWT tokenov a diagnostika
- **Pridané**: Detailnejšie error správy pre ľahšie ladenie

### 5. **Riešenie timeout problémov Claude.ai** (727d66827)
- **Problém**: Claude.ai web aplikácia mala connection timeouts
- **Riešenie**: Optimalizované connection handling pre web klienta
- **Výsledok**: Stabilnejšie spojenie s Claude.ai

### 6. **Relaxované Accept header pravidlá** (7e13c2afc)
- **Problém**: Prísne Accept header kontroly spôsobovali timeouts
- **Riešenie**: Defaultne JSON keď chýba Accept header alebo je `*/*`
- **Výhoda**: Kompatibilita s viacerými klientmi

### 7. **Oprava ToolsHandler štruktúry** (4dac840be)
- **Oprava**: Reparované triedy a odstránené rizikové init logovanie
- **Pridané**: Exception import pre lepšie error handling
- **Overené**: PHP syntax kontrola (`php -l` čistá)

### 8. **Detailné debug logovanie** (071c77be3)
- **Pridané**: Podrobné logy pre registráciu nástrojov
- **Nová funkcionalita**: `tools/debug` metóda
- **Vylepšené**: `list_all_tools` pre diagnostiku zakázaných nástrojov

---

## Súhrn vylepšení:

✅ **Schema fixes**: Opravené JSON schema formáty pre MCP kompatibilitu  
✅ **REST API stability**: Priame callbacks namiesto alias systému  
✅ **JWT robustnosť**: Fallback podpora a lepšie error handling  
✅ **Claude.ai kompatibilita**: Riešené connection timeouts  
✅ **Debug možnosti**: Rozšírené logovanie a diagnostické nástroje  
✅ **Klient podpora**: Relaxované header požiadavky  

## Technické zlepšenia:

- **Chybovosť**: Dramatické zníženie chýb v MCP klientoch
- **Stabilita**: Lepšie connection handling a timeout riešenia  
- **Kompatibilita**: Podpora viacerých klientov a WordPress konfigurácií
- **Diagnostika**: Rozšírené debug možnosti pre troubleshooting
- **Udržateľnosť**: Čistejší kód s lepším error handling

---

*Vygenerované: 10. august 2025*
*Počet zmenených súborov: 14*
*Počet pridaných riadkov: 6718*
*Počet odstránených riadkov: 740*
# GardenLawn Company Module

Moduł odpowiedzialny za zarządzanie danymi firm (dealerów) oraz ich importem.

## Polecenia konsoli (CLI)

### Import Dealerów
Importuje dane dealerów z plików JSON (Stihl, Husqvarna) zlokalizowanych w `GardenLawn/Core/Configs/`.
Komenda sprawdza istnienie rekordu po nazwie i grupie klienta - jeśli istnieje, aktualizuje go, w przeciwnym razie tworzy nowy.

**Użycie:**
```bash
bin/magento gardenlawn:import:dealers
```
